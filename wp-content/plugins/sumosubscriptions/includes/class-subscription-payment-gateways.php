<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Subscription Payment Gateways
 * 
 * @class SUMO_Subscription_PaymentGateways
 * @category Class
 */
class SUMO_Subscription_PaymentGateways {

    /**
     * Get payment gateways to load in to the WC checkout
     * @var array 
     */
    protected static $load_gateways = array() ;

    /**
     * Subscription Automatic payment gateways
     * @var array 
     */
    protected static $subscription_payment_gateways = array() ;

    /**
     * Check whether to hid automatic payment gateways in checkout when non subscriptions are in cart
     * @var bool 
     */
    protected static $hide_auto_payment_gateways_when_non_subscriptions_in_cart = false ;

    /**
     * Check whether Mixed(Subscription Automatic and WC) payment gateways enabled in checkout
     * @var bool 
     */
    protected static $mixed_payment_gateways_enabled = false ;

    /**
     * Check whether automatic payment gateways alone enabled in checkout
     * @var bool 
     */
    protected static $auto_payment_gateways_enabled = false ;

    /**
     * Check whether payment mode switcher is enabled in checkout
     * @var bool 
     */
    protected static $payment_mode_switcher_enabled = false ;

    /**
     * Check whether checkout has subscription
     * @var bool 
     */
    protected static $checkout_has_subscription = false ;

    /**
     * Check whether checkout having multiple subscriptions
     * @var bool 
     */
    protected static $checkout_has_multiple_subscriptions = false ;

    /**
     * Check whether checkout has synced subscription
     * @var bool 
     */
    protected static $checkout_has_synced_subscription = false ;

    /**
     * Check whether checkout has subscription with signup and without trial
     * @var bool 
     */
    protected static $checkout_has_signup_subscription_without_trial = false ;

    /**
     * Check whether payment mode should be forced to checkout
     * @var bool
     */
    protected static $force_payment_mode_as = '' ;

    /**
     * Check whether WC PayPal Standard Subscription API is enabled in checkout
     * @var bool
     */
    protected static $paypal_std_subscription_api_enabled = false ;

    /**
     * Check whether the Customer has chosen Automatic payment mode in checkout
     * @var bool
     */
    protected static $customer_has_chosen_auto_payment_mode = false ;

    /**
     * Show payment gateways when order amount is zero
     * @var bool 
     */
    protected static $show_gateways_when_order_amt_zero = false ;

    /**
     * Hide PayPal Standard payment gateway
     * @var bool 
     */
    protected static $hide_paypal_std_gateway = false ;

    /**
     * Get payment gateways to hide when order amount is zero
     * @var array 
     */
    protected static $gateways_to_hide_when_order_amt_zero = array() ;

    /** List of supported automatic payment gateways * */
    const PAYPAL_SUBSCRIPTIONS         = 'paypal' ;
    const PAYPAL_ADAPTIVE_GATEWAY      = 'sumo_paypal_preapproval' ;
    const PAYPAL_REFERENCE_TXN_GATEWAY = 'sumo_paypal_reference_txns' ;
    const STRIPE_GATEWAY               = 'sumo_stripe' ;

    /**
     * Create instance for SUMO_Subscription_PaymentGateways.
     */
    public static function instance() {
        self::populate() ;

        add_action( 'plugins_loaded', __CLASS__ . '::load_payment_gateways', 20 ) ;
        add_filter( 'woocommerce_payment_gateways', __CLASS__ . '::add_payment_gateways' ) ;
        add_filter( 'woocommerce_cart_needs_payment', __CLASS__ . '::need_payment_gateways', 99, 2 ) ;
        add_filter( 'woocommerce_available_payment_gateways', __CLASS__ . '::set_payment_gateways' ) ;
        add_filter( 'woocommerce_gateway_description', __CLASS__ . '::set_payment_mode_switcher', 10, 2 ) ;
        return new self() ;
    }

