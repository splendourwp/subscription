<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Abstract Subscription Cron Event
 * 
 * @abstract SUMO_Abstract_Subscription_Cron_Event
 */
abstract class SUMO_Abstract_Subscription_Cron_Event {

    /**
     * @var int The Subscription Cron Event post ID. Handles each Subscription Cron.
     */
    public $event_id = 0 ;

    /**
     * @var int The Subscription post ID
     */
    public $subscription_id = 0 ;

    /**
     * @var array Cron Events for the Subscription
     */
    public $cron_events = array() ;

    /**
     * @var string Post Type for Cron Events
     */
    protected $post_type = 'sumosubs_cron_events' ;

    /**
     * @var WP_Query object Cron Events query
     */
    public $query ;

    /**
     * Constructor.
     */
    public function __construct( $subscription ) {
        $this->populate( $subscription ) ;
    }

    /**
     * Populate the Subscription Cron Event
     * @param int $subscription The Subscription post ID
     */
    protected function populate( $subscription ) {
        if ( ! $subscription ) {
            return false ;
        }

        $this->subscription_id = absint( $subscription ) ;
        $this->init_event_id() ;
        $this->event_id        = $this->get_event_id() ;
        $this->cron_events     = $this->get_cron_events() ;
    }

    /**
     * Init Event for the Subscription
     */
    public function init_event_id() {
        //Fire once for the Subscription
        if ( ! $this->exists() ) {
            $this->event_id = wp_insert_post( array(
                'post_type'   => $this->post_type,
                'post_status' => 'publish',
                'post_author' => 1,
                'post_title'  => __( 'Subscription Cron Job', 'sumosubscriptions' ),
                    ) ) ;

            add_post_meta( $this->event_id, '_sumo_subscription_id', $this->subscription_id ) ;
        }
    }

    /**
     * Get Cron Event ID
     */
    public function get_event_id() {

        if ( $this->exists() ) {
            $this->get_events_query() ;

            foreach ( $this->query->posts as $event ) {
                $this->event_id = $event->ID ;
                break ;
            }
        }
        return $this->event_id ;
    }

    /**
     * Check whether Cron Event exists
     * @return boolean
     */
    public function exists() {
        if ( ! $this->get_events_query() ) {
            return false ;
        }

        return $this->query->have_posts() ;
    }

    /**
     * Get Cron Events WP_Query data
     * @return \WP_Query|boolean
     */
    public function get_events_query() {
        if ( ! $this->subscription_id ) {
            return false ;
        }

        $this->query = sumosubscriptions()->query->get( array(
            'type'         => $this->post_type,
            'status'       => 'publish',
            'return'       => 'q',
            'limit'        => 1,
            'meta_key'     => '_sumo_subscription_id',
            'meta_value'   => $this->subscription_id,
            'meta_compare' => '=',
                ) ) ;
        return $this->query ;
    }

    /**
     * Get Cron Events associated for the Subscription
     * @return array
     */
    public function get_cron_events() {
        $_cron_events = get_post_meta( $this->event_id, '_sumo_subscription_cron_events', true ) ;

        if ( isset( $_cron_events[ $this->subscription_id ] ) && is_array( $_cron_events[ $this->subscription_id ] ) ) {
            $this->cron_events = $_cron_events[ $this->subscription_id ] ;
        }

        return $this->cron_events ;
    }

    /**
     * Set Cron Event to Schedule. It may be elapsed by wp_schedule_event
     * @param int $timestamp
     * @param string $event_name
     * @param array $args
     * @return boolean true on success
     */
    public function set_cron_event( $timestamp, $event_name, $args = array() ) {

        if ( ! is_numeric( $timestamp ) || ! $timestamp || ! is_array( $args ) ) {
            return false ;
        }

        $new_arg           = array( absint( $timestamp ) => $args ) ;
        $this->cron_events = $this->get_cron_events() ;

        if ( $this->exists() ) {
            //may the Event has multiple timestamps so that we are doing this way 
            if ( isset( $this->cron_events[ $event_name ] ) && is_array( $this->cron_events[ $event_name ] ) ) {
                $this->cron_events[ $event_name ] += $new_arg ;
            } else {
                //may the new Event is registering
                $this->cron_events[ $event_name ] = $new_arg ;
            }

            if ( $this->set_events() ) {
                return true ;
            }
        }

        return false ;
    }

    /**
     * Update Cron events
     * @return boolean true on success
     */
    public function set_events() {

        if ( ! is_array( $this->cron_events ) ) {
            return ;
        }

        update_post_meta( $this->event_id, '_sumo_subscription_cron_events', array(
            $this->subscription_id => $this->cron_events
        ) ) ;

        return true ;
    }

