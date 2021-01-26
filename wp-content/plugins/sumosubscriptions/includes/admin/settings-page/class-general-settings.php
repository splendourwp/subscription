<?php

/**
 * General Settings.
 * 
 * @class SUMOSubscriptions_General_Settings
 * @category Class
 */
class SUMOSubscriptions_General_Settings extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_General_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'general' ;
        $this->label         = __( 'General', 'sumosubscriptions' ) ;
        $this->custom_fields = array(
            'get_shortcodes_and_its_usage',
            'get_renewal_coupon_limit_by_users',
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
            array( 'type' => $this->get_custom_field_type( 'get_shortcodes_and_its_usage' ) ),
            array(
                'name' => __( 'Button Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_add_to_cart_text_setting'
            ),
            array(
                'name'     => __( 'Add to Cart Button Text', 'sumosubscriptions' ),
                'id'       => 'sumo_add_to_cart_text',
                'newids'   => 'sumo_add_to_cart_text',
                'type'     => 'text',
                'std'      => __( 'Sign up Now', 'sumosubscriptions' ),
                'desc_tip' => true,
                'desc'     => __( 'It changes the "Add to Cart" button label for subscription products.', 'sumosubscriptions' ),
                'default'  => __( 'Sign up Now', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_add_to_cart_text_setting' ),
            array(
                'name' => __( 'Renewal Order Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_renewal_order_settings'
            ),
            array(
                'name'              => __( 'Create Renewal Order', 'sumosubscriptions' ),
                'id'                => 'sumo_create_renewal_order_on',
                'newids'            => 'sumo_create_renewal_order_on',
                'type'              => 'number',
                'std'               => '1',
                'css'               => 'width:75px;',
                'desc'              => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls when the renewal order for the subscription needs to be created.', 'sumosubscriptions' ),
                'default'           => '1',
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required',
                )
            ),
            array(
                'name'     => __( 'Include Shipping Cost in Renewal Order', 'sumosubscriptions' ),
                'id'       => 'sumo_shipping_option',
                'newids'   => 'sumo_shipping_option',
                'type'     => 'checkbox',
                'std'      => 'yes',
                'default'  => 'yes',
                'desc_tip' => __( 'If enabled, shipping cost from the parent order will be added to the renewal order.', 'sumosubscriptions' )
            ),
            array(
                'name'     => __( 'Charge Shipping Cost only during Renewals of Subscriptions when Subtotal is 0', 'sumosubscriptions' ),
                'id'       => 'sumo_charge_shipping_only_in_renewals_when_subtotal_zero',
                'newids'   => 'sumo_charge_shipping_only_in_renewals_when_subtotal_zero',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, shipping cost will be charged only during renewal of the subscription when the order subtotal is 0.', 'sumosubscriptions' )
            ),
            array(
                'name'     => __( 'Include Tax Cost in Renewal Order', 'sumosubscriptions' ),
                'id'       => 'sumo_tax_option',
                'newids'   => 'sumo_tax_option',
                'type'     => 'checkbox',
                'std'      => 'yes',
                'default'  => 'yes',
                'desc_tip' => __( 'If enabled, tax cost from the parent order will be added to the renewal order.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Apply Coupon Code Discount in Renewal Order', 'sumosubscriptions' ),
                'id'       => 'sumo_coupon_in_renewal_order',
                'newids'   => 'sumo_coupon_in_renewal_order',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'If enabled, coupon code discount for the subscription product will be applied to the renewal orders. You can see the discount which will be applied in order details page(WooCommerce → Orders → Edit) of the parent order. <br>Note: This option is not applicable for "Signup fee discount, Signup fee&nbsp;%&nbsp;discount, Recurring fee discount, Recurring fee&nbsp;%&nbsp;discount" coupon types.', 'sumosubscriptions' )
            ),
            array(
                'name'     => __( 'Coupon Code Discount in Renewal Order applicable for', 'sumosubscriptions' ),
                'id'       => 'sumo_coupon_in_renewal_order_applicable_for',
                'newids'   => 'sumo_coupon_in_renewal_order_applicable_for',
                'type'     => 'select',
                'std'      => 'all_users',
                'default'  => 'all_users',
                'options'  => array(
                    'all_users'         => __( 'All Users', 'sumosubscriptions' ),
                    'include_users'     => __( 'Include User(s)', 'sumosubscriptions' ),
                    'exclude_users'     => __( 'Exclude User(s)', 'sumosubscriptions' ),
                    'include_user_role' => __( 'Include User Role(s)', 'sumosubscriptions' ),
                    'exclude_user_role' => __( 'Exclude User Role(s)', 'sumosubscriptions' )
                ),
                'desc_tip' => true,
                'desc'     => __( 'This option controls who are eligible for getting coupon code discount.', 'sumosubscriptions' )
            ),
            array(
                'type' => $this->get_custom_field_type( 'get_renewal_coupon_limit_by_users' )
            ),
            array(
                'name'    => __( 'Select User Role(s)', 'sumosubscriptions' ),
                'id'      => 'sumo_selected_user_roles_for_renewal_order_discount',
                'newids'  => 'sumo_selected_user_roles_for_renewal_order_discount',
                'type'    => 'multiselect',
                'std'     => array(),
                'default' => array(),
                'options' => sumosubs_user_roles()
            ),
            array(
                'name'     => __( 'Number of Renewal(s) to Apply Coupon Code Discount', 'sumosubscriptions' ),
                'id'       => 'sumo_apply_coupon_discount',
                'newids'   => 'sumo_apply_coupon_discount',
                'type'     => 'select',
                'std'      => '1',
                'options'  => array(
                    '1' => __( 'All Renewals', 'sumosubscriptions' ),
                    '2' => __( 'Fixed Renewal(s)', 'sumosubscriptions' ),
                ),
                'desc_tip' => true,
                'desc'     => __( 'This option controls whether the coupon code discount needs to be applied for all the future renewals or else only for specific number of renewals.', 'sumosubscriptions' )
            ),
            array(
                'name'              => __( 'Number of Times Apply Coupon Code Discount in Renewal Order', 'sumosubscriptions' ),
                'id'                => 'no_of_sumo_selected_renewal_order_discount',
                'newids'            => 'no_of_sumo_selected_renewal_order_discount',
                'type'              => 'number',
                'std'               => '',
                'default'           => '',
                'css'               => 'width:75px;',
                'custom_attributes' => array(
                    'min' => 0,
                ),
                'desc_tip'          => true,
                'desc'              => __( 'This option controls the renewal order count to apply coupon code discount. For example, if 2 is set here, coupon code discount will be applied for 2 renewal orders. From 3rd renewal order onwards coupon code discount will not be applied.', 'sumosubscriptions' )
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_renewal_order_settings' ),
            array(
                'name' => __( 'Renewal Order Email Notification Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_renewal_order_notification_email_settings'
            ),
            array(
                'name'     => __( 'Send Payment Reminder Email - Manual', 'sumosubscriptions' ),
                'id'       => 'sumo_remaind_notification_email',
                'newids'   => 'sumo_remaind_notification_email',
                'type'     => 'text',
                'std'      => '3,2,1',
                'default'  => '3,2,1',
                'desc'     => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the payment reminder email for the renewal order during manual renewal needs to be sent. For example, if it is set as 3,2,1 then, the first reminder email will be sent 3 days before subscription renewal, second before 2 days and third before 1 day of the subscription renewal. If set 0 or else left empty, reminder email will be sent on the subscription renewal date.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Send Payment Reminder Email - Automatic', 'sumosubscriptions' ),
                'id'       => 'sumo_remaind_notification_email_for_automatic',
                'newids'   => 'sumo_remaind_notification_email_for_automatic',
                'type'     => 'text',
                'std'      => '3,2,1',
                'default'  => '3,2,1',
                'desc'     => __( 'day(s) before due date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the payment reminder email for the renewal order during automatic renewal needs to be sent. For example, if it is set as 3,2,1 then, the first reminder email will be sent 3 days before subscription renewal, second before 2 days and third before 1 day of the subscription renewal. If set 0 or else left empty, reminder email will be sent on the subscription renewal date.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_renewal_order_notification_email_settings' ),
            array(
                'name' => __( 'Expiry Email Notification Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_expiry_notification_email_settings'
            ),
            array(
                'name'     => __( 'Send Expiry Reminder Email', 'sumosubscriptions' ),
                'id'       => 'sumo_expiry_reminder_email',
                'newids'   => 'sumo_expiry_reminder_email',
                'type'     => 'text',
                'std'      => '3,2,1',
                'default'  => '3,2,1',
                'desc'     => __( 'day(s) before expiry date', 'sumosubscriptions' ),
                'desc_tip' => __( 'This option controls when the expiry reminder email needs to be sent. For example, if it is set as 3,2,1 then, the first reminder email will be sent 3 days before subscription expiry, second before 2 days and third before 1 day of the subscription expiry. If set 0 or else left empty, reminder email will be sent on the subscription expiry date.', 'sumosubscriptions' ),
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_expiry_notification_email_settings' ),
            array(
                'name' => __( 'Overdue and Suspend Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_overdue_and_suspend_settings'
            ),
            array(
                'name'              => __( 'Overdue Period', 'sumosubscriptions' ),
                'id'                => 'sumo_settings_overdue_notification_email',
                'newids'            => 'sumo_settings_overdue_notification_email',
                'type'              => 'number',
                'std'               => '5',
                'css'               => 'width:75px',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Overdue" status until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Overdue" status for 2 days from the subscription due date. During overdue period, subscriber still have access to their subscription.', 'sumosubscriptions' ),
                'default'           => '5',
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required'
                )
            ),
            array(
                'name'              => __( 'Suspend Period', 'sumosubscriptions' ),
                'id'                => 'sumo_suspend_notification_email',
                'newids'            => 'sumo_suspend_notification_email',
                'type'              => 'number',
                'std'               => '5',
                'css'               => 'width:75px',
                'desc'              => __( 'day(s)', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Suspended" status after the overdue period until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Suspended" status for 2 days from the overdue period. During suspend period, subscriber is not allowed to access their subscription.', 'sumosubscriptions' ),
                'default'           => '5',
                'custom_attributes' => array(
                    'min'      => 0,
                    'required' => 'required'
                )
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_overdue_and_suspend_settings' ),
            array(
                'name' => __( 'Limit/Restriction Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_limit_settings'
            ),
            array(
                'name'   => __( 'Mixed Checkout', 'sumosubscriptions' ),
                'desc'   => __( 'Purchase non-subscription products along with subscription products in same order', 'sumosubscriptions' ),
                'tip'    => '',
                'id'     => 'sumosubscription_apply_mixed_checkout',
                'css'    => '',
                'class'  => '',
                'std'    => 'yes',
                'type'   => 'checkbox',
                'newids' => 'sumosubscription_apply_mixed_checkout',
            ),
            array(
                'name'     => __( 'Limit Subscription for each Subscriber', 'sumosubscriptions' ),
                'id'       => 'sumo_limit_subscription_quantity',
                'newids'   => 'sumo_limit_subscription_quantity',
                'css'      => 'width:315px',
                'type'     => 'select',
                'std'      => '1',
                'options'  => array(
                    '1' => __( 'No Limit', 'sumosubscriptions' ),
                    '2' => __( 'One Active Subscription per Product', 'sumosubscriptions' ),
                    '3' => __( 'One Active Subscription throughout the site', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls the number of subscriptions each subscriber can purchase in the site. If the subscription status becomes Cancelled, Failed, Expired or the subscription is Trashed by admin then, the limit will be relaxed and they can purchase another subscription.', 'sumosubscriptions' ),
                'desc_tip' => true
            ),
            array(
                'name'     => __( 'Limit Trial for each Subscriber', 'sumosubscriptions' ),
                'id'       => 'sumo_trial_handling',
                'newids'   => 'sumo_trial_handling',
                'css'      => 'width:315px',
                'type'     => 'select',
                'std'      => '1',
                'options'  => array(
                    '1' => __( 'No Limit', 'sumosubscriptions' ),
                    '2' => __( 'One Trial per Product', 'sumosubscriptions' ),
                    '3' => __( 'One Trial throughout the site', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls the number of trials each subscriber can obtain on the site.', 'sumosubscriptions' ),
                'desc_tip' => true
            ),
            array(
                'name'     => __( 'Limit Variable Subscription Products at', 'sumosubscriptions' ),
                'id'       => 'sumo_limit_variable_product_level',
                'newids'   => 'sumo_limit_variable_product_level',
                'type'     => 'select',
                'std'      => '1',
                'options'  => array(
                    '1' => __( 'Variant Level', 'sumosubscriptions' ),
                    '2' => __( 'Product Level', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'If "Variant level" is selected, subscription and trial will be limited from variant level for variable subscription products. If "Product level" is selected, subscription and trial will be limited from product level which means user cannot purchase all the variations of the variable subscription product.', 'sumosubscriptions' ),
                'desc_tip' => true
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_limit_settings' ),
            array(
                'name' => __( 'Payment Gateway Settings', 'sumosubscriptions' ),
                'type' => 'title',
                'id'   => 'sumo_payment_gateway_settings'
            ),
            array(
                'name'    => __( 'Hide Inbuilt Payment Gateways when Non Subscription Products are in Cart', 'sumosubscriptions' ),
                'id'      => 'sumosubs_hide_auto_payment_gateways_when_non_subscriptions_in_cart',
                'newids'  => 'sumosubs_hide_auto_payment_gateways_when_non_subscriptions_in_cart',
                'type'    => 'checkbox',
                'std'     => 'no',
                'default' => 'no',
            ),
            array(
                'name'    => __( 'Accept Manual Payment Gateways', 'sumosubscriptions' ),
                'id'      => 'sumosubs_accept_manual_payment_gateways',
                'newids'  => 'sumosubs_accept_manual_payment_gateways',
                'type'    => 'checkbox',
                'std'     => 'yes',
                'default' => 'yes',
                'desc'    => __( 'If enabled, manual payment gateways will be displayed along with automatic payment gateways in checkout page when subscription product is added in cart.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Disable Automatic Payment Gateways', 'sumosubscriptions' ),
                'id'      => 'sumosubs_disable_auto_payment_gateways',
                'newids'  => 'sumosubs_disable_auto_payment_gateways',
                'type'    => 'checkbox',
                'std'     => 'no',
                'default' => 'no',
                'desc'    => __( 'If enabled, automatic payment gateways will be hidden in checkout page.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Remove the option for the Subscriber to choose Automatic/Manual Subscription Renewal', 'sumosubscriptions' ),
                'id'       => 'sumo_paypal_payment_option',
                'newids'   => 'sumo_paypal_payment_option',
                'type'     => 'checkbox',
                'std'      => 'no',
                'default'  => 'no',
                'desc_tip' => __( 'This option controls whether the user should have an option for preapproving the future subscription renewals.', 'sumosubscriptions' ),
            ),
            array(
                'name'     => __( 'Force Automatic/Manual Payment', 'sumosubscriptions' ),
                'id'       => 'sumo_force_auto_manual_paypal_adaptive',
                'newids'   => 'sumo_force_auto_manual_paypal_adaptive',
                'type'     => 'select',
                'std'      => '2',
                'options'  => array(
                    '1' => __( 'Force Manual Payments', 'sumosubscriptions' ),
                    '2' => __( 'Force Automatic Payments', 'sumosubscriptions' ),
                ),
                'desc'     => __( 'This option controls how the subscription renewals has to be managed when the user purchases using inbuilt automatic payment gateways.', 'sumosubscriptions' ),
                'desc_tip' => true
            ),
            array(
                'name'     => __( 'Enable PayPal Standard Subscription API', 'sumosubscriptions' ),
                'id'       => 'sumo_include_paypal_subscription_api_option',
                'newids'   => 'sumo_include_paypal_subscription_api_option',
                'type'     => 'checkbox',
                'std'      => 'yes',
                'default'  => 'yes',
                'desc_tip' => __( 'If enabled, subscription will be managed through "PayPal Subscriptions API" to get automatic subscription payments when default PayPal payment gateway is used for payment.', 'sumosubscriptions' ),
            ),
            array(
                'name'    => __( 'Show PayPal Standard Gateway when Multiple Subscription Products are in Cart', 'sumosubscriptions' ),
                'id'      => 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart',
                'newids'  => 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart',
                'type'    => 'checkbox',
                'std'     => 'yes',
                'default' => 'yes',
            ),
            array( 'type' => 'sectionend', 'id' => 'sumo_payment_gateway_settings' ),
                ) ) ;
    }

    /**
     * Save the custom options once.
     */
    public function custom_types_add_options() {
        add_option( 'sumo_selected_users_for_renewal_order_discount', array() ) ;
    }

    /**
     * Delete the custom options.
     */
    public function custom_types_delete_options() {
        delete_option( 'sumo_selected_users_for_renewal_order_discount' ) ;
    }

    /**
     * Save custom settings.
     */
    public function custom_types_save() {

        if ( isset( $_POST[ 'sumo_selected_users_for_renewal_order_discount' ] ) ) {
            update_option( 'sumo_selected_users_for_renewal_order_discount',  ! is_array( $_POST[ 'sumo_selected_users_for_renewal_order_discount' ] ) ? array_filter( array_map( 'absint', explode( ',', $_POST[ 'sumo_selected_users_for_renewal_order_discount' ] ) ) ) : $_POST[ 'sumo_selected_users_for_renewal_order_discount' ]  ) ;
        }
    }

    /**
     * Custom type field.
     */
    public function get_renewal_coupon_limit_by_users() {

        sumosubs_wc_search_field( array(
            'class'       => 'wc-customer-search',
            'id'          => 'sumo_selected_users_for_renewal_order_discount',
            'type'        => 'customer',
            'title'       => __( 'Select User(s)', 'sumosubscriptions' ),
            'placeholder' => __( 'Search for a user&hellip;', 'sumosubscriptions' ),
            'options'     => ( array ) get_option( 'sumo_selected_users_for_renewal_order_discount', array() )
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_shortcodes_and_its_usage() {
        $shortcodes = array(
            '[sumo_my_subscriptions]' => __( 'Use this shortcode to display My Subscriptions.', 'sumosubscriptions' ),
                ) ;
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Shortcode', 'sumosubscriptions' ) ; ?></th>
                    <th><?php _e( 'Purpose', 'sumosubscriptions' ) ; ?></th>
                </tr>
            </thead>
            <tbody>                
                <?php foreach ( $shortcodes as $shortcode => $purpose ): ?>
                    <tr>
                        <td><?php echo $shortcode ; ?></td>
                        <td><?php echo $purpose ; ?></td>
                    </tr>
                <?php endforeach ; ?>
            </tbody>
        </table>
        <?php
    }

}

return new SUMOSubscriptions_General_Settings() ;
