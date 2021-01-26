<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>
<tr class="item <?php echo apply_filters( 'woocommerce_admin_html_order_item_class' , ( ! empty( $class ) ? $class : '' ) , $item , $order ) ; ?>" data-order_item_id="<?php echo $item_id ; ?>">
    <td class="thumb">
        <?php if ( $_product ) : ?>
            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( sumosubs_get_product_id( $_product ) ) . '&action=edit' ) ) ; ?>" class="tips" data-tip="<?php
            echo '<strong>' . __( 'Product ID:' , 'sumosubscriptions' ) . '</strong> ' . absint( $item[ 'product_id' ] ) ;

            if ( ! empty( $item[ 'variation_id' ] ) && 'product_variation' === get_post_type( $item[ 'variation_id' ] ) ) {
                echo '<br/><strong>' . __( 'Variation ID:' , 'sumosubscriptions' ) . '</strong> ' . absint( $item[ 'variation_id' ] ) ;
            } elseif ( ! empty( $item[ 'variation_id' ] ) ) {
                echo '<br/><strong>' . __( 'Variation ID:' , 'sumosubscriptions' ) . '</strong> ' . absint( $item[ 'variation_id' ] ) . ' (' . __( 'No longer exists' , 'sumosubscriptions' ) . ')' ;
            }

            if ( $_product && $_product->get_sku() ) {
                echo '<br/><strong>' . __( 'Product SKU:' , 'sumosubscriptions' ) . '</strong> ' . esc_html( $_product->get_sku() ) ;
            }

            if ( $_product && sumosubs_get_product_type( $_product ) === 'variation' ) {
                echo '<br/>' . wc_get_formatted_variation( $_product->get_variation_attributes() , true ) ;
            }
            ?>">
                   <?php echo $_product->get_image( array ( 40 , 40 ) , array ( 'title' => '' ) ) ; ?>
            </a>
        <?php else : ?>
            <?php echo wc_placeholder_img( 'shop_thumbnail' ) ; ?>
        <?php endif ; ?>
    </td>
    <td class="name" data-sort-value="<?php echo esc_attr( $item[ 'name' ] ) ; ?>">

        <?php echo ( $_product && $_product->get_sku() ) ? esc_html( $_product->get_sku() ) . ' &ndash; ' : '' ; ?>

        <?php if ( $_product ) : ?>
            <a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( sumosubs_get_product_id( $_product , true ) ) . '&action=edit' ) ) ; ?>">
                <?php
                if ( $subscription_product_name = get_post_meta( $post->ID , 'sumo_product_name' , true ) ) :
                    if ( is_array( $subscription_product_name ) ) {
                        echo esc_html( isset( $subscription_product_name[ $product_id ] ) ? $subscription_product_name[ $product_id ] : ''  ) ;
                    } else {
                        echo esc_html( get_post_meta( $post->ID , 'sumo_product_name' , true ) ) ;
                    }
                endif ;
                ?>
            </a>
         <?php
            if ( $item->get_variation_id() ) {
                echo '<div class="wc-order-item-variation"><strong>' . esc_html__( 'Variation ID:', 'woocommerce' ) . '</strong> ' ;
                if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
                    echo esc_html( $item->get_variation_id() ) ;
                } else {
                    /* translators: %s: variation id */
                    printf( esc_html__( '%s (No longer exists)', 'woocommerce' ), esc_html( $item->get_variation_id() ) ) ;
                }
                echo '</div>' ;
            }
            ?>
        <?php else : ?>
            <?php echo esc_html( $item[ 'name' ] ) ; ?>
        <?php endif ; ?>
        <?php echo '&nbsp;&nbsp;' . SUMO_Subscription_Variation_Switcher::display( $post->ID ) ; ?>

        <input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ) ; ?>" />
        <input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ) ; ?>]" value="<?php echo isset( $item[ 'tax_class' ] ) ? esc_attr( $item[ 'tax_class' ] ) : '' ; ?>" />
        <div class="view">
            <?php
            echo sumosubs_get_order_item_metadata( $order , array (
                'order_item_id' => $item_id ,
                'order_item'    => $item ,
                'product'       => $_product ,
                'to_display'    => true
            ) ) ;
            ?>
        </div>
    </td>

    <td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item , false , true ) ) ; ?>">
        <div class="view">
            <?php
            $text              = '' ;
            $is_trial_enabled  = '1' === $subscription_plan[ 'trial_status' ] ;
            $is_signup_enabled = '1' === $subscription_plan[ 'signup_status' ] ;

            if ( isset( $item[ 'line_total' ] ) ) {
                if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ] , 2 ) != round( ( float ) $item[ 'line_total' ] , 2 ) ) {
                    echo '<del>' . wc_price( $order->get_item_subtotal( $item , false , true ) , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</del> ' ;
                }

                echo '<b>' . wc_price( $order->get_item_total( $item , false , true ) , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</b>' ;

                if ( ! SUMO_Order_Subscription::is_subscribed( $post->ID ) ) {
                    if ( $is_signup_enabled || $is_trial_enabled ) {
                        $text .= '<br>' ;
                        $text .= __( ' (Including ' , 'sumosubscriptions' ) ;
                    }

                    if ( $is_signup_enabled ) {
                        $text .= sprintf( __( 'Sign up Fee of %s' , 'sumosubscriptions' ) , '<b>' . wc_price( $subscription_plan[ 'signup_fee' ] , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</b>' ) ;

                        if ( ! $is_trial_enabled ) {
                            $text .= ')' ;
                        }
                    }

                    if ( $is_trial_enabled ) {
                        if ( $is_signup_enabled ) {
                            $text .= ' & ' ;
                        }
                        if ( 'paid' === $subscription_plan[ 'trial_type' ] ) {
                            $text .= sprintf( __( 'Trial Fee of %s)' , 'sumosubscriptions' ) , '<b>' . wc_price( $subscription_plan[ 'trial_fee' ] , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</b>' ) ;
                        } else {
                            $text .= '<b>' . __( 'Free Trial !!!' , 'sumosubscriptions' ) . '</b>)' ;
                        }
                    }
                    echo $text ;
                }
            }
            ?>
        </div>
    </td>

    <td class="quantity" width="1%">
        <div class="view">
            <?php
            echo '<small class="times">&times;</small> ' . ( isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : '1' ) ;
            ?>
        </div>
    </td>

    <td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( isset( $item[ 'line_total' ] ) ? $item[ 'line_total' ] : ''  ) ; ?>">
        <div class="view">
            <?php
            if ( isset( $item[ 'line_total' ] ) ) {
                echo '<b>' . wc_price( $item[ 'line_total' ] , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</b>' ;
            }
            if ( isset( $item[ 'line_subtotal' ] ) && round( ( float ) $item[ 'line_subtotal' ] , 2 ) != round( ( float ) $item[ 'line_total' ] , 2 ) ) {
                echo '<br><span class="wc-order-item-discount">-' . wc_price( wc_format_decimal( $item[ 'line_subtotal' ] - $item[ 'line_total' ] , '' ) , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</span>' ;
            }
            ?>
        </div>
    </td>

    <?php
    if ( empty( $legacy_order ) && wc_tax_enabled() ) :
        $line_tax_data = isset( $item[ 'line_tax_data' ] ) ? $item[ 'line_tax_data' ] : '' ;
        $tax_data      = maybe_unserialize( $line_tax_data ) ;

        foreach ( $order_taxes as $tax_item ) :
            $tax_item_id        = $tax_item[ 'rate_id' ] ;
            $shipping_tax_total = isset( $tax_item[ 'shipping_tax_total' ] ) ? $tax_item[ 'shipping_tax_total' ] : 0 ;
            $tax_item_total     = isset( $tax_data[ 'total' ][ $tax_item_id ] ) ? $tax_data[ 'total' ][ $tax_item_id ] : 0 ;
            $tax_item_subtotal  = isset( $tax_data[ 'subtotal' ][ $tax_item_id ] ) ? $tax_data[ 'subtotal' ][ $tax_item_id ] : 0 ;
            ?>
            <td class="line_tax" width="1%">
                <div class="view">
                    <?php
                    if ( $tax_item_total ) {
                        if ( isset( $tax_item_subtotal ) && round( ( float ) $tax_item_subtotal , 2 ) != round( ( float ) $tax_item_total , 2 ) ) {
                            echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ) , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</del> ' ;
                        }

                        $tax_total += $tax_item_total + $shipping_tax_total ;
                        echo '<b>' . wc_price( wc_round_tax_total( $tax_item_total ) , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) . '</b>' ;
                    } else {
                        echo '&ndash;' ;
                    }
                    ?>
                </div>
            </td>
            <?php
        endforeach ;
    endif ;
    ?>
</tr>
