<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle subscription frontend part.
 * 
 * @class SUMOSubscriptions_Frontend
 * @category Class
 */
class SUMOSubscriptions_Frontend {

    /**
     * @var object Get the subscription. 
     */
    public static $subscription = false ;

    /**
     * @var array Get the subscription product types. 
     */
    public static $subscription_product_types = array( 'simple', 'variation', 'grouped' ) ;

    /**
     * @var string Get the subscription object type. 
     */
    public static $subscription_obj_type = 'product' ;

    /**
     * @var array variation data to display 
     */
    protected static $variation_data = array() ;

    /**
     * Init SUMOSubscriptions_Frontend
     */
    public static function init() {
        add_filter( 'woocommerce_product_add_to_cart_text', __CLASS__ . '::alter_add_to_cart_label', 999, 2 ) ;
        add_filter( 'woocommerce_product_single_add_to_cart_text', __CLASS__ . '::alter_add_to_cart_label', 999, 2 ) ;
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display', __CLASS__ . '::alter_add_to_cart_label', 10, 2 ) ;

        add_filter( 'woocommerce_get_price_html', __CLASS__ . '::get_product_data_to_display', 0, 2 ) ;
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display', __CLASS__ . '::get_variation_data_to_display', 9, 2 ) ;
        add_action( 'woocommerce_before_variations_form', __CLASS__ . '::get_variation_data_to_display', 10 ) ;
        add_action( 'woocommerce_before_single_variation', __CLASS__ . '::get_variation_data_to_display', 10 ) ;
        add_action( 'woocommerce_after_single_variation', __CLASS__ . '::get_variation_data_to_display', 10 ) ;

        add_filter( 'woocommerce_cart_item_price', __CLASS__ . '::get_subscription_message_in_cart_r_checkout', 10, 3 ) ;
        add_filter( 'woocommerce_checkout_cart_item_quantity', __CLASS__ . '::get_subscription_message_in_cart_r_checkout', 10, 3 ) ;

        add_action( 'wp_enqueue_scripts', __CLASS__ . '::add_custom_style' ) ;

        //Force Signup if Guests placing the Subscription Order.
        add_action( 'woocommerce_before_checkout_form', __CLASS__ . '::force_enable_guest_signup_on_checkout', 10, 1 ) ;
        add_action( 'woocommerce_checkout_process', __CLASS__ . '::force_create_account_for_guest' ) ;

        //Manage Subscription Cart/Checkout Total.
        add_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;
        add_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;
        add_filter( 'woocommerce_calculated_total', __CLASS__ . '::alter_cart_total', 10, 2 ) ;
        add_filter( 'woocommerce_cart_total', __CLASS__ . '::alter_cart_total' ) ;
    }

    public static function is_subscription_product_type( $type ) {
        $valid_product_types = apply_filters( 'sumosubscriptions_valid_subscription_product_types', self::$subscription_product_types ) ;
        return in_array( $type, ( array ) $valid_product_types ) ;
    }

