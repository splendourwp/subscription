<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Apply inline CSS.
 */
function sumosubs_style_inline() {
    global $wp ;

    $is_user_table = (is_callable( 'is_account_page' ) && is_callable( 'sumosubs_is_wc_version' ) && is_account_page() && ((sumosubs_is_wc_version( '<', '2.6' ) && isset( $_GET[ 'subscription-id' ] )) || isset( $wp->query_vars[ 'view-subscription' ] ) || ! isset( $wp->query_vars[ 'view-order' ] ))) ;

    if ( 'sumosubscriptions' === get_post_type() || $is_user_table ) {
        ob_start() ;
        sumosubscriptions_get_template( 'subscription-dynamic-css.php' ) ;
        $css = ob_get_clean() ;

        wp_register_style( 'sumo-subsc-dynamic-css-inline', false ) ;
        wp_enqueue_style( 'sumo-subsc-dynamic-css-inline' ) ;
        wp_add_inline_style( 'sumo-subsc-dynamic-css-inline', $css ) ;
    }
}

add_action( 'admin_enqueue_scripts', 'sumosubs_style_inline' ) ;
add_action( 'wp_enqueue_scripts', 'sumosubs_style_inline' ) ;

/**
 * Output Email Order items table
 * @param object $order The Order object
 * @param int $post_id The Subscription post ID
 * @param string $email
 */
function sumosubs_display_email_order_items_table( $order, $post_id, $email ) {

    switch ( $email->name ) {
        case 'invoice' :
        case 'overdue' :
        case 'suspended' :
        case 'auto-renewed':
        case 'automatic-to-manual-renewal':
        case 'preapproval-access-revoked':
        case 'automatic-charging-reminder':
        case 'pending-authorization':
            echo sumosubs_get_email_order_items_table( $order ) ;
            break ;
        case 'new-order' :
        case 'completed' :
        case 'processing' :
        case 'cancelled' :
            if ( 1 === sizeof( $order->get_items() ) || doing_action( 'woocommerce_order_status_changed' ) || SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
                echo sumosubs_get_email_order_items_table( $order ) ;
            } else {
                echo sumosubs_get_email_order_items_table( $order, $post_id ) ;
            }
            break ;
        case 'paused' :
        case 'cancel-request-submitted':
        case 'cancel-request-revoked':
        case 'turnoff-automatic-payments-success':
        case 'expired' :
            if ( 1 === sizeof( $order->get_items() ) || SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
                echo sumosubs_get_email_order_items_table( $order ) ;
            } else {
                echo sumosubs_get_email_order_items_table( $order, $post_id ) ;
            }
            break ;
    }
}

add_action( 'sumosubscriptions_email_order_details', 'sumosubs_display_email_order_items_table', 10, 3 ) ;

/**
 * Output Email Order items totals.
 * @param object $order The Order object
 * @param int $post_id The Subscription post ID
 * @param string $email
 * @param boolean $plain
 */
function sumosubs_display_email_order_items_totals( $order, $post_id, $email, $plain = false ) {

    switch ( $email->name ) {
        case 'invoice' :
        case 'overdue' :
        case 'suspended' :
        case 'auto-renewed':
        case 'automatic-to-manual-renewal':
        case 'preapproval-access-revoked':
        case 'automatic-charging-reminder':
        case 'pending-authorization':
            echo sumosubs_get_email_order_items_totals( $order, false, $plain ) ;
            break ;
        case 'new-order' :
        case 'completed' :
        case 'processing' :
        case 'cancelled' :
            if ( 1 === sizeof( $order->get_items() ) || doing_action( 'woocommerce_order_status_changed' ) || SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
                echo sumosubs_get_email_order_items_totals( $order, false, $plain ) ;
            } else {
                echo sumosubs_get_email_order_items_totals( $order, $post_id, $plain ) ;
            }
            break ;
        case 'paused' :
        case 'cancel-request-submitted':
        case 'cancel-request-revoked':
        case 'turnoff-automatic-payments-success':
        case 'expired' :
            if ( 1 === sizeof( $order->get_items() ) || SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
                echo sumosubs_get_email_order_items_totals( $order, false, $plain ) ;
            } else {
                echo sumosubs_get_email_order_items_totals( $order, $post_id, $plain ) ;
            }
            break ;
    }
}

add_action( 'sumosubscriptions_email_order_meta', 'sumosubs_display_email_order_items_totals', 10, 4 ) ;

