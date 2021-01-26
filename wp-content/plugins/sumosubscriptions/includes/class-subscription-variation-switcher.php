<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription variation switcher.
 * 
 * @class SUMO_Subscription_Variation_Switcher
 * @category Class
 */
class SUMO_Subscription_Variation_Switcher {

    /**
     * Get Product Variations of Subscription placed Plan matched with Subscription Product Variation Plan. 
     * Provide $subscription_id and $product_id and check whether those Subscription plans matched or not.
     * @param int $subscription_id The Subscription post ID
     * @param int $product_id The Product post ID
     * @param array $selected_attributes
     * @return array
     */
    public static function get_subscription_plan_matched_variations( $subscription_id , $product_id , $selected_attributes = array () ) {
        $matched_variations = array () ;

        $_product                = wc_get_product( $product_id ) ;
        $subscription_variations = sumo_get_available_subscription_variations( $product_id ) ;
        $subscription_plan       = sumo_get_subscription_plan( $subscription_id , 0 , 0 , false ) ;
        $order_item_meta_data    = sumo_get_order_item_meta( get_post_meta( $subscription_id , 'sumo_get_parent_order_id' , true ) , 'item_attr' ) ;

        $saved_variation_id = $subscription_plan[ 'subscription_product_id' ] ? $subscription_plan[ 'subscription_product_id' ] : '' ;

        unset( $subscription_plan[ 'subscription_product_id' ] ) ;
        unset( $subscription_plan[ 'subscription_product_qty' ] ) ;
        unset( $subscription_plan[ 'subscription_order_item_fee' ] ) ;
        unset( $subscription_plan[ 'variable_product_id' ] ) ;

        if ( ! is_array( $subscription_variations ) || ! is_object( $_product ) || 'variable' !== sumosubs_get_product_type( $_product ) ) {
            return $matched_variations ;
        }

        foreach ( $_product->get_variation_attributes() as $key => $options ) {
            if ( is_array( $selected_attributes ) && ! empty( $selected_attributes ) ) {
                foreach ( $selected_attributes as $attribute_key => $attribute_value ) {
                    if ( in_array( $attribute_value , $options ) ) {
                        array_unshift( $options , $attribute_value ) ;
                    }
                }
            }

            $product_attributes[ sanitize_title( "attribute_$key" ) ] = array_unique( $options ) ;
        }

        foreach ( $subscription_variations as $variation_id ) {
            $_variation = wc_get_product( $variation_id ) ;

            if ( ! sumo_is_subscription_product( $variation_id ) || ! is_object( $_variation ) ) {
                continue ;
            }
            if ( ! $_variation->is_in_stock() ) {
                continue ;
            }

            $subscription_variation_plan = sumo_get_subscription_plan( 0 , $variation_id ) ;

            unset( $subscription_variation_plan[ 'subscription_product_id' ] ) ;
            unset( $subscription_variation_plan[ 'subscription_product_qty' ] ) ;
            unset( $subscription_variation_plan[ 'subscription_order_item_fee' ] ) ;
            unset( $subscription_variation_plan[ 'variable_product_id' ] ) ;

            if ( $subscription_plan == $subscription_variation_plan && $saved_variation_id && ($saved_variation_id != $variation_id) ) {
                $variation_data          = $_variation->get_variation_attributes() ;
                $is_variation_data_valid = true ;

                foreach ( $product_attributes as $attribute_key => $attribute_value ) {
                    if ( isset( $variation_data[ $attribute_key ] ) && '' == $variation_data[ $attribute_key ] ) {
                        $variation_data[ $attribute_key ] = $attribute_value ;
                    }
                }
                foreach ( $order_item_meta_data as $item_id => $item_data ) {
                    if ( is_array( $item_data ) && array_values( $item_data ) == array_values( $variation_data ) ) {
                        $is_variation_data_valid = false ;
                    }
                }

                if ( ! $is_variation_data_valid ) {
                    continue ;
                }
                $matched_variations[ $variation_id ] = $variation_data ;
            }
        }

        return $matched_variations ;
    }

    /**
     * Get Matched Variation Data based upon Admin/User selection.
     * @param int $subscription_id The Subscription post ID
     * @param array $selected_attributes
     * @param boolean $get_as_id
     * @return array
     */
    public static function get_matched_variation( $subscription_id , $selected_attributes = array () , $get_as_id = false ) {
        $matched_variation_data  = array () ;
        $filtered_variation_data = array () ;

        $subscription_plan               = sumo_get_subscription_plan( $subscription_id ) ;
        $plan_matched_product_variations = self::get_subscription_plan_matched_variations( $subscription_id , $subscription_plan[ 'variable_product_id' ] , $selected_attributes ) ;

        foreach ( $plan_matched_product_variations as $variation_id => $data ) {
            foreach ( $selected_attributes as $selected_attribute_key => $selected_attribute_value ) {

                if ( isset( $data[ $selected_attribute_key ] ) && (
                        (is_array( $data[ $selected_attribute_key ] ) && in_array( $selected_attribute_value , $data[ $selected_attribute_key ] )) ||
                        ( $data[ $selected_attribute_key ] == $selected_attribute_value) )
                ) {
                    $filtered_variation_data[ $variation_id ][] = $selected_attribute_value ;
                }
            }
        }

        foreach ( $filtered_variation_data as $filtered_variation_id => $filtered_data_value ) {
            if ( ! is_array( $filtered_data_value ) ) {
                continue ;
            }
            $diff = array_diff( array_values( $selected_attributes ) , $filtered_data_value ) ;

            if ( empty( $diff ) ) {
                $data                     = isset( $plan_matched_product_variations[ $filtered_variation_id ] ) ? $plan_matched_product_variations[ $filtered_variation_id ] : '' ;
                $matched_variation_data[] = $get_as_id && $data ? $filtered_variation_id : $data ;
            }
        }
        return $matched_variation_data ;
    }

