<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Manage Subscription Preapprovals.
 * 
 * @class SUMO_Subscription_Preapproval
 * @category Class
 */
class SUMO_Subscription_Preapproval {

    public static $payment_type   = '' ;
    public static $payment_method = '' ;

    /**
     * Init SUMO_Subscription_Preapproval.
     */
    public static function init() {

        add_action( 'sumosubscriptions_preapproved_payment_transaction_success', __CLASS__ . '::payment_success' ) ;
        add_action( 'sumosubscriptions_preapproved_payment_transaction_failed', __CLASS__ . '::payment_failed' ) ;
        add_action( 'sumosubscriptions_cancel_subscription', __CLASS__ . '::payment_cancelled', 9999, 2 ) ;
        add_action( 'sumosubscriptions_preapproved_access_is_revoked', __CLASS__ . '::preapproved_access_revoked' ) ;
    }

    /**
     * Check whether it is valid to charge the payment automatically
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon preapproval status is valid
     */
    public static function is_valid( $subscription_id, $payment_order ) {
        self::$payment_type   = sumo_get_payment_type( $subscription_id ) ;
        self::$payment_method = sumo_get_subscription_payment( $subscription_id, 'payment_method' ) ;

        if ( 'auto' === self::$payment_type ) {
            if (
                    apply_filters( 'sumosubscriptions_is_' . self::$payment_method . '_preapproval_status_valid', false, $subscription_id, $payment_order ) ||
                    'valid' === get_post_meta( $subscription_id, 'sumo_subscription_preapproval_status', true )
            ) {
                return true ;
            }
        }
        return false ;
    }

    /**
     * Check whether auto payment transaction is success
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon recurring payment success
     */
    public static function is_payment_txn_success( $subscription_id, $payment_order ) {
        $is_txn_success = false ;

        if ( 'auto' === self::$payment_type ) {
            if ( $payment_order->get_total() > 0 ) {
                if (
                        apply_filters( 'sumosubscriptions_is_' . self::$payment_method . '_preapproved_payment_transaction_success', false, $subscription_id, $payment_order ) ||
                        'success' === get_post_meta( $subscription_id, 'sumo_subscription_preapproved_payment_transaction_status', true )
                ) {
                    $is_txn_success = true ;
                }
            } else {
                $is_txn_success = true ;
            }
        }
        return $is_txn_success ;
    }

    /**
     * Clear saved data
     * @param int $subscription_id
     */
    public static function maybe_reset_data( $subscription_id ) {
        delete_post_meta( $subscription_id, 'sumo_subscription_preapproval_status' ) ;
        delete_post_meta( $subscription_id, 'sumo_subscription_preapproved_payment_transaction_status' ) ;
    }

