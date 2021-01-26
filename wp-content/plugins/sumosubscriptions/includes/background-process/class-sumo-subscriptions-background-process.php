<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle when Recurrence cron gets elapsed
 * 
 * @class SUMOSubscriptions_Background_Process
 * @category Class
 */
class SUMOSubscriptions_Background_Process {

    /**
     * Cron Interval in Seconds.
     * 
     * @var int
     * @access private
     */
    private static $cron_interval = SUMO_SUBSCRIPTIONS_CRON_INTERVAL ;

    /**
     * Cron hook identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_hook_identifier ;

    /**
     * Cron interval identifier
     *
     * @var mixed
     * @access protected
     */
    protected static $cron_interval_identifier ;

    /**
     * Init SUMOSubscriptions_Background_Process
     */
    public static function init() {

        self::$cron_hook_identifier     = 'sumosubscriptions_background_updater' ;
        self::$cron_interval_identifier = 'sumosubscriptions_cron_interval' ;

        self::schedule_event() ;
        self::handle_cron_healthcheck() ;
    }

    /**
     * Schedule event
     */
    public static function schedule_event() {

        //may be preventing the recurrence Cron interval not to be greater than SUMO_SUBSCRIPTIONS_CRON_INTERVAL
        if ( (wp_next_scheduled( self::$cron_hook_identifier ) - sumo_get_subscription_timestamp()) > self::$cron_interval ) {
            self::cancel() ;
        }

        //Schedule Recurrence Cron job
        if ( ! wp_next_scheduled( self::$cron_hook_identifier ) ) {
            wp_schedule_event( sumo_get_subscription_timestamp() + self::$cron_interval, self::$cron_interval_identifier, self::$cron_hook_identifier ) ;
        }
    }

    /**
     * Handle cron healthcheck
     */
    public static function handle_cron_healthcheck() {
        //Fire when Recurrence cron gets elapsed
        add_action( self::$cron_hook_identifier, array( __CLASS__, 'run' ) ) ;

        // Fire Scheduled Cron Hooks.
        $jobs = array(
            'start_subscription'               => 'start_subscription',
            'create_renewal_order'             => 'create_renewal_order',
            'notify_invoice_reminder'          => 'remind_invoice',
            'notify_expiry_reminder'           => 'remind_expiry',
            'notify_overdue'                   => 'set_overdue',
            'notify_suspend'                   => 'set_suspend',
            'notify_cancel'                    => 'set_cancel',
            'notify_expire'                    => 'set_expire',
            'automatic_pay'                    => 'automatic_pay',
            'automatic_resume'                 => 'automatic_resume',
            'switch_to_manual_pay_mode'        => 'switch_to_manual_pay_mode',
            'retry_automatic_pay_in_overdue'   => 'retry_automatic_pay',
            'retry_automatic_pay_in_suspended' => 'retry_automatic_pay',
                ) ;

        foreach ( $jobs as $job_name => $job_callback ) {
            add_action( "sumosubscriptions_fire_{$job_name}", __CLASS__ . "::{$job_callback}" ) ;
        }

        self::do_backward_compatibility() ;
    }

    /**
     * Fire when recurrence Cron gets Elapsed
     * 
     * Background process.
     */
    public static function run() {
        $crons = sumosubscriptions()->query->get( array(
            'type'   => 'sumosubs_cron_events',
            'status' => 'publish',
                ) ) ;

        if ( empty( $crons ) ) {
            return ;
        }

        //Loop through each Cron Event Query post and check whether time gets elapsed
        foreach ( $crons as $cron_id ) {
            $cron_events = get_post_meta( $cron_id, '_sumo_subscription_cron_events', true ) ;

            if ( ! is_array( $cron_events ) ) {
                continue ;
            }

            foreach ( $cron_events as $subscription_id => $events ) {
                foreach ( $events as $_event_name => $args ) {
                    if ( ! is_array( $args ) ) {
                        continue ;
                    }

                    foreach ( $args as $event_timestamp => $event_args ) {
                        if ( ! is_int( $event_timestamp ) || ! $event_timestamp ) {
                            continue ;
                        }
                        //When the time gets elapsed then fire the Subscription Event Hooks.
                        if ( sumo_get_subscription_timestamp() >= $event_timestamp ) {
                            do_action( "sumosubscriptions_fire_{$_event_name}", array_merge( array(
                                'subscription_id' => $subscription_id
                                            ), $event_args ) ) ;

                            //Refresh post.
                            $cron_events = get_post_meta( $cron_id, '_sumo_subscription_cron_events', true ) ;

                            //Clear the Event when the corresponding Subscription Event Hook gets fired.
                            if ( did_action( "sumosubscriptions_fire_{$_event_name}" ) ) {
                                unset( $cron_events[ $subscription_id ][ $_event_name ][ $event_timestamp ] ) ;
                            }
                        }
                    }
                    //Flush the meta once the timestamp is not available for the specific Event
                    if ( empty( $cron_events[ $subscription_id ][ $_event_name ] ) ) {
                        unset( $cron_events[ $subscription_id ][ $_event_name ] ) ;
                    }
                }
            }
            //Get updated post.
            if ( is_array( $cron_events ) ) {
                update_post_meta( $cron_id, '_sumo_subscription_cron_events', $cron_events ) ;
            }
        }
    }