    /**
     * Display Variation Switch Fields in Admin page and My Account page.
     * @param int $subscription_id The Subscription post ID
     * @return string
     */
    public static function display( $subscription_id ) {
        $subscription_plan = sumo_get_subscription_plan( $subscription_id ) ;

        if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
            return '' ;
        }

        $_product                        = wc_get_product( $subscription_plan[ 'variable_product_id' ] ) ;
        $plan_matched_product_variations = self::get_subscription_plan_matched_variations( $subscription_id , $subscription_plan[ 'variable_product_id' ] ) ;

        ob_start() ;

        //Display only for Active Subscription.
        if ( 'Active' === get_post_meta( $subscription_id , 'sumo_get_status' , true ) && $plan_matched_product_variations ) {
            $plan_matched_attributes     = array () ;
            $filtered_attributes_value   = array () ;
            $plan_matched_attributes_key = array () ;

            foreach ( $_product->get_variation_attributes() as $key => $options ) {
                foreach ( $plan_matched_product_variations as $variation_id => $variation_attributes ) {
                    foreach ( $variation_attributes as $attribute_name => $attribute_value ) {

                        if ( sanitize_title( 'attribute_' . $key ) != sanitize_title( $attribute_name ) ) {
                            continue ;
                        }

                        if ( is_array( $attribute_value ) && ! empty( $attribute_value ) ) {
                            foreach ( $attribute_value as $each_option ) {
                                if ( ! in_array( $each_option , $filtered_attributes_value ) ) {
                                    $filtered_attributes_value[] = $each_option ;
                                }
                            }
                        } else {
                            $filtered_attributes_value[] = $attribute_value ;
                        }
                    }
                }
                $plan_matched_attributes[ sanitize_title( $key ) ] = array_unique( $filtered_attributes_value ) ;
                $filtered_attributes_value                         = array () ;
            }

            foreach ( $plan_matched_attributes as $attribute_name => $attribute_values ) {
                if ( ! empty( $attribute_name ) ) {
                    $plan_matched_attributes_key[] = sanitize_title( 'attribute_' . $attribute_name ) ;
                }
            }

            if ( $plan_matched_attributes ) {
                ?>
                <div class="sumo_subscription_variation_switcher">
                    <a class="button variation_switch_button" id="variation_switch_button_<?php echo $subscription_id ; ?>" href="javascript:void(0)" data-post_id="<?php echo $subscription_id ; ?>"><?php _e( 'Switch Variation' , 'sumosubscriptions' ) ; ?></a>
                    <?php
                    foreach ( $plan_matched_attributes as $attribute_name => $attribute_values ) {
                        if ( $attribute_values ) {
                            ?>
                            <select class="variation_attribute_switch_selector variation_attribute_switch_selector_<?php echo $subscription_id ; ?>" id="variation_attribute_switch_selector_<?php echo sanitize_title( 'attribute_' . $attribute_name ) ; ?>_<?php echo $subscription_id ; ?>"  data-post_id="<?php echo $subscription_id ; ?>" data-selected_attribute_key="<?php echo sanitize_title( 'attribute_' . $attribute_name ) ; ?>" data-plan_matched_attributes_key="<?php echo htmlspecialchars( json_encode( $plan_matched_attributes_key ) , ENT_QUOTES , 'UTF-8' ) ; ?>" style="display: none">
                                <option value="<?php echo $attribute_name ; ?>"><?php echo __( "Select $attribute_name .." , 'sumosubscriptions' ) ; ?></option>
                                <?php foreach ( $attribute_values as $attribute_value ) { ?>
                                    <option value="<?php echo $attribute_value ; ?>"><?php echo $attribute_value ; ?></option>
                                <?php } ?>
                            </select>
                            <?php
                        }
                    }
                    ?>
                    <img id="load_variation_attributes_<?php echo $subscription_id ; ?>" src="<?php echo SUMO_SUBSCRIPTIONS_PLUGIN_URL . '/assets/images/update.gif' ; ?>" data-post_id="<?php echo $subscription_id ; ?>" style="display: none;width:20px;height:20px;"/>
                    <a class="reset_variation_switch" id="reset_variation_switch_<?php echo $subscription_id ; ?>" href="javascript:void(0)" data-post_id="<?php echo $subscription_id ; ?>" data-plan_matched_attributes_key="<?php echo htmlspecialchars( json_encode( $plan_matched_attributes_key ) , ENT_QUOTES , 'UTF-8' ) ; ?>" data-plan_matched_attributes="<?php echo htmlspecialchars( json_encode( $plan_matched_attributes ) , ENT_QUOTES , 'UTF-8' ) ; ?>" style="display: none"><?php _e( 'Clear' , 'sumosubscriptions' ) ; ?></a><br>
                    <a class="button variation_switch_submit" id="variation_switch_submit_<?php echo $subscription_id ; ?>" href="javascript:void(0)" data-post_id="<?php echo $subscription_id ; ?>" data-plan_matched_attributes_key="<?php echo htmlspecialchars( json_encode( $plan_matched_attributes_key ) , ENT_QUOTES , 'UTF-8' ) ; ?>" style="display: none"><?php _e( 'Submit' , 'sumosubscriptions' ) ; ?></a>
                </div>
                <?php
            }
        }

        return apply_filters( 'sumosubscriptions_display_variation_switch_fields' , ob_get_clean() , $subscription_id ) ;
    }

}
