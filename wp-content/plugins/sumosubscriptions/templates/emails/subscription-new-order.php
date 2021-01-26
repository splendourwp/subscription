<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<?php if ( $admin_template ) { ?>
    <p><?php printf( __( 'You have received a Subscription order from %s %s. The Subscription order is as follows:' , 'sumosubscriptions' ) , sumosubs_get_order_billing_first_name( $order ) , sumosubs_get_order_billing_last_name( $order ) ) ; ?></p>
<?php } else { ?>
    <p><?php printf( __( 'You have placed a new Subscription order on %s. The Subscription order is as follows:' , 'sumosubscriptions' ) , get_option( 'blogname' ) ) ; ?></p>
<?php } ?>

<?php do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php if ( $admin_template ) { ?>
    <h2><a class="link" href="<?php echo admin_url( 'post.php?post=' . sumosubs_get_order_id( $order ) . '&action=edit' ) ; ?>"><?php printf( __( 'Order #%s' , 'sumosubscriptions' ) , $order->get_order_number() ) ; ?></a> (<?php printf( '<time datetime="%s">%s</time>' , date_i18n( 'c' , strtotime( sumosubs_get_order_date( $order ) ) ) , date_i18n( wc_date_format() , strtotime( sumosubs_get_order_date( $order ) ) ) ) ; ?>)</h2>
<?php } else { ?>
    <h2><?php printf( __( 'Order #%s' , 'sumosubscriptions' ) , sumosubs_get_order_id( $order ) ) ; ?></h2>
<?php } ?>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Product' , 'sumosubscriptions' ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Quantity' , 'sumosubscriptions' ) ; ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php _e( 'Price' , 'sumosubscriptions' ) ; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php do_action( 'sumosubscriptions_email_order_details' , $order , $post_id , $email ) ; ?>
    </tbody>
    <tfoot>
        <?php do_action( 'sumosubscriptions_email_order_meta' , $order , $post_id , $email ) ; ?>
    </tfoot>
</table>

<?php do_action( 'woocommerce_email_after_order_table' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_order_meta' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_customer_details' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>
