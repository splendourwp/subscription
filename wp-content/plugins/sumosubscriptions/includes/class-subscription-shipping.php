<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription shipping
 * 
 * @class SUMO_Subscription_Shipping
 * @category Class
 */
class SUMO_Subscription_Shipping {

    protected static $shipping ;

    /**
     * Init SUMO_Subscription_Shipping.
     */
    public static function init() {
        add_filter( 'woocommerce_my_account_edit_address_title' , __CLASS__ . '::set_shipping_page_title' , 100 , 2 ) ;
        add_action( 'woocommerce_after_edit_address_form_shipping' , __CLASS__ . '::get_bulk_update_checkbox' ) ;
        add_action( 'woocommerce_customer_save_address' , __CLASS__ . '::save_shipping_address' , 100 , 2 ) ;
    }

    /**
     * Check whether it is Subscription shipping edit address page
     * @return boolean
     */
    public static function is_edit_shipping_address_page() {
        global $wp ;

        if(
                is_page( wc_get_page_id( 'myaccount' ) ) &&
                isset( $wp->query_vars[ 'edit-address' ] ) &&
                wc_edit_address_i18n( sanitize_title( $wp->query_vars[ 'edit-address' ] ) , true ) &&
                isset( $_GET[ 'subscription_id' ] ) &&
                wp_verify_nonce( $_GET[ '_sumosubsnonce' ] , $_GET[ 'subscription_id' ] )
        ) {
            return true ;
        }
        return false ;
    }

    /**
     * Check whether the subscriber updated his shipping address
     * @param int $subscriber_id
     * @return bool
     */
    public static function is_updated( $subscriber_id ) {
        if( ! wc_shipping_enabled() ) {
            return false ;
        }

        self::$shipping = get_user_meta( $subscriber_id , 'sumosubs_shipping_address' , true ) ;

        return isset( self::$shipping[ 'updated_via' ] ) && absint( self::$shipping[ 'updated_via' ] ) > 0 ;
    }

    /**
     * Check whether the subscriber requested to update shipping address to all subscriptions belongs to him
     * @param int $subscriber_id
     * @return bool
     */
    public static function update_to_all( $subscriber_id ) {
        if( ! self::is_updated( $subscriber_id ) ) {
            return false ;
        }
        return self::$shipping[ 'update_to_all' ] ;
    }

    /**
     * Get the subscription id through which the subscriber updated his shipping address
     * @param int $subscriber_id
     * @return int
     */
    public static function updated_via( $subscriber_id ) {
        if( ! self::is_updated( $subscriber_id ) ) {
            return false ;
        }
        return absint( self::$shipping[ 'updated_via' ] ) ;
    }

    /**
     * Get Shipping address updated by subscriber
     * @param int $subscriber_id
     * @return array
     */
    public static function get_address( $subscriber_id ) {
        $name = 'shipping' ;

        return array(
            'first_name' => get_user_meta( $subscriber_id , $name . '_first_name' , true ) ,
            'last_name'  => get_user_meta( $subscriber_id , $name . '_last_name' , true ) ,
            'company'    => get_user_meta( $subscriber_id , $name . '_company' , true ) ,
            'address_1'  => get_user_meta( $subscriber_id , $name . '_address_1' , true ) ,
            'address_2'  => get_user_meta( $subscriber_id , $name . '_address_2' , true ) ,
            'city'       => get_user_meta( $subscriber_id , $name . '_city' , true ) ,
            'state'      => get_user_meta( $subscriber_id , $name . '_state' , true ) ,
            'postcode'   => get_user_meta( $subscriber_id , $name . '_postcode' , true ) ,
            'country'    => get_user_meta( $subscriber_id , $name . '_country' , true )
                ) ;
    }

    /**
     * Get Subscription Shipping address Endpoint URl
     * @param int $subscription_id
     * @return string
     */
    public static function get_shipping_endpoint_url( $subscription_id ) {
        return esc_url_raw( add_query_arg( array( 'subscription_id' => absint( $subscription_id ) , 'subscriber_id' => get_current_user_id() , '_sumosubsnonce' => wp_create_nonce( "$subscription_id" ) ) , wc_get_endpoint_url( 'edit-address' , 'shipping' , wc_get_page_permalink( 'myaccount' ) ) ) ) ;
    }

    /**
     * Set Subscription shipping address page title
     * @param string $title
     * @param string $load_address
     * @return string
     */
    public static function set_shipping_page_title( $title , $load_address = 'billing' ) {

        if( self::is_edit_shipping_address_page() ) {
            return __( 'Change Subscription Shipping Address' , 'sumosubscriptions' ) ;
        }
        return $title ;
    }

    /**
     * Get bulk update checkbox to update the Shipping address to each Subscriptions he has purchased or new Subscriptions
     */
    public static function get_bulk_update_checkbox() {

        if( ! self::is_edit_shipping_address_page() ) {
            return ;
        }
        ?>
        <input type="checkbox" class="input-checkbox sumo_update_to_all" name="update_to_all" value="yes">
        <?php

        _e( 'Update this Address to all Subscriptions' , 'sumosubscriptions' ) ;
    }

    /**
     * Save Shipping address belongs to the Subscription
     * @param int $subscriber_id
     * @param string $load_address
     */
    public static function save_shipping_address( $subscriber_id , $load_address = 'billing' ) {

        if( ! self::is_edit_shipping_address_page() ) {
            return ;
        }

        $shipping = array(
            'updated_via'   => $_GET[ 'subscription_id' ] ,
            'update_to_all' => isset( $_POST[ 'update_to_all' ] ) && 'yes' === $_POST[ 'update_to_all' ] ,
                ) ;

        if( update_user_meta( $_GET[ 'subscriber_id' ] , 'sumosubs_shipping_address' , $shipping ) ) {
            $note = __( 'Shipping address updated.' , 'sumosubscriptions' ) ;

            if( $shipping[ 'update_to_all' ] ) {
                $note = __( 'Shipping address updated to all subscriptions subscribed by this user.' , 'sumosubscriptions' ) ;
            }
            sumo_add_subscription_note( $note , $_GET[ 'subscription_id' ] , 'success' , __( 'Shipping Address Changed' , 'sumosubscriptions' ) ) ;
        } else {
            sumo_add_subscription_note( __( 'Error updating shipping address.' , 'sumosubscriptions' ) , $_GET[ 'subscription_id' ] , 'failure' , __( 'Updating Shipping Address' , 'sumosubscriptions' ) ) ;
        }

        wp_safe_redirect( sumo_get_subscription_endpoint_url( $_GET[ 'subscription_id' ] ) ) ;
        exit() ;
    }

}

SUMO_Subscription_Shipping::init() ;
