<?php

/**
 * Plugin Name: SUMO Subscriptions
 * Description: SUMO Subscriptions is a WooCommerce Subscription System.
 * Version: 12.4
 * Author: Fantastic Plugins
 * Author URI: http://fantasticplugins.com
 * 
 * WC requires at least: 3.0
 * WC tested up to: 4.8
 * 
 * Copyright: © 2019 FantasticPlugins.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: sumosubscriptions
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Start Subscriptions.
 * 
 * @class SUMOSubscriptions
 * @category Class
 */
final class SUMOSubscriptions {

    /**
     * SUMO Subscriptions version.
     * 
     * @var string 
     */
    public $version = '12.4' ;

    /**
     * Get subscription payment gateways.
     * @var array 
     */
    public $gateways = array() ;

    /**
     * Get Query instance.
     * @var SUMOSubscriptions_Query object 
     */
    public $query ;

    /**
     * The single instance of the class.
     */
    protected static $instance = null ;

    /**
     * SUMOSubscriptions constructor.
     */
    public function __construct() {

        //Prevent fatal error by load the files when you might call init hook.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' ) ;

        if ( ! $this->is_woocommerce_active() ) {
            return ;  // Return to stop the existing function to be call 
        }

        $this->define_constants() ;
        $this->include_files() ;
        $this->init_hooks() ;
    }

