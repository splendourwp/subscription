<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n" ;

if ( $order->has_status( 'pending' ) ) {

    echo sprintf( __( 'Your Subscription payment is in Overdue status. Because Subscription Renewal Payment has not been made. But you can still pay for your Subscription Renewal by using the Payment Link: %s. Please make the payment on or before <b>%s</b>. If payment is not made, Subscription will go to <b>%s</b> status' , 'sumosubscriptions' ) , $order->get_checkout_payment_url() , $upcoming_mail_date , $upcoming_mail_status ) . "\n\n" ;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ;

echo strtoupper( sprintf( __( 'Subscription #%s' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) ) ) . "\n" ;

echo "(" . date_i18n( __( 'jS F Y' , 'sumosubscriptions' ) , strtotime( sumosubs_get_order_date( $order ) ) ) . ")\n" ;

do_action( 'woocommerce_email_order_meta' , $order , $sent_to_admin , $plain_text , $email ) ;

echo "\n" . do_action( 'sumosubscriptions_email_order_details' , $order , $post_id , $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta' , $order , $post_id , $email , true ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo apply_filters( 'woocommerce_email_footer_text' , get_option( 'woocommerce_email_footer_text' ) ) ;
