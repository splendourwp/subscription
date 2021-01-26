<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Normal products in cart as Order Subscription
 * 
 * @class SUMO_Order_Subscription
 * @category Class
 */
class SUMO_Order_Subscription {

    /**
     * Check whether the customer can proceed to subscribe
     * @var bool 
     */
    protected static $can_user_subscribe ;

    /**
     * Form to render Order Subscription
     */
    protected static $form ;

    /**
     * Can Order Subscription form render in Cart?
     */
    protected static $display_form_in_cart ;

    /**
     * Get options
     */
    public static $get_option = array() ;

    /**
     * Get subscribed plan props
     * @var array 
     */
    protected static $subscribed_plan_props = array(
        'subscribed'                     => null,
        'has_signup'                     => null,
        'signup_fee'                     => null,
        'recurring_fee'                  => null,
        'duration_period'                => null,
        'duration_length'                => null,
        'recurring_length'               => null,
        'item_fee'                       => null,
        'item_qty'                       => null,
        'discounted_recurring_fee'       => null,
        'total_renewals_to_apply_coupon' => null,
            ) ;

    /**
     * Get form to render Order Subscription
     */
    public static function get_form() {
        if ( is_null( self::$form ) ) {
            self::$form = get_option( 'sumo_order_subsc_form_position', 'checkout_order_review' ) ;
        }
        return self::$form ;
    }

    /**
     * Can render Order Subscription form in Cart
     */
    public static function show_subscribe_form_in_cart() {
        if ( is_null( self::$display_form_in_cart ) ) {
            self::$display_form_in_cart = 'yes' === get_option( 'sumo_display_order_subscription_in_cart', 'no' ) ;
        }
        return self::$display_form_in_cart ;
    }

    /**
     * Init SUMO_Order_Subscription.
     */
    public static function init() {
        if ( empty( self::$get_option ) ) {
            self::populate() ;
        }

        if ( self::show_subscribe_form_in_cart() ) {
            add_action( 'woocommerce_before_cart_totals', __CLASS__ . '::render_subscribe_form' ) ;
            add_action( 'woocommerce_before_cart_totals', __CLASS__ . '::add_custom_style' ) ;
        }

        add_action( 'woocommerce_' . self::get_form(), __CLASS__ . '::render_subscribe_form' ) ;
        add_action( 'woocommerce_' . self::get_form(), __CLASS__ . '::add_custom_style' ) ;

        add_action( 'wp_loaded', __CLASS__ . '::get_subscription_from_session', 20 ) ;
        add_action( 'woocommerce_after_calculate_totals', __CLASS__ . '::get_subscription_from_session', 20 ) ;
        add_filter( 'woocommerce_cart_total', __CLASS__ . '::render_subscribed_plan_message', 10, 1 ) ;
        add_filter( 'sumosubscriptions_alter_subscription_plan_meta', __CLASS__ . '::save_subscribed_plan_meta', 10, 4 ) ;
    }

    public static function can_user_subscribe() {
        if ( is_bool( self::$can_user_subscribe ) ) {
            return self::$can_user_subscribe ;
        }

        if (
                'yes' === get_option( 'sumo_order_subsc_check_option', 'no' ) &&
                ! sumo_is_cart_contains_subscription_items( true ) &&
                (
                ! is_numeric( self::$get_option[ 'min_order_total' ] ) ||
                (isset( WC()->cart->total ) && WC()->cart->total >= floatval( self::$get_option[ 'min_order_total' ] ) )
                ) &&
                self::cart_contains_valid_products()
        ) {
            self::$can_user_subscribe = true ;
        } else {
            self::$can_user_subscribe = false ;
        }
        return self::$can_user_subscribe ;
    }

    public static function get_default_props() {
        return array_map( '__return_null', self::$subscribed_plan_props ) ;
    }

