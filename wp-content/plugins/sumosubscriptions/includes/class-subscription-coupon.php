<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle subscription's coupon
 * 
 * @class SUMO_Subscription_Coupon
 * @category Class
 */
class SUMO_Subscription_Coupon {

    protected static $apply_wc_coupon_in_renewal     = false ;
    protected static $apply_coupon_in_fixed_renewals = false ;
    protected static $coupon_applicable_for ;
    protected static $valid_users_to_apply           = array () ;
    protected static $valid_userroles_to_apply       = array () ;
    protected static $fixed_no_of_renewals           = 0 ;
    protected static $coupon_error ;

    const VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS                 = 101 ;
    const VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE = 102 ;
    const VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS         = 103 ;
    const VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS           = 104 ;
    const INVALID_COUPON                                       = 105 ;

    /**
     * Init SUMO_Subscription_Coupon.
     */
    public static function init() {
        self::$apply_wc_coupon_in_renewal     = 'yes' === get_option( 'sumo_coupon_in_renewal_order' , 'no' ) ;
        self::$coupon_applicable_for          = get_option( 'sumo_coupon_in_renewal_order_applicable_for' , 'all_users' ) ;
        self::$valid_users_to_apply           = ( array ) get_option( 'sumo_selected_users_for_renewal_order_discount' ) ;
        self::$valid_userroles_to_apply       = ( array ) get_option( 'sumo_selected_user_roles_for_renewal_order_discount' ) ;
        self::$apply_coupon_in_fixed_renewals = '2' === get_option( 'sumo_apply_coupon_discount' , '1' ) ;
        self::$fixed_no_of_renewals           = absint( get_option( 'no_of_sumo_selected_renewal_order_discount' ) ) ;

        add_filter( 'woocommerce_coupon_discount_types' , __CLASS__ . '::add_discount_types' ) ;
        add_filter( 'woocommerce_product_coupon_types' , __CLASS__ . '::add_product_coupon_types' ) ;
        add_filter( 'woocommerce_coupon_get_discount_amount' , __CLASS__ . '::get_discount_amount' , 10 , 5 ) ;
        add_filter( 'woocommerce_coupon_is_valid' , __CLASS__ . '::validate_subscription_coupon' , 10 , 3 ) ;
        add_filter( 'woocommerce_coupon_validate_minimum_amount' , __CLASS__ . '::validate_minimum_amount' , 10 , 3 ) ;
        add_filter( 'woocommerce_coupon_error' , __CLASS__ . '::add_coupon_error' ) ;
        add_filter( 'sumosubscriptions_alter_subscription_plan_meta' , __CLASS__ . '::set_recurring_discount_amount' , 10 , 4 ) ;
        add_filter( 'sumosubscriptions_get_message_to_display_in_cart_and_checkout' , __CLASS__ . '::get_discount_message_to_display_in_cart_and_checkout' , 10 , 4 ) ;
    }

    public static function get_subscription_coupon_types() {
        return array (
            'sumosubs_signupfee_discount'             => __( 'Signup fee discount' , 'sumosubscriptions' ) ,
            'sumosubs_signupfee_percent_discount'     => __( 'Signup fee % discount' , 'sumosubscriptions' ) ,
            'sumosubs_recurring_fee_discount'         => __( 'Recurring fee discount' , 'sumosubscriptions' ) ,
            'sumosubs_recurring_fee_percent_discount' => __( 'Recurring fee % discount' , 'sumosubscriptions' ) ,
                ) ;
    }

    public static function apply_wc_coupon_in_renewal( $subscription_id = null ) {
        if ( $subscription_id ) {
            return 'yes' === get_post_meta( $subscription_id , 'sumo_coupon_in_renewal_order' , true ) ;
        }
        return self::$apply_wc_coupon_in_renewal ;
    }

    public static function apply_coupon_in_fixed_renewals( $subscription_id = null ) {
        if ( $subscription_id ) {
            return '2' === get_post_meta( $subscription_id , 'sumo_apply_coupon_discount' , true ) ;
        }
        return self::$apply_coupon_in_fixed_renewals ;
    }

    public static function get_fixed_no_of_renewals( $subscription_id = null ) {
        if ( $subscription_id ) {
            return absint( get_post_meta( $subscription_id , 'no_of_sumo_selected_renewal_order_discount' , true ) ) ;
        }
        return self::$fixed_no_of_renewals ;
    }

    public static function get_renewal_orders_count( $subscription_id ) {
        $renewal_orders = get_post_meta( $subscription_id , 'sumo_get_every_renewal_ids' , true ) ;
        return is_array( $renewal_orders ) ? sizeof( $renewal_orders ) : 0 ;
    }

