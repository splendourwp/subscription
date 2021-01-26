<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =</br>" ;

echo sprintf( __( 'Automatic charging for your Subscription #%s has been turned off. <br>You will get a subscription payment reminder email using which you can pay for your subscription renewal.' , 'sumosubscriptions' ) , sumo_get_subscription_number( $post_id ) ) . "\n\n" ;

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
