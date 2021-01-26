<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Check it is a Subscription enabled product.
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_is_subscription_product( $product_id ) {
    $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

    return '1' === $subscription_plan[ 'subscription_status' ] ;
}

/**
 * Check whether Subscription has any Trial.
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id ) ;

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return false ;
    }

    return '1' === $subscription_plan[ 'trial_status' ] ;
}

/**
 * Check whether Subscription has Paid Trial.
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_paid_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id ) ;

    return 'paid' === $subscription_plan[ 'trial_type' ] ;
}

/**
 * Check whether Subscription has Free Trial.
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_free_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id ) ;

    return 'free' === $subscription_plan[ 'trial_type' ] ;
}

/**
 * Check whether Subscription has Signup fee.
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_signup( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id ) ;

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return false ;
    }

    return '1' === $subscription_plan[ 'signup_status' ] ;
}

function sumo_can_purchase_subscription( $product_id, $user_id = 0 ) {

    if ( SUMO_Subscription_Restrictions::is_restriction_available_in_site() && 102 === SUMO_Subscription_Restrictions::get_subscription_limit_code( $product_id, $user_id ) ) {
        return false ;
    }
    return true ;
}

function sumo_can_purchase_subscription_trial( $product_id, $user_id = 0 ) {

    if ( SUMO_Subscription_Restrictions::is_restriction_available_in_site() && 102 === SUMO_Subscription_Restrictions::get_trial_limit_code( $product_id, $user_id ) ) {
        return false ;
    }
    return true ;
}

/**
 * Check cart contains Subscription items.
 * @param bool $check_membership_too
 * @return boolean
 */
function sumo_is_cart_contains_subscription_items( $check_membership_too = false ) {
    if ( ! function_exists( 'WC' ) ) {
        return false ;
    }
    if ( ! isset( WC()->cart->cart_contents ) || 0 === sizeof( WC()->cart->cart_contents ) ) {
        return false ;
    }

    foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
        if ( ! isset( $cart_item[ 'product_id' ] ) ) {
            continue ;
        }

        $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

        //Check cart contains Membership Plan accessible products.
        if ( $check_membership_too ) {
            if (
                    (class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $product_id )) ||
                    sumo_is_subscription_product( $product_id )
            ) {
                //may be Subscription/Membership Plan accessible product.
                return true ;
            }
        } else if ( sumo_is_subscription_product( $product_id ) ) {
            //may be Subscription Product.
            return true ;
        }
    }
    return false ;
}

function sumo_is_my_subscriptions_page() {
    return wc_post_content_has_shortcode( 'sumo_my_subscriptions' ) ;
}

/**
 * Check whether it is Possible to create Next Renewal Order.
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_is_next_renewal_possible( $post_id ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id ) ;

    $is_trial_active          = 'Trial' === get_post_meta( $post_id, 'sumo_get_status', true ) ;
    $installment              = absint( $subscription_plan[ 'subscription_recurring' ] ) ;
    $renewed_count            = sumosubs_get_renewed_count( $post_id ) ;
    $is_next_renewal_possible = true ;

    if ( $installment > 0 && SUMO_Subscription_Synchronization::initial_payment_delayed( $post_id ) ) {
        $installment += 1 ;
    }

    if ( $installment > 0 && ! $is_trial_active && ( (1 === $installment && 1 === $renewed_count) ||
            (sumo_subscription_has_trial( $post_id ) && ($installment === $renewed_count)) ||
            ( ! sumo_subscription_has_trial( $post_id ) && ($installment - 1 == $renewed_count) )
            ) ) {
        $is_next_renewal_possible = false ;
    }

    return $is_next_renewal_possible ;
}

/**
 * Check is Order contains Subscription Item.
 * @param int $order_id The Order post ID
 * @return boolean
 */
function sumo_is_order_contains_subscriptions( $order_id ) {
    if ( ! $order_id ) {
        return false ;
    }

    $bool                     = false ;
    $parent_order_id          = sumosubs_get_parent_order_id( $order_id ) ;
    $customer_id              = sumosubs_get_order_customer_id( $order_id ) ;
    $subscription_order_items = sumo_get_subscription_items_from( $order_id ) ; //Get Subscription Order Items.
    //may be Order Subscription.
    if ( SUMO_Order_Subscription::is_subscribed( 0, $parent_order_id, $customer_id ) ) {
        $bool = true ;
    } else if ( is_array( $subscription_order_items ) && sizeof( $subscription_order_items ) > 0 ) {
        $bool = true ;
    }
    return apply_filters( 'sumosubscriptions_order_has_subscriptions', $bool, $order_id, $parent_order_id ) ;
}

