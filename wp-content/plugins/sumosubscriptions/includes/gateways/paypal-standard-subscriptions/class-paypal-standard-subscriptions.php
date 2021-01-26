<?php

//Include PayPal Standard Subscription API
include_once('inc/class-paypal-standard-subscriptions-api.php') ;
include_once('inc/class-paypal-standard-subscriptions-IPN-payment-handler.php') ;

/**
 * Perform PayPal Standard Subscription.
 * 
 * @class SUMO_PayPal_Std_Subscriptions
 * @category Class
 */
class SUMO_PayPal_Std_Subscriptions extends SUMO_PayPal_Std_Subscriptions_IPN_handler {

    /** @public string Payment gateway ID. */
    public $gateway_id = 'paypal' ;

    /**
     * SUMO_PayPal_Std_Subscriptions constructor.
     */
    public function __construct() {
        //Request Args.
        add_filter( 'woocommerce_paypal_args', array( $this, 'subscription_request' ) ) ;
        //IPN request.
        add_action( 'valid-paypal-standard-ipn-request', array( $this, 'process_subscriptions_based_upon_IPN_request' ) ) ;

        //Admin Edit Subscription Page Management.
        add_filter( 'sumosubscriptions_admin_can_change_subscription_statuses', array( $this, 'can_change_subscription_statuses' ), 10, 2 ) ;
        add_filter( 'sumosubscriptions_edit_subscription_page_readonly_mode', array( $this, 'set_subscription_as_non_editable_fields' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_edit_subscription_statuses', array( $this, 'set_paypal_statuses' ), 10, 4 ) ;
        add_action( 'sumosubscriptions_manual_suspended_subscription', array( $this, 'suspend_subscription' ), 10, 2 ) ;
        add_action( 'sumosubscriptions_manual_reactivate_subscription', array( $this, 'reactivate_subscription' ), 10, 2 ) ;
        add_action( 'sumosubscriptions_cancel_subscription', array( $this, 'cancel_subscription' ), 10, 2 ) ;
        add_filter( 'sumosubscriptions_revoke_automatic_subscription', array( $this, 'revoke_subscription' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_revoke_cancel_request_scheduled', array( $this, 'revoke_cancel_request' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_schedule_cancel', array( $this, 'suspend_subscription_on_scheduled_to_cancel' ), 10, 3 ) ;

        //My Account Page Management.
        add_filter( 'sumosubscriptions_my_subscription_table_pause_action', array( $this, 'hide_pause_action_in_my_subscriptions_table' ), 10, 3 ) ;

        add_filter( 'sumosubscriptions_can_upgrade_or_downgrade', array( $this, 'hide_switcher' ), 10, 2 ) ;
        add_filter( 'sumosubscriptions_display_variation_switch_fields', array( $this, 'hide_switcher' ), 10, 2 ) ;
        add_filter( 'sumosubscriptions_schedule_subscription_crons', array( $this, 'prevent_subscription_cron_schedules' ), 10, 4 ) ;
        add_filter( 'sumosubscriptions_payment_mode_switcher_payment_gateways', array( $this, 'manage_payment_mode_in_checkout' ), 10, 2 ) ;
    }

    /**
     * Extract the Order ID from PayPal args.
     * 
     * @param array $paypal_args
     * @return int
     */
    public function get_the_order_id( $paypal_args ) {
        if ( ! empty( $paypal_args[ 'custom' ] ) ) {
            $json_object = json_decode( $paypal_args[ 'custom' ] ) ;

            if ( ! empty( $json_object->order_id ) ) {
                $this->payment_order_id = absint( $json_object->order_id ) ;
                $this->get_payment_order() ;
            }
        }
        return $this->payment_order_id ;
    }

    /**
     * Check whether the Order has valid Subscription?
     * 
     * @return boolean
     */
    public function order_has_valid_subscription() {
        if ( $this->payment_order && 1 === sizeof( $this->payment_order->get_items() ) ) {
            $this->get_subscription() ;

            if ( $this->subscription && ! $this->subscription->is_synced() && ! $this->subscription->get_signup( 'forced' ) ) {
                return true ;
            }
        }
        return false ;
    }

    /**
     * Setup the Subscription API in the PayPal args.
     * 
     * @param array $paypal_args
     * @return array
     */
    public function subscription_request( $paypal_args ) {
        //Is Admin allowed the Subscription API.
        if ( SUMO_Subscription_PaymentGateways::is_paypal_subscription_api_enabled() && SUMO_Subscription_PaymentGateways::customer_has_chosen_auto_payment_mode_in( $this->gateway_id ) ) {
            $this->populate( $this->get_the_order_id( $paypal_args ) ) ;

            if ( $this->order_has_valid_subscription() ) {
                $paypal_args = $this->alter_payal_request( $paypal_args ) ;
            }
        }
        return $paypal_args ;
    }

    /**
     * Process PayPal Subscriptions based on incoming IPN request.
     * 
     * @param array $posted
     */
    public function process_subscriptions_based_upon_IPN_request( $posted ) {
        if ( ! empty( $posted[ 'txn_type' ] ) ) {
            $this->populate( $this->get_the_order_id( $posted ) ) ;

            if ( $this->order_has_valid_subscription() ) {
                $this->manage_subscription_via_ipn( $posted ) ;
            }
        }
    }

    /**
     * Check admin has privilege to change subscription status in edit subscription page.
     * 
     * @param boolean $bool
     * @param int $subscription_id The Subscription post ID
     * @return boolean
     */
    public function can_change_subscription_statuses( $bool, $subscription_id ) {
        $this->populate( 0, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            return true ;
        }
        return $bool ;
    }

    /**
     * Set edit subscription page as non editable fields.
     * 
     * @param boolean $bool
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     * @return boolean
     */
    public function set_subscription_as_non_editable_fields( $bool, $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            return true ;
        }
        return $bool ;
    }

    /**
     * Manage Subscription Status in backend settings.
     * 
     * @param array $statuses
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     * @param string $subscription_status
     * @return string
     */
    public function set_paypal_statuses( $statuses, $subscription_id, $order_id, $subscription_status ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            switch ( $subscription_status ) {
                case 'Trial':
                case 'Active':
                    unset( $statuses[ 'Pause' ] ) ;
                    $statuses[ 'Suspended' ]  = 'Suspended' ;
                    break ;
                case 'Suspended':
                    unset( $statuses[ 'Resume' ] ) ;
                    $statuses[ 'Reactivate' ] = 'Reactivate' ;
                    break ;
            }
        }

        return $statuses ;
    }

    /**
     * Request PayPal to Suspend Subscription.
     * 
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function suspend_subscription( $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_suspend() ) ) {
                $this->add_note( __( 'Awaiting for PayPal IPN to Suspend the Subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN to Suspend', 'sumosubscriptions' ) ) ;
            } else {
                sumo_add_subscription_note( __( "Couldn't Suspend the Subscription in PayPal.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Suspend in PayPal", 'sumosubscriptions' ) ) ;
            }
        }
    }

    /**
     * Request PayPal to Reactivate Subscription.
     * 
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function reactivate_subscription( $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_reactivate() ) ) {
                // Since we do not have IPN response from PayPal for Subscription Reactivation we are doing like this.
                update_post_meta( $subscription_id, 'sumo_get_status', 'Active' ) ;
                $this->add_note( __( 'Subscription is Reactivated in PayPal.', 'sumosubscriptions' ), __( 'Subscription Reactivated', 'sumosubscriptions' ) ) ;
            } else {
                sumo_add_subscription_note( __( "Couldn't Reactivate the Subscription in PayPal.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Reactivate in PayPal", 'sumosubscriptions' ) ) ;
            }
        }
    }

    /**
     * Request PayPal to Cancel Subscription.
     * 
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function cancel_subscription( $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_cancel() ) ) {
                $this->add_note( __( 'Awaiting for PayPal IPN to Cancel the Subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN to Cancel', 'sumosubscriptions' ) ) ;
            } else {
                sumo_add_subscription_note( __( "Couldn't Cancel the Subscription in PayPal.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Cancel in PayPal", 'sumosubscriptions' ) ) ;
            }
        }
    }

    /**
     * Since revoke API is not available in PayPal, Cancel the Subscription.
     * 
     * @param boolean $bool
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function revoke_subscription( $bool, $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_cancel() ) ) {
                $bool = true ;
                add_post_meta( $this->subscription_id, '_paypal_subscription_revoke_pending', 'yes' ) ;
                $this->add_note( __( 'Awaiting for PayPal IPN to Revoke the Automatic Subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN to Revoke', 'sumosubscriptions' ) ) ;
            } else {
                $bool = false ;
                sumo_add_subscription_note( __( "Couldn't Revoke the Subscription in PayPal.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Revoke in PayPal", 'sumosubscriptions' ) ) ;
            }
        }

        return $bool ;
    }

    /**
     * Since profile is suspended in PayPal, request PayPal to Reactivate Subscription.
     * 
     * @param boolean $bool
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function revoke_cancel_request( $bool, $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_reactivate() ) ) {
                $bool = true ;
                $this->add_note( __( 'Subscription is Reactivated in PayPal.', 'sumosubscriptions' ), __( 'Subscription Reactivated', 'sumosubscriptions' ) ) ;
            } else {
                $bool = false ;
                sumo_add_subscription_note( __( "Couldn't revoke Cancel which is Scheduled.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Reactivate in PayPal", 'sumosubscriptions' ) ) ;
            }
        }

        return $bool ;
    }

    /**
     * Suspend the Subscription when it is scheduled to cancel.
     * 
     * @param boolean $bool
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function suspend_subscription_on_scheduled_to_cancel( $bool, $subscription_id, $order_id ) {
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            if ( $this->is_ACK_success( $this->request_suspend() ) ) {
                $bool = true ;
                $this->add_note( __( 'Awaiting for PayPal IPN to Suspend the Subscription.', 'sumosubscriptions' ), __( 'Awaiting PayPal IPN to Suspend', 'sumosubscriptions' ) ) ;
            } else {
                $bool = false ;
                sumo_add_subscription_note( __( "Couldn't schedule to Cancel.", 'sumosubscriptions' ), $subscription_id, 'failure', __( "Couldn't Suspend in PayPal", 'sumosubscriptions' ) ) ;
            }
        }

        return $bool ;
    }

    /**
     * Prevent existing Subscription Crons from schedule for the PayPal Standard Subscription.
     * 
     * @param boolean $action
     * @param string $cron_job
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     * @return boolean
     */
    public function prevent_subscription_cron_schedules( $action, $cron_job, $subscription_id, $order_id ) {
        switch ( $cron_job ) {
            case 'create_renewal_order':
            case 'notify_invoice_reminder':
                if ( 'auto' === sumo_get_payment_type( $subscription_id ) ) {
                    return $action ;
                }
                break ;
            case 'notify_cancel':
                if ( 'Pending_Cancellation' === get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
                    return $action ;
                }
                break ;
            case 'notify_expire':
                return $action ;
                break ;
        }

        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            return false ;
        }
        return $action ;
    }

