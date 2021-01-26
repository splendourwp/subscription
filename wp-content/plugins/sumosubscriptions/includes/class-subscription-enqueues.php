<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle subscription enqueues.
 * 
 * @class SUMOSubscriptions_Enqueues
 * @category Class
 */
class SUMOSubscriptions_Enqueues {

    /**
     * Init SUMOSubscriptions_Enqueues
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_script' ) ;
        add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_style' ) ;
        add_action( 'wp_enqueue_scripts', __CLASS__ . '::frontend_script' ) ;
        add_filter( 'woocommerce_screen_ids', __CLASS__ . '::load_wc_enqueues', 1 ) ;
    }

    /**
     * Register and enqueue a script for use.
     *
     * @uses   wp_enqueue_script()
     * @access public
     * @param  string   $handle
     * @param  string   $path
     * @param  array   $localize_data
     * @param  string[] $deps
     * @param  string   $version
     * @param  boolean  $in_footer
     */
    public static function enqueue_script( $handle, $path = '', $localize_data = array(), $deps = array( 'jquery' ), $version = SUMO_SUBSCRIPTIONS_VERSION, $in_footer = false ) {
        wp_register_script( $handle, $path, $deps, $version, $in_footer ) ;

        $name = str_replace( '-', '_', $handle ) ;
        wp_localize_script( $handle, $name, $localize_data ) ;
        wp_enqueue_script( $handle ) ;
    }

    /**
     * Register and enqueue a styles for use.
     *
     * @uses   wp_enqueue_style()
     * @access public
     * @param  string   $handle
     * @param  string   $path
     * @param  string[] $deps
     * @param  string   $version
     * @param  string   $media
     * @param  boolean  $has_rtl
     */
    public static function enqueue_style( $handle, $path = '', $deps = array(), $version = SUMO_SUBSCRIPTIONS_VERSION, $media = 'all', $has_rtl = false ) {
        wp_register_style( $handle, $path, $deps, $version, $media, $has_rtl ) ;
        wp_enqueue_style( $handle ) ;
    }

    /**
     * Return asset URL.
     *
     * @param string $path
     * @return string
     */
    public static function get_asset_url( $path ) {
        return SUMO_SUBSCRIPTIONS_PLUGIN_URL . "/assets/{$path}" ;
    }

    /**
     * Enqueue jQuery UI events
     */
    public static function enqueue_jQuery_ui() {
        self::enqueue_script( 'sumosubscriptions-jquery-ui', self::get_asset_url( 'js/jquery-ui/jquery-ui.js' ) ) ;
        self::enqueue_style( 'sumosubscriptions-jquery-ui', self::get_asset_url( 'css/jquery-ui.css' ) ) ;
    }

    /**
     * Enqueue Footable.
     */
    public static function enqueue_footable_scripts() {

        self::enqueue_script( 'sumosubscriptions-footable', self::get_asset_url( 'js/footable/footable.js' ) ) ;
        self::enqueue_script( 'sumosubscriptions-footable-sort', self::get_asset_url( 'js/footable/footable.sort.js' ) ) ;
        self::enqueue_script( 'sumosubscriptions-footable-paginate', self::get_asset_url( 'js/footable/footable.paginate.js' ) ) ;
        self::enqueue_script( 'sumosubscriptions-footable-filter', self::get_asset_url( 'js/footable/footable.filter.js' ) ) ;
        self::enqueue_script( 'sumosubscriptions-footable-action', self::get_asset_url( 'js/footable/sumosubscriptions-footable.js' ) ) ;

        self::enqueue_style( 'sumosubscriptions-footable-core', self::get_asset_url( 'css/footable/footable.core.css' ) ) ;
        self::enqueue_style( 'sumosubscriptions-footable-standalone', self::get_asset_url( 'css/footable/footable.standalone.css' ) ) ;
        self::enqueue_style( 'sumosubscriptions-footable-bootstrap', self::get_asset_url( 'css/footable/bootstrap.css' ) ) ;
        self::enqueue_style( 'sumosubscriptions-footable-chosen', self::get_asset_url( 'css/footable/chosen.css' ) ) ;
    }