    /**
     * Cancel Process
     *
     * clear cronjob.
     */
    public static function cancel() {
        wp_clear_scheduled_hook( self::$cron_hook_identifier ) ;
    }

    /**
     * Backward Compatibility for Firing Subscription wp_schedule_single_event Hooks
     */
    public static function do_backward_compatibility() {
        add_action( 'sumo_subscription_create_renewal_order', array( __CLASS__, 'create_renewal_order_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_subscription_create_multiple_reminder_notify', array( __CLASS__, 'remind_invoice_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_subscription_create_overdue_notify', array( __CLASS__, 'set_overdue_backward_compatiblity' ), 10, 4 ) ;
        add_action( 'sumo_subscription_create_suspend_notify', array( __CLASS__, 'set_suspend_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_subscription_create_cancel_notify_after_susbscr_suspended', array( __CLASS__, 'set_cancel_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_subscription_make_schedule_to_expire_subscription', array( __CLASS__, 'set_expire_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_cron_preapprovaljob', array( __CLASS__, 'automatic_pay_backward_compatiblity' ), 10, 2 ) ;
        add_action( 'sumo_automatically_resume_subscription', array( __CLASS__, 'automatic_resume_backward_compatiblity' ), 10, 1 ) ;
        add_action( 'sumo_schedule_pay_charge_multiple_times_overdue', array( __CLASS__, 'retry_automatic_pay_backward_compatiblity' ), 10, 3 ) ;
        add_action( 'sumo_schedule_pay_charge_multiple_times_suspended', array( __CLASS__, 'retry_automatic_pay_backward_compatiblity' ), 10, 3 ) ;
    }

    /**
     * Start the subscription
     * @param array $args
     */
    public static function start_subscription( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id' => 0,
                ) ) ;

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) && 'Pending' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            $parent_order_id = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true ) ;

            SUMOSubscriptions_Order::maybe_activate_subscription( $args[ 'subscription_id' ], $parent_order_id, 'pending', 'active', true ) ;
        }
    }

