<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription Ajax Event.
 * 
 * @class SUMOSubscriptions_Ajax
 * @category Class
 */
class SUMOSubscriptions_Ajax {

    /**
     * Init SUMOSubscriptions_Ajax.
     */
    public static function init() {
        //Get Ajax Events.
        $ajax_events = array(
            'add_subscription_note'                             => false,
            'delete_subscription_note'                          => false,
            'get_subscribed_optional_plans_by_user'             => true,
            'subscriber_request'                                => false,
            'cancel_request'                                    => false,
            'checkout_order_subscription'                       => true,
            'get_subscription_variation_attributes_upon_switch' => false,
            'save_swapped_subscription_variation'               => false,
            'init_data_export'                                  => false,
            'handle_exported_data'                              => false,
            'bulk_update_product_meta'                          => false,
            'optimize_bulk_updation_of_product_meta'            => false,
            'get_subscription_as_regular_html_data'             => false,
            'json_search_subscription_products_and_variations'  => false,
            'json_search_downloadable_products_and_variations'  => false,
            'json_search_customers_by_email'                    => false,
                ) ;

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( "wp_ajax_sumosubscription_{$ajax_event}", __CLASS__ . "::{$ajax_event}" ) ;

            if ( $nopriv ) {
                add_action( "wp_ajax_nopriv_sumosubscription_{$ajax_event}", __CLASS__ . "::{$ajax_event}" ) ;
            }
        }
    }

    /**
     * Admin manually add subscription notes.
     */
    public static function add_subscription_note() {

        check_ajax_referer( 'add-subscription-note', 'security' ) ;

        $note = sumo_add_subscription_note( $_POST[ 'content' ], $_POST[ 'post_id' ], 'processing', __( 'Admin Manually Added Note', 'sumosubscriptions' ) ) ;

        if ( $note = sumosubs_get_subscription_note( $note ) ) {
            include( 'admin/views/html-subscription-note.php' ) ;
        }
        die() ;
    }

    /**
     * Admin manually delete subscription notes.
     */
    public static function delete_subscription_note() {

        check_ajax_referer( 'delete-subscription-note', 'security' ) ;

        wp_send_json( wp_delete_comment( $_POST[ 'delete_id' ], true ) ) ;
    }

    /**
     * Get optional Subscription plan subscribed by User in product page
     */
    public static function get_subscribed_optional_plans_by_user() {

        check_ajax_referer( 'get-subscription-product-data', 'security' ) ;

        if ( ! $_POST[ 'product_id' ] ) {
            die() ;
        }

        $subscription_plan = sumo_get_subscription_plan( 0, $_POST[ 'product_id' ] ) ;

        if ( in_array( 'set_trial', $_POST[ 'selected_plans' ] ) ) {
            $subscription_plan[ 'trial_status' ] = '1' ;
        }
        if ( in_array( 'set_signup_fee', $_POST[ 'selected_plans' ] ) ) {
            $subscription_plan[ 'signup_status' ] = '1' ;
        }

        wp_send_json( array(
            'next_payment_sync_on' => '1' === $subscription_plan[ 'synchronization_status' ] ? sprintf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>', __( 'Next Payment on: ', 'sumosubscriptions' ), SUMO_Subscription_Synchronization::get_initial_payment_date( $_POST[ 'product_id' ], true ) ) : '',
            'subscribed_plan'      => sumo_display_subscription_plan( 0, 0, 0, false, $subscription_plan )
        ) ) ;
    }

    public static function subscriber_request() {

        check_ajax_referer( 'subscriber-request', 'security' ) ;

        $next_payment_date   = get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_next_payment_date', true ) ;
        $renewal_order_id    = absint( get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_renewal_id', true ) ) ;
        $parent_order_id     = absint( get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_parent_order_id', true ) ) ;
        $subscription_status = get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', true ) ;

        try {
            switch ( $_POST[ 'request' ] ) {
                case 'pause':
                    sumo_pause_subscription( $_POST[ 'subscription_id' ], '', $_POST[ 'requested_by' ] ) ;

                    //Manage Automatic Resume
                    if ( ! empty( $_POST[ 'auto_resume_on' ] ) ) {
                        $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                        $cron_event->schedule_automatic_resume( $_POST[ 'auto_resume_on' ] ) ;
                        add_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_auto_resume_scheduled_on', $_POST[ 'auto_resume_on' ] ) ;
                    }

                    do_action( 'sumosubscriptions_pause_subscription', $_POST[ 'subscription_id' ], $parent_order_id ) ;
                    break ;
                case 'resume':
                    sumo_resume_subscription( $_POST[ 'subscription_id' ], $_POST[ 'requested_by' ] ) ;

                    do_action( 'sumosubscriptions_active_subscription', $_POST[ 'subscription_id' ], $parent_order_id ) ;
                    break ;
                case 'cancel-immediate':
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'requested_by' ] ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', 'immediate' ) ;
                    sumo_cancel_subscription( $_POST[ 'subscription_id' ], '', $_POST[ 'requested_by' ] ) ;

                    do_action( 'sumosubscriptions_cancel_subscription', $_POST[ 'subscription_id' ], $parent_order_id ) ;
                    break ;
                case 'cancel-at-the-end-of-billing-cycle':
                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $_POST[ 'subscription_id' ], $parent_order_id ) ) {
                        if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
                            update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_previous_status', $subscription_status ) ;
                        }

                        delete_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancellation_scheduled_on' ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', 'Pending_Cancellation' ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'requested_by' ] ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', 'end_of_billing_cycle' ) ;

                        sumo_add_subscription_note( __( 'Subscription cancel request submitted. And it is set to Cancel at the End of this Billing Cycle.', 'sumosubscriptions' ), $_POST[ 'subscription_id' ], 'success', __( 'Cancelling at the End of Billing Cycle', 'sumosubscriptions' ) ) ;
                        sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', null, $_POST[ 'subscription_id' ] ) ;

                        $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                        $cron_event->unset_events() ;
                        $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $next_payment_date, true ) ;
                    }
                    break ;
                case 'cancel-on-scheduled-date':
                    $scheduled_time    = sumo_get_subscription_timestamp( $_POST[ 'scheduled_date_to_cancel' ] ) ;
                    $next_payment_time = sumo_get_subscription_timestamp( $next_payment_date ) ;

                    if ( $scheduled_time < sumo_get_subscription_timestamp() || $scheduled_time > $next_payment_time ) {
                        wp_send_json( array(
                            'result' => 'failure',
                            'notice' => __( 'Selected date must be within current billing cycle !!', 'sumosubscriptions' ),
                        ) ) ;
                    }

                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $_POST[ 'subscription_id' ], $parent_order_id ) ) {
                        if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
                            update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_previous_status', $subscription_status ) ;
                        }
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', 'Pending_Cancellation' ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancellation_scheduled_on', $_POST[ 'scheduled_date_to_cancel' ] ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'requested_by' ] ) ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', 'scheduled_date' ) ;

                        sumo_add_subscription_note( sprintf( __( 'Subscription cancel request submitted. And it is set to Cancel on the Scheduled Date <b>%s</b>.', 'sumosubscriptions' ), $_POST[ 'scheduled_date_to_cancel' ] ), $_POST[ 'subscription_id' ], 'success', __( 'Cancelling on the Scheduled Date', 'sumosubscriptions' ) ) ;
                        sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', null, $_POST[ 'subscription_id' ] ) ;

                        $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                        $cron_event->unset_events() ;
                        $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $_POST[ 'scheduled_date_to_cancel' ], true ) ;
                    }
                    break ;
                case 'cancel-revoke':
                    sumosubs_revoke_cancel_request( $_POST[ 'subscription_id' ], __( 'User has Revoked the Cancel request.', 'sumosubscriptions' ) ) ;
                    break ;
                case 'turnoff-auto':
                    if ( 'auto' === sumo_get_payment_type( $_POST[ 'subscription_id' ] ) && apply_filters( 'sumosubscriptions_revoke_automatic_subscription', true, $_POST[ 'subscription_id' ], $parent_order_id ) ) {
                        sumo_save_subscription_payment_info( $parent_order_id, array(
                            'payment_type' => 'manual'
                        ) ) ;

                        $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                        $cron_event->unset_events( array(
                            'automatic_pay',
                            'notify_invoice_reminder',
                            'switch_to_manual_pay_mode',
                            'retry_automatic_pay_in_overdue',
                            'retry_automatic_pay_in_suspended',
                        ) ) ;

                        if ( sumosubs_unpaid_renewal_order_exists( $_POST[ 'subscription_id' ] ) ) {
                            $cron_event->schedule_next_eligible_payment_failed_status() ;
                            $cron_event->schedule_reminders( $renewal_order_id, $next_payment_date ) ;
                        }

                        sumo_add_subscription_note( __( 'Subscriber turned off their automatic charging for subscription renewals.', 'sumosubscriptions' ), $_POST[ 'subscription_id' ], 'success', __( 'Turnedoff Auto Payments', 'sumosubscriptions' ) ) ;
                        sumo_trigger_subscription_email( 'subscription_turnoff_automatic_payments_success', null, $_POST[ 'subscription_id' ] ) ;

                        do_action( 'sumosubscriptions_automatic_subscription_is_revoked', $_POST[ 'subscription_id' ], $parent_order_id ) ;
                        wp_send_json( array(
                            'result'   => 'success',
                            'redirect' => sumo_get_subscription_endpoint_url( $_POST[ 'subscription_id' ] ),
                            'notice'   => __( 'You have successfully turned off your Automatic Subscription Renewal for this subscription!!', 'sumosubscriptions' ),
                        ) ) ;
                    }
                    break ;
                case 'resubscribe':
                    $redirect = SUMO_Subscription_Resubscribe::do_resubscribe( $_POST[ 'subscription_id' ] ) ;
                    wp_send_json( array(
                        'result'   => 'success',
                        'redirect' => $redirect,
                    ) ) ;
                    break ;
                case 'quantity-change' :
                    $new_qty  = absint( $_POST[ 'quantity' ] ) ;

                    if ( ! $new_qty ) {
                        throw new Exception( __( 'Please enter the valid product quantity!!', 'sumosubscriptions' ) ) ;
                    }

                    $subscription_plan = ( array ) get_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_product_details', true ) ;
                    $old_qty           = absint( $subscription_plan[ 'product_qty' ] ) ;

                    if ( $new_qty !== $old_qty ) {
                        $subscription_plan[ 'product_qty' ] = $new_qty ;
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_product_details', $subscription_plan ) ;
                        sumo_add_subscription_note( sprintf( __( 'Customer has changed the subscription quantity from <b>%s</b> to <b>%s</b>.', 'sumosubscriptions' ), $old_qty, $new_qty ), $_POST[ 'subscription_id' ], 'success', __( 'Subscription Qty Changed', 'sumosubscriptions' ) ) ;
                        do_action( 'sumosubscriptions_subscription_qty_changed', $new_qty, $_POST[ 'subscription_id' ], $subscription_plan, $parent_order_id ) ;
                    }
                    break ;
            }

            wp_send_json( array(
                'result'   => 'success',
                'redirect' => sumo_get_subscription_endpoint_url( $_POST[ 'subscription_id' ] ),
            ) ) ;
        } catch ( Exception $e ) {
            wp_send_json( array(
                'result' => 'failure',
                'notice' => $e->getMessage(),
            ) ) ;
        }
    }

    /**
     * Cancel request by Admin. Cancelling Subscription by Immediately/End of Billing Cycle/Scheduled Date 
     */
    public static function cancel_request() {

        check_ajax_referer( 'subscription-cancel-request', 'security' ) ;

        $next_due_date       = get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_next_payment_date', true ) ;
        $renewal_order_id    = absint( get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_renewal_id', true ) ) ;
        $parent_order_id     = absint( get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_parent_order_id', true ) ) ;
        $subscription_status = get_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', true ) ;

        switch ( $_POST[ 'cancel_method_requested' ] ) {
            case 'immediate':
                update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'cancel_method_requested_by' ] ) ;
                update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', $_POST[ 'cancel_method_requested' ] ) ;

                //Cancel Subscription
                sumo_cancel_subscription( $_POST[ 'subscription_id' ], '', $_POST[ 'cancel_method_requested_by' ] ) ;
                //Trigger after Subscription gets Cancelled
                do_action( 'sumosubscriptions_cancel_subscription', $_POST[ 'subscription_id' ], $parent_order_id ) ;
                wp_send_json( 'success' ) ;
                break ;
            case 'end_of_billing_cycle':
                if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $_POST[ 'subscription_id' ], $parent_order_id ) ) {
                    if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_previous_status', $subscription_status ) ;
                    }
                    delete_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancellation_scheduled_on' ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', 'Pending_Cancellation' ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'cancel_method_requested_by' ] ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', $_POST[ 'cancel_method_requested' ] ) ;

                    sumo_add_subscription_note( __( 'Subscription cancel request submitted. And it is set to Cancel at the End of this Billing Cycle.', 'sumosubscriptions' ), $_POST[ 'subscription_id' ], 'success', __( 'Cancelling at the End of Billing Cycle', 'sumosubscriptions' ) ) ;
                    sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', null, $_POST[ 'subscription_id' ] ) ;

                    $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                    $cron_event->unset_events() ;
                    $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $next_due_date, true ) ;
                }
                wp_send_json( 'success' ) ;
                break ;
            case 'scheduled_date':
                $scheduled_time    = sumo_get_subscription_timestamp( $_POST[ 'scheduled_date' ] ) ;
                $next_payment_time = sumo_get_subscription_timestamp( $next_due_date ) ;

                if ( $scheduled_time < sumo_get_subscription_timestamp() || $scheduled_time > $next_payment_time ) {
                    wp_send_json( __( 'Selected date must be within current billing cycle !!', 'sumosubscriptions' ) ) ;
                }

                if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $_POST[ 'subscription_id' ], $parent_order_id ) ) {
                    if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
                        update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_previous_status', $subscription_status ) ;
                    }
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_get_status', 'Pending_Cancellation' ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancellation_scheduled_on', $_POST[ 'scheduled_date' ] ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_cancel_method_requested_by', $_POST[ 'cancel_method_requested_by' ] ) ;
                    update_post_meta( $_POST[ 'subscription_id' ], 'sumo_subscription_requested_cancel_method', $_POST[ 'cancel_method_requested' ] ) ;

                    sumo_add_subscription_note( sprintf( __( 'Subscription cancel request submitted. And it is set to Cancel on the Scheduled Date <b>%s</b>.', 'sumosubscriptions' ), $_POST[ 'scheduled_date' ] ), $_POST[ 'subscription_id' ], 'success', __( 'Cancelling on the Scheduled Date', 'sumosubscriptions' ) ) ;
                    sumo_trigger_subscription_email( 'subscription_cancel_request_submitted', null, $_POST[ 'subscription_id' ] ) ;

                    $cron_event = new SUMO_Subscription_Cron_Event( $_POST[ 'subscription_id' ] ) ;
                    $cron_event->unset_events() ;
                    $cron_event->schedule_cancel_notify( $renewal_order_id, 0, $_POST[ 'scheduled_date' ], true ) ;
                }
                wp_send_json( 'success' ) ;
                break ;
        }
        wp_send_json( 'failure' ) ;
    }

    /**
     * Load Subscription Variation to be Switched in Admin Page and in My Account Page.
     */
    public static function get_subscription_variation_attributes_upon_switch() {

        check_ajax_referer( 'variation-swapping', 'security' ) ;

        $this_attribute_key_selected   = sanitize_title( $_POST[ 'selected_attribute_key' ] ) ;
        $this_attribute_value_selected = $_POST[ 'selected_attribute_value' ] ;

        $selected_attributes = is_array( $_POST[ 'selected_attributes' ] ) ? array_unique( $_POST[ 'selected_attributes' ], SORT_REGULAR ) : array() ;
        $selected_attributes = isset( $selected_attributes[ 0 ] ) ? $selected_attributes[ 0 ] : array() ;

        $matched_variation_data = SUMO_Subscription_Variation_Switcher::get_matched_variation( $_POST[ 'post_id' ], $selected_attributes ) ;

        if ( empty( $matched_variation_data ) ) {
            $altered_attributes                                 = array() ;
            $altered_attributes[ $this_attribute_key_selected ] = $this_attribute_value_selected ;

            foreach ( $selected_attributes as $attribute_key => $attribute_value ) {
                if ( $attribute_key != $this_attribute_key_selected && $attribute_value != $this_attribute_value_selected ) {
                    $altered_attributes[ $attribute_key ] = $attribute_value ;
                }
            }
            array_pop( $altered_attributes ) ;

            $matched_variation_data = SUMO_Subscription_Variation_Switcher::get_matched_variation( $_POST[ 'post_id' ], $altered_attributes ) ;

            if ( empty( $matched_variation_data ) ) {
                $altered_attributes = array() ;
                $altered_attributes = array( $this_attribute_key_selected => $this_attribute_value_selected ) ;

                $matched_variation_data = SUMO_Subscription_Variation_Switcher::get_matched_variation( $_POST[ 'post_id' ], $altered_attributes ) ;
            }
        }

        wp_send_json( $matched_variation_data ) ;
    }

    /**
     * Save Swapped Subscription Variation in Admin Page and in My Account Page.
     */
    public static function save_swapped_subscription_variation() {

        check_ajax_referer( 'save-swapped-variation', 'security' ) ;

        $subscription_meta               = sumo_get_subscription_meta( $_POST[ 'post_id' ] ) ;
        $parent_order_id                 = get_post_meta( $_POST[ 'post_id' ], 'sumo_get_parent_order_id', true ) ;
        $parent_order_item_data          = get_post_meta( $_POST[ 'post_id' ], 'sumo_subscription_parent_order_item_data', true ) ;
        $subscriptions_from_parent_order = get_post_meta( $parent_order_id, 'sumo_subsc_get_available_postids_from_parent_order', true ) ;
        $payment_info                    = get_post_meta( $parent_order_id, 'sumosubscription_payment_order_information', true ) ;
        $response_code                   = '0' ;

        if ( isset( $subscription_meta[ 'productid' ] ) && is_array( $_POST[ 'plan_matched_attributes_key' ] ) && is_array( $_POST[ 'attribute_value_to_switch' ] ) && ! empty( $_POST[ 'plan_matched_attributes_key' ] ) && ! empty( $_POST[ 'attribute_value_to_switch' ] ) ) {
            $switch_variation_from = $subscription_meta[ 'productid' ] ;
            $parent_order          = wc_get_order( $parent_order_id ) ;
            $swap_variation        = false ;
            $attributes            = array() ;

            foreach ( $_POST[ 'attribute_value_to_switch' ] as $each_attribute ) {
                $attributes[] = 'attribute_' . $each_attribute ;
            }

            //Prevent if User/Admin not selecting Attribute values on Submit.
            if ( $attributes == $_POST[ 'plan_matched_attributes_key' ] ) {
                wp_send_json( '2' ) ;
            }

            //Get Variation ID from Variation attributes selected to switch by Admin/User.
            $new_variations       = array_combine( $_POST[ 'plan_matched_attributes_key' ], $_POST[ 'attribute_value_to_switch' ] ) ;
            $matched_variation_id = SUMO_Subscription_Variation_Switcher::get_matched_variation( $_POST[ 'post_id' ], $new_variations, true ) ;

            $switch_variation_to = isset( $matched_variation_id[ 0 ] ) ? $matched_variation_id[ 0 ] : 0 ;

            $_switched_to_product   = wc_get_product( $switch_variation_to ) ;
            $_switched_from_product = wc_get_product( $switch_variation_from ) ;

            if ( $switch_variation_to > 0 ) {
                foreach ( $parent_order->get_items() as $item_id => $items ) {
                    //Update Parent Order Details
                    if ( $items[ 'variation_id' ] == $switch_variation_from && is_array( $_switched_to_product->get_variation_attributes() ) ) {
                        //Update New Variation.
                        wc_update_order_item_meta( $item_id, '_variation_id', $switch_variation_to ) ;
                        //Update New Variation Attributes.
                        foreach ( $new_variations as $key => $value ) {
                            wc_update_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value ) ;
                        }
                        //Is New Variation updated successfull in the Order item meta.
                        $swap_variation = wc_get_order_item_meta( $item_id, '_variation_id' ) == $switch_variation_to ;
                    }
                }

                //Is Valid to process Variation Swap.
                if ( $swap_variation ) {
                    //Swap Variation.
                    unset( $subscriptions_from_parent_order[ $subscription_meta[ 'productid' ] ] ) ;
                    $subscriptions_from_parent_order[ $switch_variation_to ] = absint( $_POST[ 'post_id' ] ) ;

                    $payment_info[ $switch_variation_to ] = $payment_info[ $subscription_meta[ 'productid' ] ] ;
                    unset( $payment_info[ $subscription_meta[ 'productid' ] ] ) ;

                    if ( is_array( $parent_order_item_data ) ) {
                        foreach ( $parent_order_item_data as $order_item_id => $data ) {
                            if ( ! isset( $data[ 'id' ] ) ) {
                                continue ;
                            }

                            if ( $subscription_meta[ 'productid' ] == $data[ 'id' ] ) {
                                $parent_order_item_data[ $order_item_id ][ 'id' ] = $switch_variation_to ;
                            }
                        }
                    }

                    $subscription_meta[ 'productid' ] = $switch_variation_to ;

                    //Update Subscription Details
                    update_post_meta( $_POST[ 'post_id' ], 'sumo_subscription_product_details', $subscription_meta ) ;
                    update_post_meta( $_POST[ 'post_id' ], 'sumo_subscription_parent_order_item_data', $parent_order_item_data ) ;
                    update_post_meta( $_POST[ 'post_id' ], 'sumo_product_name', wc_get_product( $switch_variation_to )->get_title() ) ;
                    update_post_meta( $parent_order_id, 'sumo_subsc_get_available_postids_from_parent_order', $subscriptions_from_parent_order ) ;
                    update_post_meta( $parent_order_id, 'sumosubscription_payment_order_information', $payment_info ) ;

                    $note = sprintf( __( '%s switched the Variation Subscription from <b>%s</b> to <b>%s</b>.', 'sumosubscriptions' ), $_POST[ 'switched_by' ], sumosubs_get_formatted_name( $_switched_from_product ), sumosubs_get_formatted_name( $_switched_to_product ) ) ;
                    sumo_add_subscription_note( $note, $_POST[ 'post_id' ], 'success', __( 'Subscription Variation Switch', 'sumosubscriptions' ) ) ;

                    //Success
                    $response_code = '1' ;
                }
            }
        }
        wp_send_json( $response_code ) ;
    }

    /**
     * Init data export
     */
    public static function init_data_export() {

        check_ajax_referer( 'subscription-exporter', 'security' ) ;

        $export_databy = array() ;
        parse_str( $_POST[ 'exportDataBy' ], $export_databy ) ;

        $json_args = array() ;
        $args      = array(
            'type'     => 'sumosubscriptions',
            'status'   => 'publish',
            'order_by' => 'DESC',
                ) ;

        if ( ! empty( $export_databy ) ) {
            if ( ! empty( $export_databy[ 'subscription_from_date' ] ) ) {
                $to_date              = ! empty( $export_databy[ 'subscription_to_date' ] ) ? strtotime( $export_databy[ 'subscription_to_date' ] ) : strtotime( date( 'Y-m-d' ) ) ;
                $args[ 'date_query' ] = array(
                    array(
                        'after'     => date( 'Y-m-d', strtotime( $export_databy[ 'subscription_from_date' ] ) ),
                        'before'    => array(
                            'year'  => date( 'Y', $to_date ),
                            'month' => date( 'm', $to_date ),
                            'day'   => date( 'd', $to_date ),
                        ),
                        'inclusive' => true,
                    ),
                        ) ;
            }

            $meta_query = array() ;
            if ( ! empty( $export_databy[ 'subscription_statuses' ] ) ) {
                $meta_query[] = array(
                    'key'     => 'sumo_get_status',
                    'value'   => ( array ) $export_databy[ 'subscription_statuses' ],
                    'compare' => 'IN'
                        ) ;
            }

            if ( ! empty( $export_databy[ 'subscription_buyers' ] ) ) {
                $meta_query[] = array(
                    'key'     => 'sumo_buyer_email',
                    'value'   => ( array ) $export_databy[ 'subscription_buyers' ],
                    'compare' => 'IN'
                        ) ;
            }

            if ( ! empty( $meta_query ) ) {
                $args[ 'meta_query' ] = array( 'relation' => 'AND' ) + $meta_query ;
            }
        }

        $subscriptions = sumosubscriptions()->query->get( $args ) ;

        if ( sizeof( $subscriptions ) <= 1 ) {
            if ( ! empty( $export_databy[ 'subscription_products' ] ) ) {
                foreach ( $subscriptions as $key => $subscription_id ) {
                    $subscription = sumo_get_subscription( $subscription_id ) ;

                    if ( $subscription && ! in_array( $subscription->get_subscribed_product(), ( array ) $export_databy[ 'subscription_products' ] ) ) {
                        unset( $subscriptions[ $key ] ) ;
                    }
                }
            }

            $json_args[ 'export' ]         = 'done' ;
            $json_args[ 'generated_data' ] = array_map( array( 'SUMO_Subscription_Exporter', 'generate_data' ), $subscriptions ) ;
            $json_args[ 'redirect_url' ]   = SUMO_Subscription_Exporter::get_download_url( $json_args[ 'generated_data' ] ) ;
        } else {
            $json_args[ 'export' ]        = 'processing' ;
            $json_args[ 'original_data' ] = $subscriptions ;
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => '',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMO_Subscription_Exporter::get_exporter_page_url(),
        ) ) ) ;
    }

    /**
     * Handle exported data
     */
    public static function handle_exported_data() {

        check_ajax_referer( 'subscription-exporter', 'security' ) ;

        $export_databy = array() ;
        parse_str( $_POST[ 'exportDataBy' ], $export_databy ) ;

        $subscriptions = array_filter( ( array ) $_POST[ 'chunkedData' ] ) ;
        if ( ! empty( $export_databy[ 'subscription_products' ] ) ) {
            foreach ( $subscriptions as $key => $subscription_id ) {
                $subscription = sumo_get_subscription( $subscription_id ) ;

                if ( $subscription && ! in_array( $subscription->get_subscribed_product(), ( array ) $export_databy[ 'subscription_products' ] ) ) {
                    unset( $subscriptions[ $key ] ) ;
                }
            }
        }

        $json_args                     = array() ;
        $pre_generated_data            = json_decode( stripslashes( $_POST[ 'generated_data' ] ) ) ;
        $new_generated_data            = array_map( array( 'SUMO_Subscription_Exporter', 'generate_data' ), $subscriptions ) ;
        $json_args[ 'generated_data' ] = array_values( array_filter( array_merge( array_filter( ( array ) $pre_generated_data ), $new_generated_data ) ) ) ;

        if ( absint( $_POST[ 'originalDataLength' ] ) === absint( $_POST[ 'step' ] ) ) {
            $json_args[ 'export' ]       = 'done' ;
            $json_args[ 'redirect_url' ] = SUMO_Subscription_Exporter::get_download_url( $json_args[ 'generated_data' ] ) ;
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => 'processing',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMO_Subscription_Exporter::get_exporter_page_url(),
        ) ) ) ;
    }

    /**
     * Save order subscription.
     */
    public static function checkout_order_subscription() {

        check_ajax_referer( 'update-order-subscription', 'security' ) ;

        if ( 'yes' === $_POST[ 'subscribed' ] ) {
            WC()->session->set( 'sumo_is_order_subscription_subscribed', 'yes' ) ;
            WC()->session->set( 'sumo_order_subscription_duration_period', $_POST[ 'subscription_duration' ] ) ;
            WC()->session->set( 'sumo_order_subscription_duration_length', $_POST[ 'subscription_duration_value' ] ) ;
            WC()->session->set( 'sumo_order_subscription_recurring_length', $_POST[ 'subscription_recurring' ] ) ;
        } else {
            WC()->session->set( 'sumo_is_order_subscription_subscribed', 'no' ) ;
            WC()->session->set( 'sumo_order_subscription_duration_period', '' ) ;
            WC()->session->set( 'sumo_order_subscription_duration_length', '' ) ;
            WC()->session->set( 'sumo_order_subscription_recurring_length', '' ) ;
        }
        die() ;
    }

    /**
     * Process bulk update.
     */
    public static function bulk_update_product_meta() {

        check_ajax_referer( 'bulk-update-subscription', 'security' ) ;

        if ( $_POST[ 'is_bulk_update' ] === 'true' ) {

            $products = get_posts( array(
                'post_type'      => 'product',
                'posts_per_page' => '-1',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'cache_results'  => false
                    ) ) ;

            if ( ! is_array( $products ) || ! $products ) {
                die() ;
            }

            switch ( $_POST[ 'select_type' ] ) {
                case '1':
                    //Every Products published in the Site.
                    wp_send_json( $products ) ;
                    break ;
                case '2':
                    //Selected Products.
                    $selected_products = is_array( $_POST[ 'selected_products' ] ) ? $_POST[ 'selected_products' ] : explode( ',', $_POST[ 'selected_products' ] ) ;

                    foreach ( $selected_products as $product_id ) {
                        if ( ! $product_id ) {
                            continue ;
                        }

                        self:: update_subscription_product_meta( $product_id ) ;
                    }

                    wp_send_json( 'success' ) ;
                    break ;
                case '3':
                    //All Categories.
                    foreach ( $products as $product_id ) {
                        $_product = wc_get_product( $product_id ) ;

                        if ( ! $_product ) {
                            continue ;
                        }

                        switch ( sumosubs_get_product_type( $_product ) ) {
                            case 'variable':
                                $terms = get_the_terms( sumosubs_get_product_id( $_product ), 'product_cat' ) ;

                                if ( ! is_array( $terms ) || ! is_array( $_product->get_available_variations() ) ) {
                                    continue 2 ;
                                }

                                foreach ( $_product->get_available_variations() as $variation_data ) {
                                    if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                        continue ;
                                    }

                                    self:: update_subscription_product_meta( $variation_data[ 'variation_id' ] ) ;
                                }
                                break ;
                            default:
                                $terms = get_the_terms( $product_id, 'product_cat' ) ;

                                if ( ! is_array( $terms ) ) {
                                    continue 2 ;
                                }

                                self:: update_subscription_product_meta( $product_id ) ;
                                break ;
                        }
                    }
                    wp_send_json( 'success' ) ;
                    break ;
                case '4':
                    //Selected Categories.
                    $selected_categories = $_POST[ 'selected_category' ] ;

                    if ( ! is_array( $selected_categories ) || ! $selected_categories ) {
                        die() ;
                    }

                    foreach ( $products as $product_id ) {
                        $_product = wc_get_product( $product_id ) ;

                        if ( ! $_product ) {
                            continue ;
                        }

                        switch ( sumosubs_get_product_type( $_product ) ) {
                            case 'variable':
                                $terms          = get_the_terms( sumosubs_get_product_id( $_product ), 'product_cat' ) ;
                                $is_in_category = false ;

                                if ( ! is_array( $terms ) || ! is_array( $_product->get_available_variations() ) ) {
                                    continue 2 ;
                                }

                                foreach ( $terms as $term ) {
                                    if ( ! in_array( $term->term_id, $selected_categories ) ) {
                                        continue ;
                                    }

                                    $is_in_category = true ;
                                    break ;
                                }

                                if ( ! $is_in_category ) {
                                    break ;
                                }

                                foreach ( $_product->get_available_variations() as $variation_data ) {
                                    if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                        continue ;
                                    }

                                    self:: update_subscription_product_meta( $variation_data[ 'variation_id' ] ) ;
                                }
                                break ;
                            default:
                                $terms = get_the_terms( $product_id, 'product_cat' ) ;

                                if ( ! is_array( $terms ) ) {
                                    continue 2 ;
                                }

                                foreach ( $terms as $term ) {
                                    if ( ! in_array( $term->term_id, $selected_categories ) ) {
                                        continue ;
                                    }
                                    self:: update_subscription_product_meta( $product_id ) ;
                                    break ;
                                }
                                break ;
                        }
                    }
                    wp_send_json( 'success' ) ;
                    break ;
            }
        }
        die() ;
    }

    /**
     * Optimize bulk update.
     */
    public static function optimize_bulk_updation_of_product_meta() {

        check_ajax_referer( 'bulk-update-optimization', 'security' ) ;

        if ( is_array( $_POST[ 'ids' ] ) && $_POST[ 'ids' ] ) {
            $products = $_POST[ 'ids' ] ;

            foreach ( $products as $product_id ) {
                $_product = wc_get_product( $product_id ) ;

                if ( ! $_product ) {
                    continue ;
                }

                update_post_meta( $product_id, 'sumo_subscription_version', SUMO_SUBSCRIPTIONS_VERSION ) ;

                switch ( sumosubs_get_product_type( $_product ) ) {
                    case 'variable':
                        if ( ! is_array( $_product->get_available_variations() ) ) {
                            continue 2 ;
                        }

                        foreach ( $_product->get_available_variations() as $variation_data ) {
                            if ( ! isset( $variation_data[ 'variation_id' ] ) ) {
                                continue ;
                            }

                            self:: update_subscription_product_meta( $variation_data[ 'variation_id' ] ) ;
                        }
                        break ;
                    default:
                        self:: update_subscription_product_meta( $product_id ) ;
                        break ;
                }
            }
        }
        die() ;
    }

    /**
     * Get HTML fields of wc-product-search and wc-user-role-multiselect
     */
    public static function get_subscription_as_regular_html_data() {

        check_ajax_referer( 'subscription-as-regular-html-data', 'security' ) ;

        include_once( 'admin/settings-page/class-advance-settings.php' ) ;

        $advance_tab = new SUMOSubscriptions_Advance_Settings() ;

        wp_send_json( array(
            'wc_product_search'        => $advance_tab->wc_product_search( $_POST[ 'rowID' ] ),
            'wc_user_role_multiselect' => $advance_tab->wc_user_role_multiselect( $_POST[ 'rowID' ] )
        ) ) ;
    }

    /**
     * Update Subscription Product post Meta.
     * @param int $product_id The Product post ID Either Product ID or Variation ID
     * @return int
     */
    public static function update_subscription_product_meta( $product_id ) {
        if ( ! $_POST ) {
            return ;
        }

        if ( $_POST[ 'update_enable_subscription' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_susbcription_status', $_POST[ 'enable_subscription' ] ) ;

            //Sets Default value.
            update_post_meta( $product_id, 'sumo_susbcription_period', 'D' ) ;
            update_post_meta( $product_id, 'sumo_susbcription_period_value', '1' ) ;
        }

        if ( $_POST[ 'update_subscription_duration' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_susbcription_period', $_POST[ 'subscription_duration' ] ) ;

            if ( $_POST[ 'subscription_duration' ] === 'D' ) {
                update_post_meta( $product_id, 'sumo_susbcription_period_value', $_POST[ 'subscription_value_days' ] ) ;
            } elseif ( $_POST[ 'subscription_duration' ] === 'W' ) {
                update_post_meta( $product_id, 'sumo_susbcription_period_value', $_POST[ 'subscription_value_weeks' ] ) ;
            } elseif ( $_POST[ 'subscription_duration' ] === 'M' ) {
                update_post_meta( $product_id, 'sumo_susbcription_period_value', $_POST[ 'subscription_value_months' ] ) ;
            } else {
                update_post_meta( $product_id, 'sumo_susbcription_period_value', $_POST[ 'subscription_value_years' ] ) ;
            }
        }
        if ( $_POST[ 'update_trial_period' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_susbcription_trial_enable_disable', $_POST[ 'trial_period' ] ) ;
        }
        if ( $_POST[ 'update_fee_type' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_susbcription_fee_type_selector', $_POST[ 'trial_fee_type' ] ) ;
        }
        if ( $_POST[ 'update_fee_value' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_trial_price', $_POST[ 'trial_fee_value' ] ) ;
        }
        if ( $_POST[ 'update_trial_duration' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_trial_period', $_POST[ 'trial_duration' ] ) ;

            if ( $_POST[ 'trial_duration' ] === 'D' ) {
                update_post_meta( $product_id, 'sumo_trial_period_value', $_POST[ 'trial_value_days' ] ) ;
            } elseif ( $_POST[ 'trial_duration' ] === 'W' ) {
                update_post_meta( $product_id, 'sumo_trial_period_value', $_POST[ 'trial_value_weeks' ] ) ;
            } elseif ( $_POST[ 'trial_duration' ] === 'M' ) {
                update_post_meta( $product_id, 'sumo_trial_period_value', $_POST[ 'trial_value_months' ] ) ;
            } else {
                update_post_meta( $product_id, 'sumo_trial_period_value', $_POST[ 'trial_value_years' ] ) ;
            }
        }
        if ( $_POST[ 'update_signup_fee' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_susbcription_signusumoee_enable_disable', $_POST[ 'signup_fee' ] ) ;
        }
        if ( $_POST[ 'update_signup_fee_value' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_signup_price', $_POST[ 'signup_fee_value' ] ) ;
        }
        if ( $_POST[ 'update_recurring_cycle' ] === '1' ) {
            update_post_meta( $product_id, 'sumo_recurring_period_value', $_POST[ 'recurring_cycle' ] ) ;
        }
        return 1 ;
    }

    /**
     * JSON Search Product and Variations
     * @param array $meta_query
     */
    public static function json_search_products_and_variations( $meta_query = array() ) {

        ob_start() ;

        if ( sumosubs_is_wc_version( '>=', '2.3.10' ) ) {
            check_ajax_referer( 'search-products', 'security' ) ;
        }

        $term    = ( string ) wc_clean( stripslashes( isset( $_GET[ 'term' ] ) ? $_GET[ 'term' ] : '' ) ) ;
        $exclude = array() ;

        if ( isset( $_GET[ 'exclude' ] ) && ! empty( $_GET[ 'exclude' ] ) ) {
            $exclude = array_map( 'intval', explode( ',', $_GET[ 'exclude' ] ) ) ;
        }

        $args = array(
            'post_type'      => array( 'product', 'product_variation' ),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'orderby'        => 'parent title',
            'meta_query'     => is_array( $meta_query ) ? $meta_query : array(),
            's'              => $term,
            'exclude'        => $exclude
                ) ;

        $posts          = get_posts( $args ) ;
        $found_products = array() ;

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {

                if ( sumosubs_is_wc_version( '<', '2.2' ) ) {
                    $product = get_product( $post->ID ) ;
                } else {
                    $product = wc_get_product( $post->ID ) ;
                }

                if ( ! current_user_can( 'read_product', $post->ID ) ) {
                    continue ;
                }

                if ( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $post->ID ) ) {
                    continue ;
                }

                $found_products[ $post->ID ] = $product->get_formatted_name() ;
            }
        }

        wp_send_json( $found_products ) ;
    }

    /**
     * Search Subscription Products and Variations without SUMO Memberships products which are linked with.
     */
    public static function json_search_subscription_products_and_variations() {
        self::json_search_products_and_variations( array(
            array(
                'key'     => 'sumo_susbcription_status',
                'value'   => '1',
                'type'    => 'numeric',
                'compare' => 'LIKE'
            ),
        ) ) ;
    }

    /**
     * Search Downloadable Non Subscription and Non Membership Products and Variations.
     */
    public static function json_search_downloadable_products_and_variations() {
        self::json_search_products_and_variations( array(
            array(
                'key'   => '_downloadable',
                'value' => 'yes'
            ),
            array(
                'key'     => 'sumo_susbcription_status',
                'value'   => '1',
                'compare' => '!='
            )
        ) ) ;
    }

    /**
     * Search for customers by email and return json.
     */
    public static function json_search_customers_by_email() {
        ob_start() ;

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 ) ;
        }

        $term  = wc_clean( wp_unslash( $_GET[ 'term' ] ) ) ;
        $limit = '' ;

        if ( empty( $term ) ) {
            wp_die() ;
        }

        $ids = array() ;
        // Search by ID.
        if ( is_numeric( $term ) ) {
            $customer = new WC_Customer( intval( $term ) ) ;

            // Customer does not exists.
            if ( 0 !== $customer->get_id() ) {
                $ids = array( $customer->get_id() ) ;
            }
        }

        // Usernames can be numeric so we first check that no users was found by ID before searching for numeric username, this prevents performance issues with ID lookups.
        if ( empty( $ids ) ) {
            $data_store = WC_Data_Store::load( 'customer' ) ;

            // If search is smaller than 3 characters, limit result set to avoid
            // too many rows being returned.
            if ( 3 > strlen( $term ) ) {
                $limit = 20 ;
            }
            $ids = $data_store->search_customers( $term, $limit ) ;
        }

        $found_customers = array() ;
        if ( ! empty( $_GET[ 'exclude' ] ) ) {
            $ids = array_diff( $ids, ( array ) $_GET[ 'exclude' ] ) ;
        }

        foreach ( $ids as $id ) {
            $customer                                  = new WC_Customer( $id ) ;
            /* translators: 1: user display name 2: user ID 3: user email */
            $found_customers[ $customer->get_email() ] = sprintf(
                    esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'sumosubscriptions' ), $customer->get_first_name() . ' ' . $customer->get_last_name(), $customer->get_id(), $customer->get_email()
                    ) ;
        }

        wp_send_json( $found_customers ) ;
    }

}

SUMOSubscriptions_Ajax::init() ;