    /**
     * Enqueue Product Variation switcher.
     */
    public static function enqueue_variation_switcher_script() {
        self::enqueue_script( 'sumosubscriptions-variation-switcher', self::get_asset_url( 'js/sumosubscriptions-variation-switcher.js' ), array(
            'wp_ajax_url'                                => admin_url( 'admin-ajax.php' ),
            'switched_by'                                => is_admin() ? __( 'Admin', 'sumosubscriptions' ) : __( 'User', 'sumosubscriptions' ),
            'variation_switch_submit_nonce'              => wp_create_nonce( 'save-swapped-variation' ),
            'variation_swapping_nonce'                   => wp_create_nonce( 'variation-swapping' ),
            'default_variation_attribute_select_caption' => __( 'Select ', 'sumosubscriptions' ),
            'success_message'                            => __( 'Subscription Variation has been Switched Successfully.', 'sumosubscriptions' ),
            'failure_message'                            => __( 'Something went wrong.', 'sumosubscriptions' ),
            'notice_message'                             => __( 'Please select the variation and try again.', 'sumosubscriptions' ),
        ) ) ;
    }

    /**
     * Enqueue WC Multiselect field
     */
    public static function enqueue_wc_multiselect() {

        if ( sumosubs_is_wc_version( '<=', '2.2' ) ) {
            wp_enqueue_script( 'chosen' ) ;
        } else {
            wp_enqueue_script( 'wc-enhanced-select' ) ;
        }
    }