    /**
     * Populate the SUMO_Subscription_PaymentGateways
     */
    public static function populate() {

        self::$mixed_payment_gateways_enabled      = 'yes' === get_option( 'sumosubs_accept_manual_payment_gateways', 'yes' ) && 'yes' !== get_option( 'sumosubs_disable_auto_payment_gateways', 'no' ) ;
        self::$auto_payment_gateways_enabled       = 'yes' === get_option( 'sumosubs_accept_manual_payment_gateways', 'yes' ) && 'yes' === get_option( 'sumosubs_disable_auto_payment_gateways', 'no' ) ? false : true ;
        self::$payment_mode_switcher_enabled       = 'no' === get_option( 'sumo_paypal_payment_option', 'no' ) && self::$auto_payment_gateways_enabled ;
        self::$paypal_std_subscription_api_enabled = 'yes' === get_option( 'sumo_include_paypal_subscription_api_option', 'yes' ) ;

        if ( ! self::$payment_mode_switcher_enabled && self::$auto_payment_gateways_enabled ) {
            self::$force_payment_mode_as = '2' === get_option( 'sumo_force_auto_manual_paypal_adaptive', '2' ) ? 'auto' : 'manual' ;
        }
        self::$show_gateways_when_order_amt_zero                         = 'yes' === get_option( 'sumosubscription_show_payment_gateways_when_order_amt_zero', 'yes' ) ;
        self::$gateways_to_hide_when_order_amt_zero                      = get_option( 'sumosubs_payment_gateways_to_hide_when_order_amt_zero', array() ) ;
        self::$hide_auto_payment_gateways_when_non_subscriptions_in_cart = 'yes' === get_option( 'sumosubs_hide_auto_payment_gateways_when_non_subscriptions_in_cart', 'no' ) ;
        self::$hide_paypal_std_gateway                                   = 'auto' === self::$force_payment_mode_as && self::$paypal_std_subscription_api_enabled && 'no' === get_option( 'sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart', 'yes' ) ;
    }

