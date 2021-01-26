<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle subscription order
 * 
 * @class SUMOSubscriptions_Order
 * @category Class
 */
class SUMOSubscriptions_Order {

    /**
     * SUMOSubscriptions_Order constructor.
     */
    public function __construct() {
        //Process Subscriptions depends on User's Checkout the Order.
        add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::save_customer_checkout_information', 10, 2 ) ;
        add_action( 'woocommerce_before_pay_action', __CLASS__ . '::save_customer_checkout_information', 10, 1 ) ;

        //Create New Subscriptions from Parent Order
        add_action( 'woocommerce_order_status_changed', __CLASS__ . '::create_new_subscriptions', 8, 3 ) ;
        //Manage each Subscription data based upon Order Status.
        add_action( 'woocommerce_order_status_changed', __CLASS__ . '::update_subscriptions', 10, 3 ) ;
        add_action( 'woocommerce_order_status_changed', __CLASS__ . '::send_subscription_email', 11, 3 ) ;
        add_filter( 'woocommerce_order_needs_payment', __CLASS__ . '::order_needs_payment', 10, 2 ) ;
    }

    /**
     * Save Customer checkout information
     * @param int $order_id The Order post ID
     * @param array $posted
     */
    public static function save_customer_checkout_information( $order_id, $posted = array() ) {
        if ( ! $order = wc_get_order( $order_id ) ) {
            return ;
        }

        //Check it is valid Subscription Order.
        if ( ! sumo_is_order_contains_subscriptions( $order_id ) ) {
            return ;
        }

        //may be it is Parent Order.
        if ( isset( $posted[ 'payment_method' ] ) ) {
            $payment_method = $posted[ 'payment_method' ] ;
        } else {
            //may be it is Renewal Order.
            $payment_method = isset( $_POST[ 'payment_method' ] ) ? wc_clean( $_POST[ 'payment_method' ] ) : '' ;
        }

        //Save default payment information.
        sumo_save_subscription_payment_info( $order_id, array(
            'payment_type'   => 'manual',
            'payment_method' => $payment_method,
        ) ) ;

        if ( $customer_id = sumosubs_get_order_customer_id( $order_id ) ) {

            SUMO_Subscription_Synchronization::update_user_meta( $customer_id ) ; //may be Synced Subscription is subscribed.
            SUMO_Order_Subscription::update_user_meta( $customer_id ) ; //may be it is Order Subscription.

            do_action( 'sumosubscriptions_checkout_update_order_meta', $order_id, $customer_id, $payment_method ) ;
        }
    }