    /**
     * UnSchedule Cron events.
     * @param array $events unsetting every Cron events if left empty
     */
    public function unset_events( $events = array() ) {

        if ( empty( $events ) ) {
            $events = array(
                'start_subscription',
                'create_renewal_order',
                'notify_invoice_reminder',
                'notify_expiry_reminder',
                'notify_overdue',
                'notify_suspend',
                'notify_cancel',
                'notify_expire',
                'automatic_pay',
                'automatic_resume',
                'switch_to_manual_pay_mode',
                'retry_automatic_pay_in_overdue',
                'retry_automatic_pay_in_suspended',
                    ) ;
        }
        $events            = ( array ) $events ;
        $this->cron_events = $this->get_cron_events() ;

        if ( $this->exists() ) {
            foreach ( $this->cron_events as $event_name => $event_args ) {
                if ( in_array( $event_name, $events ) ) {
                    unset( $this->cron_events[ $event_name ] ) ;
                }
            }

            $this->set_events() ;
        }

        $this->may_be_unset_events_for_backward( $events ) ;
    }

    /**
     * UnSchedule backward WP Cron events, if previously wp_single_schedule_event is scheduled
     * @param array $events unsetting every Cron events if left empty
     */
    public function may_be_unset_events_for_backward( $events = array() ) {
        $parent_order_id         = absint( get_post_meta( $this->subscription_id, 'sumo_get_parent_order_id', true ) ) ;
        $renewal_order_id        = absint( get_post_meta( $this->subscription_id, 'sumo_get_renewal_id', true ) ) ;
        $next_payment_date       = get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ) ;
        $saved_next_payment_date = get_post_meta( $this->subscription_id, 'sumo_get_saved_due_date', true ) ;
        $parent_key              = get_post_meta( $this->subscription_id, 'sumo_parent_key', true ) ;

        //Backward Compatibility for wp_single_schedule_event
        $backward_cron_events = array(
            'create_renewal_order'             => array(
                'hook' => 'sumo_subscription_create_renewal_order',
                'args' => array( $parent_key, $this->subscription_id, $next_payment_date )
            ),
            'notify_invoice_reminder'          => array(
                'hook' => 'sumo_subscription_create_multiple_reminder_notify',
                'args' => array( $parent_key, $renewal_order_id, $this->subscription_id )
            ),
            'notify_overdue'                   => array(
                'hook' => 'sumo_subscription_create_overdue_notify',
                'args' => array( $parent_key, $renewal_order_id, $this->subscription_id, $next_payment_date )
            ),
            'notify_suspend'                   => array(
                'hook' => 'sumo_subscription_create_suspend_notify',
                'args' => array( $parent_key, $renewal_order_id, $this->subscription_id )
            ),
            'notify_cancel'                    => array(
                'hook' => 'sumo_subscription_create_cancel_notify_after_susbscr_suspended',
                'args' => array( $parent_key, $renewal_order_id, $this->subscription_id )
            ),
            'notify_expire'                    => array(
                'hook' => 'sumo_subscription_make_schedule_to_expire_subscription',
                'args' => array( $this->subscription_id, ( string ) $renewal_order_id, $saved_next_payment_date )
            ),
            'switch_to_manual_pay_mode'        => array(
                'hook' => 'sumo_subscription_switch_to_manual_pay_mode',
                'args' => array( $this->subscription_id )
            ),
            'automatic_pay'                    => array(
                'hook'              => 'sumo_cron_preapprovaljob',
                'has_multiple_args' => true,
                'args'              => array( array( $this->subscription_id, $parent_order_id ), array( $this->subscription_id, ( string ) $parent_order_id ) )
            ),
            'automatic_resume'                 => array(
                'hook' => 'sumo_automatically_resume_subscription',
                'args' => array( $this->subscription_id )
            ),
            'retry_automatic_pay_in_overdue'   => array(
                'hook'              => 'sumo_schedule_pay_charge_multiple_times_overdue',
                'has_multiple_args' => true,
                'args'              => array( array( $this->subscription_id, $parent_order_id, 'Overdue' ), array( $this->subscription_id, $parent_order_id, 'Suspended' ) )
            ),
            'retry_automatic_pay_in_suspended' => array(
                'hook'              => 'sumo_schedule_pay_charge_multiple_times_suspended',
                'has_multiple_args' => true,
                'args'              => array( array( $this->subscription_id, $parent_order_id, 'Suspended' ), array( $this->subscription_id, $parent_order_id, 'Cancelled' ) )
            ) ) ;

        foreach ( $backward_cron_events as $event_name => $event_args ) {
            if ( ! in_array( $event_name, $events ) ) {
                continue ;
            }

            if ( isset( $event_args[ 'has_multiple_args' ] ) && $event_args[ 'has_multiple_args' ] === true ) {
                foreach ( $event_args[ 'args' ] as $arg ) {
                    wp_clear_scheduled_hook( $event_args[ 'hook' ], $arg ) ;
                }
            } else {
                wp_clear_scheduled_hook( $event_args[ 'hook' ], $event_args[ 'args' ] ) ;
            }
        }
    }

}