    /**
     * Main SUMOSubscriptions Instance.
     * Ensures only one instance of SUMOSubscriptions is loaded or can be loaded.
     * 
     * @return SUMOSubscriptions - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self() ;
        }
        return self::$instance ;
    }

    /**
     * Check WooCommerce Plugin is Active.
     * @return boolean
     */
    public function is_woocommerce_active() {
        //Prevent Header Problem.
        add_action( 'init', array( $this, 'prevent_header_already_sent_problem' ), 1 ) ;
        //Display warning if woocommerce is not active.
        add_action( 'init', array( $this, 'woocommerce_dependency_warning_message' ) ) ;

        if ( is_multisite() && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return false ;
        } else if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return false ;
        }
        return true ;
    }

    /**
     * Prevent header problem while plugin activates.
     */
    public function prevent_header_already_sent_problem() {
        ob_start() ;
    }

    public function woocommerce_dependency_warning_message() {
        if ( ! $this->is_woocommerce_active() && is_admin() ) {
            $error = "<div class='error'><p> SUMO Subscriptions Plugin requires WooCommerce Plugin should be Active !!! </p></div>" ;
            echo $error ;
        }
        return ;
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_FILE', __FILE__ ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME', plugin_basename( SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR', trailingslashit( dirname( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME ) ) ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_DIR', plugin_dir_path( SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_URL', untrailingslashit( plugins_url( '/', SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) ) ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_TEMPLATE_PATH', SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/' ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_VERSION', $this->version ) ;
        $this->define( 'SUMO_SUBSCRIPTIONS_CRON_INTERVAL', 300 ) ; //in seconds
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value ) ;
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function include_files() {
        //Load Welcome page
        include_once('includes/welcome.php') ;

        //Abstracts
        include_once('includes/abstracts/abstract-sumo-subscriptions-settings.php') ;
        include_once('includes/abstracts/abstract-sumo-subscription-cron-event.php') ;

        include_once('includes/class-subscription-factory.php') ;
        include_once('includes/class-subscription.php') ;
        include_once('includes/class-subscription-product.php') ;
        include_once('includes/class-subscription-cron-event.php') ;

        include_once('includes/subscription-order-functions.php') ;
        include_once('includes/subscription-product-functions.php') ;
        include_once('includes/subscription-core-functions.php') ;
        include_once('includes/subscription-functions.php') ;
        include_once('includes/subscription-conditional-functions.php') ;
        include_once('includes/subscription-formatting-functions.php') ;
        include_once('includes/subscription-UI-functions.php') ;
        include_once('includes/subscription-template-functions.php') ;

        $this->query = include_once('includes/class-subscription-query.php') ;

        include_once('includes/admin/class-subscription-admin-settings.php') ;
        include_once('includes/admin/class-subscription-admin-post-types.php') ;
        include_once('includes/admin/class-subscription-admin-meta-boxes.php') ;
        include_once('includes/admin/class-subscription-admin-product-settings.php') ;

        include_once('includes/class-subscription-comments.php') ;
        include_once('includes/class-subscription-coupon.php') ;
        include_once('includes/class-subscription-order-subscription.php') ;
        include_once('includes/class-subscription-synchronization.php') ;
        include_once('includes/class-subscription-optional-trial-or-signup.php') ;
        include_once('includes/class-subscription-upgrade-or-downgrade.php') ;
        include_once('includes/class-subscription-resubscribe.php') ;
        include_once('includes/class-subscription-restrictions.php') ;
        include_once('includes/class-subscription-downloadable-restrictions.php') ;
        include_once('includes/class-subscription-shipping.php') ;
        include_once('includes/class-subscription-frontend.php') ;
        include_once('includes/class-subscription-preapproval.php') ;
        include_once('includes/class-subscription-order.php') ;
        include_once('includes/class-subscription-my-account.php') ;

        $this->gateways = include_once('includes/class-subscription-payment-gateways.php') ;

        include_once('includes/class-subscription-enqueues.php') ;
        include_once('includes/class-subscription-ajax.php') ;
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'init_upon_activation' ) ) ;
        register_deactivation_hook( __FILE__, array( $this, 'init_upon_deactivation' ) ) ;
        add_action( 'plugins_loaded', array( $this, 'set_language_to_translate' ) ) ; //Register String Translation
        add_action( 'init', array( $this, 'init' ) ) ;
        add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ), 9999 ) ;
    }

    /**
     *  Fire upon activating SUMO Payment Plans
     */
    public function init_upon_activation() {
        SUMOSubscriptions_Welcome::load() ;
    }

    /**
     * Fire upon deactivating SUMO Subscriptions
     */
    public function init_upon_deactivation() {
        update_option( 'sumosubs_flush_rewrite_rules', 1 ) ;
        wp_clear_scheduled_hook( 'sumosubscriptions_background_updater' ) ;
    }

    /**
     *  Load language files. 
     */
    public function set_language_to_translate() {
        if ( function_exists( 'determine_locale' ) ) {
            $locale = determine_locale() ;
        } else {
            $locale = is_admin() ? get_user_locale() : get_locale() ;
        }

        $locale = apply_filters( 'plugin_locale', $locale, 'sumosubscriptions' ) ;

        unload_textdomain( 'sumosubscriptions' ) ;
        load_textdomain( 'sumosubscriptions', WP_LANG_DIR . '/sumosubscriptions/sumosubscriptions-' . $locale . '.mo' ) ;
        load_plugin_textdomain( 'sumosubscriptions', false, SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR . 'languages' ) ;
    }

    /**
     * Init SUMOSubscriptions when WordPress Initialises. 
     */
    public function init() {
        $this->update_subscription_version() ;

        include_once('includes/class-subscription-emails.php') ;
        include_once('includes/background-process/class-sumo-subscriptions-background-process.php' ) ;
        include_once('includes/privacy/class-subscription-privacy.php') ;

        if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
            include_once('includes/compatibilities/wc-backward-compatibility/wc-below-v3.0-backward-compatibility.php') ;
        }
        if ( class_exists( 'TM_Extra_Product_Options' ) || class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) ) {
            include_once('includes/compatibilities/product-addons/class-wc-tm-extra-product-options.php') ;
        }
    }

    /**
     * Schedule cron healthcheck
     *
     * @access public
     * @param mixed $schedules Schedules.
     * @return mixed
     */
    public function schedule_cron_healthcheck( $schedules ) {
        $schedules[ 'sumosubscriptions_cron_interval' ] = array(
            'interval' => SUMO_SUBSCRIPTIONS_CRON_INTERVAL,
            'display'  => sprintf( __( 'Every %d Minutes', 'sumosubscriptions' ), SUMO_SUBSCRIPTIONS_CRON_INTERVAL / 60 )
                ) ;

        return $schedules ;
    }

    /**
     * Check SUMOSubscriptions version and run updater
     */
    public function update_subscription_version() {
        if ( get_option( 'sumosubscriptions_version' ) !== $this->version ) {
            delete_option( 'sumosubscriptions_version' ) ;
            add_option( 'sumosubscriptions_version', $this->version ) ;

            SUMOSubscriptions_Admin_Settings::save_default_options() ;
        }
    }

}

/**
 * Main instance of SUMOSubscriptions.
 * Returns the main instance of SUMOSubscriptions.
 *
 * @return SUMOSubscriptions
 */
function sumosubscriptions() {
    return SUMOSubscriptions::instance() ;
}

/**
 * Run SUMO Subscriptions
 */
sumosubscriptions() ;
