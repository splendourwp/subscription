<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin menus and post types.
 * 
 * @class SUMOSubscriptions_Admin_Settings
 * @category Class
 */
class SUMOSubscriptions_Admin_Settings {

    /**
     * Setting pages.
     *
     * @var array
     */
    private static $settings = array () ;

    /**
     * Init SUMOSubscriptions_Admin_Settings.
     */
    public static function init() {
        add_action( 'init' , __CLASS__ . '::register_post_types' ) ;
        add_action( 'admin_menu' , __CLASS__ . '::settings_menu' ) ;
        add_filter( 'plugin_row_meta' , __CLASS__ . '::plugin_row_meta' , 10 , 2 ) ;
        add_filter( 'plugin_action_links_' . SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME , __CLASS__ . '::plugin_action_links' ) ;
        add_action( 'sumosubscriptions_reset_options' , __CLASS__ . '::reset_options' ) ;
        add_filter( 'woocommerce_account_settings' , __CLASS__ . '::add_note_to_subscription_order_data_retention_settings' ) ;
        add_filter( 'woocommerce_account_settings' , __CLASS__ . '::add_wc_account_settings' ) ;

        include 'class-subscription-admin-exporter.php' ;
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param	mixed $links Plugin Action links
     * @return	array
     */
    public static function plugin_action_links( $links ) {
        $setting_page_link = '<a  href="' . admin_url( 'admin.php?page=sumosettings' ) . '">Settings</a>' ;
        array_unshift( $links , $setting_page_link ) ;
        return $links ;
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param	mixed $links Plugin Row Meta
     * @param	mixed $file  Plugin Base file
     * @return	array
     */
    public static function plugin_row_meta( $links , $file ) {
        if ( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME == $file ) {
            $row_meta = array (
                'about'   => '<a href="' . esc_url( admin_url( 'admin.php?page=sumosubscriptions-welcome-page' ) ) . '" aria-label="' . esc_attr__( 'About' , 'sumosubscriptions' ) . '">' . esc_html__( 'About' , 'sumosubscriptions' ) . '</a>' ,
                'support' => '<a href="' . esc_url( 'http://fantasticplugins.com/support/' ) . '" aria-label="' . esc_attr__( 'Support' , 'sumosubscriptions' ) . '">' . esc_html__( 'Support' , 'sumosubscriptions' ) . '</a>' ,
                    ) ;

            return array_merge( $links , $row_meta ) ;
        }

        return ( array ) $links ;
    }

    /**
     * Register Custom Post Type.
     */
    public static function register_post_types() {
        //Register CPT for Subscriptions.
        register_post_type( 'sumosubscriptions' , array (
            'labels'       => array (
                'name'               => _x( 'List of Subscriptions' , 'general name' , 'sumosubscriptions' ) ,
                'singular_name'      => _x( 'Subscription' , 'singular name' , 'sumosubscriptions' ) ,
                'menu_name'          => _x( 'SUMO Subscriptions' , 'admin menu' , 'sumosubscriptions' ) ,
                'name_admin_bar'     => _x( 'Subscription' , 'add new on admin bar' , 'sumosubscriptions' ) ,
                'add_new'            => _x( 'Add New' , 'subscription' , 'sumosubscriptions' ) ,
                'add_new_item'       => __( 'Add New Subscription' , 'sumosubscriptions' ) ,
                'new_item'           => __( 'New Subscription' , 'sumosubscriptions' ) ,
                'edit_item'          => __( 'Edit Subscription' , 'sumosubscriptions' ) ,
                'view_item'          => __( 'View Subscription' , 'sumosubscriptions' ) ,
                'all_items'          => __( 'List of Subscriptions' , 'sumosubscriptions' ) ,
                'search_items'       => __( 'Search Subscription' , 'sumosubscriptions' ) ,
                'parent_item_colon'  => __( 'Parent Subscriptions:' , 'sumosubscriptions' ) ,
                'not_found'          => __( 'No Subscription Found.' , 'sumosubscriptions' ) ,
                'not_found_in_trash' => __( 'No Subscription found in Trash.' , 'sumosubscriptions' )
            ) ,
            'description'  => __( 'Description.' , 'sumosubscriptions' ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => 'sumosubscriptions' ,
            'rewrite'      => array ( 'slug' => 'sumosubscriptions' ) ,
            'has_archive'  => true ,
            'supports'     => false ,
            'capabilities' => array (
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            )
        ) ) ;

        //Register CPT for Subscription Cron Events.
        register_post_type( 'sumosubs_cron_events' , array (
            'labels'       => array (
                'name'               => _x( 'Subscription Cron Events' , 'general name' , 'sumosubscriptions' ) ,
                'singular_name'      => _x( 'Subscription Cron Events' , 'singular name' , 'sumosubscriptions' ) ,
                'menu_name'          => _x( 'Subscription Cron Events' , 'admin menu' , 'sumosubscriptions' ) ,
                'name_admin_bar'     => _x( 'Subscription Cron Events' , 'add new on admin bar' , 'sumosubscriptions' ) ,
                'add_new'            => _x( 'Add New' , 'subscription' , 'sumosubscriptions' ) ,
                'add_new_item'       => __( 'Add New Cron Event' , 'sumosubscriptions' ) ,
                'new_item'           => __( 'New Cron Event' , 'sumosubscriptions' ) ,
                'edit_item'          => __( 'Edit Cron Event' , 'sumosubscriptions' ) ,
                'view_item'          => __( 'View Cron Event' , 'sumosubscriptions' ) ,
                'all_items'          => __( 'Scheduled Cron Events' , 'sumosubscriptions' ) ,
                'search_items'       => __( 'Search Cron Event' , 'sumosubscriptions' ) ,
                'parent_item_colon'  => __( 'Parent Cron Events:' , 'sumosubscriptions' ) ,
                'not_found'          => __( 'No Cron Event Found.' , 'sumosubscriptions' ) ,
                'not_found_in_trash' => __( 'No Cron Event found in Trash.' , 'sumosubscriptions' )
            ) ,
            'description'  => __( 'Description.' , 'sumosubscriptions' ) ,
            'public'       => false ,
            'show_ui'      => apply_filters( 'sumosubscriptions_show_cron_events_post_type_ui' , false ) ,
            'show_in_menu' => 'sumosubscriptions' ,
            'rewrite'      => array ( 'slug' => 'sumosubs_cron_events' ) ,
            'has_archive'  => true ,
            'supports'     => array () ,
            'capabilities' => array (
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            ) ,
        ) ) ;

        //Register CPT for Master Log.
        register_post_type( 'sumomasterlog' , array (
            'labels'       => array (
                'name'               => _x( 'Master Log' , 'general name' , 'sumosubscriptions' ) ,
                'singular_name'      => _x( 'Master Log' , 'singular name' , 'sumosubscriptions' ) ,
                'menu_name'          => _x( 'Master Log' , 'admin menu' , 'sumosubscriptions' ) ,
                'name_admin_bar'     => _x( 'Master Log' , 'add new on admin bar' , 'sumosubscriptions' ) ,
                'add_new'            => _x( 'Add New' , 'subscription' , 'sumosubscriptions' ) ,
                'add_new_item'       => __( 'Add New Log' , 'sumosubscriptions' ) ,
                'new_item'           => __( 'New Log' , 'sumosubscriptions' ) ,
                'edit_item'          => __( 'Edit Log' , 'sumosubscriptions' ) ,
                'view_item'          => __( 'View Log' , 'sumosubscriptions' ) ,
                'all_items'          => __( 'Master Log' , 'sumosubscriptions' ) ,
                'search_items'       => __( 'Search Log' , 'sumosubscriptions' ) ,
                'parent_item_colon'  => __( 'Parent Log:' , 'sumosubscriptions' ) ,
                'not_found'          => __( 'No Logs Found.' , 'sumosubscriptions' ) ,
                'not_found_in_trash' => __( 'No Logs found in Trash.' , 'sumosubscriptions' )
            ) ,
            'description'  => __( 'Description.' , 'sumosubscriptions' ) ,
            'public'       => false ,
            'show_ui'      => true ,
            'show_in_menu' => 'sumosubscriptions' ,
            'rewrite'      => array ( 'slug' => 'sumomasterlog' ) ,
            'has_archive'  => true ,
            'supports'     => array () ,
            'capabilities' => array (
                'edit_post'          => 'manage_woocommerce' ,
                'edit_posts'         => 'manage_woocommerce' ,
                'edit_others_posts'  => 'manage_woocommerce' ,
                'publish_posts'      => 'manage_woocommerce' ,
                'read_post'          => 'manage_woocommerce' ,
                'read_private_posts' => 'manage_woocommerce' ,
                'delete_post'        => 'manage_woocommerce' ,
                'delete_posts'       => true ,
                'create_posts'       => 'do_not_allow'
            )
        ) ) ;
    }

    /**
     * Add admin menu pages.
     */
    public static function settings_menu() {

        add_menu_page( __( 'SUMO Subscriptions' , 'sumosubscriptions' ) , __( 'SUMO Subscriptions' , 'sumosubscriptions' ) , 'manage_woocommerce' , 'sumosubscriptions' , null , 'dashicons-backup' , '56.6' ) ;
        add_submenu_page( 'sumosubscriptions' , __( 'Settings' , 'sumosubscriptions' ) , __( 'Settings' , 'sumosubscriptions' ) , 'manage_woocommerce' , 'sumosettings' , __CLASS__ . '::output' ) ;
        add_submenu_page( 'sumosubscriptions' , __( 'Subscription Export' , 'sumosubscriptions' ) , __( 'Subscription Export' , 'sumosubscriptions' ) , 'manage_woocommerce' , SUMO_Subscription_Exporter::$exporter_page , 'SUMO_Subscription_Exporter::render_exporter_html_fields' ) ;
    }

    /**
     * Include the settings page classes.
     */
    public static function get_settings_pages() {
        if ( empty( self::$settings ) ) {

            self::$settings[] = include( 'settings-page/class-general-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-order-subscription-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-synchronization-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-upgrade-or-downgrade-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-my-account-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-advance-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-bulk-action-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-message-settings.php' ) ;
            self::$settings[] = include( 'settings-page/class-help.php' ) ;
        }

        return self::$settings ;
    }

    /**
     * Settings page.
     *
     * Handles the display of the main sumosubscriptions settings page in admin.
     */
    public static function output() {
        global $current_section , $current_tab ;

        do_action( 'sumosubscriptions_settings_start' ) ;

        $current_tab     = ( empty( $_GET[ 'tab' ] ) ) ? 'general' : sanitize_text_field( urldecode( $_GET[ 'tab' ] ) ) ;
        $current_section = ( empty( $_REQUEST[ 'section' ] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST[ 'section' ] ) ) ;

        // Include settings pages
        self::get_settings_pages() ;

        do_action( 'sumosubscriptions_add_options_' . $current_tab ) ;
        do_action( 'sumosubscriptions_add_options' ) ;

        if ( $current_section ) {
            do_action( 'sumosubscriptions_add_options_' . $current_tab . '_' . $current_section ) ;
        }

        if ( ! empty( $_POST[ 'save' ] ) ) {
            if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumosubscriptions-settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , 'sumosubscriptions' ) ) ;

            // Save settings if data has been posted
            do_action( 'sumosubscriptions_update_options_' . $current_tab ) ;
            do_action( 'sumosubscriptions_update_options' ) ;

            if ( $current_section ) {
                do_action( 'sumosubscriptions_update_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array ( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        if ( ! empty( $_POST[ 'reset' ] ) || ! empty( $_POST[ 'reset_all' ] ) ) {
            if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ] , 'sumosubscriptions-reset_settings' ) )
                die( __( 'Action failed. Please refresh the page and retry.' , 'sumosubscriptions' ) ) ;

            do_action( 'sumosubscriptions_reset_options_' . $current_tab ) ;

            if ( ! empty( $_POST[ 'reset_all' ] ) ) {
                do_action( 'sumosubscriptions_reset_options' ) ;
            }
            if ( $current_section ) {
                do_action( 'sumosubscriptions_reset_options_' . $current_tab . '_' . $current_section ) ;
            }

            wp_safe_redirect( esc_url_raw( add_query_arg( array ( 'saved' => 'true' ) ) ) ) ;
            exit ;
        }
        // Get any returned messages
        $error   = ( empty( $_GET[ 'wc_error' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_error' ] ) ) ;
        $message = ( empty( $_GET[ 'wc_message' ] ) ) ? '' : urldecode( stripslashes( $_GET[ 'wc_message' ] ) ) ;

        if ( $error || $message ) {
            if ( $error ) {
                echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>' ;
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>' ;
            }
        } elseif ( ! empty( $_GET[ 'saved' ] ) ) {
            echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.' , 'sumosubscriptions' ) . '</strong></p></div>' ;
        }
        ?>
        <div class="wrap woocommerce">
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
                <div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
                <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <?php
                    $tabs = apply_filters( 'sumosubscriptions_settings_tabs_array' , array () ) ;

                    foreach ( $tabs as $name => $label ) {
                        echo '<a href="' . admin_url( 'admin.php?page=sumosettings&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>' ;
                    }
                    do_action( 'sumosubscriptions_settings_tabs' ) ;
                    ?>
                </h2>
                <?php
                switch ( $current_tab ) :
                    default :
                        do_action( 'sumosubscriptions_sections_' . $current_tab ) ;
                        do_action( 'sumosubscriptions_settings_' . $current_tab ) ;
                        break ;
                endswitch ;
                ?>
                <?php if ( apply_filters( 'sumosubscriptions_submit_' . $current_tab , true ) ) : ?>
                    <p class="submit">
                        <?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
                            <input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes' , 'sumosubscriptions' ) ; ?>" />
                        <?php endif ; ?>
                        <input type="hidden" name="subtab" id="last_tab" />
                        <?php wp_nonce_field( 'sumosubscriptions-settings' ) ; ?>
                    </p>
                <?php endif ; ?>
            </form>
            <?php if ( apply_filters( 'sumosubscriptions_reset_' . $current_tab , true ) ) : ?>
                <form method="post" id="mainforms" action="" enctype="multipart/form-data" style="float: left; margin-top: -52px; margin-left: 159px;">
                    <input name="reset" class="button-secondary" type="submit" value="<?php _e( 'Reset' , 'sumosubscriptions' ) ; ?>"/>
                    <input name="reset_all" class="button-secondary" type="submit" value="<?php _e( 'Reset All' , 'sumosubscriptions' ) ; ?>"/>
                    <?php wp_nonce_field( 'sumosubscriptions-reset_settings' ) ; ?>
                </form>
            <?php endif ; ?>
        </div>
        <?php
    }

    /**
     * Default options.
     *
     * Sets up the default options used on the settings page.
     */
    public static function save_default_options( $reset_all = false ) {

        if ( empty( self::$settings ) ) {
            self::get_settings_pages() ;
        }

        foreach ( self::$settings as $tab ) {
            if ( ! isset( $tab->settings ) || ! is_array( $tab->settings ) ) {
                continue ;
            }

            $tab->add_options( $reset_all ) ;
        }
    }

    /**
     * Reset All settings
     */
    public static function reset_options() {

        self::save_default_options( true ) ;
    }

    /**
     * Add notice to admin when data retention in SUMO Subscription orders
     * @param array $settings
     * @return array
     */
    public static function add_note_to_subscription_order_data_retention_settings( $settings ) {
        if ( is_array( $settings ) && ! empty( $settings ) ) {

            foreach ( $settings as $pos => $setting ) {
                if (
                        isset( $setting[ 'id' ] ) &&
                        isset( $setting[ 'type' ] ) &&
                        'personal_data_retention' === $setting[ 'id' ] &&
                        'title' === $setting[ 'type' ]
                ) {
                    $settings[ $pos ][ 'desc' ] .=__( '<br><strong>Note:</strong> This settings will not be applicable for SUMO Subscription orders.' , 'sumosubscriptions' ) ;
                }
            }
        }
        return $settings ;
    }

    /**
     * Add privacy setings under WooCommerce Privacy
     * @param array $settings
     * @return array
     */
    public static function add_wc_account_settings( $settings ) {
        $original_settings = $settings ;

        if ( is_array( $original_settings ) && ! empty( $original_settings ) ) {
            $new_settings = array () ;

            foreach ( $original_settings as $pos => $setting ) {
                if ( ! isset( $setting[ 'id' ] ) ) {
                    continue ;
                }

                switch ( $setting[ 'id' ] ) {
                    case 'woocommerce_erasure_request_removes_order_data':
                        $new_settings[ $pos + 1 ] = array (
                            'title'         => __( 'Account erasure requests' , 'sumosubscriptions' ) ,
                            'desc'          => __( 'Remove personal data from SUMO Subscriptions and its related Orders' , 'sumosubscriptions' ) ,
                            /* Translators: %s URL to erasure request screen. */
                            'desc_tip'      => sprintf( __( 'When handling an <a href="%s">account erasure request</a>, should personal data within SUMO Subscriptions be retained or removed?' , 'sumosubscriptions' ) , esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ) ) ,
                            'id'            => 'sumo_erasure_request_removes_subscription_data' ,
                            'type'          => 'checkbox' ,
                            'default'       => 'no' ,
                            'checkboxgroup' => '' ,
                            'autoload'      => false ,
                                ) ;
                        break ;
                    case 'woocommerce_anonymize_completed_orders':
                        $new_settings[ $pos + 1 ] = array (
                            'title'       => __( 'Retain ended SUMO Subscription Orders' , 'sumosubscriptions' ) ,
                            'desc_tip'    => __( 'Retain ended SUMO Subscription Orders for a specified duration before anonymizing the personal data within them.' , 'sumosubscriptions' ) ,
                            'id'          => 'sumo_anonymize_ended_subscriptions' ,
                            'type'        => 'relative_date_selector' ,
                            'placeholder' => __( 'N/A' , 'sumosubscriptions' ) ,
                            'default'     => array (
                                'number' => '' ,
                                'unit'   => 'months' ,
                            ) ,
                            'autoload'    => false ,
                                ) ;
                        break ;
                }
            }
            if ( ! empty( $new_settings ) ) {
                foreach ( $new_settings as $pos => $new_setting ) {
                    array_splice( $settings , $pos , 0 , array ( $new_setting ) ) ;
                }
            }
        }
        return $settings ;
    }

}

SUMOSubscriptions_Admin_Settings::init() ;
