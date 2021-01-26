<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n" ;

if ( $admin_template ) {
    echo sprintf( __( 'You have received a Subscription order from %s %s. The Subscription order is as follows:' , 'sumosubscriptions' ) , sumosubs_get_order_billing_first_name( $order ) , sumosubs_get_order_billing_last_name( $order ) ) . "\n\n" ;
} else {
    echo sprintf( __( 'You have placed a new Subscription order on %s. The Subscription order is as follows:' , 'sumosubscriptions' ) , get_option( 'blogname' ) ) . "\n\n" ;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ;

echo strtoupper( sprintf( __( 'Order #%s' , 'sumosubscriptions' ) , $order->get_order_number() ) ) . "\n" ;

echo "(" . date_i18n( __( 'jS F Y' , 'sumosubscriptions' ) , strtotime( sumosubs_get_order_date( $order ) ) ) . ")\n" ;

do_action( 'woocommerce_email_order_meta' , $order , $sent_to_admin , $plain_text , $email ) ;

echo "\n" . do_action( 'sumosubscriptions_email_order_details' , $order , $post_id , $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta' , $order , $post_id , $email , true ) ;

echo "\n" . sprintf( __( 'View Subscription: %s' , 'sumosubscriptions' ) , admin_url( 'post.php?post=' . sumosubs_get_order_id( $order ) . '&action=edit' ) ) . "\n" ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_after_order_table' , $order , $sent_to_admin , $plain_text , $email ) ;

do_action( 'woocommerce_email_customer_details' , $order , $sent_to_admin , $plain_text , $email ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo apply_filters( 'woocommerce_email_footer_text' , get_option( 'woocommerce_email_footer_text' ) ) ;
