<?php

/**
 * Synchronization Settings.
 * 
 * @class SUMOSubscriptions_Synchronization_Settings
 * @category Class
 */
class SUMOSubscriptions_Synchronization_Settings extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_Synchronization_Settings constructor.
     */
    public function __construct() {

        $this->id       = 'synchronization' ;
        $this->label    = __( 'Synchronization' , 'sumosubscriptions' ) ;
        $this->settings = $this->get_settings() ;
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
                'name' => __( 'Synchronization Settings' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_synchronization_settings'
            ) ,
            array (
                'name'     => __( 'Enable Synchronization for Subscription Products' , 'sumosubscriptions' ) ,
                'id'       => 'sumo_synchronize_check_option' ,
                'newids'   => 'sumo_synchronize_check_option' ,
                'type'     => 'checkbox' ,
                'std'      => 'no' ,
                'default'  => 'no' ,
                'desc_tip' => __( 'If enabled, Synchronization option will be visible in product edit page when SUMO Subscriptions is enabled for the product.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'     => __( 'Synchronization Behavior' , 'sumosubscriptions' ) ,
                'id'       => 'sumo_subscription_synchronize_mode' ,
                'newids'   => 'sumo_subscription_synchronize_mode' ,
                'type'     => 'select' ,
                'std'      => '1' ,
                'default'  => '1' ,
                'options'  => array (
                    '1' => __( 'Exact Date/Day' , 'sumosubscriptions' ) ,
                    '2' => __( 'First Occurrence' , 'sumosubscriptions' ) ,
                ) ,
                'desc_tip' => __( 'If you need to charge the renewal on a specific date of the month  then, select "Exact Date/Day" option. If you need to charge the renewal on a specific date without considering the month then, select “First Occurrence” option.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'     => __( 'Show Synchronized Next Payment Date in Single Product Page' , 'sumosubscriptions' ) ,
                'id'       => 'sumo_synchronized_next_payment_date_option' ,
                'newids'   => 'sumo_synchronized_next_payment_date_option' ,
                'type'     => 'checkbox' ,
                'std'      => 'yes' ,
                'default'  => 'yes' ,
                'desc_tip' => __( 'If enabled, the upcoming payment date for the subscription will be displayed in single product page.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name'    => __( 'Payment for Synchronized Period' , 'sumosubscriptions' ) ,
                'id'      => 'sumosubs_payment_for_synced_period' ,
                'newids'  => 'sumosubs_payment_for_synced_period' ,
                'type'    => 'select' ,
                'options' => array (
                    'free'         => __( 'Free' , 'sumosubscriptions' ) ,
                    'prorate'      => __( 'Prorate Payment' , 'sumosubscriptions' ) ,
                    'full_payment' => __( 'Full Subscription Fee' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => 'free' ,
                'default' => 'free' ,
            ) ,
            array (
                'name'    => __( 'Prorate Payment for' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_prorate_payment_for_selection' ,
                'newids'  => 'sumo_prorate_payment_for_selection' ,
                'type'    => 'select' ,
                'options' => array (
                    'all_subscriptions' => __( 'All Subscription Products' , 'sumosubscriptions' ) ,
                    'all_virtual'       => __( 'All Virtual Subscriptions' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => 'all_subscriptions' ,
                'default' => 'all_subscriptions' ,
            ) ,
            array (
                'name'    => __( 'Prorate Payment on' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_prorate_payment_on_selection' ,
                'newids'  => 'sumo_prorate_payment_on_selection' ,
                'type'    => 'radio' ,
                'options' => array (
                    'first_payment' => __( 'First Payment' , 'sumosubscriptions' ) ,
                    'first_renewal' => __( 'First Renewal' , 'sumosubscriptions' ) ,
                ) ,
                'std'     => 'first_payment' ,
                'default' => 'first_payment' ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_synchronization_settings' ) ,
                ) ) ;
    }

    /**
     * Save the custom options once.
     */
    public function custom_types_add_options() {

        //may be backwrd cmptblty
        if ( SUMO_Subscription_Synchronization::$can_prorate ) {
            add_option( 'sumosubs_payment_for_synced_period' , 'prorate' ) ;
        }
    }

}

return new SUMOSubscriptions_Synchronization_Settings() ;
