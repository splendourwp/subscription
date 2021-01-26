<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$_product = apply_filters( 'woocommerce_order_item_product' , $order->get_product_from_item( $item ) , $item ) ;

if ( apply_filters( 'woocommerce_order_item_visible' , true , $item ) ) {
    ?>
    <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class' , 'order_item' , $item , $order ) ) ; ?>">
        <td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;"><?php
            // Product name
            echo apply_filters( 'woocommerce_order_item_name' , $item[ 'name' ] , $item , false ) ;

            // Variation
            if ( sumosubs_is_wc_version( '>=' , '3.0' ) ) {
                wc_display_item_meta( $item ) ;
            } else {
                $item_meta = new WC_Order_Item_Meta( $item , $_product ) ;

                if ( ! empty( $item_meta->meta ) ) {
                    echo '<br/><small>' . nl2br( $item_meta->display( true , true , '_' , "\n" ) ) . '</small>' ;
                }
            }

            $item_fee = wc_price( $total , array ( 'currency' => sumosubs_get_order_currency( $order ) ) ) ;
            ?>
        </td>
        <td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo apply_filters( 'woocommerce_email_order_item_quantity' , $item[ 'qty' ] , $item ) ; ?></td>
        <td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo $item_fee ; ?></td>
    </tr>
    <?php
}

	