    /**
     * Add to cart Label Customisation.
     * @param string|array $data
     * @param mixed $product
     * @return string
     */
    public static function alter_add_to_cart_label( $data, $product ) {
        $maybe_subscription = new SUMO_Subscription_Product( $product ) ;

        if ( $maybe_subscription->exists() && self::is_subscription_product_type( $maybe_subscription->get_type() ) ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variation':
                    if ( 'sumosubscriptions_get_single_variation_data_to_display' === current_filter() ) {
                        if ( $maybe_subscription->is_subscription() ) {
                            $data[ 'add_to_cart_label' ] = get_option( 'sumo_add_to_cart_text' ) ;
                        } else {
                            $data[ 'add_to_cart_label' ] = $maybe_subscription->product->single_add_to_cart_text() ;
                        }
                    }
                    break ;
                case 'grouped':
                    foreach ( $maybe_subscription->product->get_children() as $child_id ) {
                        $child = new SUMO_Subscription_Product( $child_id ) ;

                        if ( $child->exists() && $child->product->is_in_stock() && $child->is_subscription() ) {
                            return get_option( 'sumo_add_to_cart_text' ) ;
                        }
                    }
                    break ;
                default:
                    if ( $maybe_subscription->product->is_in_stock() && $maybe_subscription->is_subscription() ) {
                        if ( sumo_can_purchase_subscription( $maybe_subscription->get_id() ) ) {
                            return get_option( 'sumo_add_to_cart_text' ) ;
                        }
                        return __( 'Read More', 'sumosubscriptions' ) ;
                    }
                    break ;
            }
        }
        return $data ;
    }

    /**
     * Get subscription data product level.
     * 
     * @param string $price
     * @param WC_Product $product
     * @return string
     */
    public static function get_product_data_to_display( $price, $product ) {
        $maybe_subscription = new SUMO_Subscription_Product( $product ) ;

        if ( $maybe_subscription->exists() ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variable':
                    $display_price_by = get_option( 'sumosubs_apply_variable_product_price_msg_based_on', 'subscription-message' ) ;

                    if (
                            in_array( $display_price_by, array( 'subscription-message', 'non-subscription-message' ) ) &&
                            ($variations = $maybe_subscription->product->get_children())
                    ) {

                        remove_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;
                        remove_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;

                        $subscription_variations     = array() ;
                        $non_subscription_variations = array() ;
                        foreach ( $variations as $variation_id ) {
                            $variation = new SUMO_Subscription_Product( $variation_id ) ;

                            if ( ! $variation->exists() ) {
                                continue ;
                            }

                            $variation_price = $variation->get_price() ;
                            if ( $variation->is_subscription() ) {
                                $subscription_variations[ $variation_price ] = array(
                                    'id'    => $variation_id,
                                    'price' => $variation_price,
                                        ) ;
                            } else {
                                $non_subscription_variations[ $variation_price ] = array(
                                    'id'            => $variation_id,
                                    'price'         => $variation_price,
                                    'regular_price' => $variation->product->get_regular_price(),
                                    'is_on_sale'    => $variation->product->is_on_sale(),
                                        ) ;
                            }
                        }
                        add_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;
                        add_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 ) ;

                        if ( 'non-subscription-message' === $display_price_by ) {
                            if ( empty( $non_subscription_variations ) ) {
                                return $price ;
                            }

                            if ( 1 === sizeof( $non_subscription_variations ) ) {
                                $variation_data = current( $non_subscription_variations ) ;

                                if ( $variation_data[ 'is_on_sale' ] ) {
                                    return wc_format_sale_price( $variation_data[ 'regular_price' ], $variation_data[ 'price' ] ) ;
                                }
                                return wc_price( $variation_data[ 'price' ] ) ;
                            } else {
                                ksort( $non_subscription_variations, SORT_NUMERIC ) ;
                                $min = current( $non_subscription_variations ) ;
                                $max = end( $non_subscription_variations ) ;

                                return wc_format_price_range( wc_price( $min[ 'price' ] ), wc_price( $max[ 'price' ] ) ) ;
                            }
                        } else {
                            if ( empty( $subscription_variations ) ) {
                                return $price ;
                            }

                            ksort( $subscription_variations, SORT_NUMERIC ) ;
                            $variation_data = current( $subscription_variations ) ;

                            return sumo_display_subscription_plan( 0, $variation_data[ 'id' ], 0, sizeof( $subscription_variations ) > 1 ) ;
                        }
                    }
                    break ;
                case 'variation':
                    if ( $maybe_subscription->is_subscription() ) {
                        self::$variation_data[ $maybe_subscription->get_id() ][ 'is_subscription' ]           = true ;
                        self::$variation_data[ $maybe_subscription->get_id() ][ 'can_purchase_subscription' ] = true ;

                        if ( is_product() && ! sumo_can_purchase_subscription( $maybe_subscription->get_id() ) ) {
                            self::$variation_data[ $maybe_subscription->get_id() ][ 'can_purchase_subscription' ] = false ;
                        } else {
                            self::$variation_data[ $maybe_subscription->get_id() ][ 'plan_message' ] = sumo_display_subscription_plan( 0, $maybe_subscription->get_id() ) ;
                        }
                        $price = '' ;
                    }
                    break ;
                default :
                    if ( $maybe_subscription->is_subscription() ) {
                        if ( is_product() && ! sumo_can_purchase_subscription( $maybe_subscription->get_id() ) ) {
                            if ( 'yes' === get_option( 'sumo_show_hide_err_msg_product_page', 'yes' ) ) {
                                $price = '<span id="sumosubs_restricted_message">' . SUMO_Subscription_Restrictions::add_error_notice() . '</span>' ;
                            }
                        } else {
                            $price = '<span id="sumosubs_plan_message">' . sumo_display_subscription_plan( 0, $maybe_subscription->get_id() ) . '</span>' ;
                        }
                    }
                    break ;
            }
        }
        return $price ;
    }

    /**
     * Get subscription data variation level.
     */
    public static function get_variation_data_to_display( $data = array(), $variation = null ) {
        if ( 'sumosubscriptions_get_single_variation_data_to_display' === current_filter() ) {
            if ( $variation && $variation->exists() ) {
                if ( ! empty( self::$variation_data[ $variation->get_id() ][ 'is_subscription' ] ) && self::$variation_data[ $variation->get_id() ][ 'is_subscription' ] ) {
                    if ( self::$variation_data[ $variation->get_id() ][ 'can_purchase_subscription' ] ) {
                        $data[ 'plan_message' ] = '<span id="sumosubs_plan_message">' . self::$variation_data[ $variation->get_id() ][ 'plan_message' ] . '</span>' ;
                    } else {
                        if ( 'yes' === get_option( 'sumo_show_hide_err_msg_product_page', 'yes' ) ) {
                            $data[ 'subscription_restricted_message' ] = '<span id="sumosubs_restricted_message">' . SUMO_Subscription_Restrictions::add_error_notice() . '</span>' ;
                        }
                    }
                } else if ( $variation->is_subscription() ) {
                    if ( sumo_can_purchase_subscription( $variation->get_id() ) ) {
                        $data[ 'plan_message' ] = '<span id="sumosubs_plan_message">' . sumo_display_subscription_plan( 0, $variation->get_id() ) . '</span>' ;
                    } else {
                        if ( 'yes' === get_option( 'sumo_show_hide_err_msg_product_page', 'yes' ) ) {
                            $data[ 'subscription_restricted_message' ] = '<span id="sumosubs_restricted_message">' . SUMO_Subscription_Restrictions::add_error_notice() . '</span>' ;
                        }
                    }
                }
            }
        } else if ( doing_action( 'woocommerce_before_variations_form' ) ) {
            global $product ;
            $maybe_subscription = new SUMO_Subscription_Product( $product ) ;
            $children           = $maybe_subscription->product->get_visible_children() ;

            if ( ! empty( $children ) ) {
                $variation_data = array() ;
                foreach ( $children as $child_id ) {
                    $variation = new SUMO_Subscription_Product( $child_id ) ;

                    if ( $variation->exists() && $variation->product->variation_is_visible() ) {
                        $_variation_data = apply_filters( 'sumosubscriptions_get_single_variation_data_to_display', array(), $variation ) ;

                        if ( ! empty( $_variation_data ) ) {
                            $variation_data[ $variation->get_id() ] = $_variation_data ;
                        }
                    }
                }

                if ( ! empty( $variation_data ) ) {
                    $variations   = wp_json_encode( array_keys( $variation_data ) ) ;
                    $hidden_field = "<input type='hidden' id='sumosubs_single_variations'" ;
                    $hidden_field .= "data-variations='{$variations}'" ;
                    $hidden_field .= "/>" ;
                    $hidden_field .= "<input type='hidden' id='sumosubs_single_variation_data'" ;
                    foreach ( $variation_data as $variation_id => $data ) {
                        foreach ( $data as $key => $message ) {
                            $message      = htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' ) ;
                            $hidden_field .= "data-{$key}_{$variation_id}='{$message}'" ;
                        }
                    }
                    $hidden_field .= "/>" ;
                    echo $hidden_field ;
                }
            }
        } else if ( doing_action( 'woocommerce_before_single_variation' ) ) {
            echo '<span id="sumosubs_before_single_variation"></span>' ;
        } else if ( doing_action( 'woocommerce_after_single_variation' ) ) {
            echo '<span id="sumosubs_after_single_variation"></span>' ;
        }
        return $data ;
    }

    /**
     * Get subscription message to display in cart/checkout.
     * @param string $message
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public static function get_subscription_message_in_cart_r_checkout( $message, $cart_item, $cart_item_key ) {
        if ( empty( $cart_item[ 'product_id' ] ) ) {
            return $message ;
        }
        $maybe_subscription = new SUMO_Subscription_Product( $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ) ;

        if ( $maybe_subscription->exists() && $maybe_subscription->is_subscription() ) {
            self::$subscription_obj_type = 'product' ;
            self::$subscription          = $maybe_subscription ;

            if ( ! self::is_subscription_product_type( self::$subscription->get_type() ) ) {
                return $message ;
            }

            if ( SUMO_Subscription_Resubscribe::is_subscription_resubscribed( self::$subscription->get_id() ) ) {
                self::$subscription_obj_type = 'subscription' ;
                self::$subscription          = new SUMO_Subscription( SUMO_Subscription_Resubscribe::get_resubscribed_subscription( self::$subscription->get_id() ) ) ;
            }

            if ( 'product' === self::$subscription_obj_type ) {
                $addon_fee                = apply_filters( 'sumosubscriptions_get_product_addon_fee', 0, self::$subscription->get_id(), $cart_item, $cart_item_key ) ;
                $is_trial_enabled         = self::$subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( self::$subscription->get_id() ) ? true : false ;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_id(), $addon_fee ) ;

                if ( SUMO_Subscription_Synchronization::cart_item_contains_sync( $cart_item ) ) {
                    $initial_renewal_date = $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ] ;
                } else {
                    $initial_renewal_date = sumosubs_get_next_payment_date( 0, self::$subscription->get_id(), array(
                        'initial_payment'     => true,
                        'use_trial_if_exists' => $is_trial_enabled
                            ) ) ;
                }
            } else {
                $is_trial_enabled         = false ;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_subscribed_product() ) ;
                $initial_renewal_date     = sumosubs_get_next_payment_date( self::$subscription->get_id(), 0, array( 'initial_payment' => true ) ) ;
            }

            if ( is_checkout() ) {
                $message .= '<div>( ' . $subscription_plan_string . ')</div>' ;
            } else {
                $message = $subscription_plan_string ;
            }

            $message .= '<p><small style="color:#777;font-size:smaller;">' ;
            if ( $is_trial_enabled || 1 !== self::$subscription->get_installments() ) {
                if (
                        'product' === self::$subscription_obj_type &&
                        SUMO_Subscription_Synchronization::cart_item_contains_sync( $cart_item ) &&
                        SUMO_Subscription_Synchronization::cart_item_contains_sync( $cart_item, 'xtra_time_to_charge_full_fee' ) &&
                        SUMO_Subscription_Synchronization::cart_item_contains_sync( $cart_item, 'awaiting_initial_payment' )
                ) {
                    $message .= sprintf( __( 'First Payment On: <b>%s</b>', 'sumosubscriptions' ), sumo_display_subscription_date( $initial_renewal_date ) ) ;
                } else {
                    $message .= sprintf( __( 'First Renewal On: <b>%s</b>', 'sumosubscriptions' ), sumo_display_subscription_date( $initial_renewal_date ) ) ;
                }
            }
            $message = apply_filters( 'sumosubscriptions_get_message_to_display_in_cart_and_checkout', $message, self::$subscription, $cart_item, $cart_item_key ) ;
            $message .= '</small><p>' ;
        }
        return $message ;
    }

    /**
     * Apply custom style
     */
    public static function add_custom_style() {
        if ( '' === get_option( 'sumo_subsc_custom_css', '' ) ) {
            return ;
        }

        wp_register_style( 'sumo-subsc-inline', false ) ;
        wp_enqueue_style( 'sumo-subsc-inline' ) ;
        wp_add_inline_style( 'sumo-subsc-inline', get_option( 'sumo_subsc_custom_css' ) ) ;
    }

    /**
     * Calculate Subscription Product Line Total in Cart/Checkout.
     * @param string $price
     * @param object $product
     * @return string
     */
    public static function set_cart_item_line_total( $price, $product ) {
        if ( is_shop() && ! is_front_page() ) {
            return $price ;
        }

        $maybe_subscription = new SUMO_Subscription_Product( $product ) ;
        if (
                ! $maybe_subscription->exists() ||
                ! $maybe_subscription->is_subscription() ||
                ! self::is_subscription_product_type( $maybe_subscription->get_type() )
        ) {
            return $price ;
        }

        $default_line_total          = $price ;
        self::$subscription_obj_type = 'product' ;
        self::$subscription          = $maybe_subscription ;
        $subscription_price          = self::$subscription->get_recurring_amount() ;

        if ( ! is_admin() && SUMO_Subscription_Resubscribe::is_subscription_resubscribed( self::$subscription->get_id() ) ) {
            $default_line_total          = 0 ;
            self::$subscription_obj_type = 'subscription' ;
            self::$subscription          = new SUMO_Subscription( SUMO_Subscription_Resubscribe::get_resubscribed_subscription( self::$subscription->get_id() ) ) ;
            $subscription_price          = self::$subscription->get_recurring_amount() / self::$subscription->get_subscribed_qty() ;
        }

        if ( ! is_numeric( $subscription_price ) ) {
            return $price ;
        }

        $is_trial_enabled = 'product' === self::$subscription_obj_type && self::$subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( self::$subscription->get_id() ) ? true : false ;
        $line_total       = floatval( $subscription_price ) ;

        if ( $is_trial_enabled ) {
            $line_total = 0 ; //Consider fee as 0 for Free Trial

            if ( 'paid' === self::$subscription->get_trial( 'type' ) ) {
                $line_total = floatval( self::$subscription->get_trial( 'fee' ) ) ;
            }
        }
        //Onetime fee
        if ( 'product' === self::$subscription_obj_type && self::$subscription->get_signup( 'forced' ) ) {
            $line_total += floatval( self::$subscription->get_signup( 'fee' ) ) ;
        }
        return apply_filters( 'sumosubscriptions_get_line_total', $line_total, self::$subscription, $default_line_total, $is_trial_enabled, self::$subscription_obj_type ) ;
    }

    /**
     * Alter cart total
     * @param float|int $total
     * @param object $cart
     * @return mixed
     */
    public static function alter_cart_total( $total, $cart = '' ) {
        $charge_shipping_in_renewals_only = 'yes' === get_option( 'sumo_shipping_option', 'yes' ) && 'yes' === get_option( 'sumo_charge_shipping_only_in_renewals_when_subtotal_zero', 'no' ) ;

        if (
                WC()->cart->needs_shipping() &&
                (( 1 === sizeof( WC()->cart->cart_contents ) && SUMO_Subscription_Synchronization::cart_contains_sync( 'xtra_time_to_charge_full_fee' )) ||
                ($charge_shipping_in_renewals_only && sumo_is_cart_contains_subscription_items()) )
        ) {
            if ( (WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax()) <= 0 ) {
                if ( 'woocommerce_cart_total' === current_filter() ) {
                    $message = '<div>' ;
                    $message .= '<small style="color:#777;font-size:smaller;">' ;
                    $message .= sprintf( __( '(Shipping amount <strong>%s%s</strong> will be calculated during each renewal)', 'sumosubscriptions' ), get_woocommerce_currency_symbol(), WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ) ;
                    $message .= '</small>' ;
                    $message .= '</div>' ;

                    return $total . $message ;
                }
                return 0 ;
            }
        }
        return $total ;
    }

    /**
     * Force Display Signup on Checkout for Guest. 
     * Since Guest don't have the permission to buy Subscriptions.
     */
    public static function force_enable_guest_signup_on_checkout( $checkout ) {
        if ( is_user_logged_in() || $checkout->is_registration_required() ) {
            return ;
        }

        if ( ! $checkout->is_registration_enabled() && SUMO_Order_Subscription::can_user_subscribe() ) {
            add_filter( 'woocommerce_checkout_registration_enabled', '__return_true', 99 ) ;
            add_filter( 'woocommerce_checkout_registration_required', '__return_true', 99 ) ;
        } else if ( (sumo_is_cart_contains_subscription_items() || SUMO_Order_Subscription::is_subscribed() ) ) {
            $checkout->enable_signup         = true ;
            $checkout->enable_guest_checkout = false ;
        }
    }

    /**
     * To Create account for Guest. 
     */
    public static function force_create_account_for_guest() {
        if ( ! is_user_logged_in() && (sumo_is_cart_contains_subscription_items() || SUMO_Order_Subscription::is_subscribed()) ) {
            add_filter( 'woocommerce_checkout_registration_enabled', '__return_true', 99 ) ;
            add_filter( 'woocommerce_checkout_registration_required', '__return_true', 99 ) ;
            $_POST[ 'createaccount' ] = 1 ;
        }
    }

}

SUMOSubscriptions_Frontend::init() ;