    /**
     * Perform script localization in backend.
     */
    public static function admin_script() {
        global $post ;

        //Subscription Welcome page
        if ( SUMOSubscriptions_Welcome::is_welcome() ) {
            self::enqueue_script( 'sumosubscriptions-welcome-page', self::get_asset_url( 'js/admin/admin-welcome-page.js' ) ) ;
        }

        //Subscription Dashboard Settings.
        switch ( get_post_type() ) {
            case 'sumosubscriptions':
                self::enqueue_script( 'sumosubscriptions-dashboard', self::get_asset_url( 'js/admin/admin-subscription-dashboard.js' ), array(
                    'wp_ajax_url'                                       => admin_url( 'admin-ajax.php' ),
                    'add_note_nonce'                                    => wp_create_nonce( 'add-subscription-note' ),
                    'delete_note_nonce'                                 => wp_create_nonce( 'delete-subscription-note' ),
                    'cancel_request_nonce'                              => wp_create_nonce( 'subscription-cancel-request' ),
                    'is_synced'                                         => $post ? ( SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ? 'yes' : '' ) : '',
                    'view_renewal_orders_text'                          => __( 'View Unpaid Renewal Order', 'sumosubscriptions' ),
                    'display_dialog_upon_cancel'                        => 'yes' === get_option( 'sumo_display_dialog_upon_cancel' ),
                    'display_dialog_upon_revoking_cancel'               => 'yes' === get_option( 'sumo_display_dialog_upon_revoking_cancel' ),
                    'warning_message_upon_immediate_cancel'             => get_option( 'sumo_cancel_dialog_message' ),
                    'warning_message_upon_at_the_end_of_billing_cancel' => get_option( 'sumo_cancel_at_the_end_of_billing_dialog_message' ),
                    'warning_message_upon_on_the_scheduled_date_cancel' => get_option( 'sumo_cancel_on_the_scheduled_date_dialog_message' ),
                    'warning_message_upon_revoking_cancel'              => get_option( 'sumo_revoking_cancel_confirmation_dialog_message' ),
                    'warning_message_upon_invalid_date'                 => __( 'Please enter the Date and Try again !!', 'sumosubscriptions' ),
                    'warning_message_before_pause'                      => __( 'This is a Synchronized Subscription and hence if you have paused this subscription, then the customer might not get the extended number of days based on the Pause duration once the subscription is resumed. Are you sure you want to Pause this subscription?', 'sumosubscriptions' ),
                ) ) ;
                self::enqueue_jQuery_ui() ;
                self::enqueue_footable_scripts() ;
                self::enqueue_variation_switcher_script() ;

                // Disable WP Auto Save on Subscription Edit Page.
                wp_dequeue_script( 'autosave' ) ;
                break ;
            case 'product':
                self::enqueue_script( 'sumosubscriptions-product-settings', self::get_asset_url( 'js/admin/admin-product-settings.js' ), array(
                    'synchronize_mode'                                 => SUMO_Subscription_Synchronization::$sync_mode,
                    'subscription_week_duration_options'               => sumo_get_subscription_duration_options( 'W', false ),
                    'subscription_month_duration_options'              => sumo_get_subscription_duration_options( 'M', false ),
                    'subscription_year_duration_options'               => sumo_get_subscription_duration_options( 'Y', false ),
                    'subscription_day_duration_options'                => sumo_get_subscription_duration_options( 'D', false ),
                    'synced_subscription_week_duration_options'        => SUMO_Subscription_Synchronization::get_duration_options( 'W' ),
                    'synced_subscription_month_duration_options'       => SUMO_Subscription_Synchronization::get_duration_options( 'M', true ),
                    'synced_subscription_year_duration_options'        => SUMO_Subscription_Synchronization::get_duration_options( 'Y', true ),
                    'synced_subscription_month_duration_value_options' => SUMO_Subscription_Synchronization::get_duration_options( 'M' ),
                    'synced_subscription_year_duration_value_options'  => SUMO_Subscription_Synchronization::get_duration_options( 'Y' ),
                ) ) ;
                break ;
        }

        //Subscription Settings Page.
        if ( isset( $_GET[ 'page' ] ) ) {
            switch ( $_GET[ 'page' ] ) {
                case SUMO_Subscription_Exporter::$exporter_page:
                    self::enqueue_script( 'sumosubscriptions-exporter', self::get_asset_url( 'js/admin/admin-subscription-exporter.js' ), array(
                        'wp_ajax_url'    => admin_url( 'admin-ajax.php' ),
                        'exporter_nonce' => wp_create_nonce( 'subscription-exporter' ),
                    ) ) ;
                    self::enqueue_jQuery_ui() ;
                    break ;
                case 'sumosettings':
                    switch ( isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : '' ) {
                        case 'order_subscription':
                            self::enqueue_script( 'sumosubscriptions-order-tab-settings', self::get_asset_url( 'js/admin/admin-order-subscription-tab-settings.js' ), array(
                                'subscription_week_duration_options'        => sumo_get_subscription_duration_options( 'W' ),
                                'subscription_month_duration_options'       => sumo_get_subscription_duration_options( 'M' ),
                                'subscription_year_duration_options'        => sumo_get_subscription_duration_options( 'Y' ),
                                'subscription_day_duration_options'         => sumo_get_subscription_duration_options( 'D' ),
                                'warning_message_upon_invalid_no_of_days'   => __( 'Please enter the valid number of days for Subscription Duration Value !!', 'sumosubscriptions' ),
                                'warning_message_upon_invalid_no_of_weeks'  => __( 'Please enter the valid number of weeks for Subscription Duration Value !!', 'sumosubscriptions' ),
                                'warning_message_upon_invalid_no_of_months' => __( 'Please enter the valid number of months for Subscription Duration Value !!', 'sumosubscriptions' ),
                                'warning_message_upon_invalid_no_of_years'  => __( 'Please enter the valid number of years for Subscription Duration Value !!', 'sumosubscriptions' ),
                                'warning_message_upon_max_recurring_cycle'  => __( 'Please select the valid number of maximum recurring cycle !!', 'sumosubscriptions' ),
                            ) ) ;
                            break ;
                        case 'synchronization':
                            self::enqueue_script( 'sumosubscriptions-synchronization-tab-settings', self::get_asset_url( 'js/admin/admin-synchronization-tab-settings.js' ) ) ;
                            break ;
                        case 'upgrade_r_downgrade':
                            self::enqueue_script( 'sumosubscriptions-upgrade-or-downgrade-tab-settings', self::get_asset_url( 'js/admin/admin-upgrade-or-downgrade-tab-settings.js' ), array(
                                'is_lower_wc_version' => sumosubs_is_wc_version( '<=', '2.2' ),
                            ) ) ;
                            break ;
                        case 'my_account':
                            self::enqueue_script( 'sumosubscriptions-my-account-tab-settings', self::get_asset_url( 'js/admin/admin-my-account-tab-settings.js' ), array(
                                'is_lower_wc_version' => sumosubs_is_wc_version( '<=', '2.2' ),
                            ) ) ;
                            break ;
                        case 'bulk_action':
                            self::enqueue_script( 'sumosubscriptions-bulk-action-tab-settings', self::get_asset_url( 'js/admin/admin-bulk-action-tab-settings.js' ), array(
                                'wp_ajax_url'         => admin_url( 'admin-ajax.php' ),
                                'update_nonce'        => wp_create_nonce( 'bulk-update-subscription' ),
                                'optimization_nonce'  => wp_create_nonce( 'bulk-update-optimization' ),
                                'is_lower_wc_version' => sumosubs_is_wc_version( '<=', '2.2' ),
                                'wp_create_nonce'     => wp_create_nonce( 'search-products' )
                            ) ) ;
                            break ;
                        case 'messages':
                            self::enqueue_script( 'sumosubscriptions-messages-tab-settings', self::get_asset_url( 'js/admin/admin-messages-tab-settings.js' ) ) ;
                            self::enqueue_footable_scripts() ;
                            break ;
                        case 'advanced':
                            self::enqueue_script( 'sumosubscriptions-advance-tab-settings', self::get_asset_url( 'js/admin/admin-advance-tab-settings.js' ), array(
                                'get_html_data_nonce' => wp_create_nonce( 'subscription-as-regular-html-data' ),
                            ) ) ;
                            self::enqueue_footable_scripts() ;
                            break ;
                        default :
                            self::enqueue_script( 'sumosubscriptions-general-tab-settings', self::get_asset_url( 'js/admin/admin-general-tab-settings.js' ), array(
                                'is_lower_wc_version' => sumosubs_is_wc_version( '<=', '2.2' ),
                            ) ) ;
                    }
                    self::enqueue_script( 'sumosubscriptions-jscolor', self::get_asset_url( 'js/jscolor/jscolor.js' ) ) ;
                    break ;
                case 'wc-settings':
                    if ( isset( $_GET[ 'tab' ] ) && isset( $_GET[ 'section' ] ) ) {
                        switch ( $_GET[ 'section' ] ) {
                            case 'sumo_paypal_preapproval':
                                self::enqueue_script( 'sumosubscriptions-wc-checkout-paypal-adaptive-section-settings', self::get_asset_url( 'js/admin/admin-wc-checkout-paypal-adaptive-tab-section.js' ), array(
                                    'admin_notice' => __( 'Please do not leave any fields empty.', 'sumosubscriptions' )
                                ) ) ;
                                break ;
                            case 'sumo_paypal_reference_txns':
                                self::enqueue_script( 'sumosubscriptions-wc-paypal-reference-section-settings', self::get_asset_url( 'js/admin/admin-wc-checkout-paypal-reference-tab-section.js' ), array(
                                    'wp_ajax_url'                    => admin_url( 'admin-ajax.php' ),
                                    'is_lower_wc_version'            => sumosubs_is_wc_version( '<=', '2.2' ),
                                    'paypal_change_logo_button_text' => __( 'Change Logo', 'sumosubscriptions' ),
                                    'admin_notice'                   => __( 'Please upload the logo in valid image format, such as .gif, .jpg, or .png.', 'sumosubscriptions' )
                                ) ) ;
                                wp_enqueue_media() ;
                                break ;
                            case 'sumo_stripe':
                                self::enqueue_script( 'sumosubscriptions-wc-checkout-stripe-section-settings', self::get_asset_url( 'js/admin/admin-wc-checkout-stripe-tab-section.js' ) ) ;
                                break ;
                        }
                    }
                    break ;
            }
        }
    }

