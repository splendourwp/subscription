<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Get Subscription Next Renewal/Due date.
 * @param int $post_id
 * @param int $product_id
 * @param array $args
 * @return mixed
 */
function sumosubs_get_next_payment_date( $post_id = 0, $product_id = 0, $args = array() ) {
    $next_payment_time = 0 ;
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id ) ;

    if ( is_numeric( $post_id ) && $post_id ) {
        $use_trial = 'Trial' === get_post_meta( $post_id, 'sumo_get_status', true ) || in_array( get_post_meta( $post_id, 'sumo_subscription_awaiting_status', true ), array( 'free-trial', 'paid-trial', 'Trial' ) ) ;
    } else {
        $use_trial = '1' === $subscription_plan[ 'trial_status' ] ;
    }

    $args = wp_parse_args( $args, array(
        'from_when'           => sumo_get_subscription_timestamp(),
        'initial_payment'     => false,
        'paused_to_resume'    => false,
        'due_date_exceeds'    => false,
        'get_as_timestamp'    => false,
        'use_trial_if_exists' => true
            ) ) ;

    $trial_enabled = $use_trial && $args[ 'use_trial_if_exists' ] ? true : false ;

    //May be subscription is gonna resume
    if ( $post_id && $args[ 'paused_to_resume' ] ) {
        $duration_gap_on_paused = get_post_meta( $post_id, 'sumo_time_gap_on_paused', true ) ;

        if ( isset( $duration_gap_on_paused[ 'current_time_on_paused' ] ) && isset( $duration_gap_on_paused[ 'previous_due_date' ] ) ) {
            $previous_due_date = sumo_get_subscription_timestamp( $duration_gap_on_paused[ 'previous_due_date' ] ) ;
            $paused_time       = $duration_gap_on_paused[ 'current_time_on_paused' ] ;

            //Get Next Due Time after the Subscription is Resumed.
            if ( $args[ 'due_date_exceeds' ] ) {
                $next_payment_time = sumo_get_subscription_timestamp() + absint( $previous_due_date - $paused_time ) ;
            } else {
                $next_payment_time = absint( (sumo_get_subscription_timestamp() - $paused_time) + $previous_due_date ) ;
            }
        }
    } else if ( SUMO_Subscription_Synchronization::is_subscription_synced( $post_id ? $post_id : $product_id  ) ) {
        $next_payment_time = SUMO_Subscription_Synchronization::get_sync_time( $trial_enabled, $args[ 'initial_payment' ], $args[ 'from_when' ] ) ;
        //May be it is normal subscription
    } else if ( is_numeric( $args[ 'from_when' ] ) && $args[ 'from_when' ] > 0 ) {
        //May be trial is on going for the subscription
        if ( $trial_enabled ) {
            $plan_period = sumo_format_subscription_cyle( $subscription_plan[ 'trial_duration_value' ] . ' ' . $subscription_plan[ 'trial_duration' ] ) ;
        } else {
            $plan_period = sumo_format_subscription_cyle( $subscription_plan[ 'subscription_duration_value' ] . ' ' . $subscription_plan[ 'subscription_duration' ] ) ;
        }
        $next_payment_time = sumo_get_subscription_timestamp( "+{$plan_period}", $args[ 'from_when' ] ) ; //Get Next Due Time.
    }

    if ( ! $args[ 'get_as_timestamp' ] ) {
        $next_payment_time = sumo_get_subscription_date( $next_payment_time ) ;
    }
    return apply_filters( 'sumosubscriptions_get_next_payment_date', $next_payment_time, $post_id, $product_id, $trial_enabled, $args ) ;
}

/**
 * Resume Subscription if gets Paused by Admin/Subscriber
 * @param int $post_id The Subscription post ID
 * @param string $resume_by Admin | Subscriber
 */