    /**
     * Create Renewal Order for the Subscription
     * @param array $args
     */
    public static function create_renewal_order( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id' => 0,
            'next_due_on'     => '',
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) || ! in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Active', 'Trial', 'Pending' ) ) ) {
            return ;
        }

        $cron_event           = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
        $new_renewal_order_id = absint( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) ) ;

        //may be existing Renewal Payment is completed.
        if ( ! $unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $args[ 'subscription_id' ] ) ) {
            $new_renewal_order_id = SUMOSubscriptions_Order::create_renewal_order( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true ), $args[ 'subscription_id' ] ) ;
        }

        switch ( sumo_get_payment_type( $args[ 'subscription_id' ] ) ) {
            case 'auto':
                $cron_event->schedule_automatic_pay( $new_renewal_order_id ) ;

                if ( ! $unpaid_renewal_order_exists ) {
                    $cron_event->schedule_reminders( $new_renewal_order_id, $args[ 'next_due_on' ], '', 'subscription_automatic_charging_reminder' ) ;
                }
                break ;
            case 'manual':
                $cron_event->schedule_next_eligible_payment_failed_status( 0, 0, $args[ 'next_due_on' ] ) ;

                if ( ! $unpaid_renewal_order_exists ) {
                    $cron_event->schedule_reminders( $new_renewal_order_id, $args[ 'next_due_on' ] ) ;
                }
                break ;
        }
    }

    /**
     * Create Multiple Invoice Reminder
     * @param array $args
     */
    public static function remind_invoice( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'mail_template_id' => 'subscription_invoice_order_manual'
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        switch ( $args[ 'mail_template_id' ] ) {
            case 'subscription_preapproval_access_revoked':
            case 'auto_to_manual_subscription_renewal':
                if ( in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Active', 'Trial', 'Overdue', 'Suspended' ) ) ) {
                    sumo_trigger_subscription_email( $args[ 'mail_template_id' ], $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] ) ;
                }
                break ;
            case 'subscription_invoice_order_manual':
            case 'subscription_automatic_charging_reminder':
            case 'subscription_pending_authorization':
                if ( $renewal_order->get_total() > 0 && in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Active', 'Trial', 'Pending', 'Pending_Authorization' ) ) ) {
                    sumo_trigger_subscription_email( $args[ 'mail_template_id' ], $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] ) ;
                }
                break ;
        }
    }

    /**
     * Create Multiple  Expiry Reminders
     * @param array $args
     */
    public static function remind_expiry( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'mail_template_id' => 'subscription_expiry_reminder'
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( 'Active' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            sumo_trigger_subscription_email( $args[ 'mail_template_id' ], 0, $args[ 'subscription_id' ] ) ;
        }
    }

    /**
     * Set Subscription status as Pending_Authorization
     * @param array $args
     */
    public static function set_pending_authorization( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'       => 0,
            'renewal_order_id'      => 0,
            'payment_charging_days' => 0,
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Pending_Authorization' ) ;

        $note = __( 'Subscription automatically changed to Pending Authorization. Since the Renewal Payment is not being paid so far.', 'sumosubscriptions' ) ;
        sumo_add_subscription_note( $note, $args[ 'subscription_id' ], sumo_note_status( 'Pending' ), __( 'Subscription Pending Authorization', 'sumosubscriptions' ) ) ;

        $next_due_on   = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', true ) ;
        $remind_before = sumo_get_subscription_timestamp( $next_due_on ) + ($args[ 'payment_charging_days' ] * 86400) ;

        $cron_event = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
        $cron_event->unset_events() ;
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ] ) ;
        $cron_event->schedule_reminders( $args[ 'renewal_order_id' ], $remind_before, sumo_get_subscription_timestamp(), 'subscription_pending_authorization' ) ;

        do_action( 'sumosubscriptions_status_in_pending_authorization', $args[ 'subscription_id' ] ) ;
        do_action( 'sumosubscriptions_active_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] ) ;
    }

    /**
     * Set Subscription status as Overdue
     * @param array $args
     */
    public static function set_overdue( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'             => 0,
            'renewal_order_id'            => 0,
            'next_due_on'                 => '',
            'payment_charging_days'       => 0,
            'payment_retry_times_per_day' => sumosubs_get_payment_retry_times_per_day_in( 'Overdue' ),
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        if ( $renewal_order->get_total() <= 0 ) {
            //Auto Renew the Subscription.
            $renewal_order->payment_complete() ;
            return ;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Overdue' ) ;

        $mail_template = 'subscription_overdue_order_manual' ;
        $note          = __( 'Subscription automatically changed to Overdue. Since the Renewal Payment is not being paid so far.', 'sumosubscriptions' ) ;

        if ( 'auto' === sumo_get_payment_type( $args[ 'subscription_id' ] ) ) {
            $mail_template = 'subscription_overdue_order_automatic' ;
            $note          = __( 'Subscription automatically changed to Overdue. Since the Automatic Payment failed to Renew.', 'sumosubscriptions' ) ;
        }
        sumo_add_subscription_note( $note, $args[ 'subscription_id' ], sumo_note_status( 'Overdue' ), __( 'Subscription Overdue', 'sumosubscriptions' ) ) ;

        $cron_event = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
        $cron_event->unset_events() ;
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ], $args[ 'payment_retry_times_per_day' ] ) ;

        sumo_trigger_subscription_email( $mail_template, $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] ) ;

        do_action( 'sumosubscriptions_active_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] ) ;
    }

    /**
     * Set Subscription status as Suspended
     * @param array $args
     */
    public static function set_suspend( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'             => 0,
            'renewal_order_id'            => 0,
            'next_due_on'                 => '',
            'payment_charging_days'       => 0,
            'payment_retry_times_per_day' => 0,
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        if ( $renewal_order->get_total() <= 0 ) {
            //Auto Renew the Subscription.
            $renewal_order->payment_complete() ;
            return ;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Suspended' ) ;

        $mail_template = 'subscription_suspended_order_manual' ;
        $note          = __( 'Subscription automatically changed to Suspended. Since the Renewal Payment is not being paid so far.', 'sumosubscriptions' ) ;

        if ( 'auto' === sumo_get_payment_type( $args[ 'subscription_id' ] ) ) {
            $mail_template = 'subscription_suspended_order_automatic' ;
            $note          = __( 'Subscription automatically changed to Suspended. Since the Automatic Payment failed to Renew.', 'sumosubscriptions' ) ;
        }
        sumo_add_subscription_note( $note, $args[ 'subscription_id' ], sumo_note_status( 'Suspended' ), __( 'Subscription Suspended', 'sumosubscriptions' ) ) ;

        $cron_event = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
        $cron_event->unset_events() ;
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ], $args[ 'payment_retry_times_per_day' ] ) ;

        sumo_trigger_subscription_email( $mail_template, $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] ) ;

        sumo_set_subscription_inaccessible_time_from_to( $args[ 'subscription_id' ], 'from' ) ;

        do_action( 'sumosubscriptions_pause_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] ) ;
    }

    /**
     * Set Subscription status as Cancelled
     * @param array $args
     */
    public static function set_cancel( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'force_cancel'     => false,
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        //BKWD CMPT
        $args[ 'renewal_order_id' ] = absint( $args[ 'renewal_order_id' ] > 0 ? $args[ 'renewal_order_id' ] : get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) ) ;

        if ( ! $args[ 'force_cancel' ] ) {
            if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
                return ;
            }

            if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
                return ;
            }

            if ( $renewal_order->get_total() <= 0 ) {
                //Auto Renew the Subscription.
                $renewal_order->payment_complete() ;
                return ;
            }
        }

        //Cancel Subscription.
        sumo_cancel_subscription( $args[ 'subscription_id' ], __( 'Subscription automatically Cancelled.', 'sumosubscriptions' ) ) ;

        do_action( 'sumosubscriptions_cancel_subscription', $args[ 'subscription_id' ], get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true ) ) ;
    }

    /**
     * Set Subscription status as Expired
     * @param array $args
     */
    public static function set_expire( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'expiry_on'        => '',
                ) ) ;

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            //Expire Subscription.
            sumo_expire_subscription( $args[ 'subscription_id' ], $args[ 'expiry_on' ] ) ;

            do_action( 'sumosubscriptions_cancel_subscription', $args[ 'subscription_id' ], get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true ) ) ;
        }
    }

    /**
     * Charge Automatic Payments.
     * @param array $args
     */
    public static function automatic_pay( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'                                => 0,
            'parent_order_id'                                => 0,
            'renewal_order_id'                               => 0,
            'payment_charging_days'                          => 0,
            'payment_retry_times_per_day'                    => 0,
            'next_eligible_status'                           => '',
            'switch_to_manual_pay_after_preapproval_revoked' => false,
            'switch_to_manual_pay_after_preapproval_failed'  => false,
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        SUMO_Subscription_Preapproval::maybe_reset_data( $args[ 'subscription_id' ] ) ;

        $args[ 'renewal_order_id' ] = absint( $args[ 'renewal_order_id' ] > 0 ? $args[ 'renewal_order_id' ] : get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) ) ;
        $next_due_date              = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', true ) ;

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        if ( sumo_is_next_renewal_possible( $args[ 'subscription_id' ] ) ) {
            //Fire to get Preapproval Status.
            do_action( 'sumosubscriptions_process_preapproval_status', $args[ 'subscription_id' ], $args[ 'parent_order_id' ], $args ) ;

            if ( SUMO_Subscription_Preapproval::is_valid( $args[ 'subscription_id' ], $renewal_order ) ) {
                if ( $renewal_order->get_total() > 0 ) {
                    //Trigger to get Preapproved Payment Transaction Status.
                    do_action( 'sumosubscriptions_process_preapproved_payment_transaction', $args[ 'subscription_id' ], $args[ 'parent_order_id' ], $args ) ;
                }
                if ( SUMO_Subscription_Preapproval::is_payment_txn_success( $args[ 'subscription_id' ], $renewal_order ) ) {
                    //Fire after Preapproved payment Transaction is Successfully.
                    do_action( 'sumosubscriptions_preapproved_payment_transaction_success', $args ) ;
                } else {
                    //Fire after Preapproved payment Transaction is Failed.
                    do_action( 'sumosubscriptions_preapproved_payment_transaction_failed', $args ) ;
                }
            } else {
                //Fire after Preapproved access is revoked.
                do_action( 'sumosubscriptions_preapproved_access_is_revoked', $args ) ;
            }
        } else {
            //Schedule to Expire Subscription. 
            $cron_event = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
            $cron_event->schedule_expire_notify( $next_due_date, $args[ 'renewal_order_id' ] ) ;

            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_saved_due_date', $next_due_date ) ;
            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', '--' ) ;
        }
    }

    /**
     * Automatically Resume the Subscription based on the Admin specified interval. May be valid for Subscribers
     * @param array $args
     */
    public static function automatic_resume( $args ) {

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) && 'Pause' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Active' ) ;

            sumo_add_subscription_note( __( 'Automatically Subscription has been Resumed.', 'sumosubscriptions' ), $args[ 'subscription_id' ], sumo_note_status( 'Active' ), __( 'Subscription Resumed Automatically', 'sumosubscriptions' ) ) ;

            SUMOSubscriptions_Order::set_next_payment_date( $args[ 'subscription_id' ] ) ;

            sumo_trigger_subscription_email( 'subscription_processing_order', null, $args[ 'subscription_id' ] ) ;
        }
    }

    /**
     * Switch to Manual pay mode. May be Automatic payment failed to renew the Subscription
     * @param array $args
     */
    public static function switch_to_manual_pay_mode( $args ) {

        $args = wp_parse_args( $args, array(
            'subscription_id'        => 0,
            'renewal_order_id'       => 0,
            'next_eligible_status'   => 'Cancelled',
            'is_preapproval_revoked' => false,
            'mail_template_id'       => '',
            'payment_charging_days'  => 0,
                ) ) ;

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return ;
        }

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        if ( $renewal_order->get_total() <= 0 ) {
            //Auto Renew the Subscription.
            $renewal_order->payment_complete() ;
            return ;
        }

        //Set as Manual pay mode.
        sumo_save_subscription_payment_info( $args[ 'renewal_order_id' ], array(
            'payment_type'   => 'manual',
            'payment_method' => sumosubs_get_order_payment_method( $args[ 'renewal_order_id' ] ),
        ) ) ;

        $cron_event            = new SUMO_Subscription_Cron_Event( $args[ 'subscription_id' ] ) ;
        $payment_charging_time = sumo_get_subscription_timestamp() + ($args[ 'payment_charging_days' ] * 86400) ; //may be Subscription end time
        //may be useful for display purpose in Subscription Email Templates
        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_args_for_pay_link_templates', array(
            'next_status'       => $args[ 'next_eligible_status' ],
            'scheduled_duedate' => sumo_get_subscription_date( $payment_charging_time )
        ) ) ;

        $cron_event->schedule_reminders( $args[ 'renewal_order_id' ], $payment_charging_time, sumo_get_subscription_timestamp(), $args[ 'mail_template_id' ] ) ;
        $cron_event->schedule_cancel_notify( $args[ 'renewal_order_id' ], 0, $payment_charging_time ) ;

        if ( $args[ 'is_preapproval_revoked' ] ) {
            sumo_add_subscription_note( __( 'Access to Preapproval is Invalid, since it may be revoked by the User. Subscription switched to Manual Payment.', 'sumosubscriptions' ), $args[ 'subscription_id' ], 'success', __( 'Invalid Preapproval key', 'sumosubscriptions' ) ) ;
        } else {
            sumo_add_subscription_note( __( 'Since Preapproval payment failed to renew, Subscription switched to Manual Payment.', 'sumosubscriptions' ), $args[ 'subscription_id' ], 'success', __( 'Preapproval Payment Failed', 'sumosubscriptions' ) ) ;
        }
        sumo_add_subscription_note( sprintf( __( 'Admin switched the Automatic Payment to Manual Payment from Order #%s.', 'sumosubscriptions' ), $args[ 'renewal_order_id' ] ), $args[ 'subscription_id' ], 'success', __( 'Auto to Manual Payment Switch', 'sumosubscriptions' ) ) ;
    }

    /**
     * Retry Multiple Pay request Automatically. May be the Automatic payment fails to Renew the Subscription
     * @param array $args
     */
    public static function retry_automatic_pay( $args ) {
        self::automatic_pay( $args ) ;
    }

    /**
     * Charge Automatic Payments.
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     * @param string $next_eligible_status default null. may be Upcoming or Previous Subscription Status
     */
    public static function automatic_pay_backward_compatiblity( $subscription_id, $order_id, $next_eligible_status = '' ) {
        self::automatic_pay( array(
            'subscription_id'                                => $subscription_id,
            'parent_order_id'                                => $order_id,
            'next_eligible_status'                           => $next_eligible_status,
            'switch_to_manual_pay_after_preapproval_revoked' => sumosubs_is_prepproval_revoked_subscription_eligible_for_manual_pay(),
            'switch_to_manual_pay_after_preapproval_failed'  => sumosubs_is_failed_auto_payment_eligible_for_manual_pay()
        ) ) ;
    }

    /**
     * Create Renewal Order for the Subscription
     * @param string $subscription_key
     * @param int $subscription_id The Subscription post ID
     * @param string $next_due_date
     */
    public static function create_renewal_order_backward_compatiblity( $subscription_key, $subscription_id, $next_due_date ) {
        self::create_renewal_order( array(
            'subscription_id' => $subscription_id,
            'next_due_on'     => $next_due_date
        ) ) ;
    }

    /**
     * Create Multiple Invoice Reminder Notify
     * @param string $subscription_key
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $subscription_id The Subscription post ID
     */
    public static function remind_invoice_backward_compatiblity( $subscription_key, $renewal_order_id, $subscription_id ) {
        self::remind_invoice( array(
            'subscription_id'  => $subscription_id,
            'renewal_order_id' => $renewal_order_id,
        ) ) ;
    }

    /**
     * Create Overdue Notify after the Status changed to Overdue
     * @param string $subscription_key
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $subscription_id The Subscription post ID
     * @param string $next_due_date
     */
    public static function set_overdue_backward_compatiblity( $subscription_key, $renewal_order_id, $subscription_id, $next_due_date ) {
        self::set_overdue( array(
            'subscription_id'       => $subscription_id,
            'renewal_order_id'      => $renewal_order_id,
            'next_due_on'           => $next_due_date,
            'payment_charging_days' => sumosubs_get_overdue_days()
        ) ) ;
    }

    /**
     * Create Suspend Notify after the Status changed to Suspended
     * @param string $subscription_key
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $subscription_id The Subscription post ID
     */
    public static function set_suspend_backward_compatiblity( $subscription_key, $renewal_order_id, $subscription_id ) {
        self::set_suspend( array(
            'subscription_id'       => $subscription_id,
            'renewal_order_id'      => $renewal_order_id,
            'payment_charging_days' => sumosubs_get_suspend_days()
        ) ) ;
    }

    /**
     * Create Cancel Notify after the Status changed to Cancelled
     * @param string $subscription_key
     * @param int $renewal_order_id The Renewal Order post ID
     * @param int $subscription_id The Subscription post ID
     */
    public static function set_cancel_backward_compatiblity( $subscription_key, $renewal_order_id, $subscription_id ) {
        self::set_cancel( array(
            'subscription_id'  => $subscription_id,
            'renewal_order_id' => $renewal_order_id
        ) ) ;
    }

    /**
     * Create Expire Notify after the Status changed to Expired
     * @param int $subscription_id The Subscription post ID
     * @param int $renewal_order_id The Renewal Order post ID
     * @param string $expiry_on
     */
    public static function set_expire_backward_compatiblity( $subscription_id, $renewal_order_id, $expiry_on ) {
        self::set_expire( array(
            'subscription_id'  => $subscription_id,
            'renewal_order_id' => $renewal_order_id,
            'expiry_on'        => $expiry_on
        ) ) ;
    }

    /**
     * Automatically Resume the Subscription based on the Admin specified interval. May be valid for Subscribers
     * @param int $subscription_id The Subscription post ID
     */
    public static function automatic_resume_backward_compatiblity( $subscription_id ) {
        self::automatic_resume( array(
            'subscription_id' => $subscription_id
        ) ) ;
    }

    /**
     * Retry Multiple Pay request Automatically. May be the Automatic payment fails to Renew the Subscription
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     * @param string $next_eligible_status may be Current or Upcoming Subscription Status
     */
    public static function retry_automatic_pay_backward_compatiblity( $subscription_id, $order_id, $next_eligible_status ) {
        self::automatic_pay( array(
            'subscription_id'      => $subscription_id,
            'parent_order_id'      => $order_id,
            'next_eligible_status' => $next_eligible_status
        ) ) ;
    }

}

SUMOSubscriptions_Background_Process::init() ;