    /**
     * Load style in backend.
     */
    public static function admin_style() {
        //Subscription Welcome page
        if ( SUMOSubscriptions_Welcome::is_welcome() ) {
            self::enqueue_style( 'sumosubscriptions-welcome-page', self::get_asset_url( 'css/admin-welcome-page.css' ) ) ;
        }

        if ( 'sumosubscriptions' === get_post_type() ) {
            self::enqueue_style( 'sumosubscriptions-dashboard', self::get_asset_url( 'css/admin-subscription-dashboard.css' ) ) ;
        }
    }

    /**
     * Perform script localization in frontend.
     * @global object $post
     */
    public static function frontend_script() {
        global $post ;

        $product = is_product() ? wc_get_product( $post ) : false ;
        self::enqueue_script( 'sumosubscriptions-single-product-page', self::get_asset_url( 'js/frontend/single-product-page.js' ), array(
            'wp_ajax_url'              => admin_url( 'admin-ajax.php' ),
            'get_product_nonce'        => wp_create_nonce( 'get-subscription-product-data' ),
            'get_variation_nonce'      => wp_create_nonce( 'get-subscription-variation-data' ),
            'product_id'               => isset( $post->ID ) ? sumosubs_get_product_id( $post->ID ) : '',
            'product_type'             => isset( $post->ID ) ? sumosubs_get_product_type( $post->ID ) : '',
            'default_add_to_cart_text' => $product ? $product->single_add_to_cart_text() : __( 'Add to cart', 'sumosubscriptions' ),
        ) ) ;

        if ( (is_cart() && SUMO_Order_Subscription::show_subscribe_form_in_cart()) || is_checkout() ) {
            self::enqueue_script( 'sumosubscriptions-checkout-page', self::get_asset_url( 'js/frontend/checkout-page.js' ), array(
                'wp_ajax_url'                                 => admin_url( 'admin-ajax.php' ),
                'is_user_logged_in'                           => is_user_logged_in(),
                'current_page'                                => is_checkout() ? 'checkout' : 'cart',
                'update_order_subscription_nonce'             => wp_create_nonce( 'update-order-subscription' ),
                'can_user_subscribe'                          => SUMO_Order_Subscription::can_user_subscribe(),
                'default_order_subscription_duration'         => SUMO_Order_Subscription::$get_option[ 'default_duration_period' ],
                'default_order_subscription_duration_value'   => SUMO_Order_Subscription::$get_option[ 'default_duration_length' ],
                'default_order_subscription_installment'      => SUMO_Order_Subscription::$get_option[ 'default_recurring_length' ],
                'can_user_select_plan'                        => SUMO_Order_Subscription::$get_option[ 'can_user_select_plan' ],
                'subscription_week_duration_options'          => sumo_get_subscription_duration_options( 'W', true, SUMO_Order_Subscription::$get_option[ 'min_duration_length_user_can_select' ][ 'W' ], SUMO_Order_Subscription::$get_option[ 'max_duration_length_user_can_select' ][ 'W' ] ),
                'subscription_month_duration_options'         => sumo_get_subscription_duration_options( 'M', true, SUMO_Order_Subscription::$get_option[ 'min_duration_length_user_can_select' ][ 'M' ], SUMO_Order_Subscription::$get_option[ 'max_duration_length_user_can_select' ][ 'M' ] ),
                'subscription_year_duration_options'          => sumo_get_subscription_duration_options( 'Y', true, SUMO_Order_Subscription::$get_option[ 'min_duration_length_user_can_select' ][ 'Y' ], SUMO_Order_Subscription::$get_option[ 'max_duration_length_user_can_select' ][ 'Y' ] ),
                'subscription_day_duration_options'           => sumo_get_subscription_duration_options( 'D', true, SUMO_Order_Subscription::$get_option[ 'min_duration_length_user_can_select' ][ 'D' ], SUMO_Order_Subscription::$get_option[ 'max_duration_length_user_can_select' ][ 'D' ] ),
                'sync_ajax'                                   => 'yes' === get_option( 'sumo_sync_ajax_for_order_subscription', 'no' ),
                'maybe_prevent_from_hiding_guest_signup_form' => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) && 'yes' !== get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
            ) ) ;
        }