function sumo_resume_subscription( $post_id, $resume_by = '' ) {

    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return ;
    }

    update_post_meta( $post_id, 'sumo_subscription_resume_requested_by', $resume_by ) ;

    switch ( $resume_by ) {
        case 'subscriber':
            $note_for_trial  = __( 'User has Resumed the Subscription Trial.', 'sumosubscriptions' ) ;
            $note_for_active = __( 'User has Resumed the Subscription.', 'sumosubscriptions' ) ;
            break ;
        case 'admin-in-bulk':
            $note_for_trial  = __( 'Subscription status changed by bulk edit: Admin has Manually Resumed the Subscription Trial.', 'sumosubscriptions' ) ;
            $note_for_active = __( 'Subscription status changed by bulk edit: Admin has Manually Resumed the Subscription.', 'sumosubscriptions' ) ;
            break ;
        case 'admin':
            $note_for_trial  = __( 'Admin has Manually Resumed the Subscription Trial.', 'sumosubscriptions' ) ;
            $note_for_active = __( 'Admin has Manually Resumed the Subscription.', 'sumosubscriptions' ) ;
            break ;
        default:
            $note_for_trial  = __( 'Subscription Trial has been Resumed.', 'sumosubscriptions' ) ;
            $note_for_active = __( 'Subscription has been Resumed.', 'sumosubscriptions' ) ;
            break ;
    }

    //On Resume, check the previous status was on Trial
    if ( 'Trial' === get_post_meta( $post_id, 'sumo_check_trial_status', true ) ) {
        update_post_meta( $post_id, 'sumo_get_status', 'Trial' ) ;

        sumo_add_subscription_note( $note_for_trial, $post_id, sumo_note_status( 'Active' ), __( 'Subscription Resumed', 'sumosubscriptions' ) ) ;
    } else {
        delete_post_meta( $post_id, 'sumo_check_trial_status' ) ;
        update_post_meta( $post_id, 'sumo_get_status', 'Active' ) ;

        sumo_add_subscription_note( $note_for_active, $post_id, sumo_note_status( 'Active' ), __( 'Subscription Resumed', 'sumosubscriptions' ) ) ;
    }
    delete_post_meta( $post_id, 'sumo_subscription_auto_resume_scheduled_on' ) ;

    $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
    $cron_event->unset_events( 'automatic_resume' ) ;

    SUMOSubscriptions_Order::set_next_payment_date( $post_id ) ;

    sumo_trigger_subscription_email( 'subscription_processing_order', null, $post_id ) ;

    sumo_set_subscription_inaccessible_time_from_to( $post_id, 'to' ) ;
}

/**
 * Pause Subscription by Admin/Subscriber.
 * @param int $post_id The Subscription post ID
 * @param string $note
 * @param string $pause_by Admin | Subscriber
 */
function sumo_pause_subscription( $post_id, $note = '', $pause_by = '' ) {

    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return ;
    }

    //Update for checking Trial status on Subscription Resume.
    update_post_meta( $post_id, 'sumo_check_trial_status', get_post_meta( $post_id, 'sumo_get_status', true ) ) ;
    update_post_meta( $post_id, 'sumo_get_status', 'Pause' ) ; //Update Subscription status as Paused.
    update_post_meta( $post_id, 'sumo_subscription_pause_requested_by', $pause_by ) ;
    //Valid alone for Subscribers
    if ( $pause_by === 'subscriber' ) {
        $previous_count = absint( get_post_meta( $post_id, 'sumo_no_of_pause_count', true ) ) ;
        $pause_count    = 0 ;

        if ( absint( get_option( 'sumo_settings_max_no_of_pause', '0' ) > 0 ) ) {
            $pause_count = $previous_count === 0 ? 1 : $previous_count + 1 ;
        }
        update_post_meta( $post_id, 'sumo_no_of_pause_count', $pause_count ) ;
    }

    $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
    $cron_event->unset_events( array(
        'create_renewal_order',
        'notify_overdue',
        'notify_suspend',
        'notify_cancel',
        'notify_expire',
        'automatic_pay',
        'switch_to_manual_pay_mode',
        'notify_expiry_reminder'
    ) ) ;

    sumo_set_subscription_inaccessible_time_from_to( $post_id, 'from' ) ;

    update_post_meta( $post_id, 'sumo_time_gap_on_paused', array(
        'current_time_on_paused' => sumo_get_subscription_timestamp(),
        'previous_due_date'      => get_post_meta( $post_id, 'sumo_get_next_payment_date', true )
    ) ) ;
    update_post_meta( $post_id, 'sumo_get_next_payment_date', 'N/A' ) ;

    if ( '' === $note ) {
        switch ( $pause_by ) {
            case 'admin':
                $note = __( 'Admin has Manually Paused the Subscription.', 'sumosubscriptions' ) ;
                break ;
            case 'admin-in-bulk':
                $note = __( 'Subscription status changed by bulk edit: Admin has Manually Paused the Subscription.', 'sumosubscriptions' ) ;
                break ;
            case 'subscriber':
                $note = __( 'User has Paused the Subscription.', 'sumosubscriptions' ) ;
                break ;
            default:
                $note = __( 'Subscription has been Paused.', 'sumosubscriptions' ) ;
                break ;
        }
    }

    sumo_add_subscription_note( $note, $post_id, sumo_note_status( 'Pause' ), __( 'Subscription Paused', 'sumosubscriptions' ) ) ;
    sumo_trigger_subscription_email( 'subscription_pause_order', null, $post_id ) ;
}