    /**
     * Create new subscriptions after the admin manually/subscriber placed successfully new subscription order.
     * Fire only for the Parent Order Payment.
     * 
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function create_new_subscriptions( $order_id, $old_order_status, $new_order_status ) {

        if ( ! $order = wc_get_order( $order_id ) ) {
            return ;
        }

        $get_subscriptions = sumosubs_get_subscriptions_from_parent_order( $order_id ) ;
        $add_subscriptions = sumosubs_is_parent_order( $order_id ) && empty( $get_subscriptions ) && sumo_is_order_contains_subscriptions( $order_id ) ;

        if ( ! $add_subscriptions ) {
            return ;
        }

        $customer_id = sumosubs_get_order_customer_id( $order_id ) ;

        if ( apply_filters( 'sumosubscriptions_add_new_subscriptions', true, $order_id, $old_order_status, $new_order_status ) ) :

            do_action( 'sumosubscriptions_before_adding_new_subscriptions', $order_id, $old_order_status, $new_order_status ) ;

            //may be Order based Subscription.
            if ( SUMO_Order_Subscription::is_subscribed( 0, 0, $customer_id ) ) {
                //Add New Subscription Entry.
                self::add_new_subscription( $order ) ;
            } else {
                //may be Product/Synchronized Subscription. Adding Post entries based on each Subscription product in the Parent Order.
                foreach ( sumo_get_order_item_meta( $order_id, 'item' ) as $item ) :
                    if ( ! isset( $item[ 'product_id' ] ) ) {
                        continue ;
                    }

                    $product_id = $item[ 'product_id' ] ;

                    if ( is_numeric( $item[ 'variation_id' ] ) && $item[ 'variation_id' ] ) {
                        $product_id = $item[ 'variation_id' ] ;
                    }

                    //may be prevent duplication.
                    if ( in_array( $product_id, sumosubs_get_subscribed_products_from_parent_order( $order_id ) ) ) {
                        continue ;
                    }

                    if ( sumo_is_subscription_product( $product_id ) ) {
                        //Add New Subscription Entry.
                        self::add_new_subscription( $order, $item, $product_id ) ;
                    }
                endforeach ;
            }

            $new_subscription_order_mail = 'subscription_new_order' ;
            if ( 'old-subscribers' === get_option( 'sumosubs_new_subscription_order_template_for_old_subscribers', 'default' ) ) {
                $subscriptions = sumosubscriptions()->query->get( array(
                    'type'       => 'sumosubscriptions',
                    'status'     => 'publish',
                    'limit'      => 1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'sumo_get_user_id',
                            'value'   => $customer_id,
                            'type'    => 'numeric',
                            'compare' => 'LIKE',
                        ),
                        array(
                            'key'     => 'sumo_get_parent_order_id',
                            'value'   => $order_id,
                            'type'    => 'numeric',
                            'compare' => 'NOT LIKE',
                        ),
                    ),
                        ) ) ;

                if ( sizeof( $subscriptions ) > 0 ) {
                    $new_subscription_order_mail = 'subscription_new_order_old_subscribers' ;
                }
            }

            sumo_trigger_subscription_email( $new_subscription_order_mail, $order_id ) ;

            do_action( 'sumosubscriptions_after_new_subscriptions_added', $order_id, $old_order_status, $new_order_status ) ;

            //Clear User meta after successfully saved meta values in Subscription/Order post while placing order.
            delete_user_meta( $customer_id, 'sumo_subscriptions_order_details' ) ;
            delete_user_meta( $customer_id, 'sumosubs_subscription_prorated_data' ) ;
            delete_user_meta( $customer_id, 'sumosubs_subscribed_optional_plans_by_user' ) ;
        endif ;
    }

    /**
     * Add new subscriptions.
     * @param object $order
     * @param array $item
     * @param int $product_id The Product post ID
     */
    public static function add_new_subscription( $order, $item = array(), $product_id = 0 ) {

        $subscription_plan = '' ;
        $trial_plan        = '' ;
        $has_trial         = false ;
        $has_free_trial    = false ;
        $subscription_meta = array() ;

        $order_id          = sumosubs_get_order_id( $order ) ;
        $customer_id       = sumosubs_get_order_customer_id( $order_id ) ;
        $subscription_type = sumo_get_subscription_type( 0, $customer_id ) ;
        $change_status_to  = sumo_get_subscription_status_from_order_status( sumosubs_get_order_status( $order_id ) ) ;
        $product_name      = isset( $item[ 'name' ] ) ? $item[ 'name' ] : '' ;

        //get Order data
        switch ( $subscription_type ) {
            case 'order-subscription':
                $product_name      = sumo_get_order_item_meta( $order_id, 'item_title' ) ;
                $subscription_meta = sumo_get_subscription_meta( 0, 0, $customer_id ) ;
                $subscription_plan = $subscription_meta[ 'subperiodvalue' ] . ' ' . $subscription_meta[ 'subperiod' ] ;
                break ;
            case 'product-subscription':
                $subscription_meta = sumo_get_subscription_meta( 0, $product_id, $customer_id ) ;
                $populated_plan    = sumo_get_subscription_plan( 0, $product_id, $customer_id ) ;
                $has_trial         = '1' === $populated_plan[ 'trial_status' ] && sumo_can_purchase_subscription_trial( $product_id, $customer_id ) ;

                if ( $has_trial ) {
                    $trial_plan     = $subscription_meta[ 'trialperiodvalue' ] . ' ' . $subscription_meta[ 'trialperiod' ] ;
                    $has_free_trial = 'free' === $populated_plan[ 'trial_type' ] && '1' !== $populated_plan[ 'signup_status' ] ;
                } else {
                    $subscription_meta[ 'trial_selection' ] = '2' ;
                }

                $subscription_plan = $subscription_meta[ 'subperiodvalue' ] . ' ' . $subscription_meta[ 'subperiod' ] ;
                $subscription_meta = array_merge( $subscription_meta, array(
                    'product_qty' => $item[ 'qty' ],
                        ) ) ;
                break ;
            default :
                break ;
        }

        do_action( 'sumosubscriptions_before_subscription_is_created', $order_id, $product_id, $item, $change_status_to, $subscription_type ) ;

        if ( $post_id = apply_filters( 'sumosubscriptions_create_subscription', null, $order_id, $product_id, $item, $change_status_to, $subscription_type ) ) {
            //Allow other plugins to create the subscription
            return $post_id ;
        }

        //register a Subscription post
        $post_id = wp_insert_post( array(
            'post_type'   => 'sumosubscriptions',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_title'  => __( 'Subscription', 'sumosubscriptions' ),
                ) ) ;

        $subscriptions[ $product_id ] = $post_id ;

        add_post_meta( $post_id, 'sumo_get_subscription_number', sumo_register_subscription_number() ) ;
        add_post_meta( $post_id, 'sumo_product_name', $product_name ) ;
        add_post_meta( $post_id, 'sumo_get_parent_order_id', $order_id ) ;
        add_post_meta( $post_id, 'sumo_buyer_email', sumosubs_get_order_billing_email( $order_id ) ) ;
        add_post_meta( $post_id, 'sumo_get_user_id', $customer_id ) ;
        add_post_meta( $post_id, 'sumo_subscr_plan', $subscription_plan ) ;
        add_post_meta( $post_id, 'sumo_trial_plan', $trial_plan ) ;
        add_post_meta( $post_id, 'sumo_get_renewals_count', 0 ) ;
        add_post_meta( $post_id, 'sumo_subscription_version', SUMO_SUBSCRIPTIONS_VERSION ) ;

        //Save Subscription related datas.
        switch ( $subscription_type ) {
            case 'order-subscription':
                add_post_meta( $post_id, 'sumo_subscriptions_order_details', $subscription_meta ) ;
                add_post_meta( $post_id, 'sumo_is_order_based_subscriptions', 'yes' ) ;
                add_post_meta( $order_id, 'sumo_is_order_based_subscriptions', 'yes' ) ;
                break ;
            case 'product-subscription':
                add_post_meta( $post_id, 'sumo_subscription_product_details', $subscription_meta ) ;

                if ( ! $has_trial && SUMO_Subscription_Synchronization::is_subscription_synced( $post_id ) ) {
                    add_post_meta( $post_id, 'sumo_subscription_prorated_amount', SUMO_Subscription_Synchronization::get_prorated_fee( $product_id, $customer_id ) ) ;
                    add_post_meta( $post_id, 'sumo_subscription_prorated_amount_to_apply_on', SUMO_Subscription_Synchronization::apply_prorated_fee_on( $product_id, $customer_id ) ) ;
                    add_post_meta( $post_id, 'sumo_subscription_synced_data', SUMO_Subscription_Synchronization::get_synced( $product_id, $customer_id ) ) ;
                }
                if ( SUMO_Subscription_Resubscribe::is_subscription_resubscribed( $product_id, $customer_id ) ) {
                    $resubscribed_plan_associated_subscriptions = SUMO_Subscription_Resubscribe::get_resubscribed_plan_associated_subscriptions( $product_id, $customer_id ) ;

                    add_post_meta( $post_id, 'sumo_subscription_is_resubscribed', 'yes' ) ;
                    add_post_meta( $post_id, 'sumo_resubscribed_plan_associated_subscriptions', $resubscribed_plan_associated_subscriptions ) ;

                    foreach ( $resubscribed_plan_associated_subscriptions as $associated_subscription_id ) {
                        update_post_meta( $associated_subscription_id, 'sumo_subscription_can_resubscribe', 'no' ) ;
                    }
                }

                self::save_order_item_data( $post_id, $order_id ) ; //Save parent order item data.
                break ;
            default :
                break ;
        }

        add_post_meta( $post_id, 'sumo_get_subscription_type', $subscription_type ) ; //set Subscription type
        add_post_meta( $post_id, 'sumo_get_subscriber_data', get_user_by( 'id', $customer_id ) ) ; //set Customer data
        add_post_meta( $order_id, 'sumo_is_subscription_order', 'yes' ) ; //Since v6.9

        self::save_global_admin_settings( $post_id, $product_id, $customer_id ) ; //Save Global Admin Settings.
        self::save_subscription_in_parent_order( $order_id, $post_id, $subscriptions ) ;

        if ( $has_trial ) {
            $change_status_to = $has_free_trial ? 'free-trial' : 'paid-trial' ;
        }
        add_post_meta( $post_id, 'sumo_get_status', 'Pending' ) ;
        add_post_meta( $post_id, 'sumo_subscription_awaiting_status', $change_status_to ) ;

        do_action( 'sumosubscriptions_subscription_created', $post_id, $order_id, $product_id, $item, $change_status_to, $subscription_type ) ;
        return $post_id ;
    }