    public static function is_coupon_applicable_for_renewal_by_user( $subscription_id ) {
        if ( self::apply_coupon_in_fixed_renewals( $subscription_id ) && self::get_renewal_orders_count( $subscription_id ) >= self::get_fixed_no_of_renewals( $subscription_id ) ) {
            return false ;
        }

        return self::user_can_use_coupon( array (
                    'limit_by'             => get_post_meta( $subscription_id , 'sumo_coupon_in_renewal_order_applicable_for' , true ) ,
                    'filtered_user_emails' => ( array ) get_post_meta( $subscription_id , 'sumo_selected_user_emails_for_renewal_order_discount' , true ) ,
                    'filtered_user_roles'  => ( array ) get_post_meta( $subscription_id , 'sumo_selected_user_roles_for_renewal_order_discount' , true ) ,
                        ) , get_post_meta( $subscription_id , 'sumo_get_user_id' , true ) ) ;
    }

    public static function subscription_contains_recurring_coupon( $subscription ) {
        if ( is_object( $subscription ) ) {
            if ( is_a( $subscription , 'SUMO_Subscription_Product' ) ) {
                return $subscription->is_subscription() && $subscription->get_coupons() ;
            } else if ( is_a( $subscription , 'SUMO_Subscription' ) ) {
                return $subscription->get_coupons() && true ;
            }
        } else {
            return '1' === $subscription[ 'subscription_status' ] && ! empty( $subscription[ 'subscription_discount' ][ 'coupon_code' ] ) ;
        }
        return false ;
    }

    public static function cart_contains_subscription( $context = '' ) {
        if ( ! empty( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                if ( empty( $cart_item[ 'product_id' ] ) ) {
                    continue ;
                }
                $subscription_plan = sumo_get_subscription_plan( 0 , ($cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ) ) ;

                if ( '1' === $subscription_plan[ 'subscription_status' ] ) {
                    if ( is_numeric( $context ) ) {
                        if ( $context == $subscription_plan[ 'subscription_product_id' ] ) {
                            return $subscription_plan ;
                        }
                    } else if ( 'signup_fee' === $context ) {
                        if ( '1' === $subscription_plan[ 'signup_status' ] ) {
                            //Subscription Product contains Signup fee.
                            return true ;
                        }
                    } else {
                        return true ;
                    }
                }
            }
        }
        return false ;
    }

    public static function user_can_use_coupon( $args = array () , $user = null ) {
        include_once( ABSPATH . 'wp-includes/pluggable.php' ) ;

        $args = wp_parse_args( $args , array (
            'limit_by'             => self::$coupon_applicable_for ,
            'filtered_users'       => self::$valid_users_to_apply ,
            'filtered_user_roles'  => self::$valid_userroles_to_apply ,
            'filtered_user_emails' => array () ,
                ) ) ;

        $current_user_id = get_current_user_id() ;
        if ( is_numeric( $user ) && $user ) {
            $current_user_id = $user ;
        } else if ( isset( $user->ID ) ) {
            $current_user_id = $user->ID ;
        }
        $current_user = get_user_by( 'id' , $current_user_id ) ;

        switch ( $args[ 'limit_by' ] ) {
            case 'all_users':
                return true ;
            case 'include_users':
                if ( ! $current_user ) {
                    return false ;
                }

                if ( ! empty( $args[ 'filtered_users' ] ) ) {
                    $filtered_user_mails = array () ;
                    foreach ( $args[ 'filtered_users' ] as $user_id ) {
                        if ( ! $user = get_user_by( 'id' , $user_id ) ) {
                            continue ;
                        }

                        $filtered_user_mails[] = $user->data->user_email ;
                    }
                } else {
                    $filtered_user_mails = $args[ 'filtered_user_emails' ] ;
                }

                if ( in_array( $current_user->data->user_email , $filtered_user_mails ) ) {
                    return true ;
                }
                break ;
            case 'exclude_users':
                if ( ! $current_user ) {
                    return false ;
                }

                if ( ! empty( $args[ 'filtered_users' ] ) ) {
                    $filtered_user_mails = array () ;
                    foreach ( $args[ 'filtered_users' ] as $user_id ) {
                        if ( ! $user = get_user_by( 'id' , $user_id ) ) {
                            continue ;
                        }

                        $filtered_user_mails[] = $user->data->user_email ;
                    }
                } else {
                    $filtered_user_mails = $args[ 'filtered_user_emails' ] ;
                }

                if ( ! in_array( $current_user->data->user_email , $filtered_user_mails ) ) {
                    return true ;
                }
                break ;
            case 'include_user_role':
                if ( $current_user ) {
                    if ( isset( $current_user->roles[ 0 ] ) && in_array( $current_user->roles[ 0 ] , $args[ 'filtered_user_roles' ] ) ) {
                        return true ;
                    }
                } elseif ( in_array( 'guest' , $args[ 'filtered_user_roles' ] ) ) {
                    return true ;
                }
                break ;
            case 'exclude_user_role':
                if ( $current_user ) {
                    if ( isset( $current_user->roles[ 0 ] ) && ! in_array( $current_user->roles[ 0 ] , $args[ 'filtered_user_roles' ] ) ) {
                        return true ;
                    }
                } elseif ( ! in_array( 'guest' , $args[ 'filtered_user_roles' ] ) ) {
                    return true ;
                }
                break ;
        }
        return false ;
    }

