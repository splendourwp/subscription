<?php if ( ! defined( 'ABSPATH' ) ) exit ; // Exit if accessed directly              ?>

<?php do_action( 'woocommerce_email_header' , $email_heading , $email ) ; ?>

<p><?php printf( __( 'Your Subscription #%s has been Cancelled.' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) ) ; ?></p>

<?php do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<p><?php _e( 'Subscription Details are shown below for your reference' , 'sumosubscriptions' ) ; ?></p>

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

<?php do_action( 'woocommerce_email_after_order_table' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_order_meta' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_customer_details' , $order , $sent_to_admin , $plain_text , $email ) ; ?>

<?php do_action( 'woocommerce_email_footer' , $email ) ; ?>