    public static function populate() {
        self::$get_option = array(
            'default_subscribed'                   => 'yes' === get_option( 'sumo_order_subsc_checkout_option', 'no' ),
            'can_user_select_plan'                 => 'user' === get_option( 'sumo_order_subsc_chosen_by_option', 'admin' ),
            'can_user_select_recurring_length'     => 'yes' === get_option( 'sumo_order_subsc_enable_recurring_cycle_option_for_users', 'yes' ),
            'min_order_total'                      => get_option( 'sumo_min_order_total_to_display_order_subscription' ),
            'default_duration_period'              => get_option( 'sumo_order_subsc_duration_option' ),
            'default_duration_length'              => get_option( 'sumo_order_subsc_duration_value_option' ),
            'default_recurring_length'             => get_option( 'sumo_order_subsc_recurring_option' ),
            'duration_period_selector'             => get_option( 'sumo_get_order_subsc_duration_period_selector_for_users', array() ),
            'min_duration_length_user_can_select'  => get_option( 'sumo_order_subsc_min_subsc_duration_value_user_can_select', array() ),
            'max_duration_length_user_can_select'  => get_option( 'sumo_order_subsc_max_subsc_duration_value_user_can_select', array() ),
            'min_recurring_length_user_can_select' => get_option( 'sumo_order_subsc_min_recurring_cycle_user_can_select', '1' ),
            'max_recurring_length_user_can_select' => get_option( 'sumo_order_subsc_max_recurring_cycle_user_can_select', '0' ),
            'has_signup'                           => get_option( 'sumo_order_subsc_has_signup' ),
            'signup_fee'                           => get_option( 'sumo_order_subsc_signup_fee' ),
            'product_select_type'                  => get_option( 'sumo_order_subsc_get_product_selected_type', 'all-products' ),
            'included_products'                    => get_option( 'sumo_order_subsc_get_included_products', array() ),
            'excluded_products'                    => get_option( 'sumo_order_subsc_get_excluded_products', array() ),
            'included_categories'                  => get_option( 'sumo_order_subsc_get_included_categories', array() ),
            'excluded_categories'                  => get_option( 'sumo_order_subsc_get_excluded_categories', array() ),
                ) ;
    }

