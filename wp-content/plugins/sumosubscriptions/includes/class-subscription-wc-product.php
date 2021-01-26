<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle WC Product
 * 
 * @class SUMO_Subscription_WC_Product
 * @category Class
 */
class SUMO_Subscription_WC_Product {

    public $product ;

    public function __construct( $product ) {
        if ( is_numeric( $product ) ) {
            $this->product    = wc_get_product( $product ) ;
            $this->product_id = absint( $product ) ;
        } elseif ( $product instanceof WC_Product ) {
            $this->product    = $product ;
            $this->product_id = $this->get_id() ;
        }
    }

    public function get_id() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            if ( in_array( $this->get_type() , array ( 'variation' , 'variable' ) ) ) {
                return $this->product->variation_id ;
            } else {
                return $this->product->id ;
            }
        }
        return $this->product->get_id() ;
    }

    public function get_parent_id() {
        return wp_get_post_parent_id( $this->get_id() ) ;
    }

    public function get_prop( $context = '' ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            if ( isset( $this->product->$context ) ) {
                return $this->product->$context ;
            } else if ( isset( $this->product->{"product_{$context}"} ) ) {
                return $this->product->{"product_{$context}"} ;
            }
        } else {
            $method = "get_{$context}" ;
            if ( is_callable( array ( $this->product , $method ) ) ) {
                return $this->product->{$method}() ;
            }
        }
        return false ;
    }

    public function get_type() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->product_type ;
        }
        return $this->product->get_type() ;
    }

    public function get_price() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->price ;
        }
        return $this->product->get_price() ;
    }

    public function get_price_excluding_tax( $args = array () ) {
        $args = wp_parse_args( $args , array (
            'qty'   => 1 ,
            'price' => ''
                ) ) ;

        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->get_price_excluding_tax( $args[ 'qty' ] , $args[ 'price' ] ) ;
        }
        return wc_get_price_excluding_tax( $this->product , $args ) ;
    }

    public function get_sale_price_html_from_to( $from , $to ) {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->get_price_html_from_to( $from , $to ) ;
        }
        return wc_format_sale_price( $from , $to ) ;
    }

    public function get_formatted_name() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->get_formatted_name() ;
        }

        if ( $this->product->get_sku() ) {
            $identifier = $this->product->get_sku() ;
        } else {
            $identifier = '#' . $this->get_id() ;
        }

        $formatted_attributes = wc_get_formatted_variation( $this->product , true ) ;
        $extra_data           = ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $this->product->get_price() ) ;

        return sprintf( __( '%s &ndash; %s%s' , 'woocommerce' ) , $identifier , $this->product->get_title() , $extra_data ) ;
    }

    public function get_downloads() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->get_files() ;
        }
        return $this->product->get_downloads() ;
    }

    public function is_version( $comparison_opr , $version ) {
        return sumosubs_is_wc_version( $comparison_opr , $version ) ;
    }

    public function is_downloadable() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->downloadable ;
        }
        return $this->product->is_downloadable() ;
    }

    public function is_virtual() {
        if ( $this->is_version( '<' , '3.0' ) ) {
            return $this->product->virtual ;
        }
        return $this->product->is_virtual() ;
    }

    public function exists() {
        return $this->product ? true : false ;
    }

}