    /**
     * Do some action when preapproved payment success.
     * @param array $args
     */
    public static function payment_success( $args ) {

        if ( ! $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] ) ) {
            return ;
        }

        if ( $renewal_order->has_status( array( 'completed', 'processing' ) ) ) {
            return ;
        }

        //Update new Order status to Renew the Subscription.
        $renewal_order->payment_complete() ;
    }

    /**
     * Do some action when preapproved payment failed.
     * @param array $args
     */
    public static function payment_failed( $args ) {
        $subscription_status = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ;

        sumo_add_subscription_note( sprintf( __( 'Renewal Order #%s failed to Renew this Subscription. Preapproval payment Failed.', 'sumosubscriptions' ), $args[ 'renewal_order_id' ] ), $args[ 'subscription_id' ], 'failure', __( 'Preapproval Payment Failed', 'sumosubscriptions' ) ) ;

        switch ( apply_filters( 'sumosubscriptions_get_next_eligible_subscription_failed_status', $args[ 'next_eligible_status' ], $args[ 'subscription_id' ] ) ) {
            case 'Pending_Authorization':
                if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {
                    $payment_method        = sumo_get_subscription_payment_method( $args[ 'subscription_id' ] ) ;
                    $payment_charging_days = apply_filters( "sumosubscriptions_{$payment_method}_pending_auth_period", 1, $args[ 'subscription_id' ] ) ;

                    SUMOSubscriptions_Background_Process::set_pending_authorization( array(
                        'subscription_id'       => $args[ 'subscription_id' ],
                        'renewal_order_id'      => $args[ 'renewal_order_id' ],
                        'payment_charging_days' => absint( $payment_charging_days ),
                    ) ) ;
                }
                break ;
            case 'Overdue':
                if ( in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {

                    SUMOSubscriptions_Background_Process::set_overdue( array(
                        'subscription_id'             => $args[ 'subscription_id' ],
                        'renewal_order_id'            => $args[ 'renewal_order_id' ],
                        'payment_charging_days'       => 0 === $args[ 'payment_charging_days' ] ? sumosubs_get_overdue_days() : $args[ 'payment_charging_days' ],
                        'payment_retry_times_per_day' => 0 === $args[ 'payment_retry_times_per_day' ] ? sumosubs_get_payment_retry_times_per_day_in( 'Overdue' ) : $args[ 'payment_retry_times_per_day' ]
                    ) ) ;
                }
                break ;
            case 'Suspended':
                if ( in_array( $subscription_status, array( 'Trial', 'Active', 'Overdue', 'Pending_Authorization' ) ) ) {

                    SUMOSubscriptions_Background_Process::set_suspend( array(
                        'subscription_id'             => $args[ 'subscription_id' ],
                        'renewal_order_id'            => $args[ 'renewal_order_id' ],
                        'payment_charging_days'       => 0 === $args[ 'payment_charging_days' ] ? sumosubs_get_suspend_days() : $args[ 'payment_charging_days' ],
                        'payment_retry_times_per_day' => 0 === $args[ 'payment_retry_times_per_day' ] ? sumosubs_get_payment_retry_times_per_day_in( 'Suspended' ) : $args[ 'payment_retry_times_per_day' ]
                    ) ) ;
                }
                break ;
            case 'Cancelled':
                if ( $args[ 'switch_to_manual_pay_after_preapproval_failed' ] && in_array( $subscription_status, array( 'Trial', 'Active' ) ) ) {

                    SUMOSubscriptions_Background_Process::switch_to_manual_pay_mode( array(
                        'subscription_id'       => $args[ 'subscription_id' ],
                        'renewal_order_id'      => $args[ 'renewal_order_id' ],
                        'mail_template_id'      => 'auto_to_manual_subscription_renewal',
                        'payment_charging_days' => absint( get_option( 'sumo_min_waiting_time_after_switched_to_manual_pay', 5 ) )
                    ) ) ;
                } else if ( in_array( $subscription_status, array( 'Trial', 'Active', 'Overdue', 'Suspended', 'Pending_Authorization' ) ) ) {

                    SUMOSubscriptions_Background_Process::set_cancel( array(
                        'subscription_id'  => $args[ 'subscription_id' ],
                        'renewal_order_id' => $args[ 'renewal_order_id' ]
                    ) ) ;
                }
                break ;
        }
    }

    /**
     * Do some action after preapproved access is revoked.
     * @param array $args
     */
    public static function preapproved_access_revoked( $args ) {

        if ( $args[ 'switch_to_manual_pay_after_preapproval_revoked' ] ) {

            SUMOSubscriptions_Background_Process::switch_to_manual_pay_mode( array(
                'subscription_id'        => $args[ 'subscription_id' ],
                'renewal_order_id'       => $args[ 'renewal_order_id' ],
                'is_preapproval_revoked' => true,
                'mail_template_id'       => 'subscription_preapproval_access_revoked',
                'payment_charging_days'  => absint( get_option( 'sumo_min_waiting_time_after_switched_to_manual_pay_when_preapproval_revoked', 5 ) )
            ) ) ;
        } else {
            SUMOSubscriptions_Background_Process::set_cancel( array(
                'subscription_id'  => $args[ 'subscription_id' ],
                'renewal_order_id' => $args[ 'renewal_order_id' ]
            ) ) ;
            sumo_add_subscription_note( __( 'Access to Preapproval is Invalid, since it may be revoked by the User. Subscription is Cancelled.', 'sumosubscriptions' ), $args[ 'subscription_id' ], 'success', __( 'Invalid Preapproval key', 'sumosubscriptions' ) ) ;
        }
    }

    /**
     * Clear Payment Information upon Subscription gets Cancelled.
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Order post ID. Either Renewal Order | Parent Order
     */
    public static function payment_cancelled( $subscription_id, $order_id ) {
        $subscription_plan = sumo_get_subscription_plan( $subscription_id ) ;
        //Clear Payment Info.
        sumo_save_subscription_payment_info( $order_id, array(), $subscription_plan[ 'subscription_product_id' ] ) ;
    }

}

SUMO_Subscription_Preapproval::init() ;