    /**
     * Update each Subscription data based upon Order status.
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function update_subscriptions( $order_id, $old_order_status, $new_order_status ) {

        if ( ! $order = wc_get_order( $order_id ) ) {
            return ;
        }

        if ( 'yes' === get_post_meta( $order_id, 'sumosubs_order_paid', true ) ) {
            return ;
        }

        $parent_order_id         = sumosubs_get_parent_order_id( $order_id ) ;
        $subscription_new_status = sumo_get_subscription_status_from_order_status( $new_order_status ) ;

        if ( in_array( $old_order_status, array( 'completed', 'processing' ) ) ) {
            return ;
        }

        $subscriptions = sumosubscriptions()->query->get( array(
            'type'       => 'sumosubscriptions',
            'status'     => 'publish',
            'meta_key'   => 'sumo_get_parent_order_id',
            'meta_value' => $parent_order_id,
                ) ) ;

        if ( empty( $subscriptions ) ) {
            return ;
        }

        add_post_meta( $order_id, 'sumo_is_subscription_order', 'yes' ) ; //Since v6.9

        $order_paid = false ;
        foreach ( $subscriptions as $post_id ) :
            $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;

            //may be Renewal Order Payment.
            if ( sumosubs_is_renewal_order( $order_id ) ) {
                //Check which subscription is renewing from the parent order.
                if ( $order_id != get_post_meta( $post_id, 'sumo_get_renewal_id', true ) ) {
                    continue ;
                }

                add_post_meta( $order_id, 'sumo_subscription_id', $post_id ) ; //Since v6.9

                $valid_statuses = array( 'Active', 'Trial', 'Overdue', 'Suspended', 'Pending', 'Failed', 'Cancelled', 'Pending_Authorization' ) ;

                //may be Subscription status is valid to change.
                if ( ! in_array( get_post_meta( $post_id, 'sumo_get_status', true ), $valid_statuses ) ) {
                    continue ;
                }

                //Proceed this Subscription based upon the Renewal Order status.
                switch ( $subscription_new_status ) {
                    case 'Pending' ;
                        //Here Renewal Payment Pending. Subscription Renewal Order status change to pending/on-hold. But this will not affect the Subscription current status.
                        if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                            $event = __( 'Awaiting Automatic Payment Status', 'sumosubscriptions' ) ;
                            $note  = sprintf( __( 'Renewal Payment for Order #%s made. Awaiting Subscription to renew Automatically.', 'sumosubscriptions' ), $order_id ) ;
                        } else {
                            //In this case Admin should manually change the Order status to complete/processing to Renew this Subscription after the funds have been cleared in the Admin side.
                            $event = __( 'Awaiting Admin Approval', 'sumosubscriptions' ) ;
                            $note  = sprintf( __( 'Renewal Order #%s payment is made by the User. Awaiting Admin approval to renew this Subscription.', 'sumosubscriptions' ), $order_id ) ;
                        }

                        sumo_add_subscription_note( $note, $post_id, 'success', $event ) ;
                        break ;
                    case 'Active' ;
                        //by triggering next Renewal, clear previous Cron Events.
                        $cron_event->unset_events() ;
                        delete_post_meta( $post_id, 'sumo_check_trial_status' ) ;

                        //may be manual to auto payment switching happened
                        if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                            if ( $payment_method = sumosubs_get_order_payment_method( $order ) ) {
                                $note = sprintf( __( 'User switched the Renewal Payment method to Automatic Payment from Order #%s.', 'sumosubscriptions' ), $order_id ) ;

                                sumo_add_subscription_note( $note, $post_id, 'success', __( 'Manual to Auto Payment Switch', 'sumosubscriptions' ) ) ;
                            } else {
                                $order->set_payment_method( sumo_get_subscription_payment_method( $post_id ) ) ;
                                $order->save() ;
                            }
                        }

                        //may be subscription is renewing from Trial, initialize Subscription Start Date.
                        if ( '' === get_post_meta( $post_id, 'sumo_get_sub_start_date', true ) ) {
                            update_post_meta( $post_id, 'sumo_get_sub_start_date', sumo_get_subscription_date() ) ;
                        }

                        switch ( get_post_meta( $post_id, 'sumo_get_status', true ) ) {
                            case 'Suspended':
                                //may be useful for future use. Check old Subscription status and set current time on Subscription Active. 
                                sumo_set_subscription_inaccessible_time_from_to( $post_id, 'to' ) ;
                                break ;
                        }

                        //update new Subscription data
                        update_post_meta( $post_id, 'sumo_get_status', $subscription_new_status ) ;
                        update_post_meta( $post_id, 'sumo_get_last_payment_date', sumo_get_subscription_date() ) ;

                        self::set_next_payment_date( $post_id ) ;

                        if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                            sumo_trigger_subscription_email( 'subscription_renewed_order_automatic', $order_id, $post_id ) ;
                        }

                        $order_paid = true ;
                        do_action( 'sumosubscriptions_active_subscription', $post_id, $order_id ) ;
                        do_action( 'sumosubscriptions_renewal_payment_complete', $post_id, $order_id ) ;
                        break ;
                    case 'Cancelled' ;
                    case 'Failed' ;
                        //Here Renewal Payment Failed. Subscription Renewal Order status change to cancelled/failed. But this will not affect the Subscription current status.
                        //In this case Subscription will go to Overdue/Suspend/Cancel status based on Overdue/Suspend/Cancel cron time which was set early.
                        $note       = sprintf( __( 'Error in receiving Renewal Order #%s Payment from the User. Subscription Renewal Order has been %s.', 'sumosubscriptions' ), $order_id, $subscription_new_status ) ;

                        sumo_add_subscription_note( $note, $post_id, 'failure', sprintf( __( 'Renewal Order %s', 'sumosubscriptions' ), $subscription_new_status ) ) ;
                        break ;
                }
                //may be Parent Order Payment.
            } else if ( sumosubs_is_parent_order( $order_id ) && '' === get_post_meta( $post_id, 'sumo_get_sub_start_date', true ) ) {
                //Init the Subscription.
                switch ( $subscription_new_status ) {
                    case 'Pending' ;
                        if ( 'yes' === get_post_meta( $post_id, 'sumo_subscription_is_resubscribed', true ) ) {
                            $note = __( 'Subscriber resubscribed this Subscription and awaiting Payment Order to Complete.', 'sumosubscriptions' ) ;

                            if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                                $note = __( 'Subscriber resubscribed this Subscription and awaiting Payment to Complete automatically for this Order.', 'sumosubscriptions' ) ;
                            }
                        } else {
                            $note = __( 'Subscription awaiting Payment Order to Complete.', 'sumosubscriptions' ) ;

                            if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                                $note = __( 'Subscription awaiting Payment to Complete automatically for this Order.', 'sumosubscriptions' ) ;
                            }
                        }

                        update_post_meta( $post_id, 'sumo_get_status', $subscription_new_status ) ;

                        sumo_add_subscription_note( $note, $post_id, sumo_note_status( 'Pending' ), __( 'New Subscription Order', 'sumosubscriptions' ) ) ;
                        break ;
                    case 'Active' ;
                        $order_paid = true ;

                        if ( ! apply_filters( 'sumosubscription_activate_subscription', true, $post_id, $order_id ) ) {
                            continue 2 ;
                        }

                        if ( 'paypal' === $order->get_payment_method() && 'auto' === sumo_get_payment_type( $post_id ) ) {
                            self::maybe_activate_subscription( $post_id, $order_id, $old_order_status, $new_order_status, true ) ;
                            continue 2 ;
                        }

                        if ( sumosubs_free_trial_awaiting_admin_approval( $post_id, $new_order_status ) ) {
                            sumo_add_subscription_note( __( 'Awaiting admin to activate the Free Trial.', 'sumosubscriptions' ), $post_id, sumo_note_status( 'Pending' ), __( 'Awaiting Free Trial Activation', 'sumosubscriptions' ) ) ;
                            continue 2 ;
                        }

                        if ( $next_payment_date = SUMO_Subscription_Synchronization::maybe_get_awaiting_initial_payment_charge_time( $post_id ) ) {
                            if ( $cron_event->schedule_next_renewal_order( sumo_get_subscription_date( $next_payment_date ) ) ) {
                                sumo_add_subscription_note( __( 'Awaiting for initial payment.', 'sumosubscriptions' ), $post_id, sumo_note_status( 'Pending' ), __( 'Awaiting Initial Payment', 'sumosubscriptions' ) ) ;
                                continue 2 ;
                            }
                        }

                        if ( $subscription_start_time = apply_filters( 'sumosubscriptions_start_subscription_by_specific_time', false, $post_id, $order_id ) ) {
                            if ( is_numeric( $subscription_start_time ) && $cron_event->schedule_to_start_subscription( $subscription_start_time ) ) {
                                sumo_add_subscription_note( sprintf( __( 'Subscription will begin on %s.', 'sumosubscriptions' ), sumo_display_subscription_date( $subscription_start_time ) ), $post_id, sumo_note_status( 'Pending' ), __( 'Subscription Start Time Delayed', 'sumosubscriptions' ) ) ;
                                continue 2 ;
                            }
                        }

                        self::maybe_activate_subscription( $post_id, $order_id, $old_order_status, $new_order_status ) ;
                        break ;
                    case 'Cancelled' ;
                    case 'Failed' ;
                        if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed' ) ) ) {
                            break ;
                        }

                        update_post_meta( $post_id, 'sumo_get_status', $subscription_new_status ) ;
                        update_post_meta( $post_id, 'sumo_get_sub_start_date', '' ) ;
                        update_post_meta( $post_id, 'sumo_get_last_payment_date', '' ) ;
                        update_post_meta( $post_id, 'sumo_get_next_payment_date', '' ) ;

                        sumo_add_subscription_note( sprintf( __( 'Error in receiving New Order Payment from the User. Subscription New Order has been %s.', 'sumosubscriptions' ), $subscription_new_status ), $post_id, 'failure', sprintf( __( 'New Order %s', 'sumosubscriptions' ), $subscription_new_status ) ) ;
                        break ;
                }
            }
        endforeach ;

        if ( $order_paid ) {
            add_post_meta( $order_id, 'sumosubs_order_paid', 'yes' ) ; //Since v11.2
        }
    }

    /**
     * Send Subscription email based upon Order status.
     * @param int $order_id The Order post ID
     * @param string $old_order_status
     * @param string $new_order_status
     */
    public static function send_subscription_email( $order_id, $old_order_status, $new_order_status ) {

        if ( sumo_is_order_contains_subscriptions( $order_id ) ) {
            $post_id = null ;

            if ( sumosubs_is_parent_order( $order_id ) ) {
                $subscriptions = sumosubscriptions()->query->get( array(
                    'type'       => 'sumosubscriptions',
                    'status'     => 'publish',
                    'limit'      => 1,
                    'meta_key'   => 'sumo_get_parent_order_id',
                    'meta_value' => $order_id,
                        ) ) ;
                $post_id       = isset( $subscriptions[ 0 ] ) ? $subscriptions[ 0 ] : null ;
            } else {
                $post_id = get_post_meta( $order_id, 'sumo_subscription_id', true ) ;
            }

            switch ( $new_order_status ) {
                case 'completed' ;
                    sumo_trigger_subscription_email( 'subscription_completed_order', $order_id, $post_id ) ;
                    break ;
                case 'processing' ;
                    sumo_trigger_subscription_email( 'subscription_processing_order', $order_id, $post_id ) ;
                    break ;
                case 'cancelled' ;
                    if ( sumosubs_is_parent_order( $order_id ) ) {
                        sumo_trigger_subscription_email( 'subscription_cancel_order', $order_id, $post_id ) ;
                    }
                    break ;
            }
        }
    }

