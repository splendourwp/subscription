<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get WC Product object
 * @param WC_Product $the_product
 * @return boolean|\WC_Product
 */
function sumosubs_get_product( $the_product ) {

    if ( is_numeric( $the_product ) ) {
        return wc_get_product( $the_product ) ;
    } elseif ( $the_product instanceof WC_Product ) {
        return $the_product ;
    }

    return false ;
}

/**
 * Get Product/Variation ID
 * @param object | int $product The Product post ID
 * @param bool $check_parent
 * @return int
 */
function sumosubs_get_product_id( $product , $check_parent = false ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return 0 ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        $product_id = $product->id ;

        if ( in_array( sumosubs_get_product_type( $product_id ) , array ( 'variation' , 'variable' ) ) ) {
            $product_id = $product->variation_id ;
        }
    } else {
        $product_id = $product->get_id() ;
    }

    $parent_id = 0 ;
    if ( $check_parent ) {
        $parent_id = wp_get_post_parent_id( $product_id ) ;
    }

    return $parent_id ? $parent_id : $product_id ;
}

/**
 * Get Product type
 * @param object | int $product The Product post ID
 * @return string
 */
function sumosubs_get_product_type( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->product_type ;
    }
    return $product->get_type() ;
}

/**
 * Get Product price
 * @param object | int $product The Product post ID
 * @return string
 */
function sumosubs_get_product_price( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->price ;
    }
    return get_post_meta( sumosubs_get_product_id( $product ) , '_price' , true ) ;
}

/**
 * Get Product price excludes tax
 * @param object | int $product The Product post ID
 * @param array $args
 * @return float
 */
function sumosubs_get_price_excluding_tax( $product , $args = array () ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return 0 ;
    }

    $args = wp_parse_args( $args , array (
        'qty'   => 1 ,
        'price' => ''
            ) ) ;

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_price_excluding_tax( $args[ 'qty' ] , $args[ 'price' ] ) ;
    }
    return wc_get_price_excluding_tax( $product , $args ) ;
}

/**
 * Format a sale price for display.
 * @param object | int $product The Product post ID
 * @param string $from
 * @param string $to
 */
function sumosubs_get_sale_price_html_from_to( $product , $from , $to ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_price_html_from_to( $from , $to ) ;
    }
    return wc_format_sale_price( $from , $to ) ;
}

/**
 * Get formatted product name
 * @param object | int $product The Product post ID
 * @return string
 */
function sumosubs_get_formatted_name( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_formatted_name() ;
    }

    if ( $product->get_sku() ) {
        $identifier = $product->get_sku() ;
    } else {
        $identifier = '#' . sumosubs_get_product_id( $product ) ;
    }

    $formatted_attributes = wc_get_formatted_variation( $product , true ) ;
    $extra_data           = ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $product->get_price() ) ;

    return sprintf( __( '%s &ndash; %s%s' , 'woocommerce' ) , $identifier , $product->get_title() , $extra_data ) ;
}

/**
 * Get product downloads
 * @param object | int $product
 * @return array
 */
function sumosubs_get_downloads( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->get_files() ;
    }

    return $product->get_downloads() ;
}

/**
 * Check whether it is downloadable product
 * @param object | int $product
 * @return bool
 */
function sumosubs_is_downloadable( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->downloadable ;
    }

    return $product->is_downloadable() ;
}

/**
 * Check whether it is virtual product
 * @param object | int $product
 * @return bool
 */
function sumosubs_is_virtual( $product ) {

    if ( ! $product = sumosubs_get_product( $product ) ) {
        return '' ;
    }

    if ( sumosubs_is_wc_version( '<' , '3.0' ) ) {
        return $product->virtual ;
    }

    return $product->is_virtual() ;
}
