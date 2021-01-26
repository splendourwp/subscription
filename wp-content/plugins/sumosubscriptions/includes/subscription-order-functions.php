<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get Order ID
 * @param object | int $order The Order post ID
 * @return int
 */
function sumosubs_get_order_id( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return 0 ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->id ;
    }
    return $order->get_id() ;
}

/**
 * Get Parent Order ID
 * @param object | int $order The Order post ID
 * @param boolean $check_in_renewal 
 * @return int
 */
function sumosubs_get_parent_order_id( $order , $check_in_renewal = true ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return 0 ;
    }

    $order_id = sumosubs_get_order_id( $order ) ;

    if ( $check_in_renewal ) {
        return wp_get_post_parent_id( $order_id ) > 0 ? wp_get_post_parent_id( $order_id ) : $order_id ;
    }
    return wp_get_post_parent_id( $order_id ) ;
}

/**
 * Get Renewal Orders
 * @param object | int $order The Order post ID
 * @return boolean
 */
function sumosubs_get_renewal_orders( $order ) {
    if ( ! $order_id = sumosubs_get_order_id( $order ) ) {
        return false ;
    }
    if ( sumosubs_is_renewal_order( $order_id ) ) {
        return false ;
    }

    $renewal_orders = is_callable( 'get_children' ) ? get_children( array (
                'post_parent' => sumosubs_get_parent_order_id( $order_id ) ,
                'post_type'   => 'shop_order' ,
                'numberposts' => -1 ,
                'post_status' => 'any' ,
                'fields'      => 'ids'
            ) ) : array () ;

    return $renewal_orders ;
}

/**
 * Get Order key
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_key( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->order_key ;
    }
    return $order->get_order_key() ;
}

/**
 * Check Order Prices includes Tax
 * @param object | int $order The Order post ID
 * @return bool
 */
function sumosubs_get_order_prices_include_tax( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->prices_include_tax ;
    }
    return $order->get_prices_include_tax() ;
}

/**
 * Get Order Status
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_status( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->status ;
    }
    return $order->get_status() ;
}

/**
 * Get Order Currency
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_currency( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->get_order_currency() ;
    }
    return $order->get_currency() ;
}

/**
 * Get Order Customer ID
 * @param object | int $order The Order post ID
 * @return int
 */
function sumosubs_get_order_customer_id( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->user_id ;
    }
    return $order->get_customer_id() ;
}

/**
 * Get Order Payment method
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_payment_method( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->payment_method ;
    }
    return $order->get_payment_method() ;
}

/**
 * Get Order Billing Email
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_billing_email( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->billing_email ;
    }
    return $order->get_billing_email() ;
}

/**
 * Get Order Billing First Name
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_billing_first_name( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->billing_first_name ;
    }
    return $order->get_billing_first_name() ;
}

/**
 * Get Order Billing Last Name
 * @param object | int $order The Order post ID
 * @return string
 */
function sumosubs_get_order_billing_last_name( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->billing_last_name ;
    }
    return $order->get_billing_last_name() ;
}

/**
 * Get Order Date
 * @param object | int $order The Order post ID
 * @param bool $date_i18n
 * @return string
 */
function sumosubs_get_order_date( $order , $date_i18n = false ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->order_date ;
    }
    return $date_i18n ? $order->get_date_created()->date_i18n( 'Y-m-d H:i:s' ) : $order->get_date_created() ;
}

/**
 * Get Order modified Date
 * @param object | int $order The Order post ID
 * @param bool $date_i18n
 * @return string
 */
function sumosubs_get_order_modified_date( $order , $date_i18n = false ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->modified_date ;
    }

    return $date_i18n ? $order->get_date_modified()->date_i18n( 'Y-m-d H:i:s' ) : $order->get_date_modified() ;
}

/**
 * Reduce Order Stock
 * @param object | int $order The Order post ID
 */
function sumosubs_reduce_order_stock( $order ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $order->reduce_order_stock() ;
    }
    return wc_reduce_stock_levels( $order ) ;
}

/**
 * Set payment transaction ID in order
 * @param object | int $order The Order post ID
 * @param string $transaction_id
 * @param bool $set_in_parent_order
 */
function sumosubs_set_transaction_id( $order , $transaction_id , $set_in_parent_order = false ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return false ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        update_post_meta( $order->id , '_transaction_id' , wc_clean( $transaction_id ) ) ;
    } else {
        $order->set_transaction_id( $transaction_id ) ;
        $order->save() ;
    }

    if ( $set_in_parent_order ) {
        sumosubs_set_transaction_id( sumosubs_get_parent_order_id( $order ) , $transaction_id ) ;
    }
    return true ;
}

/**
 * Get Order Item meta data
 * @param object | int $order The Order post ID
 * @param array $args
 * @return array|string
 */
function sumosubs_get_order_item_metadata( $order , $args = array () ) {

    $args = wp_parse_args( $args , array (
        'order_item_id' => 0 ,
        'order_item'    => '' ,
        'product'       => '' ,
        'to_display'    => false
            ) ) ;

    if ( ! $order = wc_get_order( $order ) ) {
        return ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        $meta_data = $order->has_meta( $args[ 'order_item_id' ] ) ;
    } else {
        if ( $args[ 'to_display' ] ) {
            $meta_data = $args[ 'order_item' ]->get_formatted_meta_data( '' ) ;
        } else {
            $meta_data = $order->get_meta_data( $args[ 'order_item_id' ] ) ;
        }
    }

    if ( empty( $meta_data ) || ! is_array( $meta_data ) ) {
        return ;
    }

    $item_metadata = array () ;
    $meta_key      = '' ;
    $meta_value    = '' ;

    if ( $args[ 'to_display' ] ) {
        echo '<table cellspacing="0" class="display_meta">' ;
    }

    foreach ( $meta_data as $meta ) {
        if ( ! $meta ) {
            continue ;
        }

        if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
            $meta_key   = $meta[ 'meta_key' ] ;
            $meta_value = $meta[ 'meta_value' ] ;
        } else {
            $meta_key   = $meta->key ;
            $meta_value = $meta->value ;
        }

        if ( in_array( $meta_key , apply_filters( 'woocommerce_hidden_order_itemmeta' , array (
                    '_qty' ,
                    '_tax_class' ,
                    '_product_id' ,
                    '_variation_id' ,
                    '_line_subtotal' ,
                    '_line_subtotal_tax' ,
                    '_line_total' ,
                    '_line_tax' ,
                ) ) )
        ) {
            continue ;
        }
        if ( is_serialized( $meta_value ) ) {
            continue ;
        }
        if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta_key ) ) ) {
            $term       = get_term_by( 'slug' , $meta_value , wc_sanitize_taxonomy_name( $meta_key ) ) ;
            $meta_key   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta_key ) ) ;
            $meta_value = isset( $term->name ) ? $term->name : $meta_value ;
        } else {
            $meta_key = wc_attribute_label( $meta_key , $args[ 'product' ] ) ;
        }

        $item_metadata[ $meta_key ] = $meta_value ;

        if ( $args[ 'to_display' ] ) {
            if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
                echo '<tr><th>' . wp_kses_post( rawurldecode( $meta_key ) ) . ':</th><td>' . wp_kses_post( wpautop( make_clickable( rawurldecode( $meta_value ) ) ) ) . '</td></tr>' ;
            } else {
                echo '<tr><th>' . wp_kses_post( $meta->display_key ) . ':</th><td>' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '</td></tr>' ;
            }
        }
    }

    if ( $args[ 'to_display' ] ) {
        echo '</table>' ;
    } else {
        return $item_metadata ;
    }
}
