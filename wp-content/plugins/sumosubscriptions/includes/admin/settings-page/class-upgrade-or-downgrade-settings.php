<?php

/**
 * Subscription Upgrade/Downgrade Tab.
 * 
 * @class SUMOSubscriptions_Upgrade_r_Downgrade_Tab
 * @category Class
 */
class SUMOSubscriptions_Upgrade_r_Downgrade_Tab extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_Upgrade_r_Downgrade_Tab constructor.
     */
    public function __construct() {

        $this->id            = 'upgrade_r_downgrade' ;
        $this->label         = __( 'Upgrade/Downgrade' , 'sumosubscriptions' ) ;
        $this->custom_fields = array(
            'get_tab_note' ,
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

        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings' , array(
            array(
                'type' => $this->get_custom_field_type( 'get_tab_note' )
            ) ,
            array(
                'name' => __( 'Upgrade/Downgrade Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_upgrade_r_downgrade_settings'
            ) ,
            array(
                'name'     => __( 'Allow Upgrade/Downgrade' , 'sumosubscriptions' ) ,
                'id'       => 'sumosubs_allow_upgrade_r_downgrade' ,
                'newids'   => 'sumosubs_allow_upgrade_r_downgrade' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc_tip' => true ,
                'desc'     => '' ,
            ) ,
            array(
                'name'    => __( 'Upgrade/Downgrade is based on' , 'sumosubscriptions' ) ,
                'id'      => 'sumosubs_upgrade_r_downgrade_based_on' ,
                'newids'  => 'sumosubs_upgrade_r_downgrade_based_on' ,
                'type'    => 'select' ,
                'options' => array(
                    'price'    => __( 'Subscription Price' , 'sumosubscriptions' ) ,
                    'duration' => __( 'Subscription Duration' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => 'price' ,
                'default' => 'price' ,
            ) ,
            array(
                'name'    => __( 'Allow User to' , 'sumosubscriptions' ) ,
                'id'      => 'sumosubs_allow_user_to' ,
                'newids'  => 'sumosubs_allow_user_to' ,
                'type'    => 'multiselect' ,
                'options' => array(
                    'up-grade'    => __( 'Upgrade' , 'sumosubscriptions' ) ,
                    'down-grade'  => __( 'Downgrade' , 'sumosubscriptions' ) ,
                    'cross-grade' => __( 'Crossgrade' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => array( 'up-grade' , 'down-grade' , 'cross-grade' ) ,
                'default' => array( 'up-grade' , 'down-grade' , 'cross-grade' ) ,
            ) ,
            array(
                'name'    => __( 'Allow Upgrade/Downgrade Between' , 'sumosubscriptions' ) ,
                'id'      => 'sumosubs_allow_upgrade_r_downgrade_between' ,
                'newids'  => 'sumosubs_allow_upgrade_r_downgrade_between' ,
                'type'    => 'multiselect' ,
                'options' => array(
                    'variations' => __( 'Subscription Variations' , 'sumosubscriptions' ) ,
                    'grouped'    => __( 'Grouped Subscriptions' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => array() ,
                'default' => array() ,
            ) ,
            array(
                'name'    => __( 'Payment for Upgrade/Downgrade' , 'sumosubscriptions' ) ,
                'id'      => 'sumosubs_payment_for_upgrade_r_downgrade' ,
                'newids'  => 'sumosubs_payment_for_upgrade_r_downgrade' ,
                'type'    => 'select' ,
                'options' => array(
                    'prorate'      => __( 'Prorate Payment' , 'sumosubscriptions' ) ,
                    'full_payment' => __( 'Full Subscription Fee' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => 'prorate' ,
                'default' => 'prorate' ,
            ) ,
            array(
                'name'     => __( 'Prorate Recurring Payment' , 'sumosubscriptions' ) ,
                'id'       => 'sumosubs_prorate_recurring_payment' ,
                'newids'   => 'sumosubs_prorate_recurring_payment' ,
                'type'     => 'multiselect' ,
                'std'      => array() ,
                'default'  => array() ,
                'options'  => array(
                    'virtual-upgrades'   => __( 'For Upgrades of Virtual Subscription Products' , 'sumosubscriptions' ) ,
                    'all-upgrades'       => __( 'For Upgrades of All Subscription Products' , 'sumosubscriptions' ) ,
                    'virtual-downgrades' => __( 'For Downgrades of Virtual Subscription Products' , 'sumosubscriptions' ) ,
                    'all-downgrades'     => __( 'For Downgrades of All Subscription Products' , 'sumosubscriptions' ) ,
                ) ,
                'desc_tip' => true ,
                'desc'     => '' ,
            ) ,
            array(
                'name'     => __( 'Charge Sign Up Fee' , 'sumosubscriptions' ) ,
                'id'       => 'sumosubs_charge_signup_fee' ,
                'newids'   => 'sumosubs_charge_signup_fee' ,
                'type'     => 'select' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'options'  => array(
                    'no'       => __( 'Do Not Charge' , 'sumosubscriptions' ) ,
                    'gap-fee'  => __( 'Charge Gap Signup Fee' , 'sumosubscriptions' ) ,
                    'full-fee' => __( 'Charge Full Signup Fee' , 'sumosubscriptions' ) ,
                ) ,
                'desc_tip' => true ,
                'desc'     => '' ,
            ) ,
            array(
                'name'     => __( 'Prorate Subscription Recurring Cycle' , 'sumosubscriptions' ) ,
                'id'       => 'sumosubs_prorate_subscription_recurring_cycle' ,
                'newids'   => 'sumosubs_prorate_subscription_recurring_cycle' ,
                'type'     => 'select' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'options'  => array(
                    'no'                    => __( 'Do Not Prorate' , 'sumosubscriptions' ) ,
                    'virtual-subscriptions' => __( 'For Virtual Subscriptions Products' , 'sumosubscriptions' ) ,
                    'all-subscriptions'     => __( 'For All Subscriptions Products' , 'sumosubscriptions' ) ,
                ) ,
                'desc_tip' => true ,
                'desc'     => '' ,
            ) ,
            array(
                'name'     => __( 'Upgrade/Downgrade Button Text' , 'sumosubscriptions' ) ,
                'id'       => 'sumosubs_upgrade_r_downgrade_button_text' ,
                'newids'   => 'sumosubs_upgrade_r_downgrade_button_text' ,
                'type'     => 'text' ,
                'std'      => __( 'Upgrade/Downgrade' , 'sumosubscriptions' ) ,
                'default'  => __( 'Upgrade/Downgrade' , 'sumosubscriptions' ) ,
                'desc_tip' => true ,
                'desc'     => '' ,
            ) ,
            array( 'type' => 'sectionend' , 'id' => 'sumo_upgrade_r_downgrade_settings' ) ,
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_tab_note() {
        ?>
        <tr>           
            <?php
            echo _e( '<b>Note:</b><br>'
                    . '1. Upgrade/Downgrade feature will not work for synchronization enabled subscription products.<br>'
                    . '2. If the subscription order is placed using PayPal Subscription API payment gateway, then upgrade/downgrade option will not be displayed for the user.<br>'
                    . '3. Subscribers can switch only when Subscription status is Active.' , 'sumosubscriptions' ) ;
            ?>
        </tr>
        <?php
    }

}

return new SUMOSubscriptions_Upgrade_r_Downgrade_Tab() ;
