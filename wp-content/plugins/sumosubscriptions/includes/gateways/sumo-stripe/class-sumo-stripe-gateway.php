<?php

/**
 * Register new Payment Gateway id of Stripe.
 * 
 * @class SUMO_Stripe_Gateway
 * @category Class
 */
class SUMO_Stripe_Gateway extends WC_Payment_Gateway {

    const STRIPE_REQUIRES_AUTH            = 100 ;
    const PAYMENT_RETRY_WITH_DEFAULT_CARD = 200 ;

    /**
     * Check if we need to retry with the Default card
     * @var bool 
     */
    public $retry_failed_payment = false ;

    /**
     * SUMO_Stripe_Gateway constructor.
     */
    public function __construct() {
        $this->id                                   = 'sumo_stripe' ;
        $this->method_title                         = 'SUMO Subscriptions - Stripe' ;
        $this->method_description                   = __( 'Take payments from your customers using Credit/Debit card', 'sumosubscriptions' ) ;
        $this->has_fields                           = true ;
        $this->init_form_fields() ;
        $this->init_settings() ;
        $this->enabled                              = $this->get_option( 'enabled' ) ;
        $this->title                                = $this->get_option( 'title' ) ;
        $this->description                          = $this->get_option( 'description' ) ;
        $this->cardiconfilter                       = $this->get_option( 'cardiconfilter', array() ) ;
        $this->saved_cards                          = 'yes' === $this->get_option( 'saved_cards', 'no' ) ;
        $this->testmode                             = 'yes' === $this->get_option( 'testmode' ) ;
        $this->testsecretkey                        = $this->get_option( 'testsecretkey' ) ;
        $this->testpublishablekey                   = $this->get_option( 'testpublishablekey' ) ;
        $this->livesecretkey                        = $this->get_option( 'livesecretkey' ) ;
        $this->livepublishablekey                   = $this->get_option( 'livepublishablekey' ) ;
        $this->checkoutmode                         = $this->get_option( 'checkoutmode', 'default' ) ;
        $this->pendingAuthEmailReminder             = $this->get_option( 'pendingAuthEmailReminder', '2' ) ;
        $this->pendingAuthPeriod                    = $this->get_option( 'pendingAuthPeriod', '1' ) ;
        $this->chargeDefaultCardIfOriginalCardFails = $this->saved_cards && 'yes' === $this->get_option( 'retryDefaultPM', 'no' ) ;
        $this->supports                             = array(
            'products',
            'refunds',
            'tokenization',
            'add_payment_method',
                ) ;

        //BKWD CMPT for < v11.0
        if ( 'lightbox' === $this->checkoutmode ) {
            $this->checkoutmode = 'default' ;
        }

        include_once('class-sumo-stripe-api-request.php') ;
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ) ;
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) ) ;
        add_action( 'wc_ajax_sumo_stripe_verify_intent', array( $this, 'verify_intent' ) ) ;
        add_action( 'woocommerce_payment_token_deleted', array( $this, 'wc_payment_token_deleted' ), 10, 2 ) ;
        add_action( 'woocommerce_payment_token_set_default', array( $this, 'wc_payment_token_set_default' ) ) ;
        add_filter( 'woocommerce_get_customer_payment_tokens', array( $this, 'wc_get_customer_payment_tokens' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproval_status_valid', array( $this, 'can_charge_renewal_payment' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproved_payment_transaction_success', array( $this, 'charge_renewal_payment' ), 10, 3 ) ;
        add_filter( 'sumosubscriptions_' . $this->id . '_pending_auth_period', array( $this, 'get_pending_auth_period' ) ) ;
        add_filter( 'sumosubscriptions_' . $this->id . '_remind_pending_auth_times_per_day', array( $this, 'get_pending_auth_times_per_day_to_remind' ) ) ;
        add_action( 'sumosubscriptions_stripe_requires_authentication', array( $this, 'prepare_customer_to_authorize_payment' ), 10, 2 ) ;
        add_filter( 'sumosubscriptions_get_next_eligible_subscription_failed_status', array( $this, 'set_next_eligible_subscription_status' ), 10, 2 ) ;
        add_action( 'sumosubscriptions_status_in_pending_authorization', array( $this, 'subscription_in_pending_authorization' ) ) ;
    }

    /**
     * Get option keys which are available
     */
    public function _get_option_keys() {
        return array(
            'enabled'            => 'enabled',
            'title'              => 'title',
            'description'        => 'description',
            'cardiconfilter'     => 'cardiconfilter',
            'livesecretkey'      => 'livesecretkey',
            'livepublishablekey' => 'livepublishablekey',
            'testmode'           => 'testmode',
            'testsecretkey'      => 'testsecretkey',
            'testpublishablekey' => 'testpublishablekey',
            'checkoutmode'       => 'checkoutmode',
                ) ;
    }

    /**
     * Return the name of the old option in the WP DB.
     *
     * @return string
     */
    public function _get_old_option_key() {
        return $this->plugin_id . 'sumosubscription_stripe_instant_settings' ;
    }

    /**
     * Check for an old option and get option from DB.
     *
     * @param  string $key Option key.
     * @param  mixed  $empty_value Value when empty.
     * @return string The value specified for the option or a default value for the option.
     */
    public function get_option( $key, $empty_value = null ) {
        $new_options = get_option( $this->get_option_key(), null ) ;

        if ( isset( $new_options[ $key ] ) ) {
            return parent::get_option( $key, $empty_value ) ;
        }

        $old_options = get_option( $this->_get_old_option_key(), false ) ;

        if ( false === $old_options || ! is_array( $old_options ) ) {
            return parent::get_option( $key, $empty_value ) ;
        }

        foreach ( $this->_get_option_keys() as $current_key => $maybeOld_key ) {
            if ( $key !== $current_key ) {
                continue ;
            }

            if ( is_array( $maybeOld_key ) ) {
                foreach ( $maybeOld_key as $_key ) {
                    if ( isset( $old_options[ $_key ] ) ) {
                        $this->settings[ $key ] = $old_options[ $_key ] ;
                    }
                }
            } else {
                if ( isset( $old_options[ $maybeOld_key ] ) ) {
                    $this->settings[ $key ] = $old_options[ $maybeOld_key ] ;
                }
            }
        }

        return parent::get_option( $key, $empty_value ) ;
    }

    /**
     * Admin Settings For Stripe.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'                  => array(
                'title'   => __( 'Enable/Disable', 'sumosubscriptions' ),
                'type'    => 'checkbox',
                'label'   => __( 'Stripe', 'sumosubscriptions' ),
                'default' => 'no'
            ),
            'title'                    => array(
                'title'       => __( 'Title:', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user see during checkout.', 'sumosubscriptions' ),
                'default'     => __( 'SUMO Subscriptions - Stripe', 'sumosubscriptions' ),
            ),
            'description'              => array(
                'title'    => __( 'Description', 'sumosubscriptions' ),
                'type'     => 'textarea',
                'default'  => 'Pay with Stripe. You can pay with your credit card, debit card and master card   ',
                'desc_tip' => true,
            ),
            'cardiconfilter'           => array(
                'type'              => 'multiselect',
                'title'             => __( 'Card Brands to be Displayed', 'sumosubscriptions' ),
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'default'           => array(
                    'visa',
                    'mastercard',
                    'amex',
                    'discover',
                    'jcb'
                ),
                'description'       => __( 'Selected card brands will be displayed next to gateway title.', 'sumosubscriptions' ),
                'options'           => array(
                    'visa'       => 'Visa',
                    'mastercard' => 'Mastercard',
                    'amex'       => 'Amex',
                    'discover'   => 'Discover',
                    'jcb'        => 'JCB'
                ),
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Select Card Brands..', 'sumosubscriptions' )
                )
            ),
            'saved_cards'              => array(
                'title'       => __( 'Saved Cards', 'sumosubscriptions' ),
                'label'       => __( 'Enable Payment via Saved Cards', 'sumosubscriptions' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Stripe servers, not on your store.', 'sumosubscriptions' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'testmode'                 => array(
                'title'       => __( 'Test Mode', 'sumosubscriptions' ),
                'type'        => 'checkbox',
                'label'       => __( 'Turn on testing', 'sumosubscriptions' ),
                'description' => __( 'Use the test mode on Stripe dashboard to verify everything works before going live.', 'sumosubscriptions' ),
                'default'     => 'no',
            ),
            'livepublishablekey'       => array(
                'type'    => 'text',
                'title'   => __( 'Stripe API Live Publishable key', 'sumosubscriptions' ),
                'default' => '',
            ),
            'livesecretkey'            => array(
                'type'    => 'text',
                'title'   => __( 'Stripe API Live Secret key', 'sumosubscriptions' ),
                'default' => '',
            ),
            'testpublishablekey'       => array(
                'type'    => 'text',
                'title'   => __( 'Stripe API Test Publishable key', 'sumosubscriptions' ),
                'default' => '',
            ),
            'testsecretkey'            => array(
                'type'    => 'text',
                'title'   => __( 'Stripe API Test Secret key', 'sumosubscriptions' ),
                'default' => '',
            ),
            'checkoutmode'             => array(
                'title'   => __( 'Checkout Mode', 'sumosubscriptions' ),
                'type'    => 'select',
                'default' => 'default',
                'options' => array(
                    'default'        => __( 'Default', 'sumosubscriptions' ),
                    'inline_cc_form' => __( 'Inline Credit Card Form', 'sumosubscriptions' ),
                ),
            ),
            'autoPaymentFailure'       => array(
                'title' => __( 'Automatic Payment Failure Settings', 'sumosubscriptions' ),
                'type'  => 'title',
            ),
            'retryDefaultPM'           => array(
                'title'       => __( 'Authenticate Future Renewals using Default Card', 'sumosubscriptions' ),
                'label'       => __( 'Enable', 'sumosubscriptions' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, payment retries will be happen using the default card in case if the originally authorized card for the respective subscription is not able to process the recurring payment for some reason', 'sumosubscriptions' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'SCADesc'                  => array(
                'type'        => 'title',
                'description' => __( 'Some banks require customer authentication each time during a payment which is not controlled by Stripe. So, even if customer has authorized for future payments of subscription, the authorization will be declined by banks. In such case, customer has to manually process their renewal payments. The following options controls such scenarios.', 'sumosubscriptions' ),
            ),
            'pendingAuthPeriod'        => array(
                'type'              => 'number',
                'title'             => __( 'Pending Authorization Period', 'sumosubscriptions' ),
                'default'           => '1',
                'description'       => __( 'day', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Pending Authorization" status until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Pending Authorization" status for 2 days from the subscription due date. During Pending Authorization period, subscriber still have access to their subscription.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min' => 0
                )
            ),
            'pendingAuthEmailReminder' => array(
                'type'              => 'number',
                'title'             => __( 'Number of Emails to send during Pending Authorization', 'sumosubscriptions' ),
                'default'           => '2',
                'description'       => __( 'times per day', 'sumosubscriptions' ),
                'desc_tip'          => __( 'This option controls the number of times the subcription emails will be send to the customer in case of a payment failure when the subscription in Pending Authorization status.', 'sumosubscriptions' ),
                'custom_attributes' => array(
                    'min' => 0
                )
            ),
                ) ;
    }

    /**
     * Processes and saves options.
     *
     * @return bool was anything saved?
     */
    public function process_admin_options() {
        $saved       = parent::process_admin_options() ;
        $old_options = get_option( $this->_get_old_option_key(), false ) ;

        if ( false === $old_options || ! is_array( $old_options ) ) {
            return $saved ;
        }

        foreach ( $this->settings as $saved_key => $saved_val ) {
            foreach ( $this->_get_option_keys() as $key => $maybeOld_key ) {
                if ( $saved_key !== $key ) {
                    continue ;
                }

                if ( is_array( $maybeOld_key ) ) {
                    foreach ( $maybeOld_key as $_key ) {
                        if ( isset( $old_options[ $_key ] ) ) {
                            $old_options[ $_key ] = $saved_val ;
                        }
                    }
                } else {
                    if ( isset( $old_options[ $maybeOld_key ] ) ) {
                        $old_options[ $maybeOld_key ] = $saved_val ;
                    }
                }
            }
        }
        update_option( $this->_get_old_option_key(), $old_options ) ;

        return $saved ;
    }

    /**
     * Return the gateway's icon.
     *
     * @return string
     */
    public function get_icon() {
        $icon = '' ;

        foreach ( $this->cardiconfilter as $icon_name ) {
            if ( ! $icon_name ) {
                continue ;
            }

            $icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . "/assets/images/icons/credit-cards/{$icon_name}.png" ) . '" alt="' . esc_attr( ucfirst( $icon_name ) ) . '" />' ;
        }

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id ) ;
    }

    /**
     * Gets the transaction URL linked to Stripe dashboard.
     */
    public function get_transaction_url( $order ) {
        if ( $this->testmode ) {
            $this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s' ;
        } else {
            $this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s' ;
        }

        if ( 'setup_intent' === $this->get_intentObj_from_order( $order ) ) {
            $this->view_transaction_url = '' ;
        }

        return parent::get_transaction_url( $order ) ;
    }

    /**
     * Can the order be refunded via Stripe?
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ) {
        return $order && $order->get_transaction_id() ;
    }

    /**
     * Checks if gateway should be available to use.
     */
    public function is_available() {
        if ( is_account_page() && ! $this->saved_cards ) {
            return false ;
        }

        return parent::is_available() ;
    }

    /**
     * Outputs scripts for Stripe elements.
     */
    public function payment_scripts() {
        if ( 'yes' !== $this->enabled ) {
            return ;
        }

        if ( ! is_checkout() && ! is_add_payment_method_page() && ! is_checkout_pay_page() ) {
            return ;
        }

        if ( $this->saved_cards && $this->supports( 'tokenization' ) && is_checkout_pay_page() ) {
            $this->tokenization_script() ;
        }

        $stripe_params = array(
            'payment_method' => $this->id,
            'key'            => $this->testmode ? $this->testpublishablekey : $this->livepublishablekey,
            'checkoutmode'   => $this->checkoutmode,
                ) ;
        $stripe_params = array_merge( $stripe_params, SUMO_Stripe_API_Request::get_localized_messages() ) ;

        SUMOSubscriptions_Enqueues::enqueue_script( 'sumosubscriptions-stripe-lib', 'https://js.stripe.com/v3/', array(), array( 'jquery' ), '3.0', true ) ;
        SUMOSubscriptions_Enqueues::enqueue_script( 'sumosubscriptions-stripe', SUMOSubscriptions_Enqueues::get_asset_url( 'js/frontend/stripe.js' ), $stripe_params, array( 'jquery', 'sumosubscriptions-stripe-lib' ), SUMO_SUBSCRIPTIONS_VERSION, true ) ;
        SUMOSubscriptions_Enqueues::enqueue_style( 'sumosubscriptions-stripe-style', SUMOSubscriptions_Enqueues::get_asset_url( 'css/stripe.css' ) ) ;
    }

    /**
     * Render Elements
     */
    public function elements_form() {
        ?>
        <fieldset id="wc-<?php echo esc_attr( $this->id ) ; ?>-cc-form" class="wc-credit-card-form wc-payment-form">
            <?php
            if ( 'inline_cc_form' === $this->checkoutmode ) {
                ?>
                <label for="stripe-card-element">
                    <?php esc_html_e( 'Credit or debit card', 'sumosubscriptions' ) ; ?>
                </label>
                <div id="sumosubsc-stripe-card-element" class="sumosubsc-stripe-elements-field">
                    <!-- A Stripe Element will be inserted here. -->
                </div>
                <?php
            } else {
                ?>
                <div class="form-row form-row-wide">
                    <label for="stripe-card-element"><?php esc_html_e( 'Card Number', 'sumosubscriptions' ) ; ?> <span class="required">*</span></label>
                    <div class="sumosubsc-stripe-card-group">
                        <div id="sumosubsc-stripe-card-element" class="sumosubsc-stripe-elements-field">
                            <!-- a Stripe Element will be inserted here. -->
                        </div>

                        <i class="sumosubsc-stripe-credit-card-brand sumosubsc-stripe-card-brand" alt="Credit Card"></i>
                    </div>
                </div>

                <div class="form-row form-row-first">
                    <label for="stripe-exp-element"><?php esc_html_e( 'Expiry Date', 'sumosubscriptions' ) ; ?> <span class="required">*</span></label>

                    <div id="sumosubsc-stripe-exp-element" class="sumosubsc-stripe-elements-field">
                        <!-- a Stripe Element will be inserted here. -->
                    </div>
                </div>

                <div class="form-row form-row-last">
                    <label for="stripe-cvc-element"><?php esc_html_e( 'Card Code (CVC)', 'sumosubscriptions' ) ; ?> <span class="required">*</span></label>
                    <div id="sumosubsc-stripe-cvc-element" class="sumosubsc-stripe-elements-field">
                        <!-- a Stripe Element will be inserted here. -->
                    </div>
                </div>
                <?php
            }
            ?> 
            <!-- Used to display form errors. -->
            <div class="sumosubsc-stripe-card-errors" role="alert"></div>
        </fieldset>
        <?php
    }

    /**
     * Add payment fields.
     */
    public function payment_fields() {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) ) ;
        }

        if ( $this->saved_cards && $this->supports( 'tokenization' ) && is_checkout() ) {
            $this->tokenization_script() ;
            $this->saved_payment_methods() ;
        }

        $this->elements_form() ;
    }

    /**
     * Adds an error message wrapper to each saved method.
     *
     * @param WC_Payment_Token $token Payment Token.
     * @return string Generated payment method HTML
     */
    public function get_saved_payment_method_option_html( $token ) {
        $html          = parent::get_saved_payment_method_option_html( $token ) ;
        $error_wrapper = '<div class="sumosubsc-stripe-card-errors" role="alert"></div>' ;

        return preg_replace( '~</(\w+)>\s*$~', "$error_wrapper</$1>", $html ) ;
    }

    /**
     * Checks to see if error is of invalid request
     * error and it is no such customer.
     *
     * @param array $error
     */
    public function is_no_such_customer_error( $error ) {
        return ( $error && 'invalid_request_error' === $error[ 'type' ] && preg_match( '/No such customer/i', $error[ 'message' ] ) ) ;
    }

    /**
     * Process a Stripe Payment.
     */
    public function process_payment( $order_id ) {

        try {
            if ( ! $order = wc_get_order( $order_id ) ) {
                throw new Exception( __( 'Payment failed: Invalid order !!', 'sumosubscriptions' ) ) ;
            }

            SUMO_Stripe_API_Request::init( $this ) ;

            $pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array(
                        'id' => $this->get_pm_via_post(),
                    ) ) ;

            if ( is_wp_error( $pm ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            $is_subscription_order = sumo_is_order_contains_subscriptions( $order->get_id() ) ;
            $stripe_customer       = false ;

            if ( get_current_user_id() && ($is_subscription_order || $this->saved_cards) ) {
                $stripe_customer = $this->maybe_create_customer( $this->prepare_current_userdata() ) ;
            }

            $auto_payments_enabled = $stripe_customer && $is_subscription_order && SUMO_Subscription_PaymentGateways::customer_has_chosen_auto_payment_mode_in( $this->id ) ? true : false ;
            $save_pm               = $stripe_customer && $this->saved_cards && 'stripe' === $this->is_pm_posted_via() ;

            if ( $stripe_customer ) {
                $pm = SUMO_Stripe_API_Request::request( 'attach_pm', array(
                            'id'       => $pm->id,
                            'customer' => $stripe_customer->id,
                        ) ) ;

                if ( is_wp_error( $pm ) ) {
                    throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
                }

                $pm = SUMO_Stripe_API_Request::request( 'update_pm', array(
                            'id'              => $pm->id,
                            'billing_details' => $this->prepare_userdata_from_order( $order ),
                            'metadata'        => $this->prepare_metadata_from_order( $order, $is_subscription_order )
                        ) ) ;

                if ( is_wp_error( $pm ) ) {
                    throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
                }
            }

            if ( $auto_payments_enabled ) {
                sumo_save_subscription_payment_info( $order->get_id(), array(
                    'payment_type'   => 'auto',
                    'payment_method' => $this->id,
                    'profile_id'     => $stripe_customer->id,
                    'payment_key'    => $pm->id,
                ) ) ;

                $save_pm = ( ! $this->saved_cards || 'stripe' === $this->is_pm_posted_via() ) ? true : false ;
            }

            $this->save_pm_to_order( $order, $pm ) ;

            if ( $order->get_total() <= 0 ) {
                $result = $this->process_order_without_payment( $order, $pm, $stripe_customer, $save_pm, $auto_payments_enabled, $is_subscription_order ) ;
            } else {
                $result = $this->process_order_payment( $order, $pm, $stripe_customer, $save_pm, $auto_payments_enabled, $is_subscription_order ) ;
            }

            if ( $save_pm && isset( $result[ 'result' ], $result[ 'intent' ] ) && 'success' === $result[ 'result' ] ) {
                $this->attach_pm_to_customer( $result[ 'intent' ] ) ;
            }
        } catch ( Exception $e ) {
            if ( isset( $order ) && is_a( $order, 'WC_Order' ) ) {
                $order->add_order_note( esc_html( $e->getMessage() ) ) ;
                $order->save() ;

                $this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
                    'order' => $order->get_id(),
                ) ) ;
            } else {
                $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
            }

            wc_add_notice( esc_html( $e->getMessage() ), 'error' ) ;

            return array(
                'result'   => 'failure',
                'redirect' => $this->get_return_url( $order )
                    ) ;
        }
        return $result ;
    }

    /**
     * Process an order that does require payment.
     */
    public function process_order_payment( &$order, $pm, $stripe_customer, $save_pm = false, $auto_payments_enabled = false, $is_subscription_order = false ) {
        //Check if the pi is already available for this order
        $pi      = SUMO_Stripe_API_Request::request( 'retrieve_pi', array( 'id' => $this->get_intent_from_order( $order ) ) ) ;
        $request = array(
            'payment_method' => $pm->id,
            'amount'         => $order->get_total(),
            'currency'       => $order->get_currency(),
            'metadata'       => $this->prepare_metadata_from_order( $order, $is_subscription_order ),
            'shipping'       => wc_shipping_enabled() ? $this->prepare_userdata_from_order( $order, 'shipping' ) : $this->prepare_userdata_from_order( $order ),
            'description'    => sprintf( __( '%1$s - Order %2$s', 'sumosubscriptions' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_id() ),
                ) ;

        if ( $stripe_customer ) {
            $request[ 'customer' ]           = $stripe_customer->id ;
            $request[ 'setup_future_usage' ] = 'off_session' ;
        }

        if ( is_wp_error( $pi ) || ($stripe_customer && $stripe_customer->id !== $pi->customer) ) {
            $pi = SUMO_Stripe_API_Request::request( 'create_pi', $request ) ;
        } else {
            $request[ 'id' ] = $pi->id ;

            $pi = SUMO_Stripe_API_Request::request( 'update_pi', $request ) ;
        }

        if ( is_wp_error( $pi ) ) {
            throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
        }

        $this->save_intent_to_order( $order, $pi ) ;

        if ( 'requires_confirmation' === $pi->status ) {
            // An pi with a payment method is ready to be confirmed.
            $pi->confirm( array(
                'payment_method' => $pm->id,
            ) ) ;
        }

        // If the intent requires a 3DS flow, redirect to it.
        if ( 'requires_action' === $pi->status || 'requires_source_action' === $pi->status ) {
            if ( is_checkout_pay_page() ) {
                $this->prompt_cutomer_to_auth_payment() ;
            }

            return array(
                'result'   => 'success',
                'redirect' => $this->prepare_customer_intent_verify_url( $pi, array(
                    'order'       => $order->get_id(),
                    'save_pm'     => $save_pm,
                    'redirect_to' => $this->get_return_url( $order ),
                ) ),
                    ) ;
        }

        //Process pi response.
        $result = $this->process_response( $pi, $order ) ;

        if ( 'success' !== $result ) {
            throw new Exception( $result ) ;
        }

        return array(
            'result'   => 'success',
            'intent'   => $pi,
            'redirect' => $this->get_return_url( $order )
                ) ;
    }

    /**
     * Process an order that doesn't require payment.
     */
    public function process_order_without_payment( &$order, $pm, $stripe_customer, $save_pm = false, $auto_payments_enabled = false, $is_subscription_order = false ) {
        // To charge recurring payments make sure to confirm the si before the order gets completed.
        if ( $stripe_customer && $auto_payments_enabled ) {
            //Check if the si is already available for this order
            $si      = SUMO_Stripe_API_Request::request( 'retrieve_si', array( 'id' => $this->get_intent_from_order( $order ) ) ) ;
            $request = array(
                'payment_method' => $pm->id,
                'customer'       => $stripe_customer->id,
                'metadata'       => $this->prepare_metadata_from_order( $order, $is_subscription_order ),
                'description'    => sprintf( __( '%1$s - Order %2$s', 'sumosubscriptions' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_id() ),
                    ) ;

            if ( is_wp_error( $si ) || $stripe_customer->id !== $si->customer ) {
                $request[ 'usage' ] = 'off_session' ;

                $si = SUMO_Stripe_API_Request::request( 'create_si', $request ) ;
            } else {
                $request[ 'id' ] = $si->id ;

                $si = SUMO_Stripe_API_Request::request( 'update_si', $request ) ;
            }

            if ( is_wp_error( $si ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            $this->save_intent_to_order( $order, $si ) ;

            if ( 'requires_confirmation' === $si->status ) {
                // An si with a payment method is ready to be confirmed.
                $si->confirm( array(
                    'payment_method' => $pm->id,
                ) ) ;
            }

            // If the intent requires a 3DS flow, redirect to it.
            if ( 'requires_action' === $si->status || 'requires_source_action' === $si->status ) {
                if ( is_checkout_pay_page() ) {
                    $this->prompt_cutomer_to_auth_payment() ;
                }

                return array(
                    'result'   => 'success',
                    'redirect' => $this->prepare_customer_intent_verify_url( $si, array(
                        'order'       => $order->get_id(),
                        'save_pm'     => $save_pm,
                        'redirect_to' => $this->get_return_url( $order ),
                    ) ),
                        ) ;
            }

            //Process si response.
            $result = $this->process_response( $si, $order ) ;

            if ( 'success' !== $result ) {
                throw new Exception( $result ) ;
            }

            return array(
                'result'   => 'success',
                'intent'   => $si,
                'redirect' => $this->get_return_url( $order )
                    ) ;
        } else {
            // Complete free payment 
            $order->payment_complete() ;

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order )
                    ) ;
        }
    }

    /**
     * Process a refund if supported.
     */
    public function process_refund( $order_id, $amount = null, $reason = null ) {

        try {
            if ( ! $order = wc_get_order( $order_id ) ) {
                throw new Exception( __( 'Refund failed: Invalid order', 'sumosubscriptions' ) ) ;
            }

            if ( ! $this->can_refund_order( $order ) ) {
                throw new Exception( __( 'Refund failed: No transaction ID', 'sumosubscriptions' ) ) ;
            }

            SUMO_Stripe_API_Request::init( $this ) ;

            $pi = SUMO_Stripe_API_Request::request( 'retrieve_pi', array( 'id' => $this->get_intent_from_order( $order ) ) ) ;

            $request = array(
                'amount' => $amount,
                'reason' => $reason,
                    ) ;

            if ( ! is_wp_error( $pi ) ) {
                $charge              = end( $pi->charges->data ) ;
                $request[ 'charge' ] = $charge->id ;
            } else {
                $request[ 'charge' ] = $order->get_transaction_id() ; //BKWD CMPT
            }

            $refund = SUMO_Stripe_API_Request::request( 'create_refund', $request ) ;

            if ( is_wp_error( $refund ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }
        } catch ( Exception $e ) {
            if ( isset( $order ) && is_a( $order, 'WC_Order' ) ) {
                $this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
                    'order' => $order->get_id(),
                ) ) ;
            } else {
                $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
            }

            return new WP_Error( 'sumosubscriptions-stripe-error', $e->getMessage() ) ;
        }
        return true ;
    }

    /**
     * Add payment method via account screen
     */
    public function add_payment_method() {
        if ( 'stripe' !== $this->is_pm_posted_via() ) {
            return ;
        }

        try {
            if ( ! is_user_logged_in() ) {
                throw new Exception( __( 'Stripe: User should be logged in and continue.', 'sumosubscriptions' ) ) ;
            }

            SUMO_Stripe_API_Request::init( $this ) ;

            $pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array(
                        'id' => $this->get_pm_via_post(),
                    ) ) ;

            if ( is_wp_error( $pm ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            $stripe_customer = $this->maybe_create_customer( $this->prepare_current_userdata() ) ;
            $request         = array(
                'payment_method' => $pm->id,
                'customer'       => $stripe_customer->id,
                'usage'          => 'off_session',
                    ) ;

            $si = SUMO_Stripe_API_Request::request( 'create_si', $request ) ;

            if ( is_wp_error( $si ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            if ( 'requires_confirmation' === $si->status ) {
                // An si with a payment method is ready to be confirmed.
                $si->confirm( array(
                    'payment_method' => $pm->id,
                ) ) ;
            }

            // If the intent requires a 3DS flow, redirect to it.
            if ( 'requires_action' === $si->status || 'requires_source_action' === $si->status ) {
                $this->prompt_cutomer_to_auth_payment() ;

                return array(
                    'result'   => 'awaiting_payment_confirmation',
                    'redirect' => $this->prepare_customer_intent_verify_url( $si, array(
                        'endpoint'    => 'add-payment-method',
                        'save_pm'     => true,
                        'redirect_to' => wc_get_endpoint_url( 'payment-methods' ),
                    ) ),
                        ) ;
            }

            //Process si response.
            $result = $this->process_response( $si, false ) ;

            if ( 'success' !== $result ) {
                throw new Exception( $result ) ;
            }

            $this->attach_pm_to_customer( $si ) ;
        } catch ( Exception $e ) {
            wc_add_notice( esc_html( $e->getMessage() ), 'error' ) ;
            $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
            return ;
        }

        return array(
            'result'   => 'success',
            'redirect' => wc_get_endpoint_url( 'payment-methods' ),
                ) ;
    }

    /**
     * Delete pm from Stripe.
     */
    public function wc_payment_token_deleted( $token_id, $token ) {
        if ( $this->id !== $token->get_gateway_id() ) {
            return ;
        }

        try {
            SUMO_Stripe_API_Request::init( $this ) ;

            $pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array( 'id' => $token->get_token() ) ) ;

            if ( is_wp_error( $pm ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            $pm->detach() ;
        } catch ( Exception $e ) {
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( esc_html( $e->getMessage() ), 'error' ) ;
            }
            $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
        }
    }

    /**
     * Set as default pm in Stripe.
     */
    public function wc_payment_token_set_default( $token_id ) {
        $token = WC_Payment_Tokens::get( $token_id ) ;

        if ( $this->id !== $token->get_gateway_id() ) {
            return ;
        }

        try {
            SUMO_Stripe_API_Request::init( $this ) ;

            $stripe_customer = SUMO_Stripe_API_Request::request( 'update_customer', array(
                        'id'               => $this->get_customer_from_user(),
                        'invoice_settings' => array(
                            'default_payment_method' => $token->get_token(),
                        )
                    ) ) ;

            if ( is_wp_error( $stripe_customer ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            if ( SUMO_Stripe_API_Request::is_customer_deleted( $stripe_customer ) ) {
                throw new Exception( __( 'Stripe: Couldn\'t find valid customer to set default payment method.', 'sumosubscriptions' ) ) ;
            }
        } catch ( Exception $e ) {
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( esc_html( $e->getMessage() ), 'error' ) ;
            }
            $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
        }
    }

    /**
     * Get our payment method from WC_Payment_Token
     *
     * @return int
     */
    public function wc_get_our_token( $token ) {
        return $this->id === $token->get_gateway_id() ? $token->get_token() : '' ;
    }

    /**
     * Get our default payment method from WC_Payment_Token
     *
     * @return int
     */
    public function wc_get_our_default_token( $token ) {
        return $this->id === $token->get_gateway_id() && $token->is_default() ? $token->get_token() : '' ;
    }

    /**
     * Get saved tokens from Stripe
     *
     * @return array
     */
    public function wc_get_customer_payment_tokens( $tokens, $user_id, $gateway_id ) {
        if ( ! is_user_logged_in() || ! class_exists( 'WC_Payment_Token_CC' ) ) {
            return $tokens ;
        }

        // Gateway id is valid only in checkout page, so we are doing this way
        if ( '' !== $gateway_id && $this->id !== $gateway_id ) {
            return $tokens ;
        }

        try {
            $our_tokens = array_filter( array_map( array( $this, 'wc_get_our_token' ), $tokens ) ) ;
            $customer   = $this->get_customer_from_user( $user_id ) ;

            if ( empty( $customer ) ) {
                if ( $this->id === $gateway_id ) {
                    return array() ;
                }

                return $tokens ;
            }

            SUMO_Stripe_API_Request::init( $this ) ;

            $customers_pm = SUMO_Stripe_API_Request::request( 'retrieve_all_pm', array(
                        'customer' => $customer,
                    ) ) ;

            if ( is_wp_error( $customers_pm ) ) {
                if ( $this->is_no_such_customer_error( SUMO_Stripe_API_Request::get_last_error_response() ) ) {
                    delete_user_meta( $user_id, '_sumo_subsc_stripe_customer_id' ) ;

                    if ( $this->id === $gateway_id ) {
                        return array() ;
                    }
                }

                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            if ( empty( $customers_pm->data ) ) {
                throw new Exception( sprintf( __( 'No such payment methods available for customer %s', 'sumosubscriptions' ), $customer ) ) ;
            }

            $customer_paymentMethods = array() ;
            foreach ( $customers_pm->data as $pm ) {
                if ( ! isset( $pm->id ) || 'card' !== $pm->type ) {
                    continue ;
                }

                if ( ! in_array( $pm->id, $our_tokens ) ) {
                    $token                      = $this->add_wc_payment_token( $pm, $user_id ) ;
                    $tokens[ $token->get_id() ] = $token ;
                }

                $customer_paymentMethods[] = $pm->id ;
            }

            if ( is_add_payment_method_page() ) {
                if ( $this->chargeDefaultCardIfOriginalCardFails && ! empty( $customer_paymentMethods ) ) {
                    $our_default_token = implode( array_filter( array_map( array( $this, 'wc_get_our_default_token' ), $tokens ) ) ) ;

                    if ( in_array( $our_default_token, $customer_paymentMethods ) ) {
                        wc_print_notice( __( 'In case if the originally authorized card for the respective subscription is not able to process the recurring payment for some reason then the payment retry will happen using the default card selected here', 'sumosubscriptions' ), 'notice' ) ;
                    }
                }
            }
        } catch ( Exception $e ) {
            $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
        }
        return $tokens ;
    }

    /**
     *  Process the given response
     */
    public function process_response( $response, $order = false ) {

        switch ( $response->status ) {
            case 'succeeded':
            case 'paid': // BKWD CMPT for Charge API
                if ( $order ) {
                    if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
                        $order->payment_complete( $response->id ) ;
                    }

                    if ( 'setup_intent' === $response->object ) {
                        $order->add_order_note( __( 'Stripe: payment complete. Customer has approved for future payments.', 'sumosubscriptions' ) ) ;
                    } else {
                        $order->add_order_note( __( 'Stripe: payment complete', 'sumosubscriptions' ) ) ;
                    }

                    $order->set_transaction_id( $response->id ) ;
                    $order->save() ;
                }
                return 'success' ;
                break ;
            case 'processing':
            case 'pending': // BKWD CMPT for Charge API
                if ( $order ) {
                    if ( ! $order->has_status( 'on-hold' ) ) {
                        $order->update_status( 'on-hold' ) ;
                    }

                    if ( 'setup_intent' === $response->object ) {
                        $order->add_order_note( sprintf( __( 'Stripe: awaiting confirmation by the customer to approve for future payments: %s.', 'sumosubscriptions' ), $response->id ) ) ;
                    } else {
                        $order->add_order_note( sprintf( __( 'Stripe: awaiting payment: %s.', 'sumosubscriptions' ), $response->id ) ) ;
                    }

                    $order->set_transaction_id( $response->id ) ;
                    $order->save() ;
                }
                return 'success' ;
                break ;
            case 'requires_payment_method':
            case 'requires_source': // BKWD CMPT
            case 'canceled':
            case 'failed': // BKWD CMPT for Charge API
                $this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  ) ;

                if ( isset( $response->last_setup_error ) ) {
                    $message = $response->last_setup_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumosubscriptions' ), $response->last_setup_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumosubscriptions' ) ;
                } else if ( isset( $response->last_payment_error ) ) {
                    $message = $response->last_payment_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumosubscriptions' ), $response->last_payment_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumosubscriptions' ) ;
                } else if ( isset( $response->failure_message ) ) {
                    $message = $response->failure_message ? sprintf( __( 'Stripe: payment failed. Reason: %s', 'sumosubscriptions' ), $response->failure_message ) : __( 'Stripe: payment failed.', 'sumosubscriptions' ) ;
                } else {
                    $message = __( 'Stripe: payment failed.', 'sumosubscriptions' ) ;
                }

                if ( $order ) {
                    $order->add_order_note( $message ) ;
                    $order->save() ;
                }

                return $message ;
                break ;
        }

        $this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  ) ;
        return 'failure' ;
    }

    /**
     * Verify pi/si via Stripe.js
     */
    public function verify_intent() {

        try {
            if ( empty( $_GET[ 'nonce' ] ) || empty( $_GET[ 'endpoint' ] ) || empty( $_GET[ 'intent' ] ) || empty( $_GET[ 'intentObj' ] ) || ! wp_verify_nonce( sanitize_key( $_GET[ 'nonce' ] ), 'sumo_stripe_confirm_intent' ) ) {
                throw new Exception( __( 'Stripe: Intent verification failed.', 'sumosubscriptions' ) ) ;
            }

            if ( in_array( $_GET[ 'endpoint' ], array( 'checkout', 'pay-for-order' ) ) ) {
                $order = wc_get_order( isset( $_GET[ 'order' ] ) ? absint( $_GET[ 'order' ] ) : 0 ) ;

                if ( ! $order ) {
                    throw new Exception( __( 'Stripe: Invalid order while verifying intent confirmation.', 'sumosubscriptions' ) ) ;
                }

                if ( $this->id !== $order->get_payment_method() ) {
                    throw new Exception( __( 'Stripe: Invalid payment method while verifying intent confirmation.', 'sumosubscriptions' ) ) ;
                }
            } else {
                $order = false ;
            }

            SUMO_Stripe_API_Request::init( $this ) ;

            if ( 'setup_intent' === $_GET[ 'intentObj' ] ) {
                $intent = SUMO_Stripe_API_Request::request( 'retrieve_si', array( 'id' => wc_clean( ( string ) $_GET[ 'intent' ] ) ) ) ;
            } else {
                $intent = SUMO_Stripe_API_Request::request( 'retrieve_pi', array( 'id' => wc_clean( ( string ) $_GET[ 'intent' ] ) ) ) ;
            }

            if ( is_wp_error( $intent ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
            }

            $result = $this->process_response( $intent, $order ) ;

            if ( 'success' !== $result ) {
                throw new Exception( $result ) ;
            }

            if ( $intent->customer && isset( $_GET[ 'save_pm' ] ) && $_GET[ 'save_pm' ] ) {
                $pm = $this->attach_pm_to_customer( $intent ) ;

                if ( 'add-payment-method' === $_GET[ 'endpoint' ] ) {
                    $this->add_wc_payment_token( $pm ) ;
                    wc_add_notice( __( 'Payment method successfully added.', 'sumosubscriptions' ) ) ;
                }
            }

            if ( isset( $_GET[ 'is_ajax' ] ) ) {
                return ;
            }

            $redirect_url = ! empty( $_GET[ 'redirect_to' ] ) ? esc_url_raw( wp_unslash( $_GET[ 'redirect_to' ] ) ) : '' ;

            if ( empty( $redirect_url ) ) {
                if ( $order ) {
                    $redirect_url = $this->get_return_url( $order ) ;
                } else {
                    $redirect_url = WC()->cart->is_empty() ? get_permalink( wc_get_page_id( 'shop' ) ) : wc_get_checkout_url() ;
                }
            }

            wp_safe_redirect( $redirect_url ) ;
            exit ;
        } catch ( Exception $e ) {
            if ( isset( $_GET[ 'is_ajax' ] ) ) {
                $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
                return ;
            }

            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( esc_html( $e->getMessage() ), 'error' ) ;
            }

            $redirect_url = ! empty( $_GET[ 'redirect_to' ] ) ? esc_url_raw( wp_unslash( $_GET[ 'redirect_to' ] ) ) : '' ;

            if ( empty( $redirect_url ) ) {
                if ( isset( $order ) && is_a( $order, 'WC_Order' ) ) {
                    $this->log_err( SUMO_Stripe_API_Request::get_last_log(), array( 'order' => $order->get_id() ) ) ;
                    $redirect_url = ! empty( $_GET[ 'redirect_to' ] ) ? esc_url_raw( wp_unslash( $_GET[ 'redirect_to' ] ) ) : $this->get_return_url( $order ) ;
                } else {
                    $this->log_err( SUMO_Stripe_API_Request::get_last_log() ) ;
                    $redirect_url = WC()->cart->is_empty() ? get_permalink( wc_get_page_id( 'shop' ) ) : wc_get_checkout_url() ;
                }
            }

            wp_safe_redirect( $redirect_url ) ;
            exit ;
        }
    }

    /**
     * Check whether Stripe can charge customer for the Subscription renewal payment to happen.
     * 
     * @param bool $bool
     * @param int $subscription_id
     * @param WC_Order $renewal_order
     * @return bool
     */
    public function can_charge_renewal_payment( $bool, $subscription_id, $renewal_order ) {

        try {

            SUMO_Stripe_API_Request::init( $this ) ;

            $customer = SUMO_Stripe_API_Request::request( 'retrieve_customer', array(
                        'id' => $this->get_stripe_customer_id_from_subscription( $subscription_id )
                    ) ) ;

            if ( is_wp_error( $customer ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) ) ;
            }

            if ( SUMO_Stripe_API_Request::is_customer_deleted( $customer ) ) {
                throw new Exception( sprintf( __( 'Stripe: Couldn\'t find the customer %s', 'sumosubscriptions' ), $customer->id ) ) ;
            }

            $pm = $this->get_stripe_pm_id_from_subscription( $subscription_id ) ;

            //BKWD CMPT for < v11.0
            if ( empty( $pm ) ) {
                return true ;
            }

            $pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array(
                        'id' => $pm
                    ) ) ;

            if ( is_wp_error( $pm ) ) {
                throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::PAYMENT_RETRY_WITH_DEFAULT_CARD ) ;
            }

            $this->save_pm_to_order( $renewal_order, $pm ) ;
        } catch ( Exception $e ) {
            $this->add_subscription_err_note( $e->getMessage(), $subscription_id ) ;
            $this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
                'order'        => $renewal_order->get_id(),
                'subscription' => $subscription_id,
            ) ) ;

            switch ( $e->getCode() ) {
                case self::PAYMENT_RETRY_WITH_DEFAULT_CARD:
                    if ( $this->chargeDefaultCardIfOriginalCardFails && isset( $customer, $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method ) {
                        $this->save_pm_to_order( $renewal_order, $customer->invoice_settings->default_payment_method ) ;
                        $this->add_subscription_err_note( __( 'Start retrying payment with the default card chosen by the customer.', 'sumosubscriptions' ), $subscription_id ) ;
                        return true ;
                    }
                    break ;
            }
            return false ;
        }
        return true ;
    }

    /**
     * Charge the customer to renew the Subscription.
     * 
     * @param bool $bool
     * @param int $subscription_id
     * @param WC_Order $renewal_order
     * @return bool
     */
    public function charge_renewal_payment( $bool, $subscription_id, $renewal_order, $retry = false ) {

        try {

            SUMO_Stripe_API_Request::init( $this ) ;

            $this->retry_failed_payment = $retry ;

            $request = array(
                'customer' => $this->get_stripe_customer_id_from_subscription( $subscription_id )
                    ) ;

            if ( $this->retry_failed_payment ) {
                $customer = SUMO_Stripe_API_Request::request( 'retrieve_customer', array(
                            'id' => $request[ 'customer' ]
                        ) ) ;

                if ( is_wp_error( $customer ) ) {
                    throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) ) ;
                }

                if ( isset( $customer, $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method ) {
                    $this->save_pm_to_order( $renewal_order, $customer->invoice_settings->default_payment_method ) ;
                } else {
                    throw new Exception( __( 'Stripe: Couldn\'t find any default card from the customer.', 'sumosubscriptions' ) ) ;
                }
            }

            $request[ 'amount' ]      = $renewal_order->get_total() ;
            $request[ 'currency' ]    = $renewal_order->get_currency() ;
            $request[ 'metadata' ]    = $this->prepare_metadata_from_order( $renewal_order, true, $subscription_id ) ;
            $request[ 'shipping' ]    = wc_shipping_enabled() ? $this->prepare_userdata_from_order( $renewal_order, 'shipping' ) : $this->prepare_userdata_from_order( $renewal_order ) ;
            $request[ 'description' ] = sprintf( __( '%1$s - Order %2$s', 'sumosubscriptions' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $renewal_order->get_id() ) ;

            if ( $pm = $this->get_pm_from_order( $renewal_order ) ) {
                $request[ 'payment_method' ] = $pm ;
                $request[ 'off_session' ]    = $request[ 'confirm' ]        = true ;

                $request_api = 'create_pi' ;
            } else {
                $request_api = 'charge_customer' ;
            }

            $response = SUMO_Stripe_API_Request::request( $request_api, $request ) ;

            if ( is_wp_error( $response ) ) {
                if ( 'authentication_required' === SUMO_Stripe_API_Request::get_last_declined_code() ) {
                    throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::STRIPE_REQUIRES_AUTH ) ;
                } else {
                    if ( 'create_pi' === $request_api ) {
                        throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::PAYMENT_RETRY_WITH_DEFAULT_CARD ) ;
                    } else {
                        throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) ) ;
                    }
                }
            }

            if ( 'payment_intent' === $response->object ) {
                $this->save_intent_to_order( $renewal_order, $response ) ;
            }

            //Process response.
            $result = $this->process_response( $response, $renewal_order ) ;

            if ( 'success' !== $result ) {
                if ( 'payment_intent' === $response->object ) {
                    throw new Exception( $result, self::PAYMENT_RETRY_WITH_DEFAULT_CARD ) ;
                } else {
                    throw new Exception( $result ) ;
                }
            }
        } catch ( Exception $e ) {
            $this->add_subscription_err_note( $e->getMessage(), $subscription_id ) ;
            $this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
                'order'        => $renewal_order->get_id(),
                'subscription' => $subscription_id,
            ) ) ;

            if ( ! $this->retry_failed_payment ) {
                switch ( $e->getCode() ) {
                    case self::PAYMENT_RETRY_WITH_DEFAULT_CARD:
                        if ( $this->chargeDefaultCardIfOriginalCardFails ) {
                            return $this->charge_renewal_payment( $bool, $subscription_id, $renewal_order, true ) ;
                        }
                        break ;
                    case self::STRIPE_REQUIRES_AUTH:
                        if ( $this->chargeDefaultCardIfOriginalCardFails ) {
                            $this->add_subscription_err_note( __( 'Start retrying payment with the default card chosen by the customer.', 'sumosubscriptions' ), $subscription_id ) ;

                            return $this->charge_renewal_payment( $bool, $subscription_id, $renewal_order, true ) ;
                        }

                        do_action( 'sumosubscriptions_stripe_requires_authentication', $renewal_order, $subscription_id ) ;
                        break ;
                }
            }
            return false ;
        }
        return true ;
    }

    /**
     * Prepare the customer to bring it 'OnSession' to complete the renewal
     */
    public function prepare_customer_to_authorize_payment( $renewal_order, $subscription_id ) {
        add_post_meta( $renewal_order->get_id(), '_sumo_subsc_stripe_authentication_required', 'yes' ) ;
        add_post_meta( $subscription_id, 'stripe_authentication_required', 'yes' ) ;
    }

    /**
     * Hold the subscription until the payment is approved by the customer
     */
    public function set_next_eligible_subscription_status( $next_eligible_status, $subscription_id ) {
        if ( 'yes' === get_post_meta( $subscription_id, 'stripe_authentication_required', true ) ) {
            $next_eligible_status = 'Pending_Authorization' ;
        }
        return $next_eligible_status ;
    }

    /**
     * Clear cache
     */
    public function subscription_in_pending_authorization( $subscription_id ) {
        delete_post_meta( $subscription_id, 'stripe_authentication_required' ) ;
    }

    /**
     * Return the number of days to be hold in Pending Authorization.
     * 
     * @return int
     */
    public function get_pending_auth_period( $no_of_days ) {
        return $this->pendingAuthPeriod ;
    }

    /**
     * Return the times per day to remind users in Pending Authorization.
     * 
     * @return int
     */
    public function get_pending_auth_times_per_day_to_remind( $times_per_day ) {
        return $this->pendingAuthEmailReminder ;
    }

    /**
     * Save Stripe paymentMethod in Order
     */
    public function save_pm_to_order( $order, $pm ) {
        update_post_meta( $order->get_id(), '_sumo_subsc_stripe_pm', isset( $pm->id ) ? $pm->id : $pm  ) ;
    }

    /**
     * Save Stripe intent in Order
     */
    public function save_intent_to_order( $order, $intent ) {
        if ( 'payment_intent' === $intent->object ) {
            update_post_meta( $order->get_id(), '_sumo_subsc_stripe_pi', $intent->id ) ;
        } else if ( 'setup_intent' === $intent->object ) {
            update_post_meta( $order->get_id(), '_sumo_subsc_stripe_si', $intent->id ) ;
        }
        update_post_meta( $order->get_id(), '_sumo_subsc_stripe_intentObject', $intent->object ) ;
    }

    /**
     * Prepare pi/si verification url
     */
    public function prepare_customer_intent_verify_url( $intent, $query_args = array() ) {
        $query_args = wp_parse_args( $query_args, array(
            'intent'      => $intent->id,
            'intentObj'   => $intent->object,
            'endpoint'    => '',
            'save_pm'     => false,
            'nonce'       => wp_create_nonce( 'sumo_stripe_confirm_intent' ),
            'redirect_to' => get_site_url(),
                ) ) ;

        $query_args[ 'redirect_to' ] = rawurlencode( $query_args[ 'redirect_to' ] ) ;

        if ( empty( $query_args[ 'endpoint' ] ) ) {
            if ( is_checkout_pay_page() ) {
                $query_args[ 'endpoint' ] = 'pay-for-order' ;
            } else {
                $query_args[ 'endpoint' ] = 'checkout' ;
            }
        }

        // Redirect into the verification URL thereby we need to verify the intent
        $verification_url = rawurlencode( add_query_arg( $query_args, WC_AJAX::get_endpoint( 'sumo_stripe_verify_intent' ) ) ) ;

        return sprintf( '#confirm-sumo-stripe-intent-%s:%s:%s:%s', $intent->client_secret, $intent->object, $query_args[ 'endpoint' ], $verification_url ) ;
    }

    /**
     * Prepare current userdata
     */
    public function prepare_current_userdata() {
        if ( ! $user = get_user_by( 'id', get_current_user_id() ) ) {
            return array() ;
        }

        $billing_first_name = get_user_meta( $user->ID, 'billing_first_name', true ) ;
        $billing_last_name  = get_user_meta( $user->ID, 'billing_last_name', true ) ;

        if ( empty( $billing_first_name ) ) {
            $billing_first_name = get_user_meta( $user->ID, 'first_name', true ) ;
        }

        if ( empty( $billing_last_name ) ) {
            $billing_last_name = get_user_meta( $user->ID, 'last_name', true ) ;
        }

        $userdata = array(
            'address' => array(
                'line1'       => get_user_meta( $user->ID, 'billing_address_1', true ),
                'line2'       => get_user_meta( $user->ID, 'billing_address_2', true ),
                'city'        => get_user_meta( $user->ID, 'billing_city', true ),
                'state'       => get_user_meta( $user->ID, 'billing_state', true ),
                'postal_code' => get_user_meta( $user->ID, 'billing_postcode', true ),
                'country'     => get_user_meta( $user->ID, 'billing_country', true ),
            ),
            'fname'   => $billing_first_name,
            'lname'   => $billing_last_name,
            'phone'   => get_user_meta( $user->ID, 'billing_phone', true ),
            'email'   => $user->user_email,
                ) ;
        return $userdata ;
    }

    /**
     * Prepare userdata from order
     * 
     * @param string $type billing|shipping
     */
    public function prepare_userdata_from_order( $order, $type = 'billing' ) {
        $userdata = array(
            'address' => array(
                'line1'       => get_post_meta( $order->get_id(), "_{$type}_address_1", true ),
                'line2'       => get_post_meta( $order->get_id(), "_{$type}_address_2", true ),
                'city'        => get_post_meta( $order->get_id(), "_{$type}_city", true ),
                'state'       => get_post_meta( $order->get_id(), "_{$type}_state", true ),
                'postal_code' => get_post_meta( $order->get_id(), "_{$type}_postcode", true ),
                'country'     => get_post_meta( $order->get_id(), "_{$type}_country", true ),
            ),
            'fname'   => get_post_meta( $order->get_id(), "_{$type}_first_name", true ),
            'lname'   => get_post_meta( $order->get_id(), "_{$type}_last_name", true ),
            'phone'   => get_post_meta( $order->get_id(), '_billing_phone', true ),
            'email'   => get_post_meta( $order->get_id(), '_billing_email', true ),
                ) ;

        if ( 'shipping' === $type && empty( $userdata[ 'fname' ] ) ) {
            $userdata[ 'fname' ] = get_post_meta( $order->get_id(), '_billing_first_name', true ) ;
            $userdata[ 'lname' ] = get_post_meta( $order->get_id(), '_billing_last_name', true ) ;
        }

        return $userdata ;
    }

    /**
     * Prepare metadata to display in Stripe.
     * May be useful to keep track the subscription orders
     */
    public function prepare_metadata_from_order( $order, $order_contains_subscription = false, $subscription_id = null ) {
        $metadata = array(
            'Order' => '#' . $order->get_id()
                ) ;

        if ( $subscription_id > 0 ) {
            $metadata[ 'Subscription' ] = '#' . $subscription_id ;
        }

        if ( $subscription_id > 0 || $order_contains_subscription ) {
            $metadata[ 'Parent Order' ] = 0 === wp_get_post_parent_id( $order->get_id() ) ? true : false ;
            $metadata[ 'Payment Type' ] = 'Recurring' ;
        }

        $metadata[ 'Site Url' ] = esc_url( get_site_url() ) ;
        return $metadata ;
    }

    /**
     * Add subscription error note
     */
    public function add_subscription_err_note( $err, $subscription_id ) {
        sumo_add_subscription_note( sprintf( __( 'Stripe: <b>%s</b>', 'sumosubscriptions' ), $err ), $subscription_id, 'failure', __( 'Stripe Request Failed', 'sumosubscriptions' ) ) ;
    }

    /**
     * Add token to WooCommerce.
     */
    public function add_wc_payment_token( $pm, $user_id = '' ) {
        if ( ! class_exists( 'WC_Payment_Token_CC' ) ) {
            throw new Exception( __( 'Stripe: Couldn\'t add payment method !!', 'sumosubscriptions' ) ) ;
        }

        if ( 'payment_method' !== $pm->object || 'card' !== $pm->type ) {
            throw new Exception( __( 'Stripe: Invalid payment method. Please retry !!', 'sumosubscriptions' ) ) ;
        }

        $wc_token = new WC_Payment_Token_CC() ;
        $wc_token->set_token( $pm->id ) ;
        $wc_token->set_gateway_id( $this->id ) ;
        $wc_token->set_card_type( strtolower( $pm->card->brand ) ) ;
        $wc_token->set_last4( $pm->card->last4 ) ;
        $wc_token->set_expiry_month( $pm->card->exp_month ) ;
        $wc_token->set_expiry_year( $pm->card->exp_year ) ;
        $wc_token->set_user_id( $user_id ? $user_id : get_current_user_id()  ) ;
        $wc_token->save() ;
        return $wc_token ;
    }

    /**
     * Maybe create Stripe Customer
     */
    public function maybe_create_customer( $args = array() ) {
        $stripe_customer_saved = $this->get_customer_from_user() ;

        //Check if the user has already registered as Stripe Customer
        $stripe_customer = SUMO_Stripe_API_Request::request( 'retrieve_customer', array(
                    'id' => $stripe_customer_saved,
                ) ) ;

        if ( is_wp_error( $stripe_customer ) ) {
            if ( ! empty( $stripe_customer_saved ) && $this->is_no_such_customer_error( SUMO_Stripe_API_Request::get_last_error_response() ) ) {
                delete_user_meta( get_current_user_id(), '_sumo_subsc_stripe_customer_id' ) ;
            }
        } else {
            if ( $saved_stripe_customer_deleted = SUMO_Stripe_API_Request::is_customer_deleted( $stripe_customer ) ) {
                delete_user_meta( get_current_user_id(), '_sumo_subsc_stripe_customer_id' ) ;
            }
        }

        if ( empty( $stripe_customer_saved ) || is_wp_error( $stripe_customer ) || $saved_stripe_customer_deleted ) {
            $stripe_customer = SUMO_Stripe_API_Request::request( 'create_customer', SUMO_Stripe_API_Request::prepare_customer_details( $args ) ) ;
        }

        if ( is_wp_error( $stripe_customer ) ) {
            throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
        }

        update_user_meta( get_current_user_id(), '_sumo_subsc_stripe_customer_id', $stripe_customer->id ) ;
        return $stripe_customer ;
    }

    /**
     * Attach payment method to Customer via Intent
     */
    public function attach_pm_to_customer( $intent ) {
        if ( ! isset( $intent->customer ) || ! $intent->customer ) {
            throw new Exception( __( 'Stripe: Couldn\'t find valid customer to attach payment method.', 'sumosubscriptions' ) ) ;
        }

        $pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array( 'id' => $intent->payment_method ) ) ;

        if ( is_wp_error( $pm ) ) {
            throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() ) ;
        }

        $pm->attach( array( 'customer' => $intent->customer ) ) ;
        return $pm ;
    }

    /**
     * Stripe error logger
     */
    public function log_err( $log, $map_args = array() ) {
        if ( empty( $log ) ) {
            return ;
        }

        include_once(SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/subscription-logger/class-subscription-wc-logger.php') ;
        SUMOSubscription_WC_Logger::log( $log, $map_args ) ;
    }

    /**
     * Check if the paymentMethod posted via Stripe.js or WC Token
     */
    public function is_pm_posted_via() {
        if ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] ) {
            return 'wc-token' ;
        } else if ( ! empty( $_POST[ 'sumosubsc_stripe_pm' ] ) ) {
            return 'stripe' ;
        }
        return null ;
    }

    /**
     * Get the cleaned paymentMethod created via POST.
     */
    public function get_pm_via_post() {
        switch ( $this->is_pm_posted_via() ) {
            case 'wc-token':
                $wc_token = WC_Payment_Tokens::get( wc_clean( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) ) ;

                if ( $wc_token && $wc_token->get_user_id() === get_current_user_id() ) {
                    return $wc_token->get_token() ;
                }
                break ;
            case 'stripe':
                return wc_clean( $_POST[ 'sumosubsc_stripe_pm' ] ) ;
                break ;
        }

        throw new Exception( __( 'Invalid payment method. Please retry with a new card number.', 'sumosubscriptions' ) ) ;
    }

    /**
     * Get saved Stripe intent object from Order
     */
    public function get_intentObj_from_order( $order ) {
        return get_post_meta( $order->get_id(), '_sumo_subsc_stripe_intentObject', true ) ;
    }

    /**
     * Get saved Stripe intent from Order
     */
    public function get_intent_from_order( $order ) {
        $metakey = 'setup_intent' === $this->get_intentObj_from_order( $order ) ? '_sumo_subsc_stripe_si' : '_sumo_subsc_stripe_pi' ;

        return get_post_meta( $order->get_id(), "$metakey", true ) ;
    }

    /**
     * Get saved Stripe paymentMethod from Order
     */
    public function get_pm_from_order( $order ) {
        return get_post_meta( $order->get_id(), '_sumo_subsc_stripe_pm', true ) ;
    }

    /**
     * Check if it is auto payments
     * 
     * @return bool
     */
    public function is_order_placed_as_auto( $order ) {
        $payment_type = sumo_get_subscription_order_payment( $order->get_id(), 'payment_type' ) ;

        return in_array( $payment_type, array( 'auto', 'automatic' ) ) ? true : false ;
    }

    /**
     * Get saved Stripe customer from the user
     * 
     * @return string
     */
    public function get_customer_from_user( $user_id = '' ) {
        $user_id = $user_id ? $user_id : get_current_user_id() ;
        return get_user_meta( $user_id, '_sumo_subsc_stripe_customer_id', true ) ;
    }

    /**
     * Get saved Stripe customer ID from Subscription
     * 
     * @return string
     */
    public function get_stripe_customer_id_from_subscription( $subscription_id ) {
        $customer_id = sumo_get_subscription_payment( $subscription_id, 'profile_id' ) ;

        return is_array( $customer_id ) ? implode( $customer_id ) : $customer_id ;
    }

    /**
     * Get saved Stripe paymentMethod ID from Subscription
     * 
     * @return string
     */
    public function get_stripe_pm_id_from_subscription( $subscription_id ) {
        $pm_id = sumo_get_subscription_payment( $subscription_id, 'payment_key' ) ;

        return is_array( $pm_id ) ? implode( $pm_id ) : $pm_id ;
    }

    /**
     * Prompt the customer to authorize their payment
     */
    public function prompt_cutomer_to_auth_payment() {
        wc_add_notice( esc_html( __( 'Almost there!! the only thing that still needs to be done is for you to authorize the payment with your bank.', 'sumosubscriptions' ) ), 'success' ) ;
    }

}

return new SUMO_Stripe_Gateway() ;
