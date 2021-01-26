<?php

/**
 * My Account Settings.
 * 
 * @class SUMOSubscriptions_My_Account_Settings
 * @category Class
 */
class SUMOSubscriptions_My_Account_Settings extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_My_Account_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'my_account' ;
        $this->label         = __( 'My Account', 'sumosubscriptions' ) ;
        $this->custom_fields = array(
            'get_cancel_limit_by_products',
            'get_cancel_limit_by_users',
            'get_pause_limit_by_users',
                ) ;
        $this->settings      = $this->get_settings() ;
        $this->init() ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings', array(
            array(
                'name' => __( 'My Account Page Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_my_account_page_settings'
            ),
            array(
                'name'     => __( 'Allow Subscribers to Pause their Subscriptions', 'sumosubscriptions' ),
                'id'       => 'sumo_pause_resume_option',
                'newids'   => 'sumo_pause_resume_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers can pause their subscriptions.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Allow Subscribers to Pause their Synchronized Subscriptions', 'sumosubscriptions' ),
                'id'       => 'sumo_sync_pause_resume_option',
                'newids'   => 'sumo_sync_pause_resume_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If you have allowed the customers to pause their Synchronized Subscription, then the customers might not get the extended number of days based on the Pause duration once the subscription gets resumed.', 'sumosubscriptions' ),
            ),
            array(
                'name'              => __( 'Maximum Number of Pauses', 'sumosubscriptions' ),
                'id'                => 'sumo_settings_max_no_of_pause',
                'newids'            => 'sumo_settings_max_no_of_pause',
                'type'              => 'number',
                'css'               => 'width:75px',
                'std'               => '0',
                'default'           => '0',
                'desc'              => __( 'times', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls the number of times a user can pause their subscription. If left empty or set 0 then, they can pause their subscription infinite times.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required'
                ),
            ),
            array(
                'name'              => __( 'Maximum Pause Duration', 'sumosubscriptions' ),
                'id'                => 'sumo_settings_max_duration_of_pause',
                'newids'            => 'sumo_settings_max_duration_of_pause',
                'type'              => 'number',
                'css'               => 'width:75px',
                'std'               => '10',
                'default'           => '10',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long should the subscriber can pause their subscription.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min'      => 1,
                    'required' => 'required'
                )
            ),
            array(
                'name'    => __( 'Allow User to Select the Resume Date', 'sumosubscriptions' ),
                'id'      => 'sumo_allow_user_to_select_resume_date',
                'newids'  => 'sumo_allow_user_to_select_resume_date',
                'type'    => 'checkbox',
                'std'     => 'no',
                'default' => 'no',
            ),
            array(
                'name'    => __( 'User/Userrole Filter', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_pause_by_user_or_userrole_filter',
                'newids'  => 'sumo_subscription_pause_by_user_or_userrole_filter',
                'type'    => 'select',
                'std'     => 'all_users',
                'default' => 'all_users',
                'options' => array(
                    'all_users'          => __( 'All Users', 'sumosubscriptions' ),
                    'included_users'     => __( 'Include User(s)', 'sumosubscriptions' ),
                    'excluded_users'     => __( 'Exclude User(s)', 'sumosubscriptions' ),
                    'included_user_role' => __( 'Include User Role(s)', 'sumosubscriptions' ),
                    'excluded_user_role' => __( 'Exclude User Role(s)', 'sumosubscriptions' )
                )
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_pause_limit_by_users' ),
            ),
            array(
                'name'    => __( 'Select User Role(s)', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_pause_by_userrole_filter',
                'newids'  => 'sumo_subscription_pause_by_userrole_filter',
                'type'    => 'multiselect',
                'std'     => array(),
                'default' => array(),
                'options' => sumosubs_user_roles()
            ),
            array(
                'name'     => __( 'Allow Subscribers to Cancel their Subscriptions', 'sumosubscriptions' ),
                'id'       => 'sumo_cancel_option',
                'newids'   => 'sumo_cancel_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers can cancel their subscriptions.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Product/Category Filter', 'sumosubscriptions' ),
                'type'    => 'select',
                'id'      => 'sumo_subscription_cancel_by_product_or_category_filter',
                'std'     => 'all_products',
                'default' => 'all_products',
                'newids'  => 'sumo_subscription_cancel_by_product_or_category_filter',
                'options' => array(
                    'all_products'        => __( 'All subscription product(s)', 'sumosubscriptions' ),
                    'included_products'   => __( 'Included Subscription Product(s)', 'sumosubscriptions' ),
                    'excluded_products'   => __( 'Excluded subscription product(s)', 'sumosubscriptions' ),
                    'all_categories'      => __( 'All subscription categories', 'sumosubscriptions' ),
                    'included_categories' => __( 'Included subscription categories', 'sumosubscriptions' ),
                    'excluded_categories' => __( 'Excluded subscription categories', 'sumosubscriptions' ),
                ),
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_cancel_limit_by_products' ),
            ),
            array(
                'name'    => __( 'Select Category', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_cancel_by_category_filter',
                'class'   => 'sumo_subscription_cancel_by_category_filter',
                'css'     => 'min-width:50%',
                'type'    => 'multiselect',
                'newids'  => 'sumo_subscription_cancel_by_category_filter',
                'options' => sumosubs_category_list(),
            ),
            array(
                'name'    => __( 'User/Userrole Filter', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_cancel_by_user_or_userrole_filter',
                'newids'  => 'sumo_subscription_cancel_by_user_or_userrole_filter',
                'type'    => 'select',
                'std'     => 'all_users',
                'default' => 'all_users',
                'options' => array(
                    'all_users'          => __( 'All Users', 'sumosubscriptions' ),
                    'included_users'     => __( 'Include User(s)', 'sumosubscriptions' ),
                    'excluded_users'     => __( 'Exclude User(s)', 'sumosubscriptions' ),
                    'included_user_role' => __( 'Include User Role(s)', 'sumosubscriptions' ),
                    'excluded_user_role' => __( 'Exclude User Role(s)', 'sumosubscriptions' )
                )
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_cancel_limit_by_users' ),
            ),
            array(
                'name'    => __( 'Select User Role(s)', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_cancel_by_userrole_filter',
                'newids'  => 'sumo_subscription_cancel_by_userrole_filter',
                'type'    => 'multiselect',
                'std'     => array(),
                'default' => array(),
                'options' => sumosubs_user_roles()
            ),
            array(
                'name'              => __( 'Allow Subscribers to Cancel their Subscriptions after', 'sumosubscriptions' ),
                'id'                => 'sumo_min_days_user_wait_to_cancel_their_subscription',
                'newids'            => 'sumo_min_days_user_wait_to_cancel_their_subscription',
                'type'              => 'number',
                'std'               => '',
                'css'               => 'width:75px',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'default'           => '',
                'custom_attributes' => array(
                    'min' => 0,
                ),
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls after how many day(s) of subscription purchase, subscriber can cancel their subscriptions.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Cancel Options that should be available to the Subscriber', 'sumosubscriptions' ),
                'id'      => 'sumo_subscription_cancel_methods_available_to_subscriber',
                'newids'  => 'sumo_subscription_cancel_methods_available_to_subscriber',
                'type'    => 'multiselect',
                'options' => array(
                    'immediate'            => __( 'Cancel immediately', 'sumosubscriptions' ),
                    'end_of_billing_cycle' => __( 'Cancel at the end of billing cycle', 'sumosubscriptions' ),
                    'scheduled_date'       => __( 'Cancel on a scheduled date', 'sumosubscriptions' ),
                ),
                'std'     => array( 'immediate' ),
                'default' => array( 'immediate' ),
            ),
            array(
                'name'     => __( 'Allow Switching between Variations of a Variable Subscription Product', 'sumosubscriptions' ),
                'id'       => 'sumo_switch_variation_subscription_option',
                'newids'   => 'sumo_switch_variation_subscription_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers have the option to switch between similar variations of the variable subscription product which they have purchased.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Allow Subscribers to Change their Subscription Qty', 'sumosubscriptions' ),
                'id'       => 'sumo_allow_subscribers_to_change_qty',
                'newids'   => 'sumo_allow_subscribers_to_change_qty',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers can change their subscriptions quantity.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Allow Subscribers to Resubscribe', 'sumosubscriptions' ),
                'id'       => 'sumo_allow_subscribers_to_resubscribe',
                'newids'   => 'sumo_allow_subscribers_to_resubscribe',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'When enabled, subscribers can resubscribe for their expired/cancelled subscriptions with the same subscription price.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Hide Resubscribe Button when', 'sumosubscriptions' ),
                'id'      => 'sumo_hide_resubscribe_button_when',
                'newids'  => 'sumo_hide_resubscribe_button_when',
                'type'    => 'multiselect',
                'std'     => array(),
                'default' => array(),
                'options' => array(
                    'admin_cancel'  => __( 'Admin cancels the subscription', 'sumosubscriptions' ),
                    'user_cancel'   => __( 'User cancels the subscription', 'sumosubscriptions' ),
                    'auto_cancel'   => __( 'Automatic subscription gets cancelled', 'sumosubscriptions' ),
                    'manual_cancel' => __( 'Manual subscription gets cancelled', 'sumosubscriptions' ),
                    'auto_expire'   => __( 'Automatic subscription gets expired', 'sumosubscriptions' ),
                    'manual_expire' => __( 'Manual subscription gets expired', 'sumosubscriptions' ),
                )
            ),
            array(
                'name'     => __( 'Allow Subscribers to Turn Off Automatic Payments', 'sumosubscriptions' ),
                'id'       => 'sumo_allow_subscribers_to_turnoff_auto_payments',
                'newids'   => 'sumo_allow_subscribers_to_turnoff_auto_payments',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers have the option to turn off their automatic subscription payments in case if they have selected automatic subscription renewal during payment.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Allow Subscribers to Change the Shipping Address', 'sumosubscriptions' ),
                'id'       => 'sumo_allow_subscribers_to_change_shipping_address',
                'newids'   => 'sumo_allow_subscribers_to_change_shipping_address',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, subscribers have the option to change their shipping address so that further renewal orders will be shipped to the updated shipping address.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Activity Logs', 'sumosubscriptions' ),
                'id'      => 'sumosubs_show_activity_logs',
                'newids'  => 'sumosubs_show_activity_logs',
                'type'    => 'select',
                'std'     => 'show',
                'default' => 'show',
                'options' => array(
                    'show' => __( 'Show', 'sumosubscriptions' ),
                    'hide' => __( 'Hide', 'sumosubscriptions' ),
                )
            ),
            array(
                'name'    => __( 'Pagination and Search Box', 'sumosubscriptions' ),
                'id'      => 'sumosubs_show_pagination_and_search',
                'newids'  => 'sumosubs_show_pagination_and_search',
                'type'    => 'select',
                'std'     => 'show',
                'default' => 'show',
                'options' => array(
                    'show' => __( 'Show', 'sumosubscriptions' ),
                    'hide' => __( 'Hide', 'sumosubscriptions' ),
                )
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_my_account_page_settings' ),
            array(
                'name' => __( 'Downloadable Products Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_content_dripping_settings'
            ),
            array(
                'name'     => __( 'Content Dripping', 'sumosubscriptions' ),
                'id'       => 'sumo_enable_content_dripping',
                'newids'   => 'sumo_enable_content_dripping',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, when a new file is added to the downloadable subscription product, the already subscribed subscribers can\'t see the newly added files. It will be displayed only when the renewal of the subscription is completed for the product.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_content_dripping_settings' ),
            array(
                'name' => __( 'Additional Digital Downloads Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_additional_digital_downloads_settings'
            ),
            array(
                'name'     => __( 'Enable Additional Digital Downloads for Subscription Products', 'sumosubscriptions' ),
                'id'       => 'sumo_enable_additional_digital_downloads_option',
                'newids'   => 'sumo_enable_additional_digital_downloads_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'When enabled, site admin will have an option to link digital products to subscription products. The downloadable files from those products can be accessed by the subscriber when they purchase the subscriptions.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_additional_digital_downloads_settings' ),
                ) ) ;
    }

    /**
     * Save the custom options once.
     */
    public function custom_types_add_options() {
        add_option( 'sumo_subscription_pause_by_user_filter', array() ) ;
        add_option( 'sumo_subscription_cancel_by_user_filter', array() ) ;
        add_option( 'sumo_subscription_cancel_by_product_filter', array() ) ;
    }

    /**
     * Delete the custom options.
     */
    public function custom_types_delete_options() {
        delete_option( 'sumo_subscription_pause_by_user_filter' ) ;
        delete_option( 'sumo_subscription_cancel_by_user_filter' ) ;
        delete_option( 'sumo_subscription_cancel_by_product_filter' ) ;
    }

    /**
     * Save custom settings.
     */
    public function custom_types_save() {

        if ( isset( $_POST[ 'sumo_subscription_pause_by_user_filter' ] ) ) {
            update_option( 'sumo_subscription_pause_by_user_filter',  ! is_array( $_POST[ 'sumo_subscription_pause_by_user_filter' ] ) ? array_filter( array_map( 'absint', explode( ',', $_POST[ 'sumo_subscription_pause_by_user_filter' ] ) ) ) : $_POST[ 'sumo_subscription_pause_by_user_filter' ]  ) ;
        }
        if ( isset( $_POST[ 'sumo_subscription_cancel_by_user_filter' ] ) ) {
            update_option( 'sumo_subscription_cancel_by_user_filter',  ! is_array( $_POST[ 'sumo_subscription_cancel_by_user_filter' ] ) ? array_filter( array_map( 'absint', explode( ',', $_POST[ 'sumo_subscription_cancel_by_user_filter' ] ) ) ) : $_POST[ 'sumo_subscription_cancel_by_user_filter' ]  ) ;
        }
        if ( isset( $_POST[ 'sumo_subscription_cancel_by_product_filter' ] ) ) {
            update_option( 'sumo_subscription_cancel_by_product_filter',  ! is_array( $_POST[ 'sumo_subscription_cancel_by_product_filter' ] ) ? array_filter( array_map( 'absint', explode( ',', $_POST[ 'sumo_subscription_cancel_by_product_filter' ] ) ) ) : $_POST[ 'sumo_subscription_cancel_by_product_filter' ]  ) ;
        }
    }

    /**
     * Custom type field.
     */
    public function get_cancel_limit_by_users() {

        sumosubs_wc_search_field( array(
            'class'       => 'wc-customer-search',
            'id'          => 'sumo_subscription_cancel_by_user_filter',
            'type'        => 'customer',
            'title'       => __( 'Select User(s)', 'sumosubscriptions' ),
            'placeholder' => __( 'Search for a user&hellip;', 'sumosubscriptions' ),
            'options'     => ( array ) get_option( 'sumo_subscription_cancel_by_user_filter', array() )
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_pause_limit_by_users() {

        sumosubs_wc_search_field( array(
            'class'       => 'wc-customer-search',
            'id'          => 'sumo_subscription_pause_by_user_filter',
            'type'        => 'customer',
            'title'       => __( 'Select User(s)', 'sumosubscriptions' ),
            'placeholder' => __( 'Search for a user&hellip;', 'sumosubscriptions' ),
            'options'     => ( array ) get_option( 'sumo_subscription_pause_by_user_filter', array() )
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_cancel_limit_by_products() {

        sumosubs_wc_search_field( array(
            'class'       => 'wc-product-search',
            'id'          => 'sumo_subscription_cancel_by_product_filter',
            'type'        => 'product',
            'action'      => 'sumosubscription_json_search_subscription_products_and_variations',
            'title'       => __( 'Product Filter', 'sumosubscriptions' ),
            'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
            'options'     => ( array ) get_option( 'sumo_subscription_cancel_by_product_filter', array() )
        ) ) ;
    }

}

return new SUMOSubscriptions_My_Account_Settings() ;