/**
 * Cancel Subscription by Admin/Subscriber.
 * @param int $post_id The Subscription post ID
 * @param string $note
 * @param string $cancel_by Admin | Subscriber
 */
function sumo_cancel_subscription( $post_id, $note = '', $cancel_by = '' ) {

    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return ;
    }

    do_action( 'sumosubscriptions_before_cancel_subscription', $post_id ) ;

    update_post_meta( $post_id, 'sumo_get_status', 'Cancelled' ) ;
    update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' ) ;
    update_post_meta( $post_id, 'sumo_get_sub_end_date', sumo_get_subscription_date() ) ;
    update_post_meta( $post_id, 'sumo_subscription_cancel_requested_by', $cancel_by ) ;
    delete_post_meta( $post_id, 'sumo_subscription_awaiting_status' ) ;

    $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
    $cron_event->unset_events() ;

    if ( '' === $note ) {
        switch ( $cancel_by ) {
            case 'admin':
                $note = __( 'Admin has Manually Cancelled the Subscription.', 'sumosubscriptions' ) ;
                break ;
            case 'subscriber':
                $note = __( 'User has Cancelled the Subscription.', 'sumosubscriptions' ) ;
                break ;
            default:
                $note = __( 'Subscription has been Cancelled.', 'sumosubscriptions' ) ;
                break ;
        }
    }

    SUMO_Subscription_Resubscribe::may_be_unset_resubscribed_associated_subscriptions( $post_id ) ;

    sumo_add_subscription_note( $note, $post_id, 'success', __( 'Subscription Cancelled', 'sumosubscriptions' ) ) ;
    sumo_trigger_subscription_email( 'subscription_cancel_order', null, $post_id ) ;
}

/**
 * Expire Subscription.
 * @param int $post_id The Subscription post ID
 * @param string $expiry_on
 * @param boolean $unschedule_crons
 */
function sumo_expire_subscription( $post_id, $expiry_on = '', $unschedule_crons = true ) {
    $sub_end_date = sumo_get_subscription_date() ;

    if ( in_array( get_post_meta( $post_id, 'sumo_get_status', true ), array( 'Cancelled', 'Failed', 'Expired' ) ) ) {
        return ;
    }

    update_post_meta( $post_id, 'sumo_get_status', 'Expired' ) ;
    update_post_meta( $post_id, 'sumo_get_saved_due_date', '' === $expiry_on ? get_post_meta( $post_id, 'sumo_get_next_payment_date', true ) : $expiry_on  ) ; //Save Expiry date.
    update_post_meta( $post_id, 'sumo_get_next_payment_date', '--' ) ;
    update_post_meta( $post_id, 'sumo_get_sub_end_date', $sub_end_date ) ;
    update_post_meta( $post_id, 'sumo_get_sub_exp_date', $sub_end_date ) ;
    delete_post_meta( $post_id, 'sumo_subscription_awaiting_status' ) ;

    if ( $unschedule_crons ) {
        $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
        $cron_event->unset_events() ;
    }

    SUMO_Subscription_Resubscribe::may_be_unset_resubscribed_associated_subscriptions( $post_id ) ;

    sumo_add_subscription_note( __( 'Subscription is Expired', 'sumosubscriptions' ), $post_id, sumo_note_status( 'Expired' ), __( 'Subscription Expired', 'sumosubscriptions' ) ) ;
    sumo_trigger_subscription_email( 'subscription_expired_order', null, $post_id ) ;
}

