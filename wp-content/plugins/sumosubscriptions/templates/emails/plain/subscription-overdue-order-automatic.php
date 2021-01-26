<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n" ;

$link = '' ;
if ( $payment_link === 'yes' ) {
    $link = 'If you wish to pay using an alternate method, please use the following payment link: ' . $order->get_checkout_payment_url() ;
}

if ( $order->has_status( 'pending' ) ) {

    echo sprintf( __( 'Your Subscription #%s is in Overdue status because we couldn\'t charge your account for Subscription Renewal. Please make sure that you have sufficient funds in your account. <br>%s. If payment is not made for the Subscription Renewal by <b>%s</b>. Your Subscription will move to <b>%s</b> status.' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) , $link , $upcoming_mail_date , $upcoming_mail_status ) . "\n\n" ;
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
