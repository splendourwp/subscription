<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Restrict Download Access Part for Digital Product along with Subscription.
 * 
 * @class SUMOSubscriptions_Downloadable_Restrictions
 * @category Class
 */
class SUMOSubscriptions_Downloadable_Restrictions {

    /**
     * Init SUMOSubscriptions_Downloadable_Restrictions.
     */
    public static function init() {
        add_filter( 'woocommerce_customer_get_downloadable_products' , __CLASS__ . '::restrict_download_access_in_myaccount' ) ;
        add_action( 'woocommerce_download_product' , __CLASS__ . '::restrict_link_to_download' , 10 , 6 ) ;
        add_action( 'woocommerce_process_product_file_download_paths' , __CLASS__ . '::drip_content_download' , 999 , 3 ) ;
    }

    /**
     * Get Subscription Parent Order ID
     * @param int $subscription_id
     * @param int $order_id
     * @return int
     */
    public static function get_parent_order_id( $subscription_id = 0 , $order_id = 0 ) {
        $parent_order_id = sumosubs_get_parent_order_id( $order_id ) ;

        if( $subscription_id ) {
            $parent_order_id = absint( get_post_meta( $subscription_id , 'sumo_get_parent_order_id' , true ) ) ;
        }

        return $parent_order_id ;
    }

    /**
     * Get Subscription post ID
     * @param int $order_id
     * @param int $product_id
     * @return int
     */
    public static function get_subscription_id( $order_id , $product_id ) {
        $parent_order_id = self::get_parent_order_id( 0 , $order_id ) ;
        $subscriptions   = get_post_meta( $parent_order_id , 'sumo_subsc_get_available_postids_from_parent_order' , true ) ;

        if( isset( $subscriptions[ $product_id ] ) ) {
            return absint( $subscriptions[ $product_id ] ) ;
        }
        return 0 ;
    }

    /**
     * Check subscription is valid.
     * @param int $order_id The Order post ID.
     * @param int $product_id The Product post ID.
     * @return boolean
     */
    public static function is_subscription_valid( $subscription_id ) {

        if( ! $subscription_id ) {
            return false ;
        }

        // returns the Status of Subscription
        $subscription_status = get_post_meta( $subscription_id , 'sumo_get_status' , true ) ;

        if( in_array( $subscription_status , array(
                    'Pending' ,
                    'Pending_Cancellation' ,
                    'Trial' ,
                    'Active' ,
                    'Overdue' ,
                    'Pending_Authorization' ,
                ) )
        ) {
            return true ;
        }
        return false ;
    }

    /**
     * Check whether the Order has digitally downloadable product associated with the Subscription.
     * @param int $order_id The Order post ID.
     * @param int $product_id The Product/Variation ID.
     * @return boolean
     */
    public static function order_has_additional_digital_downloadable_products( $order_id , $product_id ) {

        if( sumosubs_is_parent_order( $order_id ) ) {
            $subscriptions = get_post_meta( $order_id , 'sumo_subsc_get_available_postids_from_parent_order' , true ) ;

            if( ! is_array( $subscriptions ) || empty( $subscriptions ) ) {
                return false ;
            }

            foreach( $subscriptions as $subscription_product_id => $subscription_id ) {
                $downloadable_product_ids = sumo_get_additional_digital_downloadable_products( $subscription_id ) ;

                if( ! self::is_subscription_valid( $subscription_id ) ) {
                    continue ;
                }

                foreach( $downloadable_product_ids as $downloadable_product ) {
                    if( $downloadable_product == $product_id ) {
                        return true ;
                    }
                }
            }
        }
        return false ;
    }

    /**
     * Check whether the downloadable file is Granted access to any newly added files on any existing.
     * @global object $wpdb
     * @param int $subscription_id
     * @param int $subscription_parent_order_id
     * @param int $product_id
     * @param string $download_id
     * @return boolean
     */
    public static function is_permission_granted_to_drip_download( $subscription_id , $subscription_parent_order_id , $product_id , $download_id ) {
        global $wpdb ;

        if( 'yes' === get_option( 'sumo_enable_content_dripping' ) || ! self::is_subscription_valid( $subscription_id ) ) {
            return false ;
        }

        // grant permission if it doesn't already exist
        $can_grant_permission = ! $wpdb->get_var( $wpdb->prepare( "SELECT 1=1 FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE order_id = %d AND product_id = %d AND download_id = %s" , $subscription_parent_order_id , $product_id , $download_id ) ) ;
        return $can_grant_permission ;
    }

    /**
     * Restrict download access in myaccount.
     * @param array $downloads
     * @return array
     */
    public static function restrict_download_access_in_myaccount( $downloads ) {
        $subscription_downloads = array() ;

        if( ! is_array( $downloads ) ) {
            return $downloads ;
        }

        foreach( $downloads as $key => $value ) {
            if( ! isset( $value[ 'download_id' ] ) ) {
                continue ;
            }

            if( ! sumo_is_order_contains_subscriptions( $value[ 'order_id' ] ) ) {
                continue ;
            }
            if( self::order_has_additional_digital_downloadable_products( $value[ 'order_id' ] , $value[ 'product_id' ] ) ) {
                continue ;
            }

            if( $subscription_id = self::get_subscription_id( $value[ 'order_id' ] , $value[ 'product_id' ] ) ) {

                if( ! self::is_subscription_valid( $subscription_id ) ) {
                    unset( $downloads[ $key ] ) ;
                }
                //may be prevent download file duplication
                if( in_array( $value[ 'product_id' ] , array_keys( $subscription_downloads ) ) && in_array( $value[ 'download_id' ] , $subscription_downloads ) ) {
                    unset( $downloads[ $key ] ) ;
                }
                $subscription_downloads[ $value[ 'product_id' ] ] = $value[ 'download_id' ] ;
            }
        }
        return $downloads ;
    }