        if ( is_account_page() || sumo_is_my_subscriptions_page() ) {
            self::enqueue_script( 'sumosubscriptions-myaccount-page', self::get_asset_url( 'js/frontend/my-account-page.js' ), array(
                'wp_ajax_url'                                       => admin_url( 'admin-ajax.php' ),
                'current_user_id'                                   => get_current_user_id(),
                'show_more_notes_label'                             => __( 'Show More', 'sumosubscriptions' ),
                'show_less_notes_label'                             => __( 'Show Less', 'sumosubscriptions' ),
                'wp_nonce'                                          => wp_create_nonce( 'subscriber-request' ),
                'subscriber_has_single_cancel_method'               => 1 === count( sumosubs_get_subscription_cancel_methods() ),
                'display_dialog_upon_cancel'                        => 'yes' === get_option( 'sumo_display_dialog_upon_cancel' ),
                'display_dialog_upon_revoking_cancel'               => 'yes' === get_option( 'sumo_display_dialog_upon_revoking_cancel' ),
                'warning_message_upon_immediate_cancel'             => get_option( 'sumo_cancel_dialog_message' ),
                'warning_message_upon_at_the_end_of_billing_cancel' => get_option( 'sumo_cancel_at_the_end_of_billing_dialog_message' ),
                'warning_message_upon_on_the_scheduled_date_cancel' => get_option( 'sumo_cancel_on_the_scheduled_date_dialog_message' ),
                'warning_message_upon_revoking_cancel'              => get_option( 'sumo_revoking_cancel_confirmation_dialog_message' ),
                'warning_message_upon_invalid_date'                 => __( 'Please enter the Date and Try again !!', 'sumosubscriptions' ),
                'warning_message_upon_turnoff_automatic_payments'   => __( 'Are you sure you want to turn off Automatic Subscription Renewal for this subscription?', 'sumosubscriptions' ),
                'warning_message_before_pause'                      => __( 'This is a Synchronized Subscription and hence if you have paused this subscription, then you might not get the extended number of days based on the Pause duration once the subscription is resumed. Are you sure you want to Pause this subscription?', 'sumosubscriptions' ),
                'failure_message'                                   => __( 'Something went wrong !!', 'sumosubscriptions' ),
            ) ) ;
            self::enqueue_jQuery_ui() ;
            self::enqueue_footable_scripts() ;
            self::enqueue_variation_switcher_script() ;
        }
    }

    /**
     * Load WC enqueues.
     * @param array $screen_ids
     * @return array
     */
    public static function load_wc_enqueues( $screen_ids ) {
        global $typenow ;

        $new_screen = get_current_screen() ;

        if ( in_array( $typenow, array( 'sumosubscriptions' ) ) || (isset( $_GET[ 'page' ] ) && in_array( $_GET[ 'page' ], array( 'sumosettings', SUMO_Subscription_Exporter::$exporter_page ) )) ) {
            $screen_ids[] = $new_screen->id ;
        }
        return $screen_ids ;
    }

}

SUMOSubscriptions_Enqueues::init() ;
