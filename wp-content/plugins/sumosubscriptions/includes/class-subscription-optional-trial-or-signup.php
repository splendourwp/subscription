<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription Optional Trial or Signup subscribed by User
 * 
 * @class SUMO_Subscription_Optional_Trial_Or_Signup
 * @category Class
 */
class SUMO_Subscription_Optional_Trial_Or_Signup {

    /**
     * @var object Get the subscription. 
     */
    protected static $subscription ;

    /**
     * @var string Get the subscription object type. 
     */
    public static $subscription_obj_type = 'product' ;

    /**
     * Init SUMO_Subscription_Optional_Trial_Or_Signup.
     */
    public static function init() {
        add_action( 'woocommerce_before_add_to_cart_button' , __CLASS__ . '::get_optional_fields_to_display' ) ;
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display' , __CLASS__ . '::get_optional_fields_to_display' , 10 , 2 ) ;
        add_filter( 'woocommerce_add_to_cart_validation' , __CLASS__ . '::validate' , 10 , 6 ) ;
        add_filter( 'woocommerce_add_cart_item' , __CLASS__ . '::subscribe_optional_trial_r_signup' , 10 , 2 ) ;
        add_action( 'woocommerce_before_calculate_totals' , __CLASS__ . '::refresh_cart' ) ;
        add_filter( 'sumosubscriptions_alter_subscription_plan_meta' , __CLASS__ . '::set_user_subscribed_optional_plan' , 10 , 4 ) ;
    }

    /**
     * Check whether the user can subscribe to optional plans
     * @param mixed $product
     * @return bool
     */
    public static function can_user_subscribe_optional_plans( $product ) {
        self::$subscription = sumo_get_subscription_product( $product ) ;

        if ( self::$subscription ) {
            return self::$subscription->get_signup( 'optional' ) || self::$subscription->get_trial( 'optional' ) ;
        }
        return false ;
    }

    public static function is_user_subscribed_optional_trial( $cart_item ) {
        if (
                ! empty( $cart_item[ 'sumosubscriptions' ][ 'trial' ] ) &&
                false === $cart_item[ 'sumosubscriptions' ][ 'trial' ][ 'optional' ] &&
                true === $cart_item[ 'sumosubscriptions' ][ 'trial' ][ 'forced' ] &&
                ! is_null( $cart_item[ 'sumosubscriptions' ][ 'trial' ][ 'type' ] )
        ) {
            return true ;
        }
        return false ;
    }

    public static function is_user_subscribed_optional_signup( $cart_item ) {
        if (
                ! empty( $cart_item[ 'sumosubscriptions' ][ 'signup' ] ) &&
                false === $cart_item[ 'sumosubscriptions' ][ 'signup' ][ 'optional' ] &&
                true === $cart_item[ 'sumosubscriptions' ][ 'signup' ][ 'forced' ] &&
                is_numeric( $cart_item[ 'sumosubscriptions' ][ 'signup' ][ 'fee' ] )
        ) {
            return true ;
        }
        return false ;
    }

    /**
     * Get Optional Signup or Trial Plan html fields which can selects by User
     * @return string
     */
    public static function get_optional_plan_fields() {
        $optional_plan_field = '' ;

        if ( self::$subscription->get_trial( 'optional' ) && sumo_can_purchase_subscription_trial( self::$subscription->product_id ) ) {
            if ( 'paid' === self::$subscription->get_trial( 'type' ) ) {
                $shortcode_content = str_replace( '[sumo_trial_fee]' , '<span style="color:#77a464;font-size:1.25em">' . sumo_format_subscription_price( self::$subscription->get_trial( 'fee' ) ) . '</span>' , get_option( 'sumo_product_optional_paid_trial_msg_customization' ) ) ;
            } else {
                $shortcode_content = get_option( 'sumo_product_optional_free_trial_msg_customization' ) ;
            }

            $optional_plan_field .= '<div>'
                    . '<input id="sumosubs_subscribe_optional_trial" '
                    . 'data-product_id="' . self::$subscription->product_id . '" '
                    . 'data-product_type="' . self::$subscription->product->get_type() . '" '
                    . 'data-plan="set_trial" '
                    . 'type="checkbox" '
                    . 'name="sumosubs_optional_trial_subscribed"'
                    . 'value="yes">'
                    . '&nbsp;'
                    . $shortcode_content
                    . '</div>' ;
        }
        if ( self::$subscription->get_signup( 'optional' ) ) {
            $optional_plan_field .= '<div>'
                    . '<input id="sumosubs_subscribe_optional_signup" '
                    . 'data-product_id="' . self::$subscription->product_id . '" '
                    . 'data-product_type="' . self::$subscription->product->get_type() . '" '
                    . 'data-plan="set_signup_fee" '
                    . 'type="checkbox" '
                    . 'name="sumosubs_optional_signup_subscribed"'
                    . 'value="yes">'
                    . '&nbsp;'
                    . str_replace( array ( '[sumo_signup_fee_only]' , '[sumo_signup_fee]' ) , '<span style="color:#77a464;font-size:1.25em">' . sumo_format_subscription_price( self::$subscription->get_signup( 'fee' ) ) . '</span>' , get_option( 'sumo_product_optional_signup_msg_customization' ) )
                    . '</div>' ;
        }
        return $optional_plan_field ;
    }