/**
 * Check whether the Addon Amount is applicable in the Parent Order.
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_subscription_has_addon_amount( $post_id ) {
    $order_item_data = get_post_meta( $post_id, 'sumo_subscription_parent_order_item_data', true ) ;

    if ( ! is_array( $order_item_data ) ) {
        return false ;
    }

    foreach ( $order_item_data as $_item_id => $_item ) {
        if ( isset( $_item[ 'addon' ] ) && is_numeric( $_item[ 'addon' ] ) && $_item[ 'addon' ] > 0 ) {
            return true ;
        }
    }
    return false ;
}

/**
 * Check whether Globally Additional Digital Downloads is Enabled.
 * @return boolean
 */
function sumo_is_additional_digital_downloads_enabled_in_the_site() {
    if ( 'yes' === get_option( 'sumo_enable_additional_digital_downloads_option', 'no' ) ) {
        return true ;
    }
    return false ;
}

/**
 * Check whether the Subscription is Published.
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_is_subscription_exists( $post_id ) {
    $posted = get_post( $post_id ) ;

    if ( isset( $posted->post_type ) && 'sumosubscriptions' === $posted->post_type ) {
        return 'publish' === $posted->post_status ;
    }
    return false ;
}

/**
 * Check whether the Subscription has Unpaid renewal order right now
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumosubs_unpaid_renewal_order_exists( $post_id ) {
    $unpaid_renewal_id = get_post_meta( $post_id, 'sumo_get_renewal_id', true ) ;

    if ( empty( $unpaid_renewal_id ) || 'yes' === get_post_meta( $unpaid_renewal_id, 'sumosubs_order_paid', true ) ) {
        return false ;
    }

    if ( $renewal_order = wc_get_order( $unpaid_renewal_id ) ) {
        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return false ;
        }
    }

    return true ;
}

/**
 * Check the currently installed WC version
 * @param string $comparison_opr The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
  This parameter is case-sensitive, values should be lowercase
 * @param string $version
 * @return boolean
 */
function sumosubs_is_wc_version( $comparison_opr, $version ) {

    if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, $version, $comparison_opr ) ) {
        return true ;
    }
    return false ;
}

/**
 * Provide Backward compatibility for Subscription Plan Shortcode.
 * @return boolean
 */
function sumo_is_valid_to_provide_backward_compatibility() {
    $message_option = get_option( 'sumo_subscription_fee_msg_customization' ) ;

    if ( is_array( $message_option ) ) {
        return false ;
    }
    $split_message = explode( ' ', $message_option ) ;

    $period_value_key = array_keys( $split_message, '[sumo_subscription_period_value]' ) ;

    if ( empty( $period_value_key ) ) {
        $period_value_key = array_keys( $split_message, '<b>[sumo_subscription_period_value]</b>' ) ;
    }

    $period_key = array_keys( $split_message, '<b>[sumo_subscription_period]</b>' ) ;

    if ( empty( $period_key ) ) {
        $period_key = array_keys( $split_message, '[sumo_subscription_period]' ) ;
    }

    if ( is_array( $period_key ) && is_array( $period_value_key ) && $period_key && $period_value_key ) {

        return max( $period_value_key ) > max( $period_key ) ;
    }
    return false ;
}

/**
 * Check product contains subscription variations.
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_is_product_contains_subscription_variations( $product_id ) {
    $subscription_variation = sumo_get_available_subscription_variations( $product_id, 1 ) ;
    return ! empty( $subscription_variation ) ;
}

/**
 * Check whether every Subscription is canceled from the Parent Order.
 * @param int $order_id The Parent Order post ID
 * @return boolean
 */
function sumo_is_every_subscription_cancelled_from_parent_order( $order_id ) {

    $subscriptions = sumosubscriptions()->query->get( array(
        'type'       => 'sumosubscriptions',
        'status'     => 'publish',
        'meta_key'   => 'sumo_get_parent_order_id',
        'meta_value' => sumosubs_get_parent_order_id( $order_id ),
            ) ) ;

    if ( empty( $subscriptions ) ) {
        return ;
    }

    $valid_cancelled_statuses = array( 'Cancelled', 'Expired', 'Failed' ) ;

    foreach ( $subscriptions as $subscription_id ) {
        $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true ) ;

        if ( ! in_array( $subscription_status, $valid_cancelled_statuses ) ) {
            return false ;
        }
    }
    return true ;
}

/**
 * Check whether the requested order is the Parent Order
 * @param int $order The Order post ID | Object
 * @return boolean
 */