    /**
     * Restrict link to download.
     * @param string $user_email
     * @param string $order_key
     * @param int $product_id The Product post ID.
     * @param int $user_id The User post ID.
     * @param int $download_id 
     * @param int $order_id The Order post ID.
     */
    public static function restrict_link_to_download( $user_email , $order_key , $product_id , $user_id , $download_id , $order_id ) {
        if( ! sumo_is_order_contains_subscriptions( $order_id ) ) {
            return ;
        }
        if( self::order_has_additional_digital_downloadable_products( $order_id , $product_id ) ) {
            return ;
        }

        if( $subscription_id = self::get_subscription_id( $order_id , $product_id ) ) {
            if( ! self::is_subscription_valid( $subscription_id ) ) {
                wc_add_notice( __( 'Sorry you cannot have the download access!!' , 'sumosubscriptions' ) , 'error' ) ;

                if( $myaccount = get_option( 'woocommerce_myaccount_page_id' ) ) {
                    wp_safe_redirect( get_permalink( $myaccount ) ) ;
                    exit() ;
                }
            }
        }
    }

    /**
     * Grant downloadable file access to any newly added files on any existing.
     * orders for this product that have previously been granted downloadable file access.
     * Here granting access only Active Subscription products 
     * 
     * @global object $wpdb
     * @param int $product_id
     * @param int $variation_id
     * @param array $downloadable_files
     */
    public static function drip_content_download( $product_id , $variation_id , $downloadable_files ) {
        global $wpdb ;

        if( sumosubs_is_wc_version( '>=' , '3.0' ) && 'yes' === get_option( 'sumo_enable_content_dripping' ) ) {
            return ;
        }
        if( $variation_id ) {
            $product_id = $variation_id ;
        }

        $product               = wc_get_product( $product_id ) ;
        $existing_download_ids = array_keys( ( array ) sumosubs_get_downloads( $product ) ) ;
        $updated_download_ids  = array_keys( ( array ) $downloadable_files ) ;
        $new_download_ids      = array_filter( array_diff( $updated_download_ids , $existing_download_ids ) ) ;

        $filtered_subscription_data = $wpdb->get_results( "SELECT DISTINCT `{$wpdb->prefix}postmeta`.`meta_value`
                                          FROM
                                            `{$wpdb->prefix}woocommerce_downloadable_product_permissions`
                                          INNER JOIN
                                            `{$wpdb->prefix}postmeta`
                                          ON
                                            `{$wpdb->prefix}woocommerce_downloadable_product_permissions`.order_id = `{$wpdb->prefix}postmeta`.post_id
                                          WHERE
                                            `{$wpdb->prefix}woocommerce_downloadable_product_permissions`.`product_id` = $product_id
                                            AND 
                                            `{$wpdb->prefix}postmeta`.`meta_key` = 'sumo_subsc_get_available_postids_from_parent_order' " , ARRAY_A ) ;

        if( ! is_array( $filtered_subscription_data ) || empty( $filtered_subscription_data ) ) {
            return ;
        }

        foreach( $filtered_subscription_data as $subscription_data ) {
            if( ! isset( $subscription_data[ 'meta_value' ] ) ) {
                continue ;
            }
            $subscriptions = maybe_unserialize( $subscription_data[ 'meta_value' ] ) ;

            if( ! is_array( $subscriptions ) || empty( $subscriptions ) ) {
                continue ;
            }

            foreach( $subscriptions as $subscription_product_id => $subscription_id ) {
                $subscription_parent_order_id = self::get_parent_order_id( $subscription_id ) ;

                if( ! $order = wc_get_order( $subscription_parent_order_id ) ) {
                    continue ;
                }
                if( $new_download_ids ) {
                    foreach( $new_download_ids as $download_id ) {
                        // remove permission 
                        if( sumosubs_is_wc_version( '<' , '3.0' ) && ('yes' === get_option( 'sumo_enable_content_dripping' ) || ! self::is_subscription_valid( $subscription_id )) ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE order_id = %d AND product_id = %d AND download_id = %s" , $subscription_parent_order_id , $product_id , $download_id ) ) ;
                        }
                        // grant permission if it doesn't already exist
                        if( sumosubs_is_wc_version( '>=' , '3.0' ) && self::is_permission_granted_to_drip_download( $subscription_id , $subscription_parent_order_id , $product_id , $download_id ) ) {
                            wc_downloadable_file_permission( $download_id , $product_id , $order ) ;
                        }
                    }
                }
                //add a permission
                if( $existing_download_ids ) {
                    foreach( $existing_download_ids as $download_id ) {
                        // grant permission if it doesn't already exist
                        if( self::is_permission_granted_to_drip_download( $subscription_id , $subscription_parent_order_id , $product_id , $download_id ) ) {
                            wc_downloadable_file_permission( $download_id , $product_id , $order ) ;
                        }
                    }
                }
            }
        }
    }

}

SUMOSubscriptions_Downloadable_Restrictions::init() ;