    public static function add_discount_types( $discount_types ) {
        return is_array( $discount_types ) ? array_merge( $discount_types , self::get_subscription_coupon_types() ) : $discount_types ;
    }

    public static function add_product_coupon_types( $product_coupon_types ) {
        return is_array( $product_coupon_types ) ? array_merge( $product_coupon_types , array_keys( self::get_subscription_coupon_types() ) ) : $product_coupon_types ;
    }

    public static function get_total_renewals_to_apply_coupon( $installments ) {
        if ( self::user_can_use_coupon() ) {
            //Limit coupon to number of renewals
            if ( self::$apply_coupon_in_fixed_renewals ) {
                $installments = absint( $installments ) ;
                $installments = 0 === $installments ? 'indefinite' : $installments - 1 ;

                if ( self::$fixed_no_of_renewals ) {
                    if ( 'indefinite' === $installments || self::$fixed_no_of_renewals < $installments ) {
                        return self::$fixed_no_of_renewals ;
                    } else {
                        return 0 ; //Apply coupon for each renewal
                    }
                }
            } else {
                return 0 ; //Apply coupon for each renewal
            }
        }
        return false ;
    }

    public static function get_discount_amount( $discount , $discounting_amount , $item , $single , $coupon ) {

        if ( is_a( $item , 'WC_Order_Item' ) ) {
            $order_id = sumosubs_get_order_id( $item->get_order() ) ;

            if ( $coupon->is_type( array ( 'sumosubs_recurring_fee_discount' , 'sumosubs_recurring_fee_percent_discount' ) ) && sumo_is_order_contains_subscriptions( $order_id ) ) {
                if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                    $discount = min( $coupon->get_amount() , $discounting_amount ) ;
                    $discount = $single ? $discount : $discount * $item->get_quantity() ;
                } else {
                    $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 ) ;
                }
            }
        } else {
            if ( ! is_a( $coupon , 'WC_Coupon' ) || empty( $item[ 'product_id' ] ) || ! $coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
                return $discount ;
            }

            $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;

            if ( $subscription_plan = self::cart_contains_subscription( $product_id ) ) {
                if ( '1' === $subscription_plan[ 'trial_status' ] && '1' !== $subscription_plan[ 'signup_status' ] ) {
                    return $discount ;
                }

                if ( '1' === $subscription_plan[ 'signup_status' ] && $coupon->is_type( array ( 'sumosubs_signupfee_discount' , 'sumosubs_signupfee_percent_discount' ) ) ) {
                    $discounting_amount = floatval( $subscription_plan[ 'signup_fee' ] ) ;

                    if ( $coupon->is_type( 'sumosubs_signupfee_discount' ) ) {
                        $discount = min( $coupon->get_amount() , $discounting_amount ) ;
                        $discount = $single ? $discount : $discount * $item[ 'quantity' ] ;
                    } else {
                        $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 ) ;
                    }
                } else if ( $coupon->is_type( array ( 'sumosubs_recurring_fee_discount' , 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                    $discounting_amount -= floatval( $subscription_plan[ 'signup_fee' ] ) ;

                    if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                        $discount = min( $coupon->get_amount() , $discounting_amount ) ;
                        $discount = $single ? $discount : $discount * $item[ 'quantity' ] ;
                    } else {
                        $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 ) ;
                    }
                }
                $discount = round( min( $discount , $discounting_amount ) , wc_get_rounding_precision() ) ;
            }
        }
        return $discount ;
    }

    public static function get_coupon_error( $err_code ) {
        $err = '' ;

        switch ( $err_code ) {
            case self::INVALID_COUPON:
                $err = __( 'Sorry, this coupon is not valid.' , 'sumosubscriptions' ) ;
                break ;
            case self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS:
                $err = __( 'Sorry, this coupon is valid only for subscription products.' , 'sumosubscriptions' ) ;
                break ;
            case self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE:
                $err = __( 'Sorry, this coupon is valid only for subscription products with signup fee.' , 'sumosubscriptions' ) ;
                break ;
            case self::VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS:
                $err = __( 'Sorry, this coupon is valid only for subscription orders.' , 'sumosubscriptions' ) ;
                break ;
            case self::VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS:
                $err = __( 'Sorry, this coupon is valid only for initial subscription orders with signup fee.' , 'sumosubscriptions' ) ;
                break ;
        }
        return $err ;
    }

    public static function validate_subscription_coupon( $valid , $coupon , $discount = null ) {
        $validate   = $order_item = false ;

        if ( is_a( $discount , 'WC_Discounts' ) ) {
            $discount_items = $discount->get_items() ;

            if ( is_array( $discount_items ) && ! empty( $discount_items ) ) {
                $order_item = current( $discount_items ) ;
                $validate   = true ;
            }
        } else {
            $validate = true ;
        }

        if ( $validate && $coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
            self::$coupon_error = '' ;

            if ( isset( $order_item->object ) && is_a( $order_item->object , 'WC_Order_Item' ) ) {
                if ( ! sumo_is_order_contains_subscriptions( sumosubs_get_order_id( $order_item->object->get_order() ) ) ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS ) ;
                } else if ( $coupon->is_type( array ( 'sumosubs_signupfee_discount' , 'sumosubs_signupfee_percent_discount' ) ) ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS ) ;
                }
            } else {
                if ( ! self::user_can_use_coupon() ) {
                    self::$coupon_error = self::get_coupon_error( self::INVALID_COUPON ) ;
                } else if ( ! self::cart_contains_subscription() ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS ) ;
                } else if ( $coupon->is_type( array ( 'sumosubs_signupfee_discount' , 'sumosubs_signupfee_percent_discount' ) ) && ! self::cart_contains_subscription( 'signup_fee' ) ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE ) ;
                }
            }

            if ( ! empty( self::$coupon_error ) ) {
                $valid = false ;
            }
        }
        return $valid ;
    }

    public static function add_coupon_error( $err ) {
        if ( ! empty( self::$coupon_error ) ) {
            return self::$coupon_error ;
        }
        return $err ;
    }

    public static function validate_minimum_amount( $throw_err , $coupon , $subtotal ) {
        if ( $coupon->is_type( array ( 'sumosubs_recurring_fee_discount' , 'sumosubs_recurring_fee_percent_discount' ) ) ) {
            $throw_err = false ;
        }
        return $throw_err ;
    }

    public static function set_recurring_discount_amount( $subscription_meta , $subscription_id , $product_id , $user_id ) {
        if ( $subscription_id ) {
            if ( sumosubs_recurring_fee_has_changed( $subscription_id ) ) {
                $subscription_meta[ 'subscription_discount' ] = '' ;
            }
            return $subscription_meta ;
        }
        if ( ! empty( WC()->cart->cart_contents ) ) {
            $applied_coupons = WC()->cart->get_applied_coupons() ;

            if ( ! empty( $applied_coupons ) ) {
                foreach ( $applied_coupons as $coupon_code ) {
                    $coupon = new WC_Coupon( $coupon_code ) ;

                    if ( $coupon->is_type( array ( 'sumosubs_recurring_fee_discount' , 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                        $subscription_meta[ 'subscription_discount' ][ 'coupon_code' ][] = $coupon_code ;
                    }
                }
            }
        }
        return $subscription_meta ;
    }

    public static function get_recurring_discount_amount( $applied_discounts , $discounting_amount , $qty = 1 ) {
        $discounted_amount = 0 ;

        if ( $discounting_amount > 0 ) {
            foreach ( $applied_discounts as $coupon_code ) {
                $coupon = new WC_Coupon( $coupon_code ) ;

                if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                    $discounted_amount += ((max( $coupon->get_amount() , $discounting_amount ) - min( $coupon->get_amount() , $discounting_amount )) * absint( $qty )) ;
                } else if ( $coupon->is_type( 'sumosubs_recurring_fee_percent_discount' ) ) {
                    $coupon_amount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 ) ;
                    $discounted_amount += ((max( $coupon_amount , $discounting_amount ) - min( $coupon_amount , $discounting_amount )) * absint( $qty )) ;
                }
            }
        }
        return $discounted_amount ;
    }

    public static function get_recurring_discount_amount_to_display( $applied_discounts , $discounting_amount , $qty = 1 , $currency = false ) {
        $discounted_amount = self::get_recurring_discount_amount( $applied_discounts , $discounting_amount , $qty ) ;

        if ( $currency ) {
            $discounted_amount = sumo_format_subscription_price( $discounted_amount , array ( 'currency' => $currency ) ) ;
        } else {
            $discounted_amount = wc_price( $discounted_amount ) ;
        }
        return str_replace( '[renewal_fee_after_discount]' , $discounted_amount , get_option( 'sumo_renewal_fee_after_discount_msg_customization' ) ) ;
    }

    public static function get_discount_message_to_display_in_cart_and_checkout( $message , $subscription , $cart_item , $cart_item_key ) {
        $total_renewals_to_apply  = ($applied_coupons          = WC()->cart->get_applied_coupons()) ? self::get_total_renewals_to_apply_coupon( $subscription->get_installments() ) : false ;
        $discounted_recurring_fee = null ;

        if ( is_numeric( $total_renewals_to_apply ) ) {
            if ( self::$apply_wc_coupon_in_renewal && $subscription->get_recurring_amount() ) {
                $discount_amount = 0 ;

                foreach ( $applied_coupons as $coupon_code ) {
                    $coupon = new WC_Coupon( $coupon_code ) ;

                    if ( $coupon->is_type( array_keys( wc_get_coupon_types() ) ) && ! $coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
                        if ( $coupon->is_type( 'fixed_cart' ) && floatval( WC()->cart->subtotal_ex_tax ) <= 0 ) {
                            //Since subtotal is zero therefore we may avoid division by zero error in WC_Coupon::get_discount_amount() so that we will directly retrieve the fixed cart discount amount
                            $discount_amount += ( ( float ) $coupon->get_amount() / $cart_item[ 'quantity' ] ) ;
                        } else {
                            $discount_amount += $coupon->get_discount_amount( $subscription->get_recurring_amount() , $cart_item , true ) ;
                        }
                    }
                }
                $discounted_recurring_fee = (max( $discount_amount , $subscription->get_recurring_amount() ) - min( $discount_amount , $subscription->get_recurring_amount() )) * absint( $cart_item[ 'quantity' ] ) ;
            }
            if ( self::subscription_contains_recurring_coupon( $subscription ) ) {
                if ( is_numeric( $discounted_recurring_fee ) && $discounted_recurring_fee ) {
                    $discounted_recurring_fee /= $cart_item[ 'quantity' ] ;
                }
                $discounting_amount       = is_numeric( $discounted_recurring_fee ) ? $discounted_recurring_fee : $subscription->get_recurring_amount() ;
                $discounted_recurring_fee = self::get_recurring_discount_amount( $subscription->get_coupons() , $discounting_amount , $cart_item[ 'quantity' ] ) ;
            }

            if ( is_numeric( $discounted_recurring_fee ) ) {
                $message .= str_replace( '[renewal_fee_after_discount]' , wc_price( $discounted_recurring_fee ) , get_option( 'sumo_renewal_fee_after_discount_msg_customization' ) ) ;
                $message .= 0 === $total_renewals_to_apply ? '' : str_replace( '[discounted_renewal_fee_upto]' , $total_renewals_to_apply , get_option( 'sumo_discounted_renewal_fee_upto_msg_customization' ) ) ;
            }
        }
        return $message ;
    }

    public static function save_global( $subscription_id ) {
        $filtered_user_mails = array () ;
        foreach ( self::$valid_users_to_apply as $user_id ) {
            if ( ! $user = get_user_by( 'id' , $user_id ) ) {
                continue ;
            }

            $filtered_user_mails[] = $user->data->user_email ;
        }

        add_post_meta( $subscription_id , 'sumo_coupon_in_renewal_order' , self::$apply_wc_coupon_in_renewal ? 'yes' : 'no'  ) ;
        add_post_meta( $subscription_id , 'sumo_coupon_in_renewal_order_applicable_for' , self::$coupon_applicable_for ) ;
        add_post_meta( $subscription_id , 'sumo_selected_user_roles_for_renewal_order_discount' , self::$valid_userroles_to_apply ) ;
        add_post_meta( $subscription_id , 'sumo_selected_user_emails_for_renewal_order_discount' , $filtered_user_mails ) ;
        add_post_meta( $subscription_id , 'no_of_sumo_selected_renewal_order_discount' , self::$fixed_no_of_renewals ) ;
        add_post_meta( $subscription_id , 'sumo_apply_coupon_discount' , self::$apply_coupon_in_fixed_renewals ? '2' : '1'  ) ;
    }

}

SUMO_Subscription_Coupon::init() ;
