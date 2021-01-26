<?php

/**
 * PayPal Reference Transaction Subscription Handler.
 * 
 * @class SUMO_Reference_Txns_Subscription_Handler
 * @category Class
 */
class SUMO_Reference_Txns_Subscription_Handler extends SUMO_PayPal_Reference_Txns_API {

    /** @protected int Payment Order (post) ID. */
    protected $payment_order_id = 0 ;

    /** @protected int Subscription (post) ID. */
    protected $subscription_id = 0 ;

    /**
     * SUMO_Reference_Txns_Subscription_Handler constructor.
     */
    public function __construct( $reference_txn ) {
        parent::__construct( $reference_txn ) ;

        add_action( 'admin_notices' , array ( $this , 'add_site_wide_notice_for_reference_transaction' ) ) ;
        add_filter( 'sumosubscriptions_need_payment_gateway' , array ( $this , 'need_payment_gateway' ) , 18 , 2 ) ;
        add_action( 'woocommerce_api_sumo_subscription_reference_transactions' , array ( $this , 'do_express_checkout' ) ) ;
        add_action( 'woocommerce_api_sumo_subscription_reference_ipn_notification' , array ( $this , 'perform_action_based_on_ipn_notification' ) ) ;

        //Subscription APIs.
        add_filter( 'sumosubscriptions_is_' . $this->reference_txn->id . '_preapproval_status_valid' , array ( $this , 'check_reference_status' ) , 10 , 3 ) ;
        add_filter( 'sumosubscriptions_is_' . $this->reference_txn->id . '_preapproved_payment_transaction_success' , array ( $this , 'do_reference_transaction' ) , 10 , 3 ) ;
        add_action( 'sumosubscriptions_cancel_subscription' , array ( $this , 'cancel_billing_agreement' ) , 10 , 2 ) ;
    }

    /**
     * Set payment Order ID
     * @param int $order_id
     */
    public function set_order_id( $order_id ) {
        $this->payment_order_id = absint( $order_id ) ;
    }

    /**
     * Set subscription ID
     * @param int $subscription_id
     */
    public function set_subscription_id( $subscription_id ) {
        $this->subscription_id = absint( $subscription_id ) ;
    }

