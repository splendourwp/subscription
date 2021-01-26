<?php

defined( 'ABSPATH' ) || exit ;

if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
    return ;
}

if ( ! class_exists( 'SUMO_WC_Stripe_Gateway' ) ) {

    /**
     * Handles the WC Stripe Automatic Payments
     * 
     * @class SUMO_WC_Stripe_Gateway
     * @package Class
     */
    class SUMO_WC_Stripe_Gateway {

        /**
         * Get the WC Stripe Gateway
         * 
         * @var WC_Gateway_Stripe 
         */
        protected static $stripe ;

        /**
         * Init SUMO_WC_Stripe_Gateway.
         */
        public static function init() {
            add_action( 'init', __CLASS__ . '::create_instance', 5 ) ;
            add_filter( 'sumosubscriptions_available_payment_gateways', __CLASS__ . '::add_subscription_supports', 99 ) ;
            add_filter( 'wc_stripe_description', __CLASS__ . '::maybe_render_payment_mode_selector', 99, 2 ) ;
            add_filter( 'wc_stripe_display_save_payment_method_checkbox', __CLASS__ . '::maybe_hide_save_checkbox', 99 ) ;
            add_filter( 'wc_stripe_force_save_source', __CLASS__ . '::force_save_source', 99 ) ;
            add_action( 'woocommerce_order_status_changed', __CLASS__ . '::save_subscription_payment_source', 5 ) ;
            add_filter( 'wc_stripe_payment_metadata', __CLASS__ . '::add_payment_metadata', 10, 2 ) ;

            add_filter( 'sumosubscriptions_is_stripe_preapproval_status_valid', __CLASS__ . '::can_charge_renewal_payment', 10, 3 ) ;
            add_filter( 'sumosubscriptions_is_stripe_preapproved_payment_transaction_success', __CLASS__ . '::charge_renewal_payment', 10, 3 ) ;
            add_action( 'sumosubscriptions_wc_stripe_requires_authentication', __CLASS__ . '::prepare_customer_to_authorize_payment', 10, 2 ) ;
            add_filter( 'sumosubscriptions_get_next_eligible_subscription_failed_status', __CLASS__ . '::set_next_eligible_subscription_status', 10, 2 ) ;
            add_action( 'sumosubscriptions_status_in_pending_authorization', __CLASS__ . '::subscription_in_pending_authorization' ) ;
        }

        /**
         * Create Stripe instance.
         */
        public static function create_instance() {
            if ( is_null( self::$stripe ) ) {
                self::$stripe = new WC_Gateway_Stripe() ;
            }
        }

        /**
         * Add gateway to support subscriptions.
         * 
         * @param array $subscription_gateways
         * @return array
         */
        public static function add_subscription_supports( $subscription_gateways ) {
            $subscription_gateways[] = 'stripe' ;
            return $subscription_gateways ;
        }

        /**
         * Render checkbox to select the mode of payment in automatic payment gateways by the customer
         * 
         * @param string $description
         * @param string $gateway_id
         * @return string
         */
        public static function maybe_render_payment_mode_selector( $description, $gateway_id ) {
            return SUMO_Subscription_PaymentGateways::set_payment_mode_switcher( $description, $gateway_id ) ;
        }

        /**
         * Checks to see if we need to hide the save checkbox field.
         * Because when cart contains a payment plans/deposit product, it will save regardless.
         */
        public static function maybe_hide_save_checkbox( $display_tokenization ) {
            if ( SUMO_Subscription_PaymentGateways::auto_payment_gateways_enabled() && SUMO_Subscription_PaymentGateways::checkout_has_subscription() ) {
                $display_tokenization = false ;
            }

            return $display_tokenization ;
        }

        /**
         * Checks to see if we need to save the source.
         * Because when cart contains a subscription product, source should be saved.
         */
        public static function force_save_source( $force_save ) {
            global $wp ;

            if ( SUMO_Subscription_PaymentGateways::customer_has_chosen_auto_payment_mode_in( 'stripe' ) ) {
                $force_save = true ;
                $order_id   = 0 ;

                if ( isset( $_POST[ 'woocommerce_pay' ], $_GET[ 'key' ] ) ) {
                    $nonce_value = '' ;

                    if ( isset( $_REQUEST[ 'woocommerce-pay-nonce' ] ) ) {
                        $nonce_value = $_REQUEST[ 'woocommerce-pay-nonce' ] ;
                    } else if ( isset( $_REQUEST[ '_wpnonce' ] ) ) {
                        $nonce_value = $_REQUEST[ '_wpnonce' ] ;
                    }

                    if ( wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
                        $order_id = absint( $wp->query_vars[ 'order-pay' ] ) ;
                    }
                } else if ( WC()->session ) {
                    $order_id = absint( WC()->session->get( 'order_awaiting_payment' ) ) ;
                }

                if ( $order_id > 0 ) {
                    update_post_meta( $order_id, 'sumosubs_payment_mode', 'auto' ) ;
                }
            }

            return $force_save ;
        }

        /**
         * Save Stripe Source and Customer to order for making automatic payments.
         */
        public static function save_subscription_payment_source( $order_id ) {
            if ( ! sumo_is_order_contains_subscriptions( $order_id ) ) {
                return ;
            }

            $order          = wc_get_order( $order_id ) ;
            $payment_method = $order->get_payment_method() ;

            if ( empty( $payment_method ) ) {
                $payment_method = isset( $_POST[ 'payment_method' ] ) ? wc_clean( wp_unslash( $_POST[ 'payment_method' ] ) ) : '' ;
            }

            if ( empty( $payment_method ) || 'stripe' !== $payment_method ) {
                return ;
            }

            $payment_mode = get_post_meta( $order->get_id(), 'sumosubs_payment_mode', true ) ;
            $payment_mode = empty( $payment_mode ) ? 'manual' : $payment_mode ;

            sumo_save_subscription_payment_info( $order->get_id(), array(
                'payment_type'   => $payment_mode,
                'payment_method' => 'stripe',
                'profile_id'     => get_post_meta( $order->get_id(), '_stripe_customer_id', true ),
                'payment_key'    => get_post_meta( $order->get_id(), '_stripe_source_id', true ),
            ) ) ;
        }

        /**
         * Add payment metadata to Stripe.
         */
        public static function add_payment_metadata( $metadata, $order ) {
            $order = wc_get_order( $order ) ;

            if ( sumo_is_order_contains_subscriptions( $order->get_id() ) ) {
                if ( $order->get_parent_id() > 0 ) {
                    $subscription_id = get_post_meta( $order->get_id(), 'sumo_subscription_id', true ) ;

                    if ( $subscription_id > 0 ) {
                        $metadata[ 'subscription' ] = $subscription_id ;
                    }

                    $metadata[ 'parent_order' ] = false ;
                } else {
                    $metadata[ 'parent_order' ] = true ;
                }

                $metadata[ 'payment_type' ] = 'recurring' ;
                $metadata[ 'payment_via' ]  = 'SUMO Subscriptions' ;
                $metadata[ 'site_url' ]     = esc_url( get_site_url() ) ;
            }

            return $metadata ;
        }

        /**
         * Check whether Stripe can charge customer for the Subscription renewal payment to happen.
         * 
         * @param bool $bool
         * @param int $subscription_id
         * @param WC_Order $renewal_order
         * @return bool
         */
        public static function can_charge_renewal_payment( $bool, $subscription_id, $renewal_order ) {

            try {
                $customer_id = sumo_get_subscription_payment( $subscription_id, 'profile_id' ) ;
                $source_id   = sumo_get_subscription_payment( $subscription_id, 'payment_key' ) ;

                $renewal_order->update_meta_data( '_stripe_customer_id', $customer_id ) ;
                $renewal_order->update_meta_data( '_stripe_source_id', $source_id ) ;
                $renewal_order->update_meta_data( 'sumosubs_payment_mode', 'auto' ) ;
                $renewal_order->save() ;

                $prepared_source = self::$stripe->prepare_order_source( $renewal_order ) ;

                if ( ! $prepared_source->customer ) {
                    throw new WC_Stripe_Exception( 'Failed to process renewal payment for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order', __( 'Customer not found', 'sumosubscriptions' ) ) ;
                }

                return true ;
            } catch ( WC_Stripe_Exception $e ) {
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() ) ;

                if ( $e->getLocalizedMessage() ) {
                    $renewal_order->add_order_note( $e->getLocalizedMessage() ) ;
                    sumo_add_subscription_note( sprintf( __( 'Stripe: <b>%s</b>', 'sumosubscriptions' ), $e->getLocalizedMessage() ), $subscription_id, 'failure', __( 'Stripe Request Failed', 'sumosubscriptions' ) ) ;
                }
            }

            return false ;
        }

        /**
         * Charge the customer from their source to renew the Subscription.
         * 
         * @param bool $bool
         * @param int $subscription_id
         * @param WC_Order $renewal_order
         * @return bool
         */
        public static function charge_renewal_payment( $bool, $subscription_id, $renewal_order, $retry = false ) {

            try {

                $prepared_source = self::$stripe->prepare_order_source( $renewal_order ) ;

                if ( $retry ) {
                    // Passing empty source will charge customer default.
                    $prepared_source->source = '' ;

                    sumo_add_subscription_note( __( 'Start retrying renewal payment with the default card.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Stripe Retry Renewal Payment', 'sumosubscriptions' ) ) ;
                }

                if ( is_callable( array( self::$stripe, 'create_and_confirm_intent_for_off_session' ) ) ) {
                    $response = self::$stripe->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $renewal_order->get_total() ) ;
                } else {
                    $request              = self::$stripe->generate_payment_request( $renewal_order, $prepared_source ) ;
                    $request[ 'capture' ] = 'true' ;

                    $response = WC_Stripe_API::request( $request ) ;
                }

                $is_authentication_required = self::$stripe->is_authentication_required_for_payment( $response ) ;

                if ( ! empty( $response->error ) && ! $is_authentication_required ) {
                    if ( ! $retry && apply_filters( 'sumosubscriptions_wc_stripe_use_default_customer_source', true ) ) {
                        return self::charge_renewal_payment( $bool, $subscription_id, $renewal_order, true ) ;
                    }

                    $localized_messages = WC_Stripe_Helper::get_localized_messages() ;

                    if ( 'card_error' === $response->error->type ) {
                        $localized_message = isset( $localized_messages[ $response->error->code ] ) ? $localized_messages[ $response->error->code ] : $response->error->message ;
                    } else {
                        $localized_message = isset( $localized_messages[ $response->error->type ] ) ? $localized_messages[ $response->error->type ] : $response->error->message ;
                    }

                    throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message ) ;
                }

                if ( $is_authentication_required ) {
                    if ( ! $retry && apply_filters( 'sumosubscriptions_wc_stripe_use_default_customer_source', true ) ) {
                        return self::charge_renewal_payment( $bool, $subscription_id, $renewal_order, true ) ;
                    }

                    $charge_id = $response->error->charge ;

                    sumo_add_subscription_note( sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'sumosubscriptions' ), $charge_id ), $subscription_id, 'failure', __( 'Stripe Requires Authentication', 'sumosubscriptions' ) ) ;
                    $renewal_order->add_order_note( sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'sumosubscriptions' ), $charge_id ) ) ;
                    $renewal_order->set_transaction_id( $charge_id ) ;
                    $renewal_order->save() ;

                    do_action( 'sumosubscriptions_wc_stripe_requires_authentication', $subscription_id, $renewal_order ) ;
                    return false ;
                }

                self::$stripe->process_response( ( isset( $response->charges->data ) ? end( $response->charges->data ) : $response ), $renewal_order ) ;

                do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order ) ;
                do_action( 'sumosubscriptions_wc_stripe_renewal_payment_successful', $subscription_id, $renewal_order ) ;

                return true ;
            } catch ( WC_Stripe_Exception $e ) {
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() ) ;

                do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order ) ;

                if ( $e->getLocalizedMessage() ) {
                    $renewal_order->add_order_note( $e->getLocalizedMessage() ) ;
                    sumo_add_subscription_note( sprintf( __( 'Stripe: <b>%s</b>', 'sumosubscriptions' ), $e->getLocalizedMessage() ), $subscription_id, 'failure', __( 'Stripe Request Failed', 'sumosubscriptions' ) ) ;
                }
            }

            return false ;
        }

        /**
         * Prepare the customer to bring it 'OnSession' to complete the renewal.
         */
        public static function prepare_customer_to_authorize_payment( $subscription_id, $renewal_order ) {
            add_post_meta( $renewal_order->get_id(), '_sumo_subsc_wc_stripe_authentication_required', 'yes' ) ;
            add_post_meta( $subscription_id, 'wc_stripe_authentication_required', 'yes' ) ;
        }

        /**
         * Hold the subscription until the payment is approved by the customer
         */
        public static function set_next_eligible_subscription_status( $next_eligible_status, $subscription_id ) {
            if ( 'yes' === get_post_meta( $subscription_id, 'wc_stripe_authentication_required', true ) ) {
                $next_eligible_status = 'Pending_Authorization' ;
            }
            return $next_eligible_status ;
        }

        /**
         * Clear cache
         */
        public static function subscription_in_pending_authorization( $subscription_id ) {
            delete_post_meta( $subscription_id, 'wc_stripe_authentication_required' ) ;
        }

    }

    SUMO_WC_Stripe_Gateway::init() ;
}
