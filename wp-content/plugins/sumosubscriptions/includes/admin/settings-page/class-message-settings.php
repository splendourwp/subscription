<?php

/**
 * Message Settings.
 * 
 * @class SUMOSubscriptions_Message_Settings
 * @category Class
 */
class SUMOSubscriptions_Message_Settings extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_Message_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'messages' ;
        $this->label         = __( 'Messages' , 'sumosubscriptions' ) ;
        $this->custom_fields = array (
            'get_shortcodes_and_its_usage' ,
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

        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Available Subscription Shortcodes and its Usage' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_subscription_shortcodes_description'
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_shortcodes_and_its_usage' )
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_subscription_shortcodes_description' ) ,
            array (
                'name' => __( 'Subscription Message Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_subscription_information_customization'
            ) ,
            array (
                'name'   => __( 'Signup Fee Message' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_signup_fee_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<b>[sumo_initial_fee]</b> for now and' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_signup_fee_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Caption for Free Trial' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_freetrial_caption_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<b>Free Trial</b>' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_freetrial_caption_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Trial Fee Message' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_trial_fee_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<b>[sumo_trial_fee]</b> for the first <b>[sumo_trial_period_value]</b> [sumo_trial_period] Then' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_trial_fee_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Subscription Price Message' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_subscription_fee_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<b>[sumo_subscription_fee]</b> for each <b>[sumo_subscription_period_value]</b> [sumo_subscription_period] ' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_subscription_fee_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Installment Message' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_instalment_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'with <b>[sumo_instalment_period_value]</b> [sumo_instalment_period] ' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_instalment_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Variable Product Price Range Prefix' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_variation_product_fee_range_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'Subscription Starts from ' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_variation_product_fee_range_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Optional Trial - Free Trial' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_product_optional_free_trial_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'Include Free Trial' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_product_optional_free_trial_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Optional Trial - Paid Trial' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_product_optional_paid_trial_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'Purchase with Trial Fee [sumo_trial_fee]' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_product_optional_paid_trial_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Optional Signup Fee' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_product_optional_signup_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'Purchase with SignUp Fee [sumo_signup_fee_only]' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_product_optional_signup_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Renewal Fee After Discount' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_renewal_fee_after_discount_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<br>Renewal Fee After Discount: <strong>[renewal_fee_after_discount]</strong>' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_renewal_fee_after_discount_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Discounted Renewal Fee Upto' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_discounted_renewal_fee_upto_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<br>(upto <strong>[discounted_renewal_fee_upto]</strong> renewal(s))' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_discounted_renewal_fee_upto_msg_customization' ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_subscription_information_customization' ) ,
            array (
                'name' => __( 'Synchronization Message Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_synchronization_msg'
            ) ,
            array (
                'name'   => __( 'Synchronization Plan Message' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_subscription_synchronization_plan_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => '' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_subscription_synchronization_plan_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Prorated Amount Message during First Payment' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_prorated_amount_first_payment_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( '<b>[sumo_prorated_fee]</b> for Prorating till <b>[sumo_synchronized_prorated_date]</b> and ' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_prorated_amount_first_payment_msg_customization' ,
            ) ,
            array (
                'name'   => __( 'Prorated Amount Message during First Renewal' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_prorated_amount_first_renewal_msg_customization' ,
                'css'    => 'min-width:550px;' ,
                'std'    => __( 'Prorated till <b>[sumo_synchronized_prorated_date]</b> and amount will be charged on <b>[sumo_synchronized_next_payment_date]</b> and ' , 'sumosubscriptions' ) ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_prorated_amount_first_renewal_msg_customization' ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_synchronization_msg' ) ,
            array (
                'name' => __( 'Period Value Singular/Plural' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_period_singular_plural'
            ) ,
            array (
                'name'   => __( 'Day|Days Customization' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_day_single_plural' ,
                'css'    => 'min-width:550px;' ,
                'std'    => 'day,days' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_day_single_plural' ,
            ) ,
            array (
                'name'   => __( 'Week|Weeks Customization' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_week_single_plural' ,
                'css'    => 'min-width:550px;' ,
                'std'    => 'week,weeks' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_week_single_plural' ,
            ) ,
            array (
                'name'   => __( 'Month|Months Customization' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_month_single_plural' ,
                'css'    => 'min-width:550px;' ,
                'std'    => 'month,months' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_month_single_plural' ,
            ) ,
            array (
                'name'   => __( 'Year|Years Customization' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_year_single_plural' ,
                'css'    => 'min-width:550px;' ,
                'std'    => 'year,years' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_year_single_plural' ,
            ) ,
            array (
                'name'   => __( 'Installment|Installments Customization' , 'sumosubscriptions' ) ,
                'tip'    => '' ,
                'id'     => 'sumo_instalment_single_plural' ,
                'css'    => 'min-width:550px;' ,
                'std'    => 'installment,installments' ,
                'type'   => 'textarea' ,
                'newids' => 'sumo_instalment_single_plural' ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_period_singular_plural' ) ,
            array (
                'name' => __( 'Subscription Cancel Dialog Message Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_cancel_dialog_message_setting'
            ) ,
            array (
                'name'     => __( 'Enable Cancel Confirmation Dialog Message' , 'sumosubscriptions' ) ,
                'id'       => 'sumo_display_dialog_upon_cancel' ,
                'newids'   => 'sumo_display_dialog_upon_cancel' ,
                'type'     => 'checkbox' ,
                'std'      => 'yes' ,
                'default'  => 'yes' ,
                'desc_tip' => __( 'When enabled, the Subscriber/Admin will have to confirm in order to cancel the Subcription.' , 'sumosubscriptions' )
            ) ,
            array (
                'name'    => __( 'Subcription Immediate Cancellation Confirmation Dialog Message' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_cancel_dialog_message' ,
                'newids'  => 'sumo_cancel_dialog_message' ,
                'type'    => 'textarea' ,
                'css'     => 'width:330px' ,
                'std'     => __( 'Are you sure you want to cancel the subscription?' , 'sumosubscriptions' ) ,
                'default' => __( 'Are you sure you want to cancel the subscription?' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'Subcription Cancellation at the End of Billing Cycle Confirmation Dialog Message' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_cancel_at_the_end_of_billing_dialog_message' ,
                'newids'  => 'sumo_cancel_at_the_end_of_billing_dialog_message' ,
                'type'    => 'textarea' ,
                'css'     => 'width:330px' ,
                'std'     => __( 'Are you sure you want to cancel your subscription at the end of this billing cycle?' , 'sumosubscriptions' ) ,
                'default' => __( 'Are you sure you want to cancel your subscription at the end of this billing cycle?' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'Subcription Cancellation on a Scheduled Date Confirmation Dialog Message' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_cancel_on_the_scheduled_date_dialog_message' ,
                'newids'  => 'sumo_cancel_on_the_scheduled_date_dialog_message' ,
                'type'    => 'textarea' ,
                'css'     => 'width:330px' ,
                'std'     => __( 'Are you sure you want to Cancel your subscription on [sumo_cancel_scheduled_date]?' , 'sumosubscriptions' ) ,
                'default' => __( 'Are you sure you want to Cancel your subscription on [sumo_cancel_scheduled_date]?' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'     => __( 'Enable Confirmation Dialog upon Cancel Revoking' , 'sumosubscriptions' ) ,
                'id'       => 'sumo_display_dialog_upon_revoking_cancel' ,
                'newids'   => 'sumo_display_dialog_upon_revoking_cancel' ,
                'type'     => 'checkbox' ,
                'std'      => 'yes' ,
                'default'  => 'yes' ,
                'desc_tip' => __( 'When enabled, the Subscriber will have to confirm in order to revoke the cancel request.' , 'sumosubscriptions' )
            ) ,
            array (
                'name'    => __( 'Subcription Cancel Revoke Confirmation Dialog Message' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_revoking_cancel_confirmation_dialog_message' ,
                'newids'  => 'sumo_revoking_cancel_confirmation_dialog_message' ,
                'type'    => 'textarea' ,
                'css'     => 'width:330px' ,
                'std'     => __( 'Are you sure you want to revoke the cancel request?' , 'sumosubscriptions' ) ,
                'default' => __( 'Are you sure you want to revoke the cancel request?' , 'sumosubscriptions' ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_cancel_dialog_message_setting' ) ,
            array (
                'name' => __( 'Error Message Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_error_msg_settings'
            ) ,
            array (
                'name'    => __( 'Display Error Message in Product Page' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_show_hide_err_msg_product_page' ,
                'newids'  => 'sumo_show_hide_err_msg_product_page' ,
                'type'    => 'checkbox' ,
                'std'     => 'yes' ,
                'default' => 'yes' ,
            ) ,
            array (
                'name'    => __( 'One Active Subscription Per Product' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_active_subsc_per_product_in_product_page' ,
                'newids'  => 'sumo_active_subsc_per_product_in_product_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'You cannot purchase this subscription product again, as you have already purchased this subscription product' , 'sumosubscriptions' ) ,
                'default' => __( 'You cannot purchase this subscription product again, as you have already purchased this subscription product' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'One Active Subscription Throughout the Site' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_active_subsc_through_site_in_product_page' ,
                'newids'  => 'sumo_active_subsc_through_site_in_product_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'You cannot purchase the subscription product again, as you have already purchased one subscription product' , 'sumosubscriptions' ) ,
                'default' => __( 'You cannot purchase the subscription product again, as you have already purchased one subscription product' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'     => __( 'When adding Non-Subscription Product along with Subscription Product' , 'sumosubscriptions' ) ,
                'tip'      => '' ,
                'id'       => 'sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ,
                'css'      => 'min-width:550px;' ,
                'std'      => __( 'You cannot Purchase Non-Subscription Product along with Subscription Product' , 'sumosubscriptions' ) ,
                'type'     => 'textarea' ,
                'newids'   => 'sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ,
                'desc_tip' => false ,
            ) ,
            array (
                'name'     => __( 'When adding Subscription Product along with Non-Subscription Product' , 'sumosubscriptions' ) ,
                'tip'      => '' ,
                'id'       => 'sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ,
                'css'      => 'min-width:550px;' ,
                'std'      => __( 'You cannot Purchase Subscription Product along with Non-Subscription Product' , 'sumosubscriptions' ) ,
                'type'     => 'textarea' ,
                'newids'   => 'sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ,
                'desc_tip' => false ,
            ) ,
            array (
                'name'    => __( 'Display Error Message in Cart Page' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_show_hide_err_msg_cart_page' ,
                'newids'  => 'sumo_show_hide_err_msg_cart_page' ,
                'type'    => 'checkbox' ,
                'std'     => 'yes' ,
                'default' => 'yes' ,
            ) ,
            array (
                'name'    => __( 'One Trial Per Product' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_active_trial_per_product_in_cart_page' ,
                'newids'  => 'sumo_active_trial_per_product_in_cart_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'You cannot purchase the trial period on the product(s) [product_name(s)] again, as you have already purchased this trial period. You have to pay the full subscription price' , 'sumosubscriptions' ) ,
                'default' => __( 'You cannot purchase the trial period on the product(s) [product_name(s)] again, as you have already purchased this trial period. You have to pay the full subscription price' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'One Trial Throughout the Site' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_active_trial_through_site_in_cart_page' ,
                'newids'  => 'sumo_active_trial_through_site_in_cart_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'You cannot purchase the trial period again. As you have already purchased one trial period. You have to pay the full subscription price' , 'sumosubscriptions' ) ,
                'default' => __( 'You cannot purchase the trial period again. As you have already purchased one trial period. You have to pay the full subscription price' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'Display Error Message in Pay for Order Page' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_show_hide_err_msg_pay_order_page' ,
                'newids'  => 'sumo_show_hide_err_msg_pay_order_page' ,
                'type'    => 'checkbox' ,
                'std'     => 'yes' ,
                'default' => 'yes' ,
            ) ,
            array (
                'name'    => __( 'If User Pay Invoice Order after the Subscription is Paused' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_err_msg_for_paused_in_pay_for_order_page' ,
                'newids'  => 'sumo_err_msg_for_paused_in_pay_for_order_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'This subscription status is currently Paused --it cannot be paid for right now. Please contact us if you need assistance.' , 'sumosubscriptions' ) ,
                'default' => __( 'This subscription status is currently Paused --it cannot be paid for right now. Please contact us if you need assistance.' , 'sumosubscriptions' ) ,
            ) ,            
            array (
                'name'    => __( 'If User Pay Invoice Order after the Subscription is Pending for Cancellation' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ,
                'newids'  => 'sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ,
                'type'    => 'textarea' ,
                'css'     => 'width:900px' ,
                'std'     => __( 'This subscription status is currently in Pending for Cancellation --it cannot be paid for right now. Please contact us if you need assistance.' , 'sumosubscriptions' ) ,
                'default' => __( 'This subscription status is currently in Pending for Cancellation --it cannot be paid for right now. Please contact us if you need assistance.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'   => __( 'If User tries to Make Payment for Renewal Orders of Automatic Subscription during Trial or Active Subscription status' , 'sumosubscriptions' ) ,
                'id'     => 'sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ,
                'newids' => 'sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ,
                'type'   => 'textarea' ,
                'css'    => 'width:900px' ,
                'std'    => __( 'This order is a renewal order for subscription #[subscription_number]. You have preapproved for automatic subscription charging. Automatic charging will take place on [next_payment_date]. Hence, making payment for this order has been disabled.' , 'sumosubscriptions' ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_error_msg_settings' )
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_shortcodes_and_its_usage() {
        $shortcodes = array (
            '[sumo_initial_fee]'                    => __( 'To display the sum of SignUp Fee and Trial/Subscription Fee' , 'sumosubscriptions' ) ,
            '[sumo_signup_fee_only]'                => __( 'To display only SignUp Fee' , 'sumosubscriptions' ) ,
            '[sumo_trial_fee]'                      => __( 'To display Trial Fee' , 'sumosubscriptions' ) ,
            '[sumo_trial_period]'                   => __( 'To display the Trial Duration' , 'sumosubscriptions' ) ,
            '[sumo_trial_period_value]'             => __( 'To display Trial Duration Value' , 'sumosubscriptions' ) ,
            '[sumo_subscription_fee]'               => __( 'To display Subscription Price' , 'sumosubscriptions' ) ,
            '[sumo_subscription_period]'            => __( 'To display Subscription Duration. That is, day(s) / week(s) / month(s) / year(s)' , 'sumosubscriptions' ) ,
            '[sumo_subscription_period_value]'      => __( 'To display Subscription Duration Value' , 'sumosubscriptions' ) ,
            '[sumo_instalment_period]'              => __( 'To display Subscription Installment. That is, installment(s)' , 'sumosubscriptions' ) ,
            '[sumo_instalment_period_value]'        => __( 'To display Subscription Recurring Cycle. That is, Indefinite / 1 to 52' , 'sumosubscriptions' ) ,
            '[sumo_prorated_fee]'                   => __( 'To display the Subscription Prorated Fee' , 'sumosubscriptions' ) ,
            '[sumo_synchronized_prorated_date]'     => __( 'To display the Subscription Prorated Date' , 'sumosubscriptions' ) ,
            '[sumo_synchronized_next_payment_date]' => __( 'To display Synchronized Next Payment Date in Single Product Page / Cart Page if Synchronization is Enabled in the Site for the Specified Subscription Products' , 'sumosubscriptions' ) ,
            '[subscription_number]'                 => __( 'To display Subscription Number' , 'sumosubscriptions' ) ,
            '[next_payment_date]'                   => __( 'To display Subscription Next Payment Date' , 'sumosubscriptions' ) ,
            '[sumo_cancel_scheduled_date]'          => __( 'To display Scheduled Cancel Date' , 'sumosubscriptions' ) ,
            '[renewal_fee_after_discount]'          => __( 'To display Subscription Renewal Fee After the Discount is Applied' , 'sumosubscriptions' ) ,
            '[discounted_renewal_fee_upto]'         => __( 'To display Number of Renewals Subscription Discounted Renewal Fee is to be applied' , 'sumosubscriptions' ) ,
                ) ;
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Shortcode' , 'sumosubscriptions' ) ; ?></th>
                    <th><?php _e( 'Purpose' , 'sumosubscriptions' ) ; ?></th>
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

return new SUMOSubscriptions_Message_Settings() ;
