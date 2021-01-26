<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Format Subscription Price.
 * @param string | int $price
 * @return string
 */
function sumo_format_subscription_price( $price , $args = array () ) {
    if ( function_exists( 'wc_price' ) ) {
        return wc_price( $price , $args ) ;
    } else if ( function_exists( 'woocommerce_price' ) ) {
        return woocommerce_price( $price , $args ) ;
    }
    return '' ;
}

/**
 * Format Subscription Duration Period.
 * @param string $duration_period
 * @param string | int $duration_period_length
 * @return string
 */
function sumo_format_subscription_duration_period( $duration_period , $duration_period_length ) {
    $day_singular_plural        = explode( ',' , get_option( 'sumo_day_single_plural' ) ) ;
    $week_singular_plural       = explode( ',' , get_option( 'sumo_week_single_plural' ) ) ;
    $month_singular_plural      = explode( ',' , get_option( 'sumo_month_single_plural' ) ) ;
    $year_singular_plural       = explode( ',' , get_option( 'sumo_year_single_plural' ) ) ;
    $instalment_singular_plural = explode( ',' , get_option( 'sumo_instalment_single_plural' ) ) ;

    switch ( $duration_period ) {
        case 'D':
            return $duration_period_length > 1 ? $day_singular_plural[ 1 ] : $day_singular_plural[ 0 ] ;
        case 'M':
            return $duration_period_length > 1 ? $month_singular_plural[ 1 ] : $month_singular_plural[ 0 ] ;
        case 'W':
            return $duration_period_length > 1 ? $week_singular_plural[ 1 ] : $week_singular_plural[ 0 ] ;
        case 'Y':
            return $duration_period_length > 1 ? $year_singular_plural[ 1 ] : $year_singular_plural[ 0 ] ;
        default :
            return $duration_period_length > 1 ? $instalment_singular_plural[ 1 ] : $instalment_singular_plural[ 0 ] ;
    }
}

/**
 * Format Subscription Cycle.
 * @param string $interval
 * @return string
 */
function sumo_format_subscription_cyle( $interval ) {
    $interval               = explode( ' ' , $interval ) ;
    $duration_period        = isset( $interval[ 1 ] ) ? $interval[ 1 ] : 'D' ;
    $duration_period_length = isset( $interval[ 0 ] ) ? absint( $interval[ 0 ] ) : 1 ;

    switch ( $duration_period ) {
        case 'D':
            return $duration_period_length . ' day' ;
        case 'W':
            return $duration_period_length . ' week' ;
        case 'M':
            return $duration_period_length . ' month' ;
        case 'Y':
            return $duration_period_length . ' year' ;
    }
}