function sumosubs_is_parent_order( $order ) {
    if ( ! $order_id = sumosubs_get_order_id( $order ) ) {
        return false ;
    }

    return 0 === sumosubs_get_parent_order_id( $order_id, false ) ;
}

/**
 * Check whether the requested order is the Renewal Order
 * @param int $order The Order post ID | Object
 * @return boolean
 */
function sumosubs_is_renewal_order( $order ) {
    if ( ! $order_id = sumosubs_get_order_id( $order ) ) {
        return false ;
    }
    if ( sumosubs_is_parent_order( $order_id ) ) {
        return false ;
    }

    return sumosubs_get_parent_order_id( $order_id, false ) > 0 ? true : false ;
}

/**
 * Check whether preapproval is revoked and it is eligible to proceed Manual Payment
 * @return bool
 */
function sumosubs_is_prepproval_revoked_subscription_eligible_for_manual_pay() {
    return '1' !== get_option( 'sumo_user_cancel_preapprove_key', '1' ) ;
}

/**
 * Check whether failed automatic payment eligible to proceed Manual Payment
 * @return bool
 */
function sumosubs_is_failed_auto_payment_eligible_for_manual_pay() {
    return '1' !== get_option( 'sumo_cancel_automatic_subscription_goes_to', '1' ) ;
}

/**
 * Check whether the Subscription is eligible to perform Pause 
 * @param int $subscription_id
 * @return boolean
 */
function sumosubs_is_subscription_eligible_for_pause( $subscription_id ) {
    $status = get_post_meta( $subscription_id, 'sumo_get_status', true ) ;

    if ( 'Pause' === $status ) {
        return true ;
    }
    if ( ! in_array( $status, array( 'Active', 'Trial' ) ) ) {
        return false ;
    }

    $max_pauses   = absint( get_option( 'sumo_settings_max_no_of_pause', '0' ) ) ;
    $paused_count = absint( get_post_meta( $subscription_id, 'sumo_no_of_pause_count', true ) ) ;

    if ( ! $max_pauses || ($paused_count < $max_pauses) ) {
        $subscriber_id       = get_post_meta( $subscription_id, 'sumo_get_user_id', true ) ;
        $user_limit_by       = get_option( 'sumo_subscription_pause_by_user_or_userrole_filter' ) ;
        $filtered_users      = ( array ) get_option( 'sumo_subscription_pause_by_user_filter', array() ) ;
        $filtered_user_roles = ( array ) get_option( 'sumo_subscription_pause_by_userrole_filter', array() ) ;

        if ( sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                    'limit_by'            => $user_limit_by,
                    'filtered_users'      => $filtered_users,
                    'filtered_user_roles' => $filtered_user_roles
                ) )
        ) {
            return true ;
        }
    }
    return false ;
}

/**
 * Check whether the Subscription is eligible to perform Cancel 
 * @param int $subscription_id
 * @return boolean
 */
function sumosubs_is_subscription_eligible_for_cancel( $subscription_id ) {

    if ( in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), array( 'Cancelled', 'Expired', 'Failed' ) ) ) {
        return false ;
    }

    $user_limit_by                   = get_option( 'sumo_subscription_cancel_by_user_or_userrole_filter' ) ;
    $product_r_cat_limit_by          = get_option( 'sumo_subscription_cancel_by_product_or_category_filter' ) ;
    $filtered_users                  = ( array ) get_option( 'sumo_subscription_cancel_by_user_filter', array() ) ;
    $filtered_user_roles             = ( array ) get_option( 'sumo_subscription_cancel_by_userrole_filter', array() ) ;
    $filtered_products               = ( array ) get_option( 'sumo_subscription_cancel_by_product_filter', array() ) ;
    $filtered_product_category_terms = ( array ) get_option( 'sumo_subscription_cancel_by_category_filter', array() ) ;

    $subscriber_id     = get_post_meta( $subscription_id, 'sumo_get_user_id', true ) ;
    $subscription_plan = sumo_get_subscription_plan( $subscription_id ) ;

    if ( SUMO_Order_Subscription::is_subscribed( $subscription_id ) && sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                'limit_by'            => $user_limit_by,
                'filtered_users'      => $filtered_users,
                'filtered_user_roles' => $filtered_user_roles
            ) )
    ) {
        return true ;
    } else {
        if ( ! sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                    'limit_by'            => $user_limit_by,
                    'filtered_users'      => $filtered_users,
                    'filtered_user_roles' => $filtered_user_roles
                ) )
        ) {
            return false ;
        }
        if ( is_numeric( $subscription_plan[ 'variable_product_id' ] ) && $subscription_plan[ 'variable_product_id' ] ) {
            if (
                    sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'variable_product_id' ], array(
                        'limit_by'                        => $product_r_cat_limit_by,
                        'filtered_products'               => $filtered_products,
                        'filtered_product_category_terms' => $filtered_product_category_terms
                    ) ) ||
                    sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'subscription_product_id' ], array(
                        'limit_by'                        => $product_r_cat_limit_by,
                        'filtered_products'               => $filtered_products,
                        'filtered_product_category_terms' => $filtered_product_category_terms
                    ) )
            ) {
                return true ;
            }
        } else if ( sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'subscription_product_id' ], array(
                    'limit_by'                        => $product_r_cat_limit_by,
                    'filtered_products'               => $filtered_products,
                    'filtered_product_category_terms' => $filtered_product_category_terms
                ) )
        ) {
            return true ;
        }
    }

    return false ;
}