    /**
     * Prevent from customer cannot make payment, maybe used when order total is zero
     *
     * @return bool
     */
    public static function order_needs_payment( $needs_payment, $order ) {
        if ( isset( $_GET[ 'pay_for_order' ], $_GET[ 'key' ] ) || isset( $_POST[ 'woocommerce_pay' ], $_GET[ 'key' ] ) ) {
            $order_id = sumosubs_get_order_id( $order ) ;

            if ( sumo_is_order_contains_subscriptions( $order_id ) && $order->has_status( array( 'pending', 'failed' ) ) ) {
                $needs_payment = true ;
            }
        }

        return $needs_payment ;
    }

    /**
     * May be activate Subscription either by Trial or Active
     * @param int $post_id
     * @param int $order_id
     * @param string $from_status
     * @param string $to_status
     */
    public static function maybe_activate_subscription( $post_id, $order_id, $from_status, $to_status, $force = false ) {

        if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Active', 'Trial' ) ) ) {
            return ;
        }

        $subscription_status     = 'Active' ;
        $subscription_start_date = sumo_get_subscription_date() ;
        $next_payment_date       = sumosubs_get_next_payment_date( $post_id, 0, array( 'initial_payment' => true ) ) ;
        $subscription_type       = sumo_get_subscription_type( $post_id, 0, false ) ; //Get this Subscription Type.
        $cron_event              = new SUMO_Subscription_Cron_Event( $post_id ) ;

        if ( ! $force && sumo_subscription_awaiting_admin_approval( $post_id ) ) {
            sumo_add_subscription_note( __( 'Awaiting admin to activate the Subscription.', 'sumosubscriptions' ), $post_id, sumo_note_status( 'Pending' ), __( 'Awaiting Subscription Activation', 'sumosubscriptions' ) ) ;
            return ;
        }

        //may be Trial Subscription. Additional 'Trial' for backward cmptblty
        if ( in_array( get_post_meta( $post_id, 'sumo_subscription_awaiting_status', true ), array( 'free-trial', 'paid-trial', 'Trial' ) ) ) {
            $subscription_status     = 'Trial' ;
            $subscription_start_date = '' ;

            update_post_meta( $post_id, 'sumo_get_trial_end_date', $next_payment_date ) ;
        }

        //Initialize Subscription data.
        update_post_meta( $post_id, 'sumo_get_status', $subscription_status ) ;
        update_post_meta( $post_id, 'sumo_get_sub_start_date', $subscription_start_date ) ;
        update_post_meta( $post_id, 'sumo_get_last_payment_date', sumo_get_subscription_date() ) ;
        update_post_meta( $post_id, 'sumo_get_next_payment_date', $next_payment_date ) ;
        //Clear cache.
        delete_post_meta( $post_id, 'sumo_subscription_awaiting_status' ) ;
        delete_post_meta( $post_id, 'sumosubs_activate_free_trial_by' ) ;
        delete_post_meta( $post_id, 'sumosubs_activate_subscription_by' ) ;
        delete_post_meta( $post_id, 'sumo_subcription_activation_scheduled_on' ) ;

        switch ( sumo_get_payment_type( $post_id ) ) {
            case 'auto':
                if ( 'yes' === get_post_meta( $post_id, 'sumo_subscription_is_resubscribed', true ) ) {
                    $note = sprintf( __( 'Subscriber resubscribed this Subscription. Payment Received Successfully. Automatic payment Completed. Subscription status changed from Pending to %s.', 'sumosubscriptions' ), $subscription_status ) ;
                } else {
                    if ( 'free-trial' === $to_status ) {
                        $note = __( 'Admin has approved the Free Trial. Subscription status changed from Pending to Trial.', 'sumosubscriptions' ) ;
                    } else {
                        $note = sprintf( __( '%s is made by the User. Payment Received Successfully. Automatic payment Completed. Subscription status changed from Pending to %s.', 'sumosubscriptions' ), $subscription_type, $subscription_status ) ;
                    }
                }
                sumo_add_subscription_note( $note, $post_id, sumo_note_status( $subscription_status ), __( 'Automatic Payment Complete', 'sumosubscriptions' ) ) ;
                break ;
            default :
                if ( 'yes' === get_post_meta( $post_id, 'sumo_subscription_is_resubscribed', true ) ) {
                    $note = sprintf( __( 'Subscriber resubscribed this Subscription. Payment Received Successfully. Subscription status changed from Pending to %s.', 'sumosubscriptions' ), $subscription_status ) ;
                } else {
                    if ( 'free-trial' === $to_status ) {
                        $note = __( 'Admin has approved the Free Trial. Subscription status changed from Pending to Trial.', 'sumosubscriptions' ) ;
                    } else {
                        $note = sprintf( __( '%s is made by the User. Payment Received Successfully. Subscription status changed from Pending to %s.', 'sumosubscriptions' ), $subscription_type, $subscription_status ) ;
                    }
                }
                sumo_add_subscription_note( $note, $post_id, sumo_note_status( $subscription_status ), __( 'Payment Received', 'sumosubscriptions' ) ) ;
                break ;
        }