    /**
     * Get payment gateways to load in to the WC checkout
     */
    public static function load_payment_gateways() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return ;
        }

        include_once('gateways/paypal-standard-subscriptions/class-paypal-standard-subscriptions.php') ;
        include_once('gateways/wc-stripe/class-wc-stripe-gateway.php') ;

        self::$load_gateways[] = include_once('gateways/paypal-adaptive/class-paypal-adaptive-gateway.php') ;
        self::$load_gateways[] = include_once('gateways/paypal-reference-txns/class-paypal-reference-txns-gateway.php') ;
        self::$load_gateways[] = include_once('gateways/sumo-stripe/class-sumo-stripe-gateway.php') ;
    }

    /**
     * Add payment gateways awaiting to load
     * @param object $gateways
     * @return array
     */
    public static function add_payment_gateways( $gateways ) {
        if ( empty( self::$load_gateways ) ) {
            return $gateways ;
        }

        foreach ( self::$load_gateways as $gateway ) {
            $gateways[] = $gateway ;
        }
        return $gateways ;
    }

    /**
     * Check if Automatic payment gateways enabled
     * @return bool 
     */
    public static function auto_payment_gateways_enabled() {
        return self::$auto_payment_gateways_enabled ;
    }

    /**
     * Get subscription supported automatic payment gateways.
     * 
     * @return array
     */
    public static function get_subscription_payment_gateways() {
        return ( array ) apply_filters( 'sumosubscriptions_available_payment_gateways', self::$subscription_payment_gateways ) ;
    }

    /**
     * Check whether the Cart/Checkout/Checkout pay page has Subscription
     * @return bool
     */
    public static function checkout_has_subscription() {
        if ( is_checkout_pay_page() ) {
            if ( $subscription_order_id = sumosubs_get_subscription_order_from_pay_for_order_page() ) {
                self::$checkout_has_subscription = true ;
            }
        } else if ( is_checkout() ) {
            self::$checkout_has_subscription = sumo_is_cart_contains_subscription_items() || SUMO_Order_Subscription::is_subscribed() ;
        }
        return self::$checkout_has_subscription ;
    }

    /**
     * Check whether the Cart/Checkout page having multiple Subscriptions
     * @return bool
     */
    public static function checkout_has_multiple_subscriptions() {
        if ( is_checkout() && ! SUMO_Order_Subscription::is_subscribed() ) {
            $subscription_items                        = sumo_get_subscription_items_from( WC()->cart ) ;
            self::$checkout_has_multiple_subscriptions = sizeof( $subscription_items ) > 1 ? true : false ;
        }
        return self::$checkout_has_multiple_subscriptions ;
    }

    /**
     * Check whether the Cart/Checkout page has synced Subscription
     * @return bool
     */
    public static function checkout_has_synced_subscription() {
        if ( is_checkout_pay_page() ) {
            if ( $subscription_order_id = sumosubs_get_subscription_order_from_pay_for_order_page() ) {
                self::$checkout_has_synced_subscription = SUMO_Subscription_Synchronization::order_has_synced_subscriptions( $subscription_order_id ) ;
            }
        } else if ( is_checkout() ) {
            self::$checkout_has_synced_subscription = SUMO_Subscription_Synchronization::cart_contains_sync() ;
        }
        return self::$checkout_has_synced_subscription ;
    }

    /**
     * Check whether the Cart/Checkout page has Subscription with signup and without trial
     * @return bool
     */
    public static function checkout_has_signup_subscription_without_trial() {
        if ( is_checkout() && ! SUMO_Order_Subscription::is_subscribed() ) {
            if ( ! empty( WC()->cart->cart_contents ) ) {
                foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
                    if ( empty( $cart_item[ 'data' ] ) ) {
                        continue ;
                    }
                    $product_id        = sumosubs_get_product_id( $cart_item[ 'data' ] ) ;
                    $subscription_plan = sumo_get_subscription_plan( 0, $product_id ) ;

                    if ( '1' === $subscription_plan[ 'signup_status' ] && '1' !== $subscription_plan[ 'trial_status' ] ) {
                        self::$checkout_has_signup_subscription_without_trial = true ;
                        break ;
                    }
                }
            }
        }
        return self::$checkout_has_signup_subscription_without_trial ;
    }

    /**
     * Check whether the cart/order total is zero in checkout
     * @return boolean
     */
    public static function checkout_has_order_total_zero() {
        if ( $subscription_order_id = sumosubs_get_subscription_order_from_pay_for_order_page() ) {
            if ( wc_get_order( $subscription_order_id )->get_total() <= 0 ) {
                return true ;
            }
        } else if ( isset( WC()->cart->total ) && WC()->cart->total <= 0 ) {
            return true ;
        }
        return false ;
    }

    /**
     * Check whether the Customer has chosen Automatic payment mode in checkout
     * @param string $gateway_id
     * @return bool
     */
    public static function customer_has_chosen_auto_payment_mode_in( $gateway_id ) {

        if ( self::$auto_payment_gateways_enabled && self::checkout_has_subscription() ) {
            if (
                    'auto' === self::$force_payment_mode_as ||
                    ( self::$payment_mode_switcher_enabled && isset( $_POST[ "{$gateway_id}_auto_payment_mode_enabled" ] ) && 'yes' === $_POST[ "{$gateway_id}_auto_payment_mode_enabled" ] )
            ) {
                self::$customer_has_chosen_auto_payment_mode = true ;
            }
        }

        return self::$customer_has_chosen_auto_payment_mode ;
    }

    /**
     * Show payment gateways for Subscriptions in cart when Order amount is 0
     * @param bool $bool
     * @param object $cart
     * @return bool
     */
    public static function need_payment_gateways( $bool, $cart ) {
        if ( $cart->total > 0 ) {
            return $bool ;
        }

        if ( self::$show_gateways_when_order_amt_zero && self::checkout_has_subscription() ) {
            return true ;
        }
        return $bool ;
    }

    /**
     * Check whether specific payment gateway is needed
     * @param string $gateway_id
     * @return bool
     */
    public static function need_payment_gateway( $gateway_id ) {
        $need = true ;

        if ( self::$checkout_has_subscription ) {
            if ( ! self::$mixed_payment_gateways_enabled ) {
                if ( self::$auto_payment_gateways_enabled ) {
                    if ( ! in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                        $need = false ;
                    }

                    if ( self::PAYPAL_SUBSCRIPTIONS === $gateway_id ) {
                        if (
                                1 !== sizeof( WC()->cart->cart_contents ) ||
                                ( self::$checkout_has_multiple_subscriptions && self::$hide_paypal_std_gateway ) ||
                                self::$checkout_has_synced_subscription ||
                                self::$checkout_has_signup_subscription_without_trial
                        ) {
                            $need = false ;
                        }
                    }
                } else {
                    if ( in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                        $need = false ;
                    }

                    if ( self::PAYPAL_SUBSCRIPTIONS === $gateway_id ) {
                        if (
                                1 !== sizeof( WC()->cart->cart_contents ) ||
                                self::$checkout_has_synced_subscription ||
                                self::$checkout_has_signup_subscription_without_trial
                        ) {
                            $need = true ;
                        }
                    }
                }
            } else {
                if ( self::$checkout_has_multiple_subscriptions && self::$hide_paypal_std_gateway && self::PAYPAL_SUBSCRIPTIONS === $gateway_id ) {
                    $need = false ;
                }
            }
            if ( self::$show_gateways_when_order_amt_zero && in_array( $gateway_id, self::$gateways_to_hide_when_order_amt_zero ) && self::checkout_has_order_total_zero() ) {
                $need = false ;
            }
        } else {
            if ( self::$hide_auto_payment_gateways_when_non_subscriptions_in_cart && in_array( $gateway_id, self::get_subscription_payment_gateways() ) ) {
                $need = false ;
            }
        }
        return apply_filters( 'sumosubscriptions_need_payment_gateway', $need, $gateway_id ) ;
    }

    /**
     * Check whether WC PayPal Standard Subscription API is enabled in checkout
     * @var bool
     */
    public static function is_paypal_subscription_api_enabled() {
        return self::$paypal_std_subscription_api_enabled ;
    }

    /**
     * Handle payment gateways in checkout
     * @param array $_available_gateways
     * @return array
     */
    public static function set_payment_gateways( $_available_gateways ) {
        if ( is_admin() ) {
            return $_available_gateways ;
        }

        self::checkout_has_subscription() ;
        self::checkout_has_multiple_subscriptions() ;
        self::checkout_has_synced_subscription() ;
        self::checkout_has_signup_subscription_without_trial() ;
        self::load_subscription_payment_gateways() ;

        foreach ( $_available_gateways as $gateway_name => $gateway ) {
            if ( ! isset( $gateway->id ) ) {
                continue ;
            }

            if ( ! self::need_payment_gateway( $gateway->id ) ) {
                unset( $_available_gateways[ $gateway_name ] ) ;
            }
        }
        return $_available_gateways ;
    }

    /**
     * Load available Subscription payment gateways
     * 
     * @return array
     */
    public static function load_subscription_payment_gateways() {
        self::$subscription_payment_gateways = array(
            self::PAYPAL_ADAPTIVE_GATEWAY,
            self::PAYPAL_REFERENCE_TXN_GATEWAY,
            self::STRIPE_GATEWAY,
                ) ;

        if ( self::$checkout_has_subscription && self::$paypal_std_subscription_api_enabled ) {
            self::$subscription_payment_gateways[] = self::PAYPAL_SUBSCRIPTIONS ;
        }
    }

    /**
     * Handle mode of payment in subscription automatic payment gateways 
     * @param string $description
     * @param string $gateway_id
     * @return string
     */
    public static function set_payment_mode_switcher( $description, $gateway_id ) {

        if (
                self::$payment_mode_switcher_enabled &&
                in_array( $gateway_id, apply_filters( 'sumosubscriptions_payment_mode_switcher_payment_gateways', self::get_subscription_payment_gateways(), $gateway_id ) ) &&
                self::checkout_has_subscription()
        ) {
            return $description . self::get_payment_mode_switcher( $gateway_id ) ;
        }
        return $description ;
    }

    /**
     * Get payment mode switcher field for subscription automatic payment gateways 
     * @return string
     */
    public static function get_payment_mode_switcher( $gateway_id ) {
        ob_start() ;
        ?>
        <div class="sumosubs_payment_mode_switcher">
            <br><br>
            <input type="checkbox" id="<?php echo $gateway_id ; ?>_auto_payment_mode_enabled" name="<?php echo $gateway_id ; ?>_auto_payment_mode_enabled" value="yes"/><?php _e( 'Enable Automatic Payments', 'sumosubscriptions' ) ?>
        </div>
        <?php
        return apply_filters( 'sumosubscriptions_get_payment_mode_switcher_in_payment_gateway', ob_get_clean(), $gateway_id ) ;
    }

}

return SUMO_Subscription_PaymentGateways::instance() ;
