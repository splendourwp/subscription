<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n" ;

echo sprintf( __( 'Your Subscription #%s is going to expire on %s.', 'sumosubscriptions' ), sumo_get_subscription_number( $post_id ), sumo_display_subscription_date( get_post_meta( $post_id, 'sumo_get_saved_due_date', true ) ) ) . "\n\n" ;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

echo strtoupper( sprintf( __( 'Subscription #%s', 'sumosubscriptions' ), sumo_get_subscription_number( $post_id ) ) ) . "\n" ;

echo "(" . date_i18n( __( 'jS F Y', 'sumosubscriptions' ), strtotime( sumosubs_get_order_date( $order ) ) ) . ")\n" ;

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n" . do_action( 'sumosubscriptions_email_order_details', $order, $post_id, $email ) ;

echo "==========\n\n" ;

do_action( 'sumosubscriptions_email_order_meta', $order, $post_id, $email, true ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ) ;

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ;