        self::associate_additional_digital_downloadable_products( $post_id, $order_id ) ;

        //Initialise Renewal Order if renewal is possible.
        if ( sumo_is_next_renewal_possible( $post_id ) ) {
            $cron_event->schedule_next_renewal_order( $next_payment_date ) ;
        } else {
            $cron_event->schedule_expire_notify( $next_payment_date ) ;
            $cron_event->schedule_reminders( 0, $next_payment_date, '', 'subscription_expiry_reminder' ) ;

            update_post_meta( $post_id, 'sumo_get_saved_due_date', $next_payment_date ) ;
            update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' ) ;
        }

        do_action( 'sumosubscriptions_active_subscription', $post_id, $order_id ) ;
        do_action( 'sumosubscriptions_initial_payment_complete', $post_id, $order_id ) ;
    }

    /**
     * Save Subscription asscoiated Subscription product in Parent Order
     * @param int $order_id The Parent Order post ID
     * @param int $subscription_id
     * @param array $subscriptions
     */
    public static function save_subscription_in_parent_order( $order_id, $subscription_id, $subscriptions ) {
        $subscriptions_in_order = get_post_meta( $order_id, 'sumo_subsc_get_available_postids_from_parent_order', true ) ;

        //Initialise meta for Order, Product and Synchronized Subscriptions.
        add_post_meta( $order_id, 'sumo_subsc_get_available_postids_from_parent_order', $subscriptions ) ;

        if ( SUMO_Order_Subscription::is_subscribed( $subscription_id ) ) {
            return ;
        }

        //Update meta for Product/Synchronized Subscription alone.
        if ( is_array( $subscriptions_in_order ) && is_array( $subscriptions ) && ! empty( $subscriptions_in_order ) ) {
            update_post_meta( $order_id, 'sumo_subsc_get_available_postids_from_parent_order', ($subscriptions + $subscriptions_in_order ) ) ;
        }
    }

    /**
     * Associate Digital Downloadable Products to the Parent Order when the Order status changes to completed/processing.
     * @param int $post_id The Subscription post ID.
     * @param int $parent_order_id The Parent Order post ID
     */
    public static function associate_additional_digital_downloadable_products( $post_id, $parent_order_id ) {
        $parent_order        = wc_get_order( $parent_order_id ) ;
        $product_ids         = sumo_get_additional_digital_downloadable_products( $post_id ) ;
        $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true ) ;
        $inserted_file       = false ;

        if ( ! sumosubs_get_order_billing_email( $parent_order ) || empty( $product_ids ) ) {
            return ;
        }

        foreach ( $product_ids as $product_id ) {
            $files = sumosubs_get_downloads( $product_id ) ;

            if ( empty( $files ) ) {
                continue ;
            }

            foreach ( $files as $download_id => $file ) {
                if ( wc_downloadable_file_permission( $download_id, $product_id, $parent_order ) ) {
                    $inserted_file = true ;
                }
            }
        }

        if ( $inserted_file ) {
            sumo_add_subscription_note( __( 'Granted download permissions of free downloadable products for the User.', 'sumosubscriptions' ), $post_id, sumo_note_status( $subscription_status ), __( 'Granted Download Permissions', 'sumosubscriptions' ) ) ;
        }
    }

    /**
     * Save Global Settings in Subscriptions.
     * @param int $post_id The Subscription post ID.
     * @param int $product_id
     * @param int $customer_id
     */
    public static function save_global_admin_settings( $post_id, $product_id, $customer_id ) {

        if ( SUMO_Subscription_Resubscribe::is_subscription_resubscribed( $product_id, $customer_id ) ) {
            $meta_keys = array(
                'sumo_coupon_in_renewal_order',
                'sumo_coupon_in_renewal_order_applicable_for',
                'sumo_selected_user_roles_for_renewal_order_discount',
                'sumo_selected_user_emails_for_renewal_order_discount',
                'no_of_sumo_selected_renewal_order_discount',
                'sumo_apply_coupon_discount'
                    ) ;

            if ( ! $resubscribed_subscription_id = SUMO_Subscription_Resubscribe::get_resubscribed_subscription( $product_id, $customer_id ) ) {
                return ;
            }

            foreach ( $meta_keys as $meta_key ) {
                $meta_value = get_post_meta( $resubscribed_subscription_id, "$meta_key", true ) ;
                add_post_meta( $post_id, "$meta_key", $meta_value ) ;
            }

            delete_user_meta( $customer_id, "sumo_resubscribed_plan_associated_subscriptions_of{$product_id}" ) ;
            delete_user_meta( $customer_id, "sumo_removed_resubscribed_plan_associated_subscriptions_of{$product_id}" ) ;
        } else {
            SUMO_Subscription_Coupon::save_global( $post_id ) ;
            add_post_meta( $post_id, 'sumosubs_activate_free_trial_by', get_option( 'sumosubs_activate_free_trial_by', 'auto' ) ) ;
            add_post_meta( $post_id, 'sumosubs_activate_subscription_by', get_option( 'sumosubs_activate_subscription_by', 'auto' ) ) ;
        }
    }

    /**
     * Save Order item data in Subscription based on Order Item ID.
     * May be it can be useful when different Addon choice of the same product is in Order.
     * 
     * @param int $post_id The Subscription post ID.
     * @param int $parent_order_id The Parent Order post ID
     */
    public static function save_order_item_data( $post_id, $parent_order_id ) {
        $item_data         = array() ;
        $item_addon_amount = array() ;

        $subscription_plan_details = sumo_get_subscription_plan( $post_id ) ;

        foreach ( sumo_get_order_item_meta( $parent_order_id, 'item' ) as $_item_id => $item ) {
            if ( ! isset( $item[ 'product_id' ] ) ) {
                continue ;
            }
            $product_id   = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ] ;
            $addon_amount = wc_get_order_item_meta( $_item_id, 'sumo_subscription_parent_order_item_addon_amount', true ) ;

            if ( $subscription_plan_details[ 'subscription_product_id' ] != $product_id ) {
                continue ;
            }

            if ( isset( $addon_amount[ $product_id ] ) && $addon_amount[ $product_id ] ) {
                $new_item_addon_amount = array(
                    $_item_id => array(
                        $product_id => $addon_amount[ $product_id ]
                    ) ) ;
                $item_addon_amount     += $new_item_addon_amount ;
            }

            $new_item_data = array(
                $_item_id => array(
                    'id'    => $product_id,
                    'name'  => $item[ 'name' ],
                    'qty'   => $item[ 'qty' ],
                    'addon' => isset( $item_addon_amount[ $_item_id ][ $product_id ] ) ? $item_addon_amount[ $_item_id ][ $product_id ] : 0,
                    'data'  => serialize( $item )
                ) ) ;
            $item_data     += $new_item_data ;
        }

        //Save each item data.
        update_post_meta( $post_id, 'sumo_subscription_parent_order_item_data', $item_data ) ;
    }

    /**
     * Set Next payment date.
     * @param int $post_id The Subscription post ID.
     * @param string $new_renewal_date
     */
    public static function set_next_payment_date( $post_id, $new_renewal_date = '' ) {
        $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true ) ;
        $due_date            = get_post_meta( $post_id, 'sumo_get_next_payment_date', true ) ;
        $renewal_order_id    = get_post_meta( $post_id, 'sumo_get_renewal_id', true ) ;
        $duration_gap        = get_post_meta( $post_id, 'sumo_time_gap_on_paused', true ) ; //may be subscription is paused.
        $cron_event          = new SUMO_Subscription_Cron_Event( $post_id ) ;

        //may be Subscription is resuming from paused.
        if ( 'N/A' === $due_date ) {
            if ( SUMO_Subscription_Synchronization::is_subscription_synced( $post_id ) ) {
                $new_due_date = sumosubs_get_next_payment_date( $post_id ) ;
            } else {
                $due_time = sumo_get_subscription_timestamp( $duration_gap[ 'previous_due_date' ] ) ;

                //may be current time is not exceeded the next due time
                if ( $due_time > sumo_get_subscription_timestamp() ) {
                    $new_due_date = sumosubs_get_next_payment_date( $post_id, 0, array( 'paused_to_resume' => true ) ) ;
                } else {
                    //Here current time exceeded the next due time
                    $new_due_date = sumosubs_get_next_payment_date( $post_id, 0, array(
                        'paused_to_resume' => true,
                        'due_date_exceeds' => true,
                            ) ) ;
                }
            }

            if ( 'Trial' === $subscription_status ) {
                update_post_meta( $post_id, 'sumo_get_trial_end_date', $new_due_date ) ;
            }
            delete_post_meta( $post_id, 'sumo_time_gap_on_paused' ) ;

            sumo_add_subscription_note( sprintf( __( 'Subscription status Changed from Paused to %s.', 'sumosubscriptions' ), $subscription_status ), $post_id, 'success', __( 'Subscription Status Changed', 'sumosubscriptions' ) ) ;
        } else {
            //Normal behaviour of updating Next Due Date.
            if ( '' === $new_renewal_date ) {
                if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                    $note = sprintf( __( 'Automatic Payment for Order #%s Completed. Subscription has been Renewed Successfully.', 'sumosubscriptions' ), $renewal_order_id ) ;
                } else {
                    $note = sprintf( __( 'Renewal Payment for Order #%s made Successfully.', 'sumosubscriptions' ), $renewal_order_id ) ;
                }
                sumo_add_subscription_note( $note, $post_id, 'success', __( 'Payment Received', 'sumosubscriptions' ) ) ;
                //After Renewal Payment made Successfully, Increment Recurring Count for this Subscription.
                sumo_update_renewal_count( $post_id ) ;

                $new_due_date = sumosubs_get_next_payment_date( $post_id, 0, array( 'from_when' => sumo_get_subscription_timestamp( $due_date ) ) ) ;
            } else {
                //Admin customised Date.
                $new_due_date = $new_renewal_date ;
            }
        }

        //Check if the Next Due Date is not manually updated by the Admin.
        if ( '' === $new_renewal_date ) {
            //Check if Next Renewal is possible.
            if ( $new_due_date && sumo_is_next_renewal_possible( $post_id ) ) {
                update_post_meta( $post_id, 'sumo_get_next_payment_date', $new_due_date ) ;

                //Schedule Next Renewal Order.
                $cron_event->schedule_next_renewal_order( $new_due_date ) ;
            } else {
                //Schedule to Expire Subscription.
                $cron_event->schedule_expire_notify( $new_due_date, $renewal_order_id ) ;
                $cron_event->schedule_reminders( 0, $new_due_date, '', 'subscription_expiry_reminder' ) ;

                update_post_meta( $post_id, 'sumo_get_saved_due_date', $new_due_date ) ;
                update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' ) ;
            }
        } else {
            //Update Admin modified Due Date.
            update_post_meta( $post_id, 'sumo_get_next_payment_date', $new_due_date ) ;

            if ( 'Trial' === $subscription_status ) {
                update_post_meta( $post_id, 'sumo_get_trial_end_date', $new_due_date ) ;
            }

            //Re Schedule Next Renewal Order.
            $cron_event->schedule_next_renewal_order( $new_due_date ) ;
        }
    }

    /**
     * Create Renewal Order.
     * @param int $parent_order_id The Parent Order post ID
     * @param int $post_id The Subscription post ID.
     * @return int
     */
    public static function create_renewal_order( $parent_order_id, $post_id ) {

        if ( ! $_parent_order = wc_get_order( $parent_order_id ) ) {
            return ;
        }

        do_action( 'sumosubscriptions_before_creating_renewal_order', $parent_order_id, $post_id ) ;

        //if SUMO Reward System plugin is Active.
        $parent_reward_referral_data    = get_post_meta( $parent_order_id, 'rs_referral_data_for_renewal_order', true ) ;
        //if SUMO Affiliates plugin is Active.
        $parent_affiliate_referral_data = get_post_meta( $parent_order_id, 'sumoaffiliate_data_for_renewal_order', true ) ;

        //Create Renewal Order.
        $order_id = wp_insert_post( array(
            'post_type'   => 'shop_order',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_parent' => $parent_order_id,
                ), true ) ;

        //set billing address
        self::set_address_details( $parent_order_id, $order_id, 'billing', $post_id ) ;
        //set shipping address
        self::set_address_details( $parent_order_id, $order_id, 'shipping', $post_id ) ;
        //set order meta
        self::set_order_details( $parent_order_id, $order_id ) ;

        //if SUMO Reward System plugin is Active.
        if ( isset( $parent_reward_referral_data[ 'award_referral_points_for_renewal' ] ) && $parent_reward_referral_data[ 'award_referral_points_for_renewal' ] === 'no' ) {
            update_post_meta( $order_id, '_referrer_name', $parent_reward_referral_data[ 'referred_user_name' ] ) ;
        }
        //if SUMO Affiliates plugin is Active.
        if ( isset( $parent_affiliate_referral_data[ 'award_commission_for_renewal_order' ] ) && $parent_affiliate_referral_data[ 'award_commission_for_renewal_order' ] === 'no' ) {
            update_post_meta( $order_id, 'sumo_affiliate_id', $parent_affiliate_referral_data[ 'affiliate_id' ] ) ;
        }

        //populate Order
        $renewal_order = wc_get_order( $order_id ) ;

        //Add Subscription items
        self::add_order_item( $parent_order_id, $order_id, $post_id ) ;
        //may be set discounts
        self::set_discounts( $parent_order_id, $order_id, $post_id ) ;

        //Is globally Shipping Enabled
        if ( 'yes' === get_option( 'sumo_shipping_option' ) ) {
            self::set_shipping_method( $parent_order_id, $order_id ) ;
        }

        //Is globally Tax Enabled
        if ( 'yes' === get_option( 'sumo_tax_option' ) ) {
            self::set_tax( $parent_order_id, $order_id ) ;
        }

        if ( is_callable( array( $renewal_order, 'save' ) ) ) {
            $renewal_order->save() ;
        }

        // Updates tax totals
        if ( is_callable( array( $renewal_order, 'update_taxes' ) ) ) {
            $renewal_order->update_taxes() ;
        }

        // Calc totals - this also triggers save
        $renewal_order->calculate_totals( 'yes' === get_option( 'sumo_tax_option' ) ) ;

        //Update Default Order status
        $renewal_order->update_status( 'pending' ) ;

        //Save Subscription Renewal Orders.
        self::save_subscription_renewal_orders( $post_id, $order_id ) ;

        $note = sprintf( __( 'Subscription Renewal Order #%s has been generated Successfully.', 'sumosubscriptions' ), $order_id ) ;

        if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
            $note = sprintf( __( 'Subscription Renewal Order #%s has been generated Successfully. Awaiting Subscription to Complete this Order automatically.', 'sumosubscriptions' ), $order_id ) ;
        }

        sumo_add_subscription_note( $note, $post_id, sumo_note_status( get_post_meta( $post_id, 'sumo_get_status', true ) ), __( 'Renewal Order Created', 'sumosubscriptions' ) ) ;

        do_action( 'sumosubscriptions_renewal_order_is_created', $parent_order_id, $order_id, $post_id ) ;

        return $order_id ;
    }

    /**
     * Extract billing and shipping information from Parent Order and set in Renewal Order 
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @param string $type valid values are 'billing' | 'shipping 
     * @param int $post_id
     * @return boolean
     */
    public static function set_address_details( $parent_order_id, $renewal_order_id, $type, $post_id ) {
        $_parent_order = wc_get_order( $parent_order_id ) ;
        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }

        $subscriber_id = sumosubs_get_order_customer_id( $_parent_order ) ;
        $data          = array(
            'first_name' => array( 'billing', 'shipping' ),
            'last_name'  => array( 'billing', 'shipping' ),
            'company'    => array( 'billing', 'shipping' ),
            'address_1'  => array( 'billing', 'shipping' ),
            'address_2'  => array( 'billing', 'shipping' ),
            'city'       => array( 'billing', 'shipping' ),
            'postcode'   => array( 'billing', 'shipping' ),
            'country'    => array( 'billing', 'shipping' ),
            'state'      => array( 'billing', 'shipping' ),
            'email'      => array( 'billing' ),
            'phone'      => array( 'billing' ),
                ) ;

        foreach ( $data as $key => $applicable_to ) {
            $value = '' ;

            if (
                    'shipping' === $type &&
                    in_array( $type, $applicable_to ) &&
                    SUMO_Subscription_Shipping::is_updated( $subscriber_id ) &&
                    (SUMO_Subscription_Shipping::update_to_all( $subscriber_id ) || $post_id == SUMO_Subscription_Shipping::updated_via( $subscriber_id ))
            ) {
                $shipping = SUMO_Subscription_Shipping::get_address( $subscriber_id ) ;
                $value    = $shipping[ $key ] ;
            } else {
                if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
                    $value = get_post_meta( $parent_order_id, "_{$type}_{$key}", true ) ;
                } else if ( is_callable( array( $_parent_order, "get_{$type}_{$key}" ) ) ) {
                    $value = $_parent_order->{"get_{$type}_{$key}"}() ;
                }
            }

            if ( '' === $value && 'shipping' === $type && in_array( $type, $applicable_to ) ) {
                //may be useful if shipping address is empty
                if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
                    $value = get_post_meta( $parent_order_id, "_billing_{$key}", true ) ;
                } else if ( is_callable( array( $_parent_order, "get_billing_{$key}" ) ) ) {
                    $value = $_parent_order->{"get_billing_{$key}"}() ;
                }
            }

            if ( is_callable( array( $renewal_order, "set_{$type}_{$key}" ) ) ) {
                $renewal_order->{"set_{$type}_{$key}"}( $value ) ;
                $renewal_order->save() ;
            } else if ( in_array( $type, $applicable_to ) ) {
                update_post_meta( $renewal_order_id, "_{$type}_{$key}", $value ) ;
            }
        }
    }

    /**
     * Extract Parent Order details other than shipping/billing and set in Renewal Order 
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @return boolean
     */
    public static function set_order_details( $parent_order_id, $renewal_order_id ) {
        $_parent_order = wc_get_order( $parent_order_id ) ;
        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }

        $data = array(
            'version'            => 'order_version',
            'currency'           => 'order_currency',
            'order_key'          => 'order_key',
            'shipping_total'     => 'order_shipping',
            'shipping_tax'       => 'order_shipping_tax',
            'total_tax'          => 'order_tax',
            'customer_id'        => 'customer_user',
            'prices_include_tax' => 'prices_include_tax',
                ) ;

        foreach ( $data as $method_key => $meta_key ) {
            $value = '' ;

            if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
                $value = get_post_meta( $parent_order_id, "_{$meta_key}", true ) ;
            } else if ( is_callable( array( $_parent_order, "get_{$method_key}" ) ) ) {
                $value = $_parent_order->{"get_{$method_key}"}() ;
            }

            if ( is_callable( array( $renewal_order, "set_{$method_key}" ) ) ) {
                $renewal_order->{"set_{$method_key}"}( $value ) ;
                $renewal_order->save() ;
            } else {
                update_post_meta( $renewal_order_id, "_{$meta_key}", $value ) ;
            }
        }
    }

    /**
     * Add Subscription order Item in Renewal Order.
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @param int $post_id
     * @return boolean
     */
    public static function add_order_item( $parent_order_id, $renewal_order_id, $post_id ) {
        $_parent_order     = wc_get_order( $parent_order_id ) ;
        $renewal_order     = wc_get_order( $renewal_order_id ) ;
        $subscription_plan = sumo_get_subscription_plan( $post_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }

        $prorated_amount       = get_post_meta( $post_id, 'sumo_subscription_prorated_amount', true ) ;
        $apply_prorated_fee_on = get_post_meta( $post_id, 'sumo_subscription_prorated_amount_to_apply_on', true ) ;

        do_action( 'sumosubscriptions_before_adding_renewal_order_item', $parent_order_id, $renewal_order_id, $post_id ) ;

        if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
            return ;
        }

        foreach ( sumo_get_order_item_meta( $parent_order_id, 'item' ) as $_item_id => $_item ) {
            $product_id = $_item[ 'variation_id' ] > 0 ? $_item[ 'variation_id' ] : $_item[ 'product_id' ] ;
            $item_qty   = $_item[ 'qty' ] ;

            if ( ! $_product = wc_get_product( $product_id ) ) {
                continue ;
            }

            $line_total = sumo_get_recurring_fee( $post_id, $_item, $_item_id, false ) ;

            $add_item = false ;
            if ( SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
                $add_item = true ;
            } else if ( $subscription_plan[ 'subscription_product_id' ] == $product_id ) {
                //Calculate if the Admin decided to Prorate Payment in the First Renewal.
                if ( 'first_renewal' === $apply_prorated_fee_on && is_numeric( $prorated_amount ) && $prorated_amount > 0 ) {
                    $line_total += $prorated_amount ;
                }

                $item_qty = absint( $subscription_plan[ 'subscription_product_qty' ] ) ;
                $add_item = true ;
            }

            if ( ! $item_qty ) {
                $item_qty = 1 ;
            }

            //Check whether it is valid to add order item meta.
            if ( ! $add_item ) {
                continue ;
            }

            $discount_amount = 0 ;
            if (
                    SUMO_Subscription_Coupon::apply_wc_coupon_in_renewal( $post_id ) &&
                    $_parent_order->get_total_discount() &&
                    SUMO_Subscription_Coupon::is_coupon_applicable_for_renewal_by_user( $post_id ) &&
                    ! SUMO_Subscription_Coupon::subscription_contains_recurring_coupon( $subscription_plan )
            ) {
                $discount_amount = ($_item[ 'line_subtotal' ] - $_item[ 'line_total' ]) / $item_qty ;
            }

            $line_subtotal = $line_total ;
            $line_total    -= $discount_amount ;
            $item_id       = $renewal_order->add_product( $_product, $item_qty, array(
                'subtotal' => wc_get_price_excluding_tax( $_product, array(
                    'qty'   => $item_qty,
                    'price' => wc_format_decimal( $line_subtotal )
                ) ),
                'total'    => wc_get_price_excluding_tax( $_product, array(
                    'qty'   => $item_qty,
                    'price' => wc_format_decimal( $line_total )
                ) ),
                    ) ) ;

            if ( isset( $_item[ 'item_meta' ] ) && is_array( $_item[ 'item_meta' ] ) ) {
                foreach ( $_item[ 'item_meta' ] as $key => $value ) {
                    wc_add_order_item_meta( $item_id, $key, $value, true ) ;
                }
            }

            //For Synchronized Subscription. Trigger after Prorated Amount gets added with the Subscription Product Line Total. Since it will only applicable for First Renewal alone
            delete_post_meta( $post_id, 'sumo_subscription_prorated_amount' ) ;
            delete_post_meta( $post_id, 'sumo_subscription_prorated_amount_to_apply_on' ) ;
        }
    }

    /**
     * Extract shipping method from Parent Order and set in Renewal Order 
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @return boolean
     */
    public static function set_shipping_method( $parent_order_id, $renewal_order_id ) {
        $_parent_order = wc_get_order( $parent_order_id ) ;
        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }
        if ( ! $shipping_methods = $_parent_order->get_shipping_methods() ) {
            return ;
        }

        do_action( 'sumosubscriptions_before_adding_shippping_in_renewal_order', $parent_order_id, $renewal_order_id ) ;

        if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
            return ;
        }

        $item = new WC_Order_Item_Shipping() ;
        foreach ( $shipping_methods as $shipping_item_id => $shipping_rate ) {

            $item->set_props( array(
                'method_title' => $shipping_rate[ 'name' ],
                'method_id'    => $shipping_rate[ 'id' ],
                'total'        => wc_format_decimal( $shipping_rate[ 'total' ] ),
                'taxes'        => 'yes' === get_option( 'sumo_tax_option' ) ? $shipping_rate[ 'taxes' ] : array(),
                'order_id'     => $renewal_order_id,
            ) ) ;

            foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
                $item->add_meta_data( $key, $value, true ) ;
            }

            $item->save() ;
            $renewal_order->add_item( $item ) ;
        }
    }

    /**
     * Extract discounts applied in Parent Order and set in Renewal Order 
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @param int $post_id
     * @return boolean
     */
    public static function set_discounts( $parent_order_id, $renewal_order_id, $post_id ) {
        $_parent_order = wc_get_order( $parent_order_id ) ;
        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }

        if ( ! SUMO_Subscription_Coupon::is_coupon_applicable_for_renewal_by_user( $post_id ) ) {
            return ;
        }

        $subscription_plan = sumo_get_subscription_plan( $post_id ) ;
        if ( SUMO_Subscription_Coupon::subscription_contains_recurring_coupon( $subscription_plan ) ) {
            foreach ( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ] as $coupon_code ) {
                $renewal_order->apply_coupon( $coupon_code ) ;
            }
        }

        if ( ! SUMO_Subscription_Coupon::apply_wc_coupon_in_renewal( $post_id ) || ! $_parent_order->get_total_discount() || ! ($coupons = $_parent_order->get_items( array( 'coupon' ) ) ) ) {
            return ;
        }

        do_action( 'sumosubscriptions_before_adding_discount_in_renewal_order', $parent_order_id, $renewal_order_id, $post_id ) ;

        if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
            return ;
        }

        $item = new WC_Order_Item_Coupon() ;
        foreach ( $coupons as $id => $coupon ) {
            $_coupon = new WC_Coupon( $coupon[ 'code' ] ) ;

            if ( $_coupon->is_type( array_keys( SUMO_Subscription_Coupon::get_subscription_coupon_types() ) ) ) {
                continue ;
            }

            $item->set_props( array(
                'code'         => $coupon[ 'code' ],
                'discount'     => $coupon[ 'discount' ],
                'discount_tax' => $coupon[ 'discount_tax' ],
                'order_id'     => $renewal_order_id,
            ) ) ;
            $item->save() ;
            $renewal_order->add_item( $item ) ;
        }
    }

    /**
     * Extract Taxes from Parent Order and set in Renewal Order 
     * @param int $parent_order_id
     * @param int $renewal_order_id
     * @return boolean
     */
    public static function set_tax( $parent_order_id, $renewal_order_id ) {
        if ( sumosubs_is_wc_version( '<', '3.0' ) ) {
            return ;
        }

        $_parent_order = wc_get_order( $parent_order_id ) ;
        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $_parent_order || ! $renewal_order ) {
            return ;
        }
        if ( ! $taxes = $_parent_order->get_taxes() ) {
            return ;
        }

        $item = new WC_Order_Item_Tax() ;
        foreach ( $taxes as $key => $tax ) {

            $item->set_props( array(
                'rate_id'            => $tax[ 'rate_id' ],
                'tax_total'          => $tax[ 'tax_total' ],
                'shipping_tax_total' => $tax[ 'shipping_tax_total' ],
            ) ) ;

            $item->set_rate( $tax[ 'rate_id' ] ) ;
            $item->set_order_id( $renewal_order_id ) ;
            $item->save() ;
            $renewal_order->add_item( $item ) ;
        }
    }

    /**
     * Save every Subscription Renewal Orders created for each Subscription.
     * @param int $post_id The Subscription post ID.
     * @param int $new_renewal_order_id The Renewal Order post ID
     */
    public static function save_subscription_renewal_orders( $post_id, $new_renewal_order_id ) {
        $previous_renewal_orders = get_post_meta( $post_id, 'sumo_get_every_renewal_ids', true ) ;

        if ( is_array( $previous_renewal_orders ) && ! empty( $previous_renewal_orders ) ) {
            $renewal_orders = array_unique( array_merge( array( $new_renewal_order_id ), $previous_renewal_orders ) ) ;
        } else {
            $renewal_orders = array( $new_renewal_order_id ) ;
        }

        update_post_meta( $post_id, 'sumo_get_every_renewal_ids', $renewal_orders ) ;
        update_post_meta( $post_id, 'sumo_get_renewal_id', $new_renewal_order_id ) ;
        add_post_meta( $new_renewal_order_id, 'sumo_subscription_id', $post_id ) ; //Since v6.9
        add_post_meta( $new_renewal_order_id, 'sumo_is_subscription_order', 'yes' ) ; //Since v6.9
    }

}

new SUMOSubscriptions_Order() ;
