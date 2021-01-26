<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =</br>" ;

if ( $order->has_status( 'pending' ) ) {

    echo sprintf( __( "The Preapproval access for Automatic charging of Subscription #%s has been revoked. Please note that Future Renewals of Subscription #%s will not be charged automatically. <br>An Invoice has been created for you to renew your Subscription #%s. To pay for this invoice, please use the following link: %s. Please make the payment on or before <b>%s</b>. If payment is not made, Subscription will go to <b>%s</b> status" , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) , sumo_get_subscription_number( $post_id ) , sumo_get_subscription_number( $post_id ) , $order->get_checkout_payment_url() , $upcoming_mail_date , $upcoming_mail_status ) . "\n\n" ;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=</br>" ;

do_action( 'woocommerce_email_before_order_table' , $order , $sent_to_admin , $plain_text , $email ) ;

echo strtoupper( sprintf( __( 'Subscription #%s' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) ) ) . "</br>" ;

echo "(" . date_i18n( __( 'jS F Y' , 'sumosubscriptions' ) , strtotime( sumosubs_get_order_date( $order ) ) ) . ")</br>" ;

do_action( 'woocommerce_email_order_meta' , $order , $sent_to_admin , $plain_text , $email ) ;

echo "</br>" . do_action( 'sumosubscriptions_email_order_details' , $order , $post_id , $email ) ;

echo "==========</br>" ;

do_action( 'sumosubscriptions_email_order_meta' , $order , $post_id , $email , true ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=</br>" ;

echo apply_filters( 'woocommerce_email_footer_text' , get_option( 'woocommerce_email_footer_text' ) ) ;
