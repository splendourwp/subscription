<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription and Trial purchase restriction.
 * 
 * @class SUMO_Subscription_Restrictions
 * @category Class
 */
class SUMO_Subscription_Restrictions {

    /**
     * Customer ID
     * @var int 
     */
    protected static $user_id = 0 ;

    /**
     * The Product ID will be either Simple|Grouped product ID or else Variation ID
     * @var int 
     */
    protected static $product_id = 0 ;

    /**
     * Get Trial restriction type chosen by Admin
     * @var string 
     */
    public static $trial_restriction_type = '' ;

    /**
     * Get Subscription restriction type chosen by Admin
     * @var string 
     */
    public static $subscription_restriction_type = '' ;

    /**
     * Check globally Product level restriction has chosen by Admin
     * @var bool 
     */
    public static $is_product_level_restriction_enabled = false ;

    /**
     * Valid Subscription restriction statuses. This is not applicable for Trial Subscriptions
     * @var array 
     */
    protected static $subscription_restriction_statuses = array( 'Pending', 'Active', 'Trial', 'Pause', 'Overdue', 'Suspended', 'Pending_Cancellation', 'Pending_Authorization' ) ;

    /**
     * Valid Trial Subscription post statuses.
     * @var array 
     */
    protected static $valid_trial_post_statuses = array( 'publish', 'trash' ) ;

    /**
     * Get Subscriptions by user.
     * @var object 
     */
    protected static $subscriptions_by_user = false ;

    /**
     * Get Subscriptions by user.
     * @var object 
     */
    protected static $subscriptions = array() ;

    /**
     * Maybe get respective variations from the self::$product_id.
     * @var array 
     */
    protected static $respective_variations = array() ;

    /**
     * Set code to limit subscription
     */
    const PURCHASABLE       = 100 ;
    const SOLD_INDIVIDUALLY = 101 ;
    const NOT_PURCHASABLE   = 102 ;

    /**
     * Init the Subscription Restrictions
     */
    public static function init() {
        self::$subscription_restriction_type        = 'no_limit' ;
        self::$trial_restriction_type               = 'no_limit' ;
        self::$is_product_level_restriction_enabled = '2' === get_option( 'sumo_limit_variable_product_level', '1' ) ;

        switch ( get_option( 'sumo_limit_subscription_quantity', '1' ) ) {
            case '2':
                self::$subscription_restriction_type = 'product_wide' ;
                break ;
            case '3':
                self::$subscription_restriction_type = 'site_wide' ;
                break ;
        }
        switch ( get_option( 'sumo_trial_handling', '1' ) ) {
            case '2':
                self::$trial_restriction_type = 'product_wide' ;
                break ;
            case '3':
                self::$trial_restriction_type = 'site_wide' ;
                break ;
        }

        if ( self::is_restriction_available_in_site() ) {
            add_filter( 'woocommerce_is_sold_individually', __CLASS__ . '::is_sold_individually', 10, 2 ) ;
            add_filter( 'woocommerce_is_purchasable', __CLASS__ . '::is_purchasable', 10, 2 ) ;
            add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::manage_stock', 999 ) ;
            add_action( 'woocommerce_remove_cart_item_from_session', __CLASS__ . '::add_subscription_limit_notice', 10, 2 ) ;
            add_action( 'wp_head', __CLASS__ . '::add_trial_limit_notice' ) ;
        }

        //may be Mixed Checkout available on cart
        if ( 'yes' !== get_option( 'sumosubscription_apply_mixed_checkout', 'yes' ) ) {
            add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::can_product_add_to_cart', 11, 5 ) ;
        }
    }

    /**
     * Populate product data
     */
    public static function populate( $product_id, $user_id ) {
        self::$product_id = absint( $product_id ) ;
        self::$user_id    = absint( $user_id ) ? absint( $user_id ) : get_current_user_id() ;
    }

    /**
     * Check the subscription product restriction is available in the site
     * @return boolean
     */
    public static function is_restriction_available_in_site() {

        if ( in_array( self::$subscription_restriction_type, array( 'product_wide', 'site_wide' ) ) || in_array( self::$trial_restriction_type, array( 'product_wide', 'site_wide' ) ) ) {
            return true ;
        }
        return false ;
    }

    /**
     * Check whether the Customer is a Subscriber
     * @param array $post_status valid statuses are array ( 'publish' , 'trash' )
     * @return boolean
     */
    public static function is_subscriber( $post_status = array( 'publish' ) ) {
        self::get_subscriptions_by_user_query( $post_status ) ;

        return ! empty( self::$subscriptions_by_user ) ;
    }

