<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

//Deprecated Hooks and filters from WooCommerce v3.0
//Actions
add_action( 'sumosubscriptions_before_adding_renewal_order_item' , 'sumosubscriptions_bkwrd_cmpblty_add_order_item' , 10 , 3 ) ;
add_action( 'sumosubscriptions_before_adding_shippping_in_renewal_order' , 'sumosubscriptions_bkwrd_cmpblty_add_shipping_method' , 10 , 2 ) ;
add_action( 'sumosubscriptions_before_adding_discount_in_renewal_order' , 'sumosubscriptions_bkwrd_cmpblty_add_discount' , 10 , 3 ) ;

//Filters
add_filter( 'woocommerce_get_price' , 'sumosubscriptions_bkwrd_cmpblty_set_cart_item_line_total' , 10 , 2 ) ;
add_filter( 'woocommerce_order_amount_cart_tax' , 'sumosubscriptions_bkwrd_cmpblty_calculate_cart_tax' , 10 , 2 ) ;
add_filter( 'woocommerce_order_amount_shipping_tax' , 'sumosubscriptions_bkwrd_cmpblty_calculate_shipping_tax' , 10 , 2 ) ;
add_filter( 'woocommerce_order_amount_total_shipping' , 'sumosubscriptions_bkwrd_cmpblty_calculate_total_shipping' , 10 , 2 ) ;

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_set_cart_item_line_total( $price , $_product ) {
    remove_filter( 'woocommerce_get_price' , 'sumosubscriptions_bkwrd_cmpblty_set_cart_item_line_total' , 10 , 2 ) ;

    $line_total = SUMOSubscriptions_Frontend::set_cart_item_line_total( $price , $_product ) ;

    add_filter( 'woocommerce_get_price' , 'sumosubscriptions_bkwrd_cmpblty_set_cart_item_line_total' , 10 , 2 ) ;
    return $line_total ;
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_calculate_cart_tax( $order_tax , $order ) {
    $cart_tax = get_post_meta( $order->id , 'sumo_get_cart_tax' , true ) ;

    if ( $cart_tax ) {
        return $order_tax - $cart_tax ;
    }
    return $order_tax ;
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_calculate_shipping_tax( $order_shipping_tax , $order ) {
    $shipping_tax = get_post_meta( $order->id , 'sumo_get_shipping_tax' , true ) ;

    if ( $shipping_tax ) {
        return $order_shipping_tax - $shipping_tax ;
    }
    return $order_shipping_tax ;
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_calculate_total_shipping( $order_shipping_amount , $order ) {
    $total_shipping_amount = get_post_meta( $order->id , 'sumo_get_total_shipping' , true ) ;

    if ( $total_shipping_amount ) {
        return $order_shipping_amount - $total_shipping_amount ;
    }
    return $order_shipping_amount ;
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_add_order_item( $parent_order_id , $renewal_order_id , $post_id ) {
    $_parent_order     = wc_get_order( $parent_order_id ) ;
    $renewal_order     = wc_get_order( $renewal_order_id ) ;
    $subscription_plan = sumo_get_subscription_plan( $post_id ) ;

    if ( ! $_parent_order || ! $renewal_order ) {
        return ;
    }

    $prorated_amount       = get_post_meta( $post_id , 'sumo_subscription_prorated_amount' , true ) ;
    $apply_prorated_fee_on = get_post_meta( $post_id , 'sumo_subscription_prorated_amount_to_apply_on' , true ) ;

    foreach ( sumo_get_order_item_meta( $parent_order_id , 'item' ) as $_item_id => $_item ) {
        $add_item   = false ;
        $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ] ;

        if ( ! $_product = wc_get_product( $product_id ) ) {
            continue ;
        }

        $line_total = sumo_get_recurring_fee( $post_id , $_item , $_item_id ) ;

        if ( SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
            $add_item = true ;
        } else {
            if ( $subscription_plan[ 'subscription_product_id' ] == $product_id ) {
                //Calculate if the Admin decided to Prorate Payment in the First Renewal.
                if ( 'first_renewal' === $apply_prorated_fee_on && is_numeric( $prorated_amount ) && $prorated_amount > 0 ) {
                    $line_total += ($prorated_amount * $_item[ 'qty' ]) ;
                }

                $add_item = true ;
            }
        }

        //Check whether it is valid to add order item meta.
        if ( ! $add_item ) {
            continue ;
        }

        $item_id = wc_add_order_item( $renewal_order_id , array (
            'order_item_name' => $_item[ 'name' ] ,
            'order_item_type' => 'line_item' ) ) ;

        if ( $item_id ) {
            wc_add_order_item_meta( $item_id , '_product_id' , $_item[ 'product_id' ] ) ;
            wc_add_order_item_meta( $item_id , '_variation_id' , $_item[ 'variation_id' ] ) ;

            if ( $_product && $_item[ 'variation_id' ] > 0 ) {
                //Add Variation Attributes.
                foreach ( $_product->get_variation_attributes() as $key => $value ) {
                    wc_add_order_item_meta( $item_id , str_replace( 'attribute_' , '' , $key ) , $value ) ;
                }
            }

            $discount_amount     = 0 ;
            $discount_amount_tax = 0 ;

            if ( SUMO_Subscription_Coupon::is_coupon_applicable_for_renewal_by_user( $post_id ) ) {

                $discount_amount     = $_item[ 'line_subtotal' ] - $_item[ 'line_total' ] ;
                $discount_amount_tax = $_item[ 'line_subtotal_tax' ] - $_item[ 'line_tax' ] ;

                if ( sumosubs_get_order_prices_include_tax( $_parent_order ) && get_option( 'sumo_tax_option' ) !== 'yes' ) {
                    $discount_amount += $_item[ 'line_subtotal_tax' ] - $_item[ 'line_tax' ] ;
                }

                $renewal_order->set_total( $discount_amount , 'cart_discount' ) ;
                $renewal_order->set_total( $discount_amount_tax , 'cart_discount_tax' ) ;
            }

            $line_total = $_product ? sumosubs_get_price_excluding_tax( $_product , array ( 'price' => $line_total ) ) : $line_total ;

            $line_subtotal = $line_total ;
            $line_total -= $discount_amount ;

            wc_add_order_item_meta( $item_id , '_line_total' , wc_format_decimal( $line_total ) ) ;
            wc_add_order_item_meta( $item_id , '_line_subtotal' , wc_format_decimal( $line_subtotal ) ) ;
            wc_add_order_item_meta( $item_id , '_line_tax' , '0' ) ;
            wc_add_order_item_meta( $item_id , '_line_subtotal_tax' , '0' ) ;
            wc_add_order_item_meta( $item_id , '_tax_class' , $_product ? $_product->get_tax_class() : ''  ) ;
            wc_add_order_item_meta( $item_id , '_qty' , $_item[ 'qty' ] ) ;

            //For Synchronized Subscription. Trigger after Prorated Amount gets added with the Subscription Product Line Total. Since it will only applicable for First Renewal alone
            delete_post_meta( $post_id , 'sumo_subscription_prorated_amount' ) ;
            delete_post_meta( $post_id , 'sumo_subscription_prorated_amount_to_apply_on' ) ;
        }
    }
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_add_shipping_method( $parent_order_id , $renewal_order_id ) {
    $_parent_order = wc_get_order( $parent_order_id ) ;
    $renewal_order = wc_get_order( $renewal_order_id ) ;

    if ( ! $shipping_methods = $_parent_order->get_shipping_methods() ) {
        return ;
    }

    //Add Shipping to the Renewal Order.
    foreach ( $shipping_methods as $shipping_item_id => $shipping_rate ) {
        $item_id = wc_add_order_item( $renewal_order_id , array (
            'order_item_name' => $shipping_rate[ 'name' ] ,
            'order_item_type' => 'shipping'
                ) ) ;

        if ( ! $item_id ) {
            return false ;
        }

        wc_add_order_item_meta( $item_id , 'method_id' , $shipping_rate[ 'method_id' ] ) ;
        wc_add_order_item_meta( $item_id , 'cost' , wc_format_decimal( $shipping_rate[ 'cost' ] ) ) ;

        // Save shipping taxes
        $taxes = array_map( 'wc_format_decimal' , maybe_unserialize( $shipping_rate[ 'taxes' ] ) ) ;
        wc_add_order_item_meta( $item_id , 'taxes' , $taxes ) ;

        // Update total
        $renewal_order->set_total( $_parent_order->get_total_shipping() , 'shipping' ) ;
    }

    $renewal_order = wc_get_order( $renewal_order_id ) ;

    //Calculate tax and shipping.
    if ( get_option( 'sumo_shipping_option' ) === 'yes' && get_option( 'sumo_tax_option' ) === 'yes' ) {

        update_post_meta( $renewal_order_id , '_order_total' , $renewal_order->calculate_totals() ) ;
    } else if ( get_option( 'sumo_shipping_option' ) === 'yes' && get_option( 'sumo_tax_option' ) !== 'yes' ) {
        update_post_meta( $renewal_order_id , '_order_total' , $renewal_order->calculate_totals( false ) ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_cart_tax' , $renewal_order->get_cart_tax() ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_shipping_tax' , $renewal_order->get_shipping_tax() ) ;

        $renewal_order->calculate_totals( false ) ;
    } else if ( get_option( 'sumo_shipping_option' ) !== 'yes' && get_option( 'sumo_tax_option' ) === 'yes' ) {
        update_post_meta( $renewal_order_id , '_order_total' , $renewal_order->calculate_totals() ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_total_shipping' , $renewal_order->get_total_shipping() ) ;
    } else {
        update_post_meta( $renewal_order_id , '_order_total' , $renewal_order->calculate_totals( false ) ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_cart_tax' , $renewal_order->get_cart_tax() ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_shipping_tax' , $renewal_order->get_shipping_tax() ) ;
        update_post_meta( $renewal_order_id , 'sumo_get_total_shipping' , $renewal_order->get_total_shipping() ) ;

        $renewal_order->calculate_totals( false ) ;
    }
}

/**
 * @deprecated
 */
function sumosubscriptions_bkwrd_cmpblty_add_discount( $parent_order_id , $renewal_order_id , $post_id ) {
    $_parent_order = wc_get_order( $parent_order_id ) ;
    $renewal_order = wc_get_order( $renewal_order_id ) ;

    if ( ! $_parent_order || ! $renewal_order ) {
        return ;
    }
    if ( ! $coupons = $_parent_order->get_items( array ( 'coupon' ) ) ) {
        return ;
    }

    foreach ( $coupons as $key => $coupon ) {
        if ( ! isset( $coupon[ 'name' ] , $coupon[ 'discount_amount' ] , $coupon[ 'discount_amount_tax' ] ) ) {
            continue ;
        }

        $renewal_order->add_coupon( $coupon[ 'name' ] , $coupon[ 'discount_amount' ] , $coupon[ 'discount_amount_tax' ] ) ;
    }
}
