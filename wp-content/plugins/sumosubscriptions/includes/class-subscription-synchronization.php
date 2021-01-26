<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription's Renewal Payment Synchronization.
 * 
 * @class SUMO_Subscription_Synchronization
 * @category Class
 */
class SUMO_Subscription_Synchronization {

    /**
     * @var bool Check whether synchronization is enabled site wide. 
     */
    public static $sync_enabled_site_wide = false ;

    /**
     * @var string Get sync mode. 
     */
    public static $sync_mode = '' ;

    /**
     * @var string Get payment for synced periods. 
     */
    public static $payment_type = '' ;

    /**
     * @var bool Check whether synchronized product can be prorated. 
     */
    public static $can_prorate = false ;

    /**
     * @var string Check whether to apply prorated fee on either in the initial payment or in the initial renewal payment.
     */
    public static $apply_prorated_fee_on = '' ;

    /**
     * @var string Check where to apply prorate payment - all subscriptions/virtual subscriptions
     */
    public static $prorate_payment_for = '' ;

    /**
     * @var bool Check whether to show synced next payment date in single product page
     */
    public static $show_synced_date = false ;

    /**
     * @var object Get the subscription. 
     */
    public static $subscription = false ;

    /**
     * @var string Get the subscription object type. 
     */
    public static $subscription_obj_type = 'product' ;

    /**
     * @var bool Check whether the subscriber signingup on exact sync date. 
     */
    public static $signingup_on_sync_date = false ;

    /**
     * @var bool Check whether the initial payment date is extented by subscription length
     */
    protected static $sync_duration_extented = false ;

    /**
     * Init SUMO_Subscription_Synchronization.
     */
    public static function init() {
        self::$sync_enabled_site_wide = 'yes' === get_option( 'sumo_synchronize_check_option' , 'no' ) ;
        self::$payment_type           = get_option( 'sumosubs_payment_for_synced_period' , 'free' ) ;
        self::$apply_prorated_fee_on  = get_option( 'sumo_prorate_payment_on_selection' , 'first_payment' ) ;
        self::$prorate_payment_for    = get_option( 'sumo_prorate_payment_for_selection' , 'all_subscriptions' ) ;
        self::$sync_mode              = get_option( 'sumo_subscription_synchronize_mode' , '1' ) ;
        self::$show_synced_date       = 'yes' === get_option( 'sumo_synchronized_next_payment_date_option' , 'yes' ) ;

        if( 'prorate' === self::$payment_type || 'yes' === get_option( 'sumo_synchronize_prorate_check_option' ) ) {
            self::$can_prorate = true ;
        }

        add_action( 'woocommerce_after_add_to_cart_form' , __CLASS__ . '::get_next_synced_payment_date_to_display' ) ;
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display' , __CLASS__ . '::get_next_synced_payment_date_to_display' , 10 , 2 ) ;
        add_filter( 'woocommerce_add_cart_item' , __CLASS__ . '::add_to_cart_synced_item' , 10 , 2 ) ;
        add_filter( 'sumosubscriptions_get_line_total' , __CLASS__ . '::set_line_total' , 10 , 5 ) ;
        add_action( 'woocommerce_before_calculate_totals' , __CLASS__ . '::refresh_cart' , 999 ) ;
    }

    /**
     * Check whether the subscription is synchronized.
     * @param mixed $subscription
     * @return boolean
     */
    public static function is_subscription_synced( $subscription ) {
        if( is_a( $subscription , 'SUMO_Subscription' ) || (is_numeric( $subscription ) && 'sumosubscriptions' === get_post_type( $subscription )) ) {
            self::$subscription_obj_type = 'subscription' ;
        }

        if( 'product' === self::$subscription_obj_type ) {
            self::$subscription = sumo_get_subscription_product( $subscription ) ;
        } else {
            self::$subscription = sumo_get_subscription( $subscription ) ;
        }
        return self::$subscription && self::$subscription->is_synced() ;
    }

    public static function can_prorate_subscription() {
        if(
                self::$can_prorate &&
                ! self::$signingup_on_sync_date &&
                (
                'xtra-time-to-charge-full-fee' !== self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) ||
                ('xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && ! self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ))
                ) &&
                'product' === self::$subscription_obj_type && ! self::$subscription->get_trial( 'forced' )
        ) {
            if(
                    'all_subscriptions' === self::$prorate_payment_for ||
                    ('all_virtual' === self::$prorate_payment_for && ( self::$subscription->is_downloadable() || self::$subscription->is_virtual()))
            ) {
                if( self::is_sync_not_started() ) {
                    return false ;
                }
                return true ;
            }
        }
        return false ;
    }