    /**
     * Get Subscriptions query posts by the Subscriber.
     * 
     * @param array $post_status valid statuses are array ( 'publish' , 'trash' )
     * By default, Subscription valid status is 'publish' where as Trial Subscription statuses are 'publish' and 'trash'
     * 
     * @return \WP_Query posts. Excluding Order subscription posts
     */
    public static function get_subscriptions_by_user_query( $post_status = array( 'publish' ) ) {

        self::$subscriptions_by_user = sumosubscriptions()->query->get( array(
            'type'       => 'sumosubscriptions',
            'status'     => $post_status ? $post_status : array( 'publish', 'trash' ),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'sumo_get_user_id',
                    'value'   => self::$user_id,
                    'type'    => 'numeric',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'sumo_is_order_based_subscriptions',
                    'value'   => 'yes',
                    'compare' => 'NOT EXISTS',
                ),
            ),
                ) ) ;

        return self::$subscriptions_by_user ;
    }

    /**
     * Get Subscriptions based upon the self::$product_id by the Subscriber.
     * @return array
     */
    public static function get_subscriptions_by_user() {

        if ( ! self::is_subscriber() ) {
            return array() ;
        }

        $subscriptions = array() ;

        foreach ( self::$subscriptions_by_user as $subscription_id ) {
            $subscription_plan = sumo_get_subscription_plan( $subscription_id ) ;

            if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
                continue ;
            }

            self::$subscriptions[ $subscription_id ] = $subscription_plan ;

            if ( 'product_wide' === self::$subscription_restriction_type ) {
                if ( self::$product_id == $subscription_plan[ 'subscription_product_id' ] ) {
                    $subscriptions[] = $subscription_id ;
                }
            } else {
                $subscriptions[] = $subscription_id ;
            }
        }
        return $subscriptions ;
    }

    /**
     * Get Trial Subscriptions by the Subscriber.
     * @return array
     */
    public static function get_trial_subscriptions_by_user() {

        if ( ! self::is_subscriber( self::$valid_trial_post_statuses ) ) {
            return array() ;
        }

        $trial_subscriptions = array() ;

        foreach ( self::$subscriptions_by_user as $subscription_id ) {
            $subscription_plan = sumo_get_subscription_plan( $subscription_id ) ;

            if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
                continue ;
            }

            self::$subscriptions[ $subscription_id ] = $subscription_plan ;

            if ( '1' === $subscription_plan[ 'trial_status' ] ) {
                $trial_subscriptions[ $subscription_plan[ 'subscription_product_id' ] ] = $subscription_id ;
            }
        }

        return $trial_subscriptions ;
    }

    public static function get_subscriptions_from_cart( $context = '', $cart_contents = array() ) {
        if ( ! isset( WC()->cart->cart_contents ) ) {
            return array() ;
        }

        $cart_contents = empty( $cart_contents ) ? WC()->session->get( 'cart', array() ) : $cart_contents ;

        if ( ! is_array( $cart_contents ) || empty( $cart_contents ) ) {
            return array() ;
        }

        $subscriptions = array() ;
        foreach ( $cart_contents as $cart_item_key => $cart_item ) {
            if ( ! isset( $cart_item[ 'product_id' ] ) ) {
                continue ;
            }
            $item_id           = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;
            $subscription_plan = sumo_get_subscription_plan( 0, $item_id ) ;

            if ( '1' === $subscription_plan[ 'subscription_status' ] ) {
                switch ( $context ) {
                    case 'trial':
                        if ( '1' === $subscription_plan[ 'trial_status' ] ) {
                            $subscriptions[] = $item_id ;
                        }
                        break ;
                    case 'respective_variation':
                        if ( in_array( $item_id, self::$respective_variations ) ) {
                            $subscriptions[] = $item_id ;
                        }
                        break ;
                    case 'respective_trial_variation':
                        if ( '1' === $subscription_plan[ 'trial_status' ] && in_array( $item_id, self::$respective_variations ) ) {
                            $subscriptions[] = $item_id ;
                        }
                        break ;
                    case 'with_cart_item_key':
                        $subscriptions[ $cart_item_key ] = $item_id ;
                        break ;
                    case 'plan_with_cart_item_key':
                        $subscriptions[ $cart_item_key ] = $subscription_plan ;
                        break ;
                    default :
                        $subscriptions[]                 = $item_id ;
                }
            }
        }

        return $subscriptions ;
    }

    public static function cart_contains_subscriptions( $context = '' ) {
        $subscriptions = self::get_subscriptions_from_cart( $context ) ;

        return sizeof( $subscriptions ) > 0 ;
    }

    public static function cart_contains_subscription( $product_id ) {
        $subscriptions = self::get_subscriptions_from_cart() ;

        return in_array( $product_id, $subscriptions ) ;
    }

    public static function is_switching() {
        if (
                SUMO_Subscription_Upgrade_or_Downgrade::is_switcher_page() ||
                SUMO_Subscription_Upgrade_or_Downgrade::is_subscription_switched( self::$product_id ) ||
                ( doing_action( 'wp_loaded' ) && 'woocommerce_is_purchasable' === current_filter() && get_transient( 'sumo_subscription_switching_into_cart' ) )//Should be useful when cart session is loaded in wp_loaded hook
        ) {
            return true ;
        }
        return false ;
    }

    /**
     * Get Subscription Limit level for the Subscriber.
     * Subscription limitation is purely based on self::$subscription_restriction_statuses
     * The restriction is not applicable when the subscription is in trash status
     * 
     * @return int
     */
    public static function get_subscription_limit_code( $product_id, $user_id = 0 ) {
        self::populate( $product_id, $user_id ) ;

        switch ( self::$subscription_restriction_type ) {
            case 'product_wide':
                if ( $subscriptions = self::get_subscriptions_by_user() ) {
                    foreach ( $subscriptions as $subscription_id ) {
                        if ( ! is_numeric( $subscription_id ) ) {
                            continue ;
                        }

                        if ( in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), self::$subscription_restriction_statuses ) ) {
                            if ( self::is_switching() ) {
                                return self::SOLD_INDIVIDUALLY ;
                            }
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                }

                if ( self::has_product_level_variable_restriction( self::$product_id ) ) {
                    $subscription_variations = self::get_subscriptions_from_cart( 'respective_variation' ) ;

                    foreach ( $subscription_variations as $item_row_id => $item_id ) {
                        if ( $item_row_id > 0 && $item_id == self::$product_id ) {
                            return self::NOT_PURCHASABLE ;
                        }
                    }

                    if ( is_product() ) {
                        return self::NOT_PURCHASABLE ;
                    }
                }

                if ( (is_product() || is_shop()) && self::cart_contains_subscription( self::$product_id ) ) {
                    return self::NOT_PURCHASABLE ;
                }

                return self::SOLD_INDIVIDUALLY ;
            case 'site_wide':
                if ( $subscriptions = self::get_subscriptions_by_user() ) {
                    foreach ( $subscriptions as $subscription_id ) {
                        if ( ! is_numeric( $subscription_id ) ) {
                            continue ;
                        }

                        if ( in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), self::$subscription_restriction_statuses ) ) {
                            if ( self::is_switching() ) {
                                return self::SOLD_INDIVIDUALLY ;
                            }
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                }

                if ( $subscriptions = self::get_subscriptions_from_cart() ) {
                    foreach ( $subscriptions as $item_row_id => $item_id ) {
                        if ( $item_id != self::$product_id ) {
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                }

                return self::SOLD_INDIVIDUALLY ;
            default :
                return self::PURCHASABLE ;
        }
    }

    /**
     * Get Trial Subscription Limit level for the Subscriber.
     * When the Subscriber purchased a Trial it may be considered as the Trial limitation
     * Here ignoring any statuses (including trash) to limit the Trial
     * 
     * @return int
     */
    public static function get_trial_limit_code( $product_id, $user_id = 0 ) {
        self::populate( $product_id, $user_id ) ;

        switch ( self::$trial_restriction_type ) {
            case 'product_wide':
                if ( $trial_subscriptions = self::get_trial_subscriptions_by_user() ) {
                    foreach ( $trial_subscriptions as $restricted_product => $subscription_id ) {
                        if ( self::$product_id == $restricted_product ) {
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                }

                if ( self::has_product_level_variable_restriction( self::$product_id, true ) ) {
                    $trial_subscriptions = self::get_subscriptions_from_cart( 'respective_trial_variation' ) ;

                    foreach ( $trial_subscriptions as $item_row_id => $item_id ) {
                        if ( $item_row_id > 0 && $item_id == self::$product_id ) {
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                }

                return self::SOLD_INDIVIDUALLY ;
            case 'site_wide':
                if ( self::get_trial_subscriptions_by_user() ) {
                    return self::NOT_PURCHASABLE ;
                }

                if ( is_cart() || is_checkout() || is_ajax() ) {
                    $trial_subscriptions = self::get_subscriptions_from_cart( 'trial' ) ;

                    foreach ( $trial_subscriptions as $item_row_id => $item_id ) {
                        if ( $item_row_id > 0 && $item_id == self::$product_id ) {
                            return self::NOT_PURCHASABLE ;
                        }
                    }
                } else {
                    if ( self::cart_contains_subscriptions( 'trial' ) ) {
                        return self::NOT_PURCHASABLE ;
                    }
                }

                return self::SOLD_INDIVIDUALLY ;
            default :
                return self::PURCHASABLE ;
        }
    }

    /**
     * Check whether the Variation Product has Product Level restrictions
     * @param int $restricted_variation
     * @return boolean
     */
    public static function has_product_level_variable_restriction( $restricted_variation, $trial_enabled = false ) {

        if ( ! self::$is_product_level_restriction_enabled || 'variation' !== sumosubs_get_product_type( $restricted_variation ) ) {
            return false ;
        }

        self::$respective_variations = sumo_get_available_subscription_variations( sumosubs_get_product_id( $restricted_variation, true ) ) ;

        if ( ! in_array( $restricted_variation, self::$respective_variations ) ) {
            return false ;
        }

        if ( ! empty( self::$subscriptions ) ) {
            foreach ( self::$subscriptions as $subscription_id => $subscription_plan ) {
                $subscription_product_id = $subscription_plan[ 'subscription_product_id' ] ;

                if ( in_array( $subscription_product_id, self::$respective_variations ) ) {
                    if ( $trial_enabled ) {
                        return true ;
                    }
                    if ( in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), self::$subscription_restriction_statuses ) ) {
                        return true ;
                    }
                }
            }
        }

        $variation_subscriptions_in_cart = self::get_subscriptions_from_cart( 'respective_variation' ) ;

        if ( $variation_subscriptions_in_cart ) {
            return true ;
        }
        return false ;
    }

    /**
     * Add error message in Single Product page, if the product is under Restriction.
     * @return string
     */
    public static function add_error_notice() {
        if ( 'product_wide' === self::$subscription_restriction_type ) {
            return '<p style = "color: red; font-size: 18px;">' . get_option( 'sumo_active_subsc_per_product_in_product_page' ) . '</p>' ;
        } else if ( 'site_wide' === self::$subscription_restriction_type ) {
            return '<p style = "color: red; font-size: 18px;">' . get_option( 'sumo_active_subsc_through_site_in_product_page' ) . '</p>' ;
        }
    }

    /**
     * Check whether this User is allowed to purchase this Subscription.
     * @param boolean $is_purchasable
     * @param object $_product
     * @return boolean
     */
    public static function is_purchasable( $is_purchasable, $_product ) {
        $product_id        = sumosubs_get_product_id( $_product ) ;
        $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

        if ( '1' === $subscription_plan[ 'subscription_status' ] && 102 === self::get_subscription_limit_code( $product_id ) ) {
            return false ;
        }
        return $is_purchasable ;
    }

    /**
     * Check whether this User is allowed to purchase more than one qty of this Subscription.
     * @param boolean $is_sold_individually
     * @param object $_product
     * @return boolean
     */
    public static function is_sold_individually( $is_sold_individually, $_product ) {
        $product_id        = sumosubs_get_product_id( $_product ) ;
        $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

        if ( '1' === $subscription_plan[ 'subscription_status' ] ) {
            if (
                    ('1' === $subscription_plan[ 'trial_status' ] && in_array( self::get_trial_limit_code( $product_id ), array( 101, 102 ) ) ) ||
                    in_array( self::get_subscription_limit_code( $product_id ), array( 101, 102 ) )
            ) {
                return true ;
            }
        }
        return $is_sold_individually ;
    }

    public static function manage_stock( $cart ) {

        $subscriptions_in_cart = self::get_subscriptions_from_cart( 'plan_with_cart_item_key', WC()->cart->cart_contents ) ;

        if ( empty( $subscriptions_in_cart ) ) {
            return ;
        }

        foreach ( $subscriptions_in_cart as $cart_item_key => $cart_item_plan ) {
            $subscribed_product_id = $cart_item_plan[ 'subscription_product_id' ] ;

            if (
                    ('1' === $cart_item_plan[ 'trial_status' ] && in_array( self::get_trial_limit_code( $subscribed_product_id ), array( 101, 102 ) )) ||
                    in_array( self::get_subscription_limit_code( $subscribed_product_id ), array( 101, 102 ) )
            ) {
                if ( WC()->cart->set_quantity( $cart_item_key, 1, false ) ) {
                    remove_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::manage_stock', 999 ) ;

                    WC()->cart->calculate_totals() ;

                    add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::manage_stock', 999 ) ;
                }
            }
        }
    }

    public static function add_subscription_limit_notice( $cart_key, $values ) {
        $product_id        = $values[ 'variation_id' ] ? $values[ 'variation_id' ] : $values[ 'product_id' ] ;
        $product           = wc_get_product( $product_id ) ;
        $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

        if ( '1' === $subscription_plan[ 'subscription_status' ] && 102 === self::get_subscription_limit_code( $product_id ) ) {
            $wc_notice = sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $product->get_name() ) ;

            if ( wc_has_notice( $wc_notice, 'error' ) ) {
                $notices = WC()->session->get( 'wc_notices', array() ) ;

                foreach ( $notices as $type => $messages ) {
                    if ( 'error' === $type ) {
                        foreach ( $messages as $key => $message ) {
                            if ( $wc_notice === $message ) {
                                unset( $notices[ $type ][ $key ] ) ;
                            }
                        }
                    }
                }

                WC()->session->set( 'wc_notices', $notices ) ;
                wc_add_notice( sprintf( __( 'You can\'t add this subscription <strong>%s</strong> to cart because you can\'t have more than one subscription', 'sumosubscriptions' ), $product->get_name() ), 'error' ) ;
            }
        }
    }

    /**
     * Add Trial limit notice to the customer
     */
    public static function add_trial_limit_notice() {

        if ( ! is_cart() && ! is_checkout() ) {
            return ;
        }

        $restricted_subscription_name = array() ;
        $trial_subscriptions          = self::get_subscriptions_from_cart( 'trial' ) ;

        foreach ( $trial_subscriptions as $product_id ) {
            if ( 102 === self::get_trial_limit_code( $product_id ) ) {
                $restricted_subscription_name[] = get_post( self::$product_id )->post_title ;
            }
        }

        if ( $restricted_subscription_name ) {
            $message = get_option( 'sumo_active_trial_through_site_in_cart_page' ) ;

            if ( 'product_wide' === self::$trial_restriction_type ) {
                $message = str_replace( '[product_name(s)]', '<b>' . implode( ', ', $restricted_subscription_name ) . '</b>', get_option( 'sumo_active_trial_per_product_in_cart_page' ) ) ;
            }
            if ( 'yes' === get_option( 'sumo_show_hide_err_msg_cart_page', 'yes' ) ) {
                wc_add_notice( $message, 'error' ) ;
            }
        }
    }

    /**
     * Applicable for Mixed Checkout. 
     * Throw error message when cutomer add to cart the Subscription product with Regular product else viceversa
     * 
     * @param bool $valid
     * @param int $product_id Product post ID
     * @param int $quantity
     * @param int $variation_id Product variation post ID
     * @param array $variations
     * @return bool
     */
    public static function can_product_add_to_cart( $valid, $product_id, $quantity, $variation_id = NULL, $variations = NULL ) {

        if ( 0 === WC()->cart->get_cart_contents_count() ) {
            return $valid ;
        }

        $add_to_cart_product_id = is_numeric( $variation_id ) && $variation_id ? $variation_id : $product_id ;
        $display_err_message    = 'yes' === get_option( 'sumo_show_hide_err_msg_product_page', 'yes' ) ;

        if ( sumo_is_cart_contains_subscription_items() ) {
            if ( ! sumo_is_subscription_product( $add_to_cart_product_id ) ) {
                if ( $display_err_message ) {
                    wc_add_notice( get_option( 'sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ), 'error' ) ;
                }

                return false ;
            }
        } else {
            if ( sumo_is_subscription_product( $add_to_cart_product_id ) ) {
                if ( $display_err_message ) {
                    wc_add_notice( get_option( 'sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ), 'error' ) ;
                }

                return false ;
            }
        }

        return $valid ;
    }

}

SUMO_Subscription_Restrictions::init() ;