/**
 * Find the Subscriber limit set by Admin and check whether the User is eligible to perform some actions 
 * @param object $user
 * @param array $args
 * @return boolean
 */
function sumosubs_is_subscriber_eligible_for_some_actions( $user, $args = array() ) {
    $user_id = 0 ;

    $args = wp_parse_args( $args, array(
        'limit_by'            => '',
        'filtered_users'      => array(),
        'filtered_user_roles' => array()
            ) ) ;

    if ( is_numeric( $user ) && $user ) {
        $user_id = $user ;
    } else if ( isset( $user->ID ) ) {
        $user_id = $user->ID ;
    }

    if ( ! $user = get_user_by( 'id', $user_id ) ) {
        return false ;
    }

    switch ( $args[ 'limit_by' ] ) {
        case 'all_users':
            return true ;
        case 'included_users':
            if ( in_array( $user->ID, $args[ 'filtered_users' ] ) ) {
                return true ;
            }
            break ;
        case 'excluded_users':
            if ( ! in_array( $user->ID, $args[ 'filtered_users' ] ) ) {
                return true ;
            }
            break ;
        case 'included_user_role':
            if ( isset( $user->roles[ 0 ] ) && in_array( $user->roles[ 0 ], $args[ 'filtered_user_roles' ] ) ) {
                return true ;
            }
            break ;
        case 'excluded_user_role':
            if ( isset( $user->roles[ 0 ] ) && ! in_array( $user->roles[ 0 ], $args[ 'filtered_user_roles' ] ) ) {
                return true ;
            }
            break ;
    }
    return false ;
}

/**
 * Find the Subscription Product/Category limit set by Admin and check whether the Product/Category is eligible to perform some actions 
 * @param object $product
 * @param array $args
 * @return boolean
 */
function sumosubs_is_product_eligible_for_some_actions( $product, $args = array() ) {

    if ( ! $product_id = sumosubs_get_product_id( $product ) ) {
        return false ;
    }

    $args = wp_parse_args( $args, array(
        'limit_by'                        => '',
        'filtered_products'               => array(),
        'filtered_product_category_terms' => array()
            ) ) ;

    switch ( $args[ 'limit_by' ] ) {
        case 'all_products':
            return true ;
        case 'included_products':
            if ( in_array( $product_id, $args[ 'filtered_products' ] ) ) {
                return true ;
            }
            break ;
        case 'excluded_products':
            if ( ! in_array( $product_id, $args[ 'filtered_products' ] ) ) {
                return true ;
            }
            break ;
        case 'all_categories':
            $product_cat = get_the_terms( $product_id, 'product_cat', true ) ;

            if ( is_array( $product_cat ) && ! empty( $product_cat ) ) {
                return true ;
            }
            break ;
        case 'included_categories':
            $product_category_terms = array() ;
            $product_categories     = get_the_terms( $product_id, 'product_cat' ) ;

            if ( is_array( $product_categories ) && ! empty( $product_categories ) ) {
                foreach ( $product_categories as $product_category ) {
                    $product_category_terms[] = $product_category->term_id ;
                }
            }

            foreach ( $product_category_terms as $term_id ) {
                if ( in_array( $term_id, $args[ 'filtered_product_category_terms' ] ) ) {
                    return true ;
                }
            }
            break ;
        case 'excluded_categories':
            $product_category_terms = array() ;
            $product_categories     = get_the_terms( $product_id, 'product_cat' ) ;

            if ( is_array( $product_categories ) && ! empty( $product_categories ) ) {
                foreach ( $product_categories as $product_category ) {
                    $product_category_terms[] = $product_category->term_id ;
                }
            }

            foreach ( $product_category_terms as $term_id ) {
                if ( ! in_array( $term_id, $args[ 'filtered_product_category_terms' ] ) ) {
                    return true ;
                }
            }
            break ;
    }
    return false ;
}