/**
 * Set Subscription inaccessible times from to
 * @param int $post_id The Subscription post ID
 */
function sumo_set_subscription_inaccessible_time_from_to( $post_id, $set = '' ) {

    $from                      = 0 ;
    $to                        = 0 ;
    $inaccessible_time_from_to = get_post_meta( $post_id, 'sumo_subscription_inaccessible_time_from_to', true ) ;

    switch ( $set ) {
        case 'from':
            $from = sumo_get_subscription_timestamp() ;

            if ( is_array( $inaccessible_time_from_to ) ) {
                foreach ( $inaccessible_time_from_to as $from_time => $to_time ) {
                    if ( 0 === $to_time ) {
                        $from = 0 ;
                        $to   = 0 ;
                        break ;
                    }
                }
            }
            break ;
        case 'to':
            if ( is_array( $inaccessible_time_from_to ) ) {
                foreach ( $inaccessible_time_from_to as $from_time => $to_time ) {
                    if ( 0 === $to_time ) {
                        $from = $from_time ;
                        $to   = sumo_get_subscription_timestamp() ;
                        break ;
                    }
                }
            }
            break ;
    }

    if ( 0 === $from ) {
        return ;
    }

    add_post_meta( $post_id, 'sumo_subscription_inaccessible_time_from_to', array(
        $from => $to
    ) ) ;

    update_post_meta( $post_id, 'sumo_subscription_inaccessible_time_from_to', array(
        $from => $to
            ) + get_post_meta( $post_id, 'sumo_subscription_inaccessible_time_from_to', true ) ) ;
}

/**
 * Fetch responsed data via PHP cURL API.
 * @param string $url PayPal Endpoint URL
 * @param array $headers
 * @param array $data
 * @return object from paypal
 */
function sumo_get_cURL_response( $url, $headers, $data ) {
    $ch         = curl_init() ;
    $get_header = array() ;

    curl_setopt( $ch, CURLOPT_URL, $url ) ;
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ) ;
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ) ;
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false ) ;
    curl_setopt( $ch, CURLOPT_SSLVERSION, 6 ) ;
    curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) ) ;

    if ( ! empty( $headers ) ) {
        foreach ( $headers as $name => $value ) {
            $get_header[] = "{$name}: $value" ;
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $get_header ) ;
    } else {
        curl_setopt( $ch, CURLOPT_HEADER, false ) ;
    }

    $response = curl_exec( $ch ) ;

    curl_close( $ch ) ;

    return $response ;
}

/**
 * Get Subscription Recurring fee. 
 * @param int $post_id The Subscription post ID
 * @param array $order_item may be Parent Order items
 * @param int $order_item_id may be Parent Order item id
 * @param boolean $calc_with_qty calculate line total with the respective item qty
 * @return float|int
 */