    public static function is_subscriber_signingup_on_sync_date() {
        $this_week  = date( 'N' ) ;
        $this_day   = date( 'd' ) ;
        $this_month = date( 'm' ) ;

        switch( self::$subscription->get_duration_period() ) {
            case 'W':
                self::$signingup_on_sync_date = $this_week == self::$subscription->get_sync( 'duration_period' ) ? true : false ;
                break ;
            case 'M':
                if( '1' === self::$subscription->get_sync( 'type' ) ) {
                    self::$signingup_on_sync_date = $this_month == self::$subscription->get_sync( 'duration_period' ) && $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false ;
                } else {
                    self::$signingup_on_sync_date = $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false ;
                }
                break ;
            case 'Y':
                if( '1' === self::$subscription->get_sync( 'type' ) ) {
                    self::$signingup_on_sync_date = $this_month == self::$subscription->get_sync( 'duration_period' ) && $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false ;
                } else {
                    self::$signingup_on_sync_date = $this_day == self::$subscription->get_sync( 'duration_period_length' ) ? true : false ;
                }
                break ;
        }
        return self::$signingup_on_sync_date ;
    }

    public static function order_has_synced_subscriptions( $order_id ) {
        if( ! sumo_is_order_contains_subscriptions( $order_id ) ) {
            return false ;
        }

        $subscription_synced_items = array() ;
        $parent_order_id           = sumosubs_get_parent_order_id( $order_id ) ;

        //Get Subscription Order Items.
        $subscription_order_items        = sumo_get_subscription_items_from( $order_id ) ;
        $subscriptions_from_parent_order = get_post_meta( $parent_order_id , 'sumo_subsc_get_available_postids_from_parent_order' , true ) ;

        if( SUMO_Order_Subscription::is_subscribed( 0 , $parent_order_id , sumosubs_get_order_customer_id( $order_id ) ) ) {
            return false ;
        }

        if( is_array( $subscriptions_from_parent_order ) && $subscriptions_from_parent_order ) {
            foreach( $subscriptions_from_parent_order as $product_id => $subscription_id ) {
                //Is Synced Subscription exists in the Order.
                if( $product_id && $subscription_id && in_array( $product_id , $subscription_order_items ) && self::is_subscription_synced( $subscription_id ) ) {
                    $subscription_synced_items[] = $product_id ;
                }
            }
            //New Subscription.
        } else {
            foreach( $subscription_order_items as $item_id ) {
                //Is Synced Product.
                if( self::is_subscription_synced( $item_id ) ) {
                    $subscription_synced_items[] = $item_id ;
                }
            }
        }

        if( is_array( $subscription_synced_items ) && sizeof( $subscription_synced_items ) > 0 ) {
            return true ;
        }
        return false ;
    }