    /**
     * Give user an option to subscribe the Plan in Subscription product page
     * @global WC_Product $product
     * @param array $variation_data
     * @param mixed $variation
     * @return array
     */
    public static function get_optional_fields_to_display( $variation_data = array () , $variation = null ) {
        global $product ;

        $maybe_subscription = new SUMO_Subscription_Product( $variation ? $variation : $product  ) ;

        if ( SUMOSubscriptions_Frontend::is_subscription_product_type( $maybe_subscription->get_type() ) ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variation':
                    if ( ! empty( $variation_data[ 'plan_message' ] ) && self::can_user_subscribe_optional_plans( $maybe_subscription ) ) {
                        $variation_data[ 'optional_plan_fields_for_user' ] = self::get_optional_plan_fields() ;
                    }
                    break ;
                default:
                    if ( self::can_user_subscribe_optional_plans( $maybe_subscription ) ) {
                        echo self::get_optional_plan_fields() ;
                    }
                    break ;
            }
        }
        return $variation_data ;
    }

    public static function validate( $bool , $product_id , $quantity , $variation_id = 0 , $variations = array () , $cart_item_data = array () ) {
        if ( is_array( WC()->cart->cart_contents ) && ( ! empty( $_REQUEST[ 'sumosubs_optional_trial_subscribed' ] ) || ! empty( $_REQUEST[ 'sumosubs_optional_signup_subscribed' ] ) ) ) {
            self::$subscription = sumo_get_subscription_product( $variation_id ? $variation_id : $product_id  ) ;

            if ( self::$subscription ) {
                foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                    if ( ! isset( $cart_item[ 'product_id' ] ) ) {
                        continue ;
                    }

                    if ( self::$subscription->product_id == $cart_item[ 'product_id' ] ) {
                        WC()->cart->remove_cart_item( $cart_item_key ) ;
                    }
                }
            }
        }
        return $bool ;
    }

    public static function subscribe_optional_trial_r_signup( $cart_item , $cart_item_key ) {
        if ( self::$subscription && ( ! empty( $_REQUEST[ 'sumosubs_optional_trial_subscribed' ] ) || ! empty( $_REQUEST[ 'sumosubs_optional_signup_subscribed' ] ) ) ) {
            if ( self::$subscription->get_trial( 'optional' ) && ! empty( $_REQUEST[ 'sumosubs_optional_trial_subscribed' ] ) && 'yes' === $_REQUEST[ 'sumosubs_optional_trial_subscribed' ] ) {
                $trial                                       = self::$subscription->get_trial() ;
                $trial[ 'optional' ]                         = false ;
                $trial[ 'forced' ]                           = true ;
                $cart_item[ 'sumosubscriptions' ][ 'trial' ] = $trial ;
            }
            if ( self::$subscription->get_signup( 'optional' ) && ! empty( $_REQUEST[ 'sumosubs_optional_signup_subscribed' ] ) && 'yes' === $_REQUEST[ 'sumosubs_optional_signup_subscribed' ] ) {
                $signup                                       = self::$subscription->get_signup() ;
                $signup[ 'optional' ]                         = false ;
                $signup[ 'forced' ]                           = true ;
                $cart_item[ 'sumosubscriptions' ][ 'signup' ] = $signup ;
            }
        }
        return $cart_item ;
    }

    public static function refresh_cart( $cart ) {

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if (
                    ! empty( $cart_item[ 'sumosubscriptions' ][ 'trial' ] ) ||
                    ! empty( $cart_item[ 'sumosubscriptions' ][ 'signup' ] )
            ) {
                remove_filter( 'sumosubscriptions_alter_subscription_plan_meta' , __CLASS__ . '::set_user_subscribed_optional_plan' , 10 , 4 ) ;
                $subscription = sumo_get_subscription_product( $cart_item[ 'data' ] ) ;
                add_filter( 'sumosubscriptions_alter_subscription_plan_meta' , __CLASS__ . '::set_user_subscribed_optional_plan' , 10 , 4 ) ;

                if ( $subscription ) {
                    $trial  = $subscription->get_trial() ;
                    $signup = $subscription->get_signup() ;

                    if ( self::is_user_subscribed_optional_trial( $cart_item ) ) {
                        $trial[ 'optional' ] = false ;
                        $trial[ 'forced' ]   = true ;
                    }
                    if ( self::is_user_subscribed_optional_signup( $cart_item ) ) {
                        $signup[ 'optional' ] = false ;
                        $signup[ 'forced' ]   = true ;
                    }

                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'signup' ] = $signup ;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'trial' ]  = $trial ;
                }
            }
        }
    }

    /**
     * Alter Subscription plan meta by saving up user subscribed optional plans
     * @param array $subscription_plan_meta
     * @param int $subscription_id
     * @param int $subscription_product_id
     * @return array
     */
    public static function set_user_subscribed_optional_plan( $subscription_plan_meta , $subscription_id = 0 , $subscription_product_id = 0 , $user_id = 0 ) {
        if ( is_numeric( $subscription_id ) && $subscription_id ) {
            return $subscription_plan_meta ;
        }

        if ( ! is_shop() && ! is_product() && ! empty( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

                if ( $subscription_product_id == $product_id ) {
                    if ( self::is_user_subscribed_optional_trial( $cart_item ) ) {
                        $subscription_plan_meta[ 'trial_selection' ] = '1' ;
                    }
                    if ( self::is_user_subscribed_optional_signup( $cart_item ) ) {
                        $subscription_plan_meta[ 'signusumoee_selection' ] = '1' ;
                    }
                }
            }
        }
        return $subscription_plan_meta ;
    }

}

SUMO_Subscription_Optional_Trial_Or_Signup::init() ;