function sumo_get_recurring_fee( $post_id, $order_item = array(), $order_item_id = 0, $calc_with_qty = true ) {
    $subscription_meta = sumo_get_subscription_meta( $post_id ) ;
    $order_item_data   = get_post_meta( $post_id, 'sumo_subscription_parent_order_item_data', true ) ;

    $product_id  = ! empty( $subscription_meta[ 'productid' ] ) ? $subscription_meta[ 'productid' ] : 0 ;
    $product_qty = ! empty( $subscription_meta[ 'product_qty' ] ) ? absint( $subscription_meta[ 'product_qty' ] ) : 1 ;

    $item_total = 0 ;
    if ( isset( $subscription_meta[ 'sale_fee' ] ) && is_numeric( $subscription_meta[ 'sale_fee' ] ) ) {
        $item_total = $subscription_meta[ 'sale_fee' ] ;
    } else if ( isset( $subscription_meta[ 'subfee' ] ) && is_numeric( $subscription_meta[ 'subfee' ] ) ) {
        $item_total = $subscription_meta[ 'subfee' ] ;
    }
    $subscription_fee = $item_total ;

    if ( isset( $order_item[ 'product_id' ] ) ) {
        $product_id  = $order_item[ 'variation_id' ] > 0 ? $order_item[ 'variation_id' ] : $order_item[ 'product_id' ] ;
        $product_qty = is_numeric( $order_item[ 'qty' ] ) && $order_item[ 'qty' ] ? $order_item[ 'qty' ] : 1 ;
    }

    if ( SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
        if ( ! $order_item && ! $order_item_id ) {
            $product_qty = 1 ;
        }
        if ( ! is_array( $product_id ) && isset( $subscription_meta[ 'item_fee' ][ $product_id ] ) ) {
            $item_total = $subscription_meta[ 'item_fee' ][ $product_id ] ;
        }

        $item_total *= ($calc_with_qty ? $product_qty : 1) ;
        //May be Admin had set custom subscription fee for this subscription.
    } else if ( sumosubs_recurring_fee_has_changed( $post_id ) ) {
        $item_total = floatval( wc_format_decimal( get_post_meta( $post_id, 'sumo_get_updated_renewal_fee', true ) ) ) ;
    } else {
        if ( 'current_fee' === get_option( 'sumosubs_apply_subscription_fee_by', 'time_of_purchase_fee' ) && 'manual' === sumo_get_payment_type( $post_id ) ) {
            $subscription_meta = sumo_get_subscription_meta( 0, $product_id ) ;

            if ( isset( $subscription_meta[ 'sale_fee' ] ) && is_numeric( $subscription_meta[ 'sale_fee' ] ) ) {
                $item_total = $subscription_meta[ 'sale_fee' ] ;
            } else if ( isset( $subscription_meta[ 'subfee' ] ) && is_numeric( $subscription_meta[ 'subfee' ] ) ) {
                $item_total = $subscription_meta[ 'subfee' ] ;
            }
        }
        $item_total *= ($calc_with_qty ? $product_qty : 1) ;

        //Calculate with Addon Amount if it is applicable in this Subscription
        if ( sumo_subscription_has_addon_amount( $post_id ) ) {
            if ( isset( $order_item_data[ $order_item_id ][ 'addon' ] ) && $order_item_data[ $order_item_id ][ 'addon' ] > 0 ) {
                $item_total = $subscription_fee + $order_item_data[ $order_item_id ][ 'addon' ] ;
                $item_total *= ($calc_with_qty ? $product_qty : 1) ;
            } else if ( ! $order_item && ! $order_item_id && is_array( $order_item_data ) ) {

                $item_total = 0 ;
                foreach ( $order_item_data as $_item_id => $_item ) {
                    if ( ! isset( $_item[ 'addon' ] ) ) {
                        continue ;
                    }
                    $_item_qty = $calc_with_qty ? $_item[ 'qty' ] : 1 ;

                    if ( $_item[ 'addon' ] > 0 ) {
                        $item_total += (($subscription_fee + $_item[ 'addon' ]) * $_item_qty) ;
                    } else {
                        $item_total += ($subscription_fee * $_item_qty) ;
                    }
                }
            }
        }
    }
    return apply_filters( 'sumosubscriptions_renewal_item_total', $item_total, $product_id, $post_id ) ;
}

/**
 * Save/Update the Subscription Order Payment Information.
 * @param int $order_id The Order post ID
 * @param array $args
 * @param int $product_id . To update custom index from the array.
 * @return boolean
 */