/**
 * Check whether the current viewing post as SUMOSubscriptions post type
 * @return boolean
 */
function is_sumosubscriptions_post_type() {

    if ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'edit' ) {
        return false ;
    }
    if ( isset( $_REQUEST[ 'page' ] ) ) {
        return false ;
    }

    if ( 'sumosubscriptions' === get_post_type() ) {
        return true ;
    } else if ( isset( $_REQUEST[ 'post_type' ] ) && $_REQUEST[ 'post_type' ] === 'sumosubscriptions' ) {
        return true ;
    }
    return false ;
}

/**
 * Check whether the user can purchase as Subscription product
 * @param int $subscription_product_id
 * @param int $customer_id
 * @return boolean
 */
function sumo_can_user_purchase_as_subscription( $subscription_product_id, $customer_id = 0 ) {
    $defined_rules = ( array ) get_option( 'sumo_subscription_as_regular_product_defined_rules', array() ) ;

    if ( $user = get_user_by( 'id', $customer_id ) ) {
        $userroles = $user->roles ;
    } else {
        $userroles = empty( wp_get_current_user()->roles ) ? array( 'guest' ) : wp_get_current_user()->roles ;
    }

    foreach ( $defined_rules as $rule ) {
        if ( ! isset( $rule[ 'selected_subscription' ] ) || ! is_array( $rule[ 'selected_subscription' ] ) ) {
            continue ;
        }

        if ( in_array( $subscription_product_id, $rule[ 'selected_subscription' ] ) ) {
            if ( ! isset( $rule[ 'selected_userrole' ] ) || ! is_array( $rule[ 'selected_userrole' ] ) ) {
                continue ;
            }

            foreach ( $userroles as $role ) {
                if ( in_array( $role, $rule[ 'selected_userrole' ] ) ) {
                    return false ;
                }
            }
        }
    }
    return true ;
}

/**
 * Check whether the subscription with pending status is awaiting Admin approval to activate the Free trial
 * @param int $subscription_id
 * @param string $parent_order_status
 * @return boolean
 */
function sumosubs_free_trial_awaiting_admin_approval( $subscription_id, $parent_order_status = '' ) {

    $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true ) ;
    $parent_order_status = '' === $parent_order_status ? sumosubs_get_order_status( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) ) : $parent_order_status ;

    if ( 'Pending' === $subscription_status && in_array( $parent_order_status, array( 'processing', 'completed' ) ) ) {
        $awaiting_status        = get_post_meta( $subscription_id, 'sumo_subscription_awaiting_status', true ) ;
        $activate_free_trial_by = get_post_meta( $subscription_id, 'sumosubs_activate_free_trial_by', true ) ;

        if ( 'free-trial' === $awaiting_status && 'admin_approval' === $activate_free_trial_by ) {
            return true ;
        }
    }
    return false ;
}

/**
 * Check whether the subscription with pending status is awaiting Admin approval to activate the Subscription.
 * 
 * @param int $subscription_id
 * @return boolean
 */
function sumo_subscription_awaiting_admin_approval( $subscription_id ) {
    $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true ) ;
    $parent_order_status = sumosubs_get_order_status( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) ) ;

    if ( 'Pending' === $subscription_status && in_array( $parent_order_status, array( 'processing', 'completed' ) ) && ! SUMO_Subscription_Synchronization::is_subscription_synced( $subscription_id ) ) {
        $awaiting_status = get_post_meta( $subscription_id, 'sumo_subscription_awaiting_status', true ) ;
        $activate_by     = get_post_meta( $subscription_id, 'sumosubs_activate_subscription_by', true ) ;

        if ( in_array( $awaiting_status, array( 'Pending', 'Active' ) ) && 'admin_approval' === $activate_by ) {
            return true ;
        }
    }

    return false ;
}

function sumosubs_recurring_fee_has_changed( $subscription_id ) {
    return is_numeric( get_post_meta( $subscription_id, 'sumo_get_updated_renewal_fee', true ) ) ;
}

function sumo_subscription_is_switching( $product_id ) {
    if (
            SUMO_Subscription_Upgrade_or_Downgrade::is_switcher_page() ||
            SUMO_Subscription_Upgrade_or_Downgrade::is_subscription_switched( $product_id ) ||
            ( doing_action( 'wp_loaded' ) && 'woocommerce_is_purchasable' === current_filter() && get_transient( 'sumo_subscription_switching_into_cart' ) )//Should be useful when cart session is loaded in wp_loaded hook
    ) {
        return true ;
    }
    return false ;
}