    /**
     * Check whether the cart contains valid products to perform Order Subscription by the user.
     * 
     * @return bool
     */
    public static function cart_contains_valid_products() {
        $products = array() ;

        if ( isset( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $item ) {
                $products[] = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
            }
        }

        $valid = true ;
        switch ( self::$get_option[ 'product_select_type' ] ) {
            case 'included-products':
                $valid    = 0 === count( array_diff( $products, self::$get_option[ 'included_products' ] ) ) ? true : false ;
                break ;
            case 'excluded-products':
                $valid    = 0 === count( array_intersect( $products, self::$get_option[ 'excluded_products' ] ) ) ? true : false ;
                break ;
            case 'included-categories':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', self::$get_option[ 'included_categories' ] ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        )
                    ),
                        ) ) ;

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts ;
                }

                $valid    = 0 === count( array_diff( $products, $found_products ) ) ? true : false ;
                break ;
            case 'excluded-categories':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', self::$get_option[ 'excluded_categories' ] ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        )
                    ),
                        ) ) ;

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts ;
                }

                $valid = 0 === count( array_intersect( $products, $found_products ) ) ? true : false ;
                break ;
        }

        return $valid ;
    }

    public static function is_subscribed( $subscription_id = 0, $parent_order_id = 0, $customer_id = 0 ) {
        if ( $subscription_id ) {
            return 'yes' === get_post_meta( $subscription_id, 'sumo_is_order_based_subscriptions', true ) ;
        }

        if ( 'yes' === get_post_meta( $parent_order_id, 'sumo_is_order_based_subscriptions', true ) ) {
            return true ;
        }

        if ( $customer_id = absint( $customer_id ) ) {
            $subscribed_plan = get_user_meta( $customer_id, 'sumo_subscriptions_order_details', true ) ;

            if ( ! empty( $subscribed_plan[ 'subscribed' ] ) && 'yes' === $subscribed_plan[ 'subscribed' ] ) {
                return true ;
            }
        }

        if ( self::can_user_subscribe() && ! empty( WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ) ) {
            return 'yes' === WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ;
        }
        return false ;
    }

    public static function get_subscribed_plan( $customer_id = 0 ) {
        $subscribed_plan = array() ;

        if ( $customer_id = absint( $customer_id ) ) {
            $subscribed_plan = get_user_meta( $customer_id, 'sumo_subscriptions_order_details', true ) ;
        }
        if ( empty( $subscribed_plan ) && self::is_subscribed() ) {
            $subscribed_plan = WC()->cart->sumosubscriptions[ 'order' ] ;
        }
        return self::$subscribed_plan_props = wp_parse_args( is_array( $subscribed_plan ) ? $subscribed_plan : array(), self::get_default_props() ) ;
    }

    public static function add_custom_style() {
        if ( self::can_user_subscribe() ) {
            wp_register_style( 'sumo-order-subsc-inline', false ) ;
            wp_enqueue_style( 'sumo-order-subsc-inline' ) ;
            wp_add_inline_style( 'sumo-order-subsc-inline', get_option( 'sumo_order_subsc_custom_css' ) ) ;
        }
    }

    public static function render_subscribe_form() {
        if ( ( ! is_cart() && ! is_checkout()) || ! self::can_user_subscribe() ) {
            return ;
        }

        sumosubscriptions_get_template( 'order-subscription-form.php', array(
            'options'     => self::$get_option,
            'chosen_plan' => self::get_subscribed_plan(),
        ) ) ;
    }

    public static function render_subscribed_plan_message( $total ) {
        if ( self::is_subscribed() ) {
            $total = sumo_display_subscription_plan() ;

            if ( is_numeric( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ) ) {
                $total .= str_replace( '[renewal_fee_after_discount]', wc_price( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ), get_option( 'sumo_renewal_fee_after_discount_msg_customization' ) ) ;
                $total .= 0 === WC()->cart->sumosubscriptions[ 'order' ][ 'total_renewals_to_apply_coupon' ] ? '' : str_replace( '[discounted_renewal_fee_upto]', WC()->cart->sumosubscriptions[ 'order' ][ 'total_renewals_to_apply_coupon' ], get_option( 'sumo_discounted_renewal_fee_upto_msg_customization' ) ) ;
            }
        }
        return $total ;
    }

    public static function get_shipping_to_apply_in_renewal( $calc_tax = false ) {
        if ( 'yes' !== get_option( 'sumo_shipping_option' ) ) {
            return false ;
        }

        $totals         = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals ;
        $shipping_total = ! empty( $totals[ 'shipping_total' ] ) ? floatval( $totals[ 'shipping_total' ] ) : false ;
        $shipping_tax   = $calc_tax && ! empty( $totals[ 'shipping_tax' ] ) ? floatval( $totals[ 'shipping_tax' ] ) : false ;

        if ( $shipping_total && $shipping_tax ) {
            $shipping_total += $shipping_tax ;
        }
        return $shipping_total ;
    }

    public static function get_items_tax_to_apply_in_renewal( $cart_item = array() ) {
        if ( 'yes' !== get_option( 'sumo_tax_option' ) || ! wc_tax_enabled() ) {
            return false ;
        }

        $items_tax = false ;
        if ( ! empty( $cart_item ) ) {
            if ( ! empty( $cart_item[ 'line_tax' ] ) ) {
                $items_tax = floatval( $cart_item[ 'line_tax' ] ) ;
            }
        } else {
            $totals       = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals ;
            $discount_tax = ! empty( $totals[ 'discount_tax' ] ) ? floatval( $totals[ 'discount_tax' ] ) : false ;
            $items_tax    = ! empty( $totals[ 'cart_contents_tax' ] ) ? floatval( $totals[ 'cart_contents_tax' ] ) : false ;
            $items_tax    = $discount_tax && $items_tax ? $items_tax + $discount_tax : $items_tax ;
        }
        return $items_tax ;
    }

    public static function update_user_meta( $customer_id ) {
        delete_user_meta( $customer_id, 'sumo_subscriptions_order_details' ) ;

        if ( self::is_subscribed() ) {
            add_user_meta( $customer_id, 'sumo_subscriptions_order_details', self::get_subscribed_plan() ) ;
        }
    }

    public static function check_session_data() {
        if ( ! in_array( WC()->session->get( 'sumo_order_subscription_duration_period' ), ( array ) self::$get_option[ 'duration_period_selector' ] ) ) {
            WC()->session->__unset( 'sumo_is_order_subscription_subscribed' ) ;
            WC()->session->__unset( 'sumo_order_subscription_duration_period' ) ;
            WC()->session->__unset( 'sumo_order_subscription_duration_length' ) ;
            WC()->session->__unset( 'sumo_order_subscription_recurring_length' ) ;
        }
    }

    public static function get_subscription_from_session() {
        if ( ! did_action( 'woocommerce_loaded' ) || ! isset( WC()->cart ) ) {
            return ;
        }

        if ( ! self::can_user_subscribe() ) {
            return ;
        }

        self::check_session_data() ;
        WC()->cart->sumosubscriptions                            = array() ;
        WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] = WC()->session->get( 'sumo_is_order_subscription_subscribed' ) ;

        if ( 'yes' !== WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ) {
            return ;
        }

        $recurring_fee        = 0 ;
        $items_tax_in_renewal = false ;
        $totals               = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals ;

        WC()->cart->sumosubscriptions[ 'order' ][ 'duration_period' ]  = WC()->session->get( 'sumo_order_subscription_duration_period', 'D' ) ;
        WC()->cart->sumosubscriptions[ 'order' ][ 'duration_length' ]  = WC()->session->get( 'sumo_order_subscription_duration_length', '1' ) ;
        WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_length' ] = WC()->session->get( 'sumo_order_subscription_recurring_length', '0' ) ;

        if ( ! empty( $totals[ 'cart_contents_tax' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true ;
            $items_tax_in_renewal                                    = self::get_items_tax_to_apply_in_renewal() ;

            if ( is_numeric( $items_tax_in_renewal ) ) {
                $recurring_fee += $items_tax_in_renewal ;
            }
        }
        if ( ! empty( $totals[ 'shipping_total' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true ;
            $shipping_in_renewal                                     = self::get_shipping_to_apply_in_renewal( is_numeric( $items_tax_in_renewal ) ) ;

            if ( is_numeric( $shipping_in_renewal ) ) {
                $recurring_fee += $shipping_in_renewal ;
            }
        }
        if ( ! empty( $totals[ 'discount_total' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ]                     = true ;
            WC()->cart->sumosubscriptions[ 'order' ][ 'total_renewals_to_apply_coupon' ] = SUMO_Subscription_Coupon::get_total_renewals_to_apply_coupon( WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_length' ] ) ;
        }

        foreach ( WC()->cart->cart_contents as $cart_item ) {
            if ( empty( $cart_item[ 'product_id' ] ) ) {
                continue ;
            }
            //Calculate Recurring Fee based no. of Item Qty
            $recurring_fee += floatval( wc_format_decimal( wc_get_price_excluding_tax( $cart_item[ 'data' ], array( 'qty' => $cart_item[ 'quantity' ] ) ), wc_get_price_decimals() ) ) ;
            $item_id       = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

            WC()->cart->sumosubscriptions[ 'order' ][ 'item_fee' ][ $item_id ] = sumosubs_get_product_price( $cart_item[ 'data' ] ) ;
            WC()->cart->sumosubscriptions[ 'order' ][ 'item_qty' ][ $item_id ] = $cart_item[ 'quantity' ] ;
        }

        if (
                isset( WC()->cart->sumosubscriptions[ 'order' ][ 'total_renewals_to_apply_coupon' ] ) &&
                is_numeric( WC()->cart->sumosubscriptions[ 'order' ][ 'total_renewals_to_apply_coupon' ] ) &&
                $recurring_fee
        ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] = WC()->cart->total ;
        }

        if ( 'yes' === self::$get_option[ 'has_signup' ] && is_numeric( self::$get_option[ 'signup_fee' ] ) && self::$get_option[ 'signup_fee' ] > 0 ) {
            WC()->cart->total                                        += wc_format_decimal( self::$get_option[ 'signup_fee' ] ) ;
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true ;
        }

        if ( wc_format_decimal( $recurring_fee ) == WC()->cart->total ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = null ;
        }

        if ( isset( WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] ) && WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'signup_fee' ]    = WC()->cart->total ;
            WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_fee' ] = $recurring_fee ;
        } else {
            WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_fee' ] = WC()->cart->total ;
        }
        WC()->cart->sumosubscriptions[ 'order' ] = self::get_subscribed_plan() ;
    }

    public static function save_subscribed_plan_meta( $subscribed_plan, $subscription_id, $product_id, $customer_id ) {
        if ( $subscription_id || $product_id ) {
            return $subscribed_plan ;
        }

        if ( self::is_subscribed( 0, 0, $customer_id ) ) {
            self::get_subscribed_plan( $customer_id ) ;

            $subscribed_plan[ 'susbcription_status' ]   = '1' ;
            $subscribed_plan[ 'subfee' ]                = self::$subscribed_plan_props[ 'recurring_fee' ] ;
            $subscribed_plan[ 'subperiod' ]             = self::$subscribed_plan_props[ 'duration_period' ] ;
            $subscribed_plan[ 'subperiodvalue' ]        = self::$subscribed_plan_props[ 'duration_length' ] ;
            $subscribed_plan[ 'instalment' ]            = self::$subscribed_plan_props[ 'recurring_length' ] ;
            $subscribed_plan[ 'signusumoee_selection' ] = self::$subscribed_plan_props[ 'has_signup' ] ? '1' : '' ;
            $subscribed_plan[ 'signup_fee' ]            = self::$subscribed_plan_props[ 'signup_fee' ] ;
            $subscribed_plan[ 'productid' ]             = array_keys( self::$subscribed_plan_props[ 'item_fee' ] ) ;
            $subscribed_plan[ 'item_fee' ]              = self::$subscribed_plan_props[ 'item_fee' ] ;
            $subscribed_plan[ 'product_qty' ]           = self::$subscribed_plan_props[ 'item_qty' ] ;
        }
        return $subscribed_plan ;
    }

}

SUMO_Order_Subscription::init() ;