function sumo_save_subscription_payment_info( $order_id, $args = array(), $product_id = '' ) {
    $parent_order_id = sumosubs_get_parent_order_id( $order_id ) ;

    if ( ! $parent_order = wc_get_order( $parent_order_id ) ) {
        return ;
    }

    $payment_info       = get_post_meta( $parent_order_id, 'sumosubscription_payment_order_information', true ) ;
    $subscription_items = sumo_get_subscription_items_from( $order_id ) ;

    $defaults = array(
        'payment_type'   => '',
        'payment_method' => '',
        'payment_key'    => '',
        'profile_id'     => '',
            ) ;

    $args = wp_parse_args( $args, $defaults ) ;

    if ( ! is_array( $payment_info ) ) {
        $payment_info = array() ;
    }

    if ( SUMO_Order_Subscription::is_subscribed( 0, $parent_order_id, sumosubs_get_order_customer_id( $order_id ) ) ) {

        update_post_meta( $parent_order_id, 'sumosubscription_payment_order_information', $args ) ;
    } else {
        if ( is_numeric( $product_id ) && $product_id ) {
            //Update custom index field.
            $payment_info[ $product_id ] = array(
                'payment_type'   => $args[ 'payment_type' ],
                'payment_method' => $args[ 'payment_method' ],
                'payment_key'    => $args[ 'payment_key' ],
                'profile_id'     => $args[ 'profile_id' ],
                    ) ;
        } else {
            foreach ( $subscription_items as $item_id ) {
                if ( ! $item_id ) {
                    continue ;
                }

                $payment_info[ $item_id ] = array(
                    'payment_type'   => $args[ 'payment_type' ],
                    'payment_method' => $args[ 'payment_method' ],
                    'payment_key'    => $args[ 'payment_key' ],
                    'profile_id'     => $args[ 'profile_id' ],
                        ) ;
            }
        }

        update_post_meta( $parent_order_id, 'sumosubscription_payment_order_information', $payment_info ) ;
    }

    return true ;
}

/**
 * Trigger Subscription Email.
 * 
 * @param string $template_id The Mail Template ID
 * @param int $order_id The Order post ID
 * @param int $post_id The Subscription post ID || Parent Order post ID
 * @param boolean $manual True, may be Admin has manually triggered the Subscription email
 */
function sumo_trigger_subscription_email( $template_id, $order_id = null, $post_id = null, $manual = false ) {
    $post_id = absint( $post_id ) ;
    $mails   = WC()->mailer()->get_emails() ; //Load Mailer.

    if ( empty( $mails ) || empty( $template_id ) || ( ! $post_id && ! $order_id) ) {
        return false ;
    }

    $order_id = $order_id ? $order_id : get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) ;

    if ( ! $order = wc_get_order( $order_id ) ) {
        return false ;
    }

    foreach ( $mails as $mail ) {
        if ( in_array( $mail->id, array( $template_id ) ) ) {
            $receiver_email = get_post_meta( $post_id, 'sumo_buyer_email', true ) ;

            if ( $is_mail_sent = $mail->trigger( $order_id, $post_id, $receiver_email ) ) {
                $text = $manual ? __( 'manually', 'sumosubscriptions' ) : sprintf( __( 'for an Order #%s ', 'sumosubscriptions' ), $order_id ) ;
                $note = sprintf( __( '%s email is created %s and has been sent to %s.', 'sumosubscriptions' ), $mail->title, $text, $mail->recipient ) ;
                sumo_add_subscription_note( $note, $post_id, sumo_note_status( get_post_meta( $post_id, 'sumo_get_status', true ) ), sprintf( __( '%s Email Sent', 'sumosubscriptions' ), $mail->title ) ) ;
            }
            return $is_mail_sent ;
        }
    }
    return false ;
}

/**
 * Get SUMO Subscriptions templates.
 *
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR)
 * @param string $default_path (default: SUMO_SUBSCRIPTIONS_TEMPLATE_PATH)
 */
function sumosubscriptions_get_template( $template_name, $args = array(), $template_path = SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR, $default_path = SUMO_SUBSCRIPTIONS_TEMPLATE_PATH ) {
    if ( ! $template_name ) {
        return ;
    }

    wc_get_template( $template_name, $args, $template_path, $default_path ) ;
}