    /**
     * Manage Pause Actions in My Subscriptions Table on My Account page.
     * 
     * @param bool $action
     * @param int $subscription_id The Subscription post ID
     * @param int $parent_order_id The Parent Order post ID
     * @return bool
     */
    public function hide_pause_action_in_my_subscriptions_table( $action, $subscription_id, $parent_order_id ) {
        $this->populate( $parent_order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            return false ;
        }
        return $action ;
    }

    /**
     * Hide Switcher in Edit Subscription Page and My Subscriptions Table on My Account page.
     * 
     * @param string $action
     * @param int $subscription_id The Subscription post ID
     * @return string
     */
    public function hide_switcher( $action, $subscription_id ) {
        $order_id = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) ;
        $this->populate( $order_id, $subscription_id ) ;

        if ( $this->is_paypal_subscription() ) {
            return '' ;
        }
        return $action ;
    }

    /**
     * Check whether Synchronized product is in Cart.
     * And Show/Hide checkbox option for the user to switch Payment mode either Automatic or Manual Pay.
     * 
     * @param array $gateways
     * @param string $gateway_id
     * @return array
     */
    public function manage_payment_mode_in_checkout( $gateways, $gateway_id ) {
        if ( ! empty( WC()->cart->cart_contents ) && in_array( $this->gateway_id, $gateways ) && ! is_checkout_pay_page() ) {
            if ( 1 === sizeof( WC()->cart->cart_contents ) && ! SUMO_Order_Subscription::is_subscribed() ) {
                foreach ( WC()->cart->cart_contents as $cart_key => $cart_item ) {
                    $product_id        = sumosubs_get_product_id( $cart_item[ 'data' ] ) ;
                    $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

                    if (
                            '1' === $subscription_plan[ 'subscription_status' ] &&
                            '1' !== $subscription_plan[ 'synchronization_status' ] &&
                            ('1' !== $subscription_plan[ 'signup_status' ] || '1' === $subscription_plan[ 'trial_status' ])
                    ) {
                        return $gateways ;
                    }
                }
            }

            $key = array_search( $this->gateway_id, ( array ) $gateways ) ;
            unset( $gateways[ $key ] ) ;
        }
        return $gateways ;
    }

}

new SUMO_PayPal_Std_Subscriptions() ;