/**
 * Get Email Order items table
 * @param object | int $order The Order post ID
 * @param int $post_id The Subscription post ID
 * @param array $args
 * @return string
 */
function sumosubs_get_email_order_items_table( $order, $post_id = false, $args = array() ) {

    if ( ! $order = wc_get_order( $order ) ) {
        return '' ;
    }

    if ( is_numeric( $post_id ) && $post_id ) {
        ob_start() ;

        if ( $item = sumosubs_get_subscription_item( $order, $post_id ) ) {
            sumosubscriptions_get_template( 'email-order-item.php', array(
                'order' => $order,
                'item'  => $item,
                'total' => sumosubs_get_line_total( $order, $post_id ),
            ) ) ;
        }
        return ob_get_clean() ;
    }

    if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
        return $order->email_order_items_table( true ) ;
    }
    return wc_get_email_order_items( $order, $args ) ;
}

/**
 * Get Email Order items totals.
 * @param object $order The Order object
 * @param int $post_id The Subscription post ID
 * @param boolean $plain
 * @return string
 */
function sumosubs_get_email_order_items_totals( $order, $post_id = false, $plain = false ) {

    if ( ! $totals = $order->get_order_item_totals() ) {
        return '' ;
    }

    if ( is_numeric( $post_id ) && $post_id ) {
        foreach ( $totals as $item_key => $item_value ) {
            if ( ! in_array( $item_key, array( 'cart_subtotal', 'shipping', 'payment_method', 'discount', 'order_total' ) ) ) {
                $totals[ $item_key ][ 'value' ] = wc_price( ($order->get_line_tax( sumosubs_get_subscription_item( $order, $post_id ) ) + $order->get_shipping_tax() ), array( 'currency' => sumosubs_get_order_currency( $order ) ) ) ;
                break ;
            }
        }

        $totals[ 'cart_subtotal' ][ 'value' ] = wc_price( sumosubs_get_line_total( $order, $post_id ), array( 'currency' => sumosubs_get_order_currency( $order ) ) ) ;
        $totals[ 'order_total' ][ 'value' ]   = wc_price( ((sumosubs_get_line_total( $order, $post_id, false ) + $order->get_total_shipping() + $order->get_shipping_tax()) - $order->get_total_discount( false ) ), array( 'currency' => sumosubs_get_order_currency( $order ) ) ) ;
    }

    ob_start() ;

    if ( $plain ) {

        foreach ( $totals as $total ) {
            echo $total[ 'label' ] . "\t " . $total[ 'value' ] . "\n" ;
        }
    } else {
        $i = 0 ;

        foreach ( $totals as $total ) {
            $i ++ ;
            ?>
            <tr>
                <th class="td" scope="row" colspan="2" style="text-align:left; <?php if ( $i === 1 ) echo 'border-top-width: 4px;' ; ?>"><?php echo $total[ 'label' ] ; ?></th>
                <td class="td" style="text-align:left; <?php if ( $i === 1 ) echo 'border-top-width: 4px;' ; ?>"><?php echo $total[ 'value' ] ; ?></td>
            </tr>
            <?php
        }
    }
    return ob_get_clean() ;
}

/**
 * Get Subscription item.
 * @param int $order The Order post
 * @param int $post_id The Subscription post ID
 * @return array
 */
function sumosubs_get_subscription_item( $order, $post_id ) {

    if ( ! $order_items = $order->get_items() ) {
        return array() ;
    }

    $subscription_plan = sumo_get_subscription_plan( $post_id ) ;

    foreach ( $order_items as $item_key => $item_value ) {
        if ( $item_value[ 'variation_id' ] > 0 ) {
            $product_id = $item_value[ 'variation_id' ] ;
        } else {
            $product_id = $item_value[ 'product_id' ] ;
        }

        if ( $subscription_plan[ 'subscription_product_id' ] == $product_id ) {
            return $order_items[ $item_key ] ;
        }
    }
    return array() ;
}

/**
 * Get line total amount.
 * @param object $order The Order object
 * @param int $post_id The Subscription post ID
 * @param bool $incl_tax_display
 * @return int
 */
function sumosubs_get_line_total( $order, $post_id, $incl_tax_display = true ) {
    $line_item = sumosubs_get_subscription_item( $order, $post_id ) ;

    if ( $incl_tax_display && 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
        $total = $order->get_line_total( $line_item ) + $order->get_line_tax( $line_item ) + $order->get_total_discount( false ) ;
    } else {
        $total = $order->get_line_total( $line_item ) + $order->get_total_discount( false ) ;
    }
    return $total ;
}