    public static function cart_contains_sync( $contains = '' ) {
        if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $cart_key => $cart_item ) {
                if( '' !== $contains ) {
                    if( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                        return true ;
                    }
                } else {
                    if( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                        return true ;
                    }
                }
            }
        }
        return false ;
    }

    public static function cart_item_contains_sync( $_cart_item , $contains = '' ) {
        if( ! empty( WC()->cart->cart_contents ) ) {
            if( is_numeric( $_cart_item ) ) {
                foreach( WC()->cart->cart_contents as $cart_key => $cart_item ) {
                    if( $_cart_item == $cart_item[ 'variation_id' ] || $_cart_item == $cart_item[ 'product_id' ] ) {
                        if( '' !== $contains ) {
                            if( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                                return true ;
                            }
                        } else {
                            if( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                                return true ;
                            }
                        }
                    }
                }
            } else {
                if( '' !== $contains ) {
                    if( ! empty( $_cart_item[ 'sumosubscriptions' ][ 'sync' ][ $contains ] ) ) {
                        return true ;
                    }
                } else if( ! empty( $_cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                    return true ;
                }
            }
        }
        return false ;
    }

    public static function get_synced( $product_id = null , $customer_id = 0 ) {
        $prorated_data = array() ;

        if( is_numeric( $customer_id ) && $customer_id ) {
            $prorated_data = get_user_meta( $customer_id , 'sumosubs_subscription_prorated_data' , true ) ;

            if( ! empty( $prorated_data[ $product_id ] ) ) {
                $prorated_data = $prorated_data[ $product_id ] ;
            }
        } else if( ! empty( WC()->cart->cart_contents ) ) {
            foreach( WC()->cart->cart_contents as $cart_item ) {
                if( ! empty( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                    $item_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

                    if( $product_id == $item_id ) {
                        $prorated_data = $cart_item[ 'sumosubscriptions' ][ 'sync' ] ;
                        break ;
                    } else {
                        $prorated_data[ $item_id ] = $cart_item[ 'sumosubscriptions' ][ 'sync' ] ;
                    }
                }
            }
        }
        return is_array( $prorated_data ) ? $prorated_data : array() ;
    }

    /**
     * Check whether to apply prorated fee on either in the initial payment or in the next renewal payment.
     * @param int $product_id The Product post ID
     * @param int $customer_id
     * @return string
     */
    public static function apply_prorated_fee_on( $product_id , $customer_id = 0 ) {
        $sync = self::get_synced( $product_id , $customer_id ) ;
        return ! empty( $sync[ 'apply_prorated_subscription_fee_on' ] ) ? $sync[ 'apply_prorated_subscription_fee_on' ] : null ;
    }

    public static function get_prorated_fee( $product_id , $customer_id = 0 , $format_decimal = false ) {
        $sync         = self::get_synced( $product_id , $customer_id ) ;
        $prorated_fee = isset( $sync[ 'prorated_subscription_fee' ] ) ? floatval( $sync[ 'prorated_subscription_fee' ] ) : null ;

        if( $format_decimal ) {
            return wc_format_decimal( $prorated_fee , wc_get_price_decimals() ) ;
        }
        return wc_format_decimal( $prorated_fee ) ;
    }

    /**
     * Get prorated date, how long the prorated fee becomes valid for the parent order ?
     * @param int $product_id
     * @return mixed
     */
    public static function get_prorated_date_till( $product_id , $customer_id = 0 ) {
        $sync               = self::get_synced( $product_id , $customer_id ) ;
        $prorated_date_till = '' ;

        if( ! empty( $sync[ 'initial_payment_time' ] ) ) {
            $prorated_date_till = $sync[ 'initial_payment_time' ] > 0 ? '<b>' . sumo_display_subscription_date( absint( $sync[ 'initial_payment_time' ] - 86400 ) ) . '</b>' : '' ;
        }
        return $prorated_date_till ;
    }

    public static function initial_payment_delayed( $subscription_id ) {
        $sync = get_post_meta( $subscription_id , 'sumo_subscription_synced_data' , true ) ;

        if( ( ! empty( $sync[ 'xtra_time_to_charge_full_fee' ] ) || ! empty( $sync[ 'extra_duration_period' ] )) && isset( $sync[ 'awaiting_initial_payment' ] ) && $sync[ 'awaiting_initial_payment' ] ) {
            return true ;
        }
        return false ;
    }

    public static function maybe_get_awaiting_initial_payment_charge_time( $subscription_id ) {
        if( self::initial_payment_delayed( $subscription_id ) && 'Pending' === get_post_meta( $subscription_id , 'sumo_get_status' , true ) ) {
            $sync = get_post_meta( $subscription_id , 'sumo_subscription_synced_data' , true ) ;
            return $sync[ 'initial_payment_time' ] ;
        }
        return false ;
    }

    public static function current_time_exceeds_xtra_time( $xtra_time_in_days , $sync_time ) {
        if( is_numeric( $xtra_time_in_days ) && $xtra_time_in_days ) {
            $previous_sync_time = self::get_previous_sync_time( $sync_time ) ;

            if( $previous_sync_time ) {
                $this_time          = sumo_get_subscription_timestamp( 0 , 0 , true ) ;
                $xtra_duration_time = sumo_get_subscription_timestamp( "+{$xtra_time_in_days} days" , $previous_sync_time , true ) ;

                if( $this_time > $xtra_duration_time ) {
                    return true ;
                }
            }
        }
        return false ;
    }

    public static function awaiting_initial_payment( $sync ) {
        return self::current_time_exceeds_xtra_time( $sync[ 'xtra_time_to_charge_full_fee' ] , $sync[ 'initial_payment_time' ] ) ;
    }

    public static function get_duration_options( $subscription_period , $show_sync_period = false ) {
        $total_no_of_days_in_the_month = 28 ; //Set Default for each Month
        $options                       = array() ;

        switch( $subscription_period ) {
            case 'W':
                $options = array(
                    '0' => __( 'Do not synchronize' , 'sumosubscriptions' ) ,
                    '1' => __( 'Monday each week' , 'sumosubscriptions' ) ,
                    '2' => __( 'Tuesday each week' , 'sumosubscriptions' ) ,
                    '3' => __( 'Wednesday each week' , 'sumosubscriptions' ) ,
                    '4' => __( 'Thursday each week' , 'sumosubscriptions' ) ,
                    '5' => __( 'Friday each week' , 'sumosubscriptions' ) ,
                    '6' => __( 'Saturday each week' , 'sumosubscriptions' ) ,
                    '7' => __( 'Sunday each week' , 'sumosubscriptions' ) ,
                        ) ;
                break ;
            case 'M':
                for( $i = 0 ; $i <= $total_no_of_days_in_the_month ; $i ++ ) {
                    if( $i === 0 ) {
                        $options[] = __( 'Do not synchronize' , 'sumosubscriptions' ) ;
                    } else {
                        $options[] = sprintf( __( '%s day of the month' , 'sumosubscriptions' ) , sumo_get_number_suffix( $i ) ) ;
                    }
                }
                if( $show_sync_period ) {
                    $options = sumo_get_available( 'months' ) ;
                }
                break ;
            case 'Y':
                for( $i = 0 ; $i <= $total_no_of_days_in_the_month ; $i ++ ) {
                    if( $i === 0 ) {
                        $options[] = __( 'Do not synchronize' , 'sumosubscriptions' ) ;
                    } else {
                        if( '2' === self::$sync_mode ) {
                            $options[] = sprintf( __( '%s day of the month' , 'sumosubscriptions' ) , sumo_get_number_suffix( $i ) ) ;
                        } else {
                            $options[] = $i ;
                        }
                    }
                }
                if( $show_sync_period ) {
                    $options = sumo_get_available( 'months' ) ;
                }
                break ;
        }
        return $options ;
    }

    public static function get_xtra_duration_options( $subscription_period , $subscription_duration_length ) {
        $subscription_cycle_in_days = 0 ;

        switch( $subscription_period ) {
            case 'Y':
                $subscription_cycle_in_days = '2' === self::$sync_mode ? 28 : 365 * $subscription_duration_length ;
                break ;
            case 'M':
                $subscription_cycle_in_days = '2' === self::$sync_mode ? 28 : 28 * $subscription_duration_length ;
                break ;
            case 'W':
                $subscription_cycle_in_days = '2' === self::$sync_mode ? 7 : 7 * $subscription_duration_length ;
                break ;
        }
        return $subscription_cycle_in_days ;
    }

    public static function get_initial_payment_date( $product_id , $display = false ) {
        $initial_payment_time = sumosubs_get_next_payment_date( 0 , $product_id , array(
            'initial_payment'  => true ,
            'get_as_timestamp' => true ,
                ) ) ;
        return $display ? sumo_display_subscription_date( $initial_payment_time ) : $initial_payment_time ;
    }

    public static function is_sync_not_started() {
        if( '1' === self::$subscription->get_sync( 'type' ) && self::get_sync_start_time() > 0 && sumo_get_subscription_timestamp( 0 , 0 , true ) < self::get_sync_start_time() ) {
            if( in_array( self::$subscription->get_duration_period() , array( 'M' , 'Y' ) ) ) {
                return true ;
            }
        }
        return false ;
    }

    public static function get_sync_start_time() {
        return sumo_get_subscription_timestamp( self::$subscription->get_sync( 'start_year' ) . '/' . self::$subscription->get_sync( 'duration_period' ) . '/' . self::$subscription->get_sync( 'duration_period_length' ) , 0 , true ) ;
    }

    public static function get_sync_time( $is_trial = false , $is_initial_payment = false , $from_when = false ) {
        $next_sync_time = self::get_next_sync_time( $is_initial_payment , $from_when ) ;

        //If it is Trial Product.
        if( $is_trial && $is_initial_payment ) {
            $trial_end_time = sumo_get_subscription_timestamp( '+' . sumo_format_subscription_cyle( self::$subscription->get_trial( 'duration_period_length' ) . ' ' . self::$subscription->get_trial( 'duration_period' ) ) ) ;

            if( $trial_end_time > $next_sync_time ) {
                $next_sync_time = self::get_next_sync_time( $is_initial_payment , $trial_end_time ) ;
            }
        }
        return $next_sync_time ;
    }

    public static function prorate_subscription_fee() {
        if( ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0 ;
        }

        $from_time                    = sumo_get_subscription_timestamp( 0 , 0 , true ) ;
        $sync_time                    = self::get_sync_time( false , true ) ;
        $subscription_duration_length = self::$sync_duration_extented ? 2 * self::$subscription->get_duration_period_length() : self::$subscription->get_duration_period_length() ;

        $total_days = 0 ;
        switch( self::$subscription->get_duration_period() ) {
            case 'W':
                if( '1' === self::$subscription->get_sync( 'type' ) ) {
                    $total_days = $subscription_duration_length * 7 ;
                } else {
                    $total_days = 7 ;
                }
                break ;
            case 'M':
            case 'Y':
                if( '1' === self::$subscription->get_sync( 'type' ) ) {
                    $period  = 'M' === self::$subscription->get_duration_period() ? 'month' : 'year' ;
                    $to_time = sumo_get_subscription_timestamp( "+{$subscription_duration_length} {$period}" , 0 , true ) ;
                } else {
                    $to_time = sumo_get_subscription_timestamp( '+1 month' , 0 , true ) ;
                }
                $total_days = $to_time ? (max( $to_time , $from_time ) - min( $to_time , $from_time )) / 86400 : 0 ;
                break ;
        }

        if( $total_days <= 0 ) {
            return 0 ;
        }

        $prorated_days   = $sync_time ? (max( $sync_time , $from_time ) - min( $sync_time , $from_time )) / 86400 : 0 ;
        $prorated_amount = $prorated_days && $total_days ? (self::$subscription->get_recurring_amount() / $total_days) * $prorated_days : self::$subscription->get_recurring_amount() ;
        return wc_format_decimal( $prorated_amount ) ;
    }

    public static function update_user_meta( $customer_id ) {
        delete_user_meta( $customer_id , 'sumosubs_subscription_prorated_data' ) ;

        if( $prorated_data = self::get_synced() ) {
            add_user_meta( $customer_id , 'sumosubs_subscription_prorated_data' , $prorated_data ) ;
        }
    }

    public static function calculate_sync_time_for( $duration_period , $from_time , $is_initial_payment = false ) {
        if( ! is_numeric( self::$subscription->get_sync( 'duration_period_length' ) ) ) {
            return 0 ;
        }

        $next_sync_time         = 0 ;
        $from_day               = date( 'd' , $from_time ) ; // returns the Day
        $from_month             = date( 'm' , $from_time ) ; // returns the Month Number as 01 for jan, 02 for feb,...12 for dec
        $next_day               = date( 'd' , sumo_get_subscription_timestamp( 'next day' , $from_time ) ) ; // returns the Next Day
        $duration_period_length = self::$subscription->get_duration_period_length() ;

        if( $is_initial_payment && '2' === self::$subscription->get_sync( 'type' ) ) {
            if(
                    self::$subscription->get_sync( 'duration_period_length' ) < $from_day ||
                    self::$subscription->get_sync( 'duration_period_length' ) == $from_day
            ) {
                //The Renewal time crossed this time while the Subscription is Renewing/Subscribing                    
                if( 12 == $from_month ) {
                    $next_renewal_month = 1 ;
                    $next_renewal_year  = date( 'Y' , $from_time ) + 1 ;
                } else {
                    $next_renewal_month = $from_month + 1 ;
                    $next_renewal_year  = date( 'Y' , $from_time ) ;
                }
                $next_sync_time = sumo_get_subscription_timestamp(
                        $next_renewal_year . '/' .
                        $next_renewal_month . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                        ) ;
            } else {
                //The Renewal time not yet crossed this time while the Subscription is Renewing/Subscribing
                $next_sync_time = sumo_get_subscription_timestamp(
                        date( 'Y' , $from_time ) . '/' .
                        date( 'm' , $from_time ) . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                        ) ;
            }
        } else {
            if( $is_initial_payment ) {
                $next_month = 1 ;
                $_from_when = 0 ;

                for( $i = 1 ; $i <= $next_month ; $i ++ ) {
                    if( $i === 1 ) {
                        $_next_sync_time = self::get_sync_start_time() ;
                    } else {
                        $_next_sync_time = sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}" , $_from_when ? $_from_when : $from_time , true ) ;
                    }

                    if( $from_time < $_next_sync_time ) {
                        $next_sync_time = sumo_get_subscription_timestamp( $_next_sync_time , 0 , true ) ;
                    } else {
                        $next_month += 1 ;
                    }
                    $_from_when = $_next_sync_time ;
                }
            } else {
                $next_sync_time = sumo_get_subscription_timestamp(
                        date( 'Y' , sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}" , $from_time ) ) . '/' .
                        date( 'm' , sumo_get_subscription_timestamp( "+{$duration_period_length} {$duration_period}" , $from_time ) ) . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                        ) ;
            }
        }
        return $next_sync_time ;
    }

    public static function calculate_previous_sync_time_for( $duration_period , $from_time ) {
        if( ! is_numeric( self::$subscription->get_sync( 'duration_period_length' ) ) ) {
            return 0 ;
        }

        $previous_sync_time     = 0 ;
        $from_day               = date( 'd' , $from_time ) ; // returns the Day
        $from_month             = date( 'm' , $from_time ) ; // returns the Month Number as 01 for jan, 02 for feb,...12 for dec
        $duration_period_length = self::$subscription->get_duration_period_length() ;

        if( '2' === self::$subscription->get_sync( 'type' ) ) {
            if(
                    self::$subscription->get_sync( 'duration_period_length' ) < $from_day ||
                    self::$subscription->get_sync( 'duration_period_length' ) == $from_day
            ) {
                if( 1 == $from_month ) {
                    $next_renewal_month = 12 ;
                    $next_renewal_year  = date( 'Y' , $from_time ) - 1 ;
                } else {
                    $next_renewal_month = $from_month - 1 ;
                    $next_renewal_year  = date( 'Y' , $from_time ) ;
                }
                $previous_sync_time = sumo_get_subscription_timestamp(
                        $next_renewal_year . '/' .
                        $next_renewal_month . '/' .
                        self::$subscription->get_sync( 'duration_period_length' )
                        ) ;
            }
        } else {
            $previous_sync_time = sumo_get_subscription_timestamp(
                    date( 'Y' , sumo_get_subscription_timestamp( "-{$duration_period_length} {$duration_period}" , $from_time ) ) . '/' .
                    date( 'm' , sumo_get_subscription_timestamp( "-{$duration_period_length} {$duration_period}" , $from_time ) ) . '/' .
                    self::$subscription->get_sync( 'duration_period_length' )
                    ) ;
        }
        return $previous_sync_time ;
    }

    public static function get_next_sync_time( $is_initial_payment = false , $from_when = false ) {
        if( ! is_numeric( self::$subscription->get_sync( 'duration_period' ) ) && ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0 ;
        }

        $next_sync_time = 0 ;
        $next_due_date  = get_post_meta( self::$subscription->get_id() , 'sumo_get_next_payment_date' , true ) ;

        if( $next_due_date ) {
            $from_time = sumo_get_subscription_timestamp( $next_due_date ) ; // returns Next Due Date set previously
        } else {
            $from_time = sumo_get_subscription_timestamp( 0 , 0 , true ) ; // returns the Current Date
        }
        if( $from_when && is_numeric( $from_when ) ) {
            $from_time = $from_when ; // get Custom - From time
        }

        switch( self::$subscription->get_duration_period() ) {
            case 'W':
                //Eg: Renewal on every Week of Monday
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...07 number for a Week
                $weeks                  = sumo_get_available( 'weeks' ) ;
                $next_renewal_week      = $weeks[ self::$subscription->get_sync( 'duration_period' ) ] ;
                $duration_period_length = self::$subscription->get_duration_period_length() ;

                if( self::$subscription->get_sync( 'duration_period' ) == date( 'N' , $from_time ) ) {
                    $next_sync_time = $from_time + ($duration_period_length * 7 * 86400) ;
                } else {
                    if( $is_initial_payment && '2' === self::$subscription->get_sync( 'type' ) ) {
                        $next_sync_time = sumo_get_subscription_timestamp( "{$next_renewal_week}" , $from_time ) ;
                    } else {
                        $next_sync_time = sumo_get_subscription_timestamp( "{$duration_period_length} {$next_renewal_week}" , $from_time ) ;
                    }
                }
                break ;
            case 'M':
                //Eg: Renewal on every Month 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates 01,02...30/31 days for a Month
                $next_sync_time = self::calculate_sync_time_for( 'month' , $from_time , $is_initial_payment ) ;
                break ;
            case 'Y':
                //Eg: Renewal on every Year January 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates Number of Years to Renew
                $next_sync_time = self::calculate_sync_time_for( 'year' , $from_time , $is_initial_payment ) ;
                break ;
        }

        self::$sync_duration_extented = false ;
        if( $next_sync_time && 'cutoff-time-to-not-renew-nxt-subs-cycle' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'cutoff_time_to_not_renew_nxt_subs_cycle' ) ) {
            if( self::current_time_exceeds_xtra_time( self::$subscription->get_sync( 'cutoff_time_to_not_renew_nxt_subs_cycle' ) , $next_sync_time ) ) {
                $next_sync_time               = self::get_next_sync_time( $is_initial_payment , $next_sync_time ) ;
                self::$sync_duration_extented = true ;
            }
        }
        return $next_sync_time ;
    }

    public static function get_previous_sync_time( $from_when ) {
        if( ! is_numeric( self::$subscription->get_sync( 'duration_period' ) ) && ! is_numeric( self::$subscription->get_duration_period_length() ) ) {
            return 0 ;
        }

        $previous_sync_time = 0 ;
        if( $from_when && is_numeric( $from_when ) ) {
            $from_time = $from_when ; // get Custom - From time
        } else {
            $from_time = sumo_get_subscription_timestamp( 0 , 0 , true ) ; // returns the Current Date
        }

        switch( self::$subscription->get_duration_period() ) {
            case 'W':
                //Eg: Renewal on every Week of Monday
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...07 number for a Week
                $weeks                  = sumo_get_available( 'weeks' ) ;
                $next_renewal_week      = $weeks[ self::$subscription->get_sync( 'duration_period' ) ] ;
                $duration_period_length = self::$subscription->get_duration_period_length() ;

                if( self::$subscription->get_sync( 'duration_period' ) == date( 'N' , $from_time ) ) {
                    $previous_sync_time = $from_time - ($duration_period_length * 7 * 86400) ;
                } else {
                    if( '2' === self::$subscription->get_sync( 'type' ) ) {
                        $previous_sync_time = sumo_get_subscription_timestamp( "-1 {$next_renewal_week}" , $from_time ) ;
                    } else {
                        $previous_sync_time = sumo_get_subscription_timestamp( "-{$duration_period_length} {$next_renewal_week}" , $from_time ) ;
                    }
                }
                break ;
            case 'M':
                //Eg: Renewal on every Month 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates 01,02...30/31 days for a Month
                $previous_sync_time = self::calculate_previous_sync_time_for( 'month' , $from_time ) ;
                break ;
            case 'Y':
                //Eg: Renewal on every Year January 01
                //Here self::$subscription->get_sync( 'duration_period' ) indicates 01,02...12 Months for a Year and self::$subscription->get_sync( 'duration_period_length' ) indicates Number of Years to Renew
                $previous_sync_time = self::calculate_previous_sync_time_for( 'year' , $from_time ) ;
                break ;
        }
        return $previous_sync_time ;
    }

    public static function get_line_total( $sync ) {
        $line_total = 0 ;

        switch( $sync[ 'initial_payment_charge_type' ] ) {
            case 'full_payment':
                $line_total = floatval( self::$subscription->get_recurring_amount() ) ;
                break ;
            case 'prorate':
                if( is_numeric( $sync[ 'prorated_subscription_fee' ] ) ) {
                    //When prorated fee is applied on First Payment itself then consider subscription fee as prorated amount or else prorated amount will be calculated in the 1st renewal order so that consider subscription fee as 0
                    if( 'first_payment' === $sync[ 'apply_prorated_subscription_fee_on' ] ) {
                        $line_total = $sync[ 'prorated_subscription_fee' ] ;
                    }
                } else {
                    if( $sync[ 'signingup_on_sync_date' ] ) {
                        $line_total = floatval( self::$subscription->get_recurring_amount() ) ;
                    }
                }
                break ;
        }

        if( $sync[ 'xtra_time_to_charge_full_fee' ] ) {
            if( $sync[ 'awaiting_initial_payment' ] ) {
                $line_total = 0 ;
            } else {
                $line_total = floatval( self::$subscription->get_recurring_amount() ) ;
            }
        }

        if( self::is_sync_not_started() ) {
            $line_total = 0 ;
        }

        if( 'product' === self::$subscription_obj_type && self::$subscription->get_signup( 'forced' ) ) {
            $line_total += floatval( self::$subscription->get_signup( 'fee' ) ) ;
        }
        return $line_total ;
    }

    public static function get_next_synced_payment_date_to_display( $variation_data = array() , $variation = null ) {
        global $product ;

        //Check whether to display in Single Product Page or Not.
        if( ! $product || ! self::$show_synced_date ) {
            return $variation_data ;
        }

        $maybe_subscription = new SUMO_Subscription_Product( $variation ? $variation : $product  ) ;

        if( SUMOSubscriptions_Frontend::is_subscription_product_type( $maybe_subscription->get_type() ) ) {
            switch( $maybe_subscription->get_type() ) {
                case 'variation':
                    if( ! empty( $variation_data[ 'plan_message' ] ) && self::is_subscription_synced( $maybe_subscription ) ) {
                        $variation_data[ 'synced_next_payment_date' ] = sprintf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>' , __( 'Next Payment on: ' , 'sumosubscriptions' ) , self::get_initial_payment_date( $maybe_subscription->get_id() , true ) ) ;
                    }
                    break ;
                case 'grouped':
                    foreach( $maybe_subscription->product->get_children() as $child_id ) {
                        if( self::is_subscription_synced( $child_id ) ) {
                            printf( '<p id="sumosubs_initial_synced_payment_date">%s -> %s<strong>%s</strong></p>' , get_post( $child_id )->post_title , __( 'Next Payment on: ' , 'sumosubscriptions' ) , self::get_initial_payment_date( $child_id , true ) ) ;
                        }
                    }
                    break ;
                default:
                    if( self::is_subscription_synced( $maybe_subscription ) ) {
                        printf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>' , __( 'Next Payment on: ' , 'sumosubscriptions' ) , self::get_initial_payment_date( $maybe_subscription->get_id() , true ) ) ;
                    }
                    break ;
            }
        }
        return $variation_data ;
    }

    public static function add_to_cart_synced_item( $cart_item , $cart_item_key ) {
        if( self::is_subscription_synced( $cart_item[ 'data' ] ) ) {
            $cart_item[ 'sumosubscriptions' ][ 'sync' ] = array(
                'enabled'                            => true ,
                'initial_payment_charge_type'        => self::$can_prorate ? 'prorate' : self::$payment_type ,
                'prorated_subscription_fee'          => null ,
                'apply_prorated_subscription_fee_on' => null ,
                'signingup_on_sync_date'             => self::is_subscriber_signingup_on_sync_date() ,
                'initial_payment_time'               => self::get_initial_payment_date( self::$subscription->get_id() ) ,
                'xtra_time_to_charge_full_fee'       => null ,
                'awaiting_initial_payment'           => null ,
                    ) ;

            if( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ) {
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ] = self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ;
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]     = self::awaiting_initial_payment( $cart_item[ 'sumosubscriptions' ][ 'sync' ] ) ;
            }

            if( self::can_prorate_subscription() ) {
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = self::prorate_subscription_fee() ;
                $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = self::$apply_prorated_fee_on ;
            }

            $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ] = self::get_line_total( $cart_item[ 'sumosubscriptions' ][ 'sync' ] ) ;
        }
        return $cart_item ;
    }

    public static function set_line_total( $line_total , $subscription , $default_line_total , $is_trial_enabled , $subscription_obj_type ) {
        if( ! $is_trial_enabled ) {
            if( 'product' === $subscription_obj_type && self::cart_item_contains_sync( $subscription->get_id() ) ) {
                $sync = self::get_synced( $subscription->get_id() ) ;
            } else if( 'subscription' === $subscription_obj_type && self::cart_item_contains_sync( $subscription->get_subscribed_product() ) ) {
                $sync = self::get_synced( $subscription->get_subscribed_product() ) ;
            }

            if( isset( $sync[ 'signup_amount' ] ) && is_numeric( $sync[ 'signup_amount' ] ) ) {
                return wc_format_decimal( $sync[ 'signup_amount' ] , wc_get_price_decimals() ) ;
            }
        }
        return $line_total ;
    }

    public static function refresh_cart() {
        foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if( isset( $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ] ) ) {
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ]                            = false ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_charge_type' ]        = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signingup_on_sync_date' ]             = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ]                      = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ]               = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ]       = null ;
                WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]           = null ;

                if( self::is_subscription_synced( $cart_item[ 'data' ] ) ) {
                    if( isset( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'extra_duration_period' ] ) ) {//BKWD CMPT
                        WC()->cart->remove_cart_item( $cart_item_key ) ;
                        continue ;
                    }

                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'enabled' ]                     = true ;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_charge_type' ] = self::$can_prorate ? 'prorate' : self::$payment_type ;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signingup_on_sync_date' ]      = self::is_subscriber_signingup_on_sync_date() ;
                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ]        = self::get_initial_payment_date( self::$subscription->get_id() ) ;

                    if( 'xtra-time-to-charge-full-fee' === self::$subscription->get_sync( 'subscribed_after_sync_date_type' ) && self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ) {
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'xtra_time_to_charge_full_fee' ] = self::$subscription->get_sync( 'xtra_time_to_charge_full_fee' ) ;
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'awaiting_initial_payment' ]     = self::awaiting_initial_payment( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ] ) ;
                    }

                    if( self::can_prorate_subscription() ) {
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'prorated_subscription_fee' ]          = self::prorate_subscription_fee() ;
                        WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'apply_prorated_subscription_fee_on' ] = self::$apply_prorated_fee_on ;
                    }

                    WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ][ 'signup_amount' ] = self::get_line_total( WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'sync' ] ) ;
                }
            }
        }
    }

}

SUMO_Subscription_Synchronization::init() ;