    /**
     * Check Reference Transactions is enabled or not with your API for SUMO Subscription.
     */
    public function add_site_wide_notice_for_reference_transaction() {
        if ( ! $this->gateway_enabled || $this->has_empty_api_credentials() ) {
            return ;
        }

        $add_query_arg = esc_url_raw( add_query_arg( array ( 'sumosubscription_check_reference_txn' => '1' ) , $_SERVER[ 'REQUEST_URI' ] ) ) ;
        $anchor_tag    = "<a href=$add_query_arg>" . __( 'Click here' , 'sumosubscriptions' ) . '</a>' ;

        if ( isset( $_REQUEST[ 'sumosubscription_check_reference_txn' ] ) ||
                (isset( $_REQUEST[ 'page' ] ) && isset( $_REQUEST[ 'tab' ] ) && isset( $_REQUEST[ 'section' ] ) && $_REQUEST[ 'page' ] === 'wc-settings' && $_REQUEST[ 'tab' ] === 'checkout' && $_REQUEST[ 'section' ] === $this->reference_txn->id ) ) {

            $status = $this->isMerchantInitiatedBillingAgreement() ? 'success' : 'failure' ;

            update_option( 'sumosubscription_reference_txn_state' , $status ) ;

            if ( isset( $_REQUEST[ 'sumosubscription_check_reference_txn' ] ) )
                wp_safe_redirect( esc_url_raw( add_query_arg( array ( 'sumosubscription_reference_txn_state' => $status ) , remove_query_arg( 'sumosubscription_check_reference_txn' , $_SERVER[ 'REQUEST_URI' ] ) ) ) ) ;
        }

        if ( 'success' !== get_option( 'sumosubscription_reference_txn_state' ) ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    if ( isset( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) && $_REQUEST[ 'sumosubscription_reference_txn_state' ] === 'success' ) {
                        _e( 'Reference Transactions has been enabled with your API Credentials.' , 'sumosubscriptions' ) ;
                    } else if ( isset( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) && $_REQUEST[ 'sumosubscription_reference_txn_state' ] === 'failure' ) {
                        _e( 'Reference Transactions is not enabled with your API credentials and hence SUMO Subscriptions – PayPal Reference Transactions gateway will not be displayed on the Checkout page. Kindly contact PayPal to enable Reference Transactions for your account.' , 'sumosubscriptions' ) ;
                    } else {
                        printf( __( 'Check here to know whether Reference Transactions can be enabled with your API Credentials %s. If your credentials are not enabled then, SUMO Subscriptions – PayPal Reference Transactions gateway will not be displayed on the Checkout page.' , 'sumosubscriptions' ) , $anchor_tag ) ;
                    }
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Show payment gateway only when the Admin has got special permissions from PayPal
     * 
     * @param bool $need
     * @param string $gateway_id
     * @return bool
     */
    public function need_payment_gateway( $need , $gateway_id ) {
        if ( $this->reference_txn->id !== $gateway_id || $this->has_empty_api_credentials() || 'success' === get_option( 'sumosubscription_reference_txn_state' ) ) {
            return $need ;
        }

        if ( $this->dev_debug_enabled ) {
            foreach ( wp_get_current_user()->roles as $current_user_role ) {
                if ( in_array( $current_user_role , ( array ) $this->user_roles_for_dev ) ) {
                    return true ;
                }
            }
        }
        return false ;
    }

    /**
     * Do Express Checkout Payment.
     */
    public function do_express_checkout() {

        if ( ! isset( $_GET[ 'action' ] ) || ! isset( $_GET[ 'token' ] ) || ! isset( $_GET[ 'order_id' ] ) ) {
            return ;
        }
        if ( 'sumosubscription_do_express_checkout' !== $_GET[ 'action' ] ) {
            return ;
        }
        if ( ! $order = wc_get_order( $_GET[ 'order_id' ] ) ) {
            return ;
        }

        $this->set_order_id( $_GET[ 'order_id' ] ) ;
        $token_details = $this->getExpressCheckoutDetails( $_GET[ 'token' ] ) ;

        //Initiate Automatic Payment.
        //BILLINGAGREEMENTACCEPTEDSTATUS return 0 mean billing is Not Approved, 1 means billing is Approved.
        if ( isset( $token_details[ 'BILLINGAGREEMENTACCEPTEDSTATUS' ] ) && $token_details[ 'BILLINGAGREEMENTACCEPTEDSTATUS' ] === '1' ) {
            // Billing Agreement Approved.

            if ( $this->get_payment_amount() > 0 ) {
                // Perform Do Express Checkout and record Billing Agreement to perform future payment.
                $this->complete_payment( $this->doExpressCheckoutPayment( $_GET[ 'token' ] , $_GET[ 'PayerID' ] ) ) ;
            } else {
                $this->complete_payment( $this->createBillingAgreement( $_GET[ 'token' ] ) ) ;
            }

            //Redirect to Success url.
            wp_safe_redirect( $this->get_payment_order()->get_checkout_order_received_url() ) ;
            //Manual Payment.
        } else if ( isset( $token_details[ 'ACK' ] ) && in_array( $token_details[ 'ACK' ] , array ( 'Success' , 'SuccessWithWarning' ) ) ) {
            // Billing Agreement is not enable so fetching payment is not valid from recurring perspective whether we can consider as normal payment.
            $this->complete_payment( $this->doExpressCheckoutPayment( $_GET[ 'token' ] , $_GET[ 'PayerID' ] ) ) ;
            //Redirect to Success url.
            wp_safe_redirect( $this->get_payment_order()->get_checkout_order_received_url() ) ;
        } else {
            // Error in PayPal
            wp_safe_redirect( WC()->cart->get_cart_url() ) ;
        }
    }

    /**
     * PayPal Reference Transaction IPN handler.
     * @param array $request
     */
    public function perform_action_based_on_ipn_notification( $request ) {
        if ( ! isset( $request[ 'txn_type' ] ) ) {
            return ;
        }

        switch ( $request[ 'txn_type' ] ) {
            case 'mp_signup':
                //perform billing creation.
                break ;
            case 'mp_cancel':
                //perform billing cancellation.
                $parent_order_id = sumosubs_get_parent_order_id( $_POST[ 'custom' ] ) ;
                $subscriptions   = sumosubscriptions()->query->get( array (
                    'type'       => 'sumosubscriptions' ,
                    'status'     => 'publish' ,
                    'meta_key'   => 'sumo_get_parent_order_id' ,
                    'meta_value' => $parent_order_id ,
                        ) ) ;

                if ( empty( $subscriptions ) ) {
                    break ;
                }

                foreach ( $subscriptions as $subscription_id ) {
                    sumo_cancel_subscription( $subscription_id , sprintf( __( 'Subscription has been Cancelled from PayPal for %s.' , 'sumosubscriptions' ) , $parent_order_id ) ) ;
                }
                //Clear every Subscription Payment Info from the Parent Order.
                sumo_save_subscription_payment_info( $parent_order_id , array () ) ;
                break ;
        }
    }

    /**
     * Check whether PayPal customer Billing ID valid or invalid.
     * @param bool $bool The Subscription post ID
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon preapproval is valid
     */
    public function check_reference_status( $bool , $subscription_id , $payment_order ) {
        $this->set_order_id( sumosubs_get_order_id( $payment_order ) ) ;
        $this->set_subscription_id( $subscription_id ) ;

        $billing_information = $this->getBillingAgreementDetails() ;

        if ( isset( $billing_information[ 'BILLINGAGREEMENTSTATUS' ] ) && 'Active' === $billing_information[ 'BILLINGAGREEMENTSTATUS' ] ) {
            return true ;
        }
        return false ;
    }

    /**
     * Check whether PayPal Reference Transaction Payment Status valid or invalid.
     * If Reference Transaction Billing Agreement is valid then Automatic Subscription Renewal Success otherwise switch Subscription either with Manual Payment/Cancel.
     * 
     * @param bool $bool The Subscription post ID
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon payment success
     */
    public function do_reference_transaction( $bool , $subscription_id , $payment_order ) {
        $this->set_order_id( sumosubs_get_order_id( $payment_order ) ) ;
        $this->set_subscription_id( $subscription_id ) ;

        $reference_txn = $this->doReferenceTransaction() ; //perform reference transactions.

        if ( isset( $reference_txn[ 'PAYMENTSTATUS' ] ) && in_array( $reference_txn[ 'PAYMENTSTATUS' ] , array ( 'Completed' , 'Processed' ) ) ) {
            //set transaction ID
            sumosubs_set_transaction_id( $this->payment_order_id , $reference_txn[ 'TRANSACTIONID' ] , true ) ;
            //recurring txn success
            return true ;
        }
        return false ;
    }

    /**
     * Do some action when Reference Transaction Payment failed/cancelled.
     * @param int $subscription_id The Subscription post ID
     * @param int $order_id The Parent Order post ID
     */
    public function cancel_billing_agreement( $subscription_id , $order_id ) {
        $payment_method = sumo_get_subscription_payment( $subscription_id , 'payment_method' ) ;

        if ( $this->reference_txn->id === $payment_method ) {
            //It might be used in case of Multiple Subscriptions placed in a single Checkout.
            if ( ! sumo_is_every_subscription_cancelled_from_parent_order( $order_id ) ) {
                return ;
            }

            $this->set_order_id( $order_id ) ;
            $this->set_subscription_id( $subscription_id ) ;
            $this->cancelBillingAgreement() ; //Cancel Billing Agreement if Subscription gets Cancelled.
        }
    }

}
