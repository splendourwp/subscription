<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<?php if ( $order->has_status( 'pending' ) ) : ?>
    <p>
        <?php
        $link = '' ;
        if ( $payment_link === 'yes' ) {
            $link = sprintf( __( 'If you wish to pay using an alternate method, please use the following payment link: <a href="%s">pay</a>' , 'sumosubscriptions' ) , esc_url( $order->get_checkout_payment_url() ) ) ;
        }

        printf( __( 'Your Subscription #%s is in Overdue status because we couldn\'t charge your account for Subscription Renewal. Please make sure that you have sufficient funds in your account. <br>%s. If payment is not made for the Subscription Renewal by <b>%s</b>. Your Subscription will move to <b>%s</b> status.' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) , $link , $upcoming_mail_date , $upcoming_mail_status ) ;
        ?>
    </p>
<?php endif ; ?>

<?php do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<h2><?php printf( __( 'Subscription #%s' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) ) ; ?></h2>

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

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>
