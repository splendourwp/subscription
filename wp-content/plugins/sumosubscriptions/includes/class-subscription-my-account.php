<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle subscriptions in My Account page.
 * 
 * @class SUMOSubscriptions_My_Account
 * @category Class
 */
class SUMOSubscriptions_My_Account {

    public static $template_base = SUMO_SUBSCRIPTIONS_TEMPLATE_PATH ;

    /**
     * Init SUMOSubscriptions_My_Account.
     */
    public static function init() {

        //Compatible with Woocommerce v2.6.x and above
        add_filter( 'woocommerce_account_menu_items', __CLASS__ . '::set_my_account_menu_items' ) ;
        add_action( 'woocommerce_account_sumo-subscriptions_endpoint', __CLASS__ . '::my_subscriptions' ) ;
        add_action( 'woocommerce_account_view-subscription_endpoint', __CLASS__ . '::view_subscription' ) ;
        add_action( 'sumosubscriptions_my_subscriptions_view-subscription_endpoint', __CLASS__ . '::view_subscription' ) ;
        add_shortcode( 'sumo_my_subscriptions', __CLASS__ . '::my_subscriptions', 10, 3 ) ;

        //Compatible up to Woocommerce v2.5.x
        add_action( 'woocommerce_before_my_account', __CLASS__ . '::bkd_cmptble_my_subscriptions' ) ;
        add_filter( 'wc_get_template', __CLASS__ . '::bkd_cmptble_view_subscription', 10, 5 ) ;

        add_filter( 'user_has_cap', __CLASS__ . '::customer_has_capability', 10, 3 ) ;

        add_filter( 'sumosubscriptions_my_subscription_table_pause_action', __CLASS__ . '::remove_pause_action', 10, 3 ) ;
        add_filter( 'sumosubscriptions_my_subscription_table_cancel_action', __CLASS__ . '::remove_cancel_action', 10, 3 ) ;

        //May be do some restrictions in Pay for Order page
        if ( isset( $_GET[ 'pay_for_order' ] ) ) {
            add_filter( 'sumosubscriptions_need_payment_gateway', __CLASS__ . '::need_payment_gateway', 19, 2 ) ;
            add_filter( 'woocommerce_no_available_payment_methods_message', __CLASS__ . '::wc_gateway_notice' ) ;
            add_filter( 'woocommerce_pay_order_button_html', __CLASS__ . '::remove_place_order_button' ) ;
            add_action( 'before_woocommerce_pay', __CLASS__ . '::wc_checkout_notice' ) ;
        }
    }

    /**
     * Checks if a user has a certain capability.
     *
     * @param array $allcaps All capabilities.
     * @param array $caps    Capabilities.
     * @param array $args    Arguments.
     *
     * @return array The filtered array of all capabilities.
     */
    public static function customer_has_capability( $allcaps, $caps, $args ) {
        if ( isset( $caps[ 0 ] ) ) {
            switch ( $caps[ 0 ] ) {
                case 'view-subscription':
                    $user_id         = absint( $args[ 1 ] ) ;
                    $subscription_id = absint( $args[ 2 ] ) ;

                    if ( sumo_is_subscription_exists( $subscription_id ) && $user_id === absint( get_post_meta( $subscription_id, 'sumo_get_user_id', true ) ) ) {
                        $allcaps[ 'view-subscription' ] = true ;
                    }
                    break ;
            }
        }
        return $allcaps ;
    }

    /**
     * Get my Subscriptions.
     */
    public static function get_subscriptions() {
        $subscriptions = sumosubscriptions()->query->get( array(
            'type'       => 'sumosubscriptions',
            'status'     => 'publish',
            'meta_key'   => 'sumo_get_user_id',
            'meta_value' => get_current_user_id(),
                ) ) ;

        sumosubscriptions_get_template( 'subscriptions.php', array(
            'subscriptions' => $subscriptions,
        ) ) ;
    }

    /**
     * Set our menus under My account menu items
     * @param array $items
     * @return array
     */
    public static function set_my_account_menu_items( $items ) {
        $endpoint = sumosubscriptions()->query->get_query_var( 'sumo-subscriptions' ) ;

        $menu     = array(
            $endpoint => apply_filters( 'sumosubscriptions_my_subscriptions_table_title', __( 'My Subscriptions', 'sumosubscriptions' ) ),
                ) ;
        $position = 2 ;

        $items = array_slice( $items, 0, $position ) + $menu + array_slice( $items, $position, count( $items ) - 1 ) ;

        return $items ;
    }

    /**
     * Output my Subscriptions table.
     */
    public static function my_subscriptions( $atts = '', $content = '', $tag = '' ) {
        if ( is_admin() ) {
            return ;
        }

        global $wp ;
        if ( 'sumo_my_subscriptions' === $tag ) {
            if ( ! empty( $wp->query_vars ) ) {
                foreach ( $wp->query_vars as $key => $value ) {
                    // Ignore pagename param.
                    if ( 'pagename' === $key ) {
                        continue ;
                    }

                    if ( has_action( 'sumosubscriptions_my_subscriptions_' . $key . '_endpoint' ) ) {
                        do_action( 'sumosubscriptions_my_subscriptions_' . $key . '_endpoint', $value ) ;
                        return ;
                    }
                }
            }
        }

        echo self::get_subscriptions() ;
    }

    /**
     * Output Subscription content.
     * @param int $subscription_id
     */
    public static function view_subscription( $subscription_id ) {
        if ( ! current_user_can( 'view-subscription', $subscription_id ) ) {
            echo '<div class="woocommerce-error">' . esc_html__( 'Invalid subscription.', 'sumosubscriptions' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'sumosubscriptions' ) . '</a></div>' ;
            return ;
        }

        sumosubscriptions_get_template( 'view-subscription.php', array(
            'subscription_id' => absint( $subscription_id ),
        ) ) ;
    }

    /**
     * Output my Subscriptions table up to Woocommerce v2.5.x
     */
    public static function bkd_cmptble_my_subscriptions() {

        if ( sumosubs_is_wc_version( '<', '2.6' ) ) {
            echo '<h2>' . apply_filters( 'sumosubscriptions_my_subscriptions_table_title', __( 'My Subscriptions', 'sumosubscriptions' ) ) . '</h2>' ;
            echo self::get_subscriptions() ;
        }
    }

    /**
     * Output Subscription content up to Woocommerce v2.5.x
     * @global object $wp
     * @param string $located
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public static function bkd_cmptble_view_subscription( $located, $template_name, $args, $template_path, $default_path ) {
        global $wp ;

        if ( sumosubs_is_wc_version( '<', '2.6' ) && isset( $_GET[ 'subscription-id' ] ) ) {

            if ( $subscription_id = is_numeric( $_GET[ 'subscription-id' ] ) && $_GET[ 'subscription-id' ] ? $_GET[ 'subscription-id' ] : 0 ) {
                $wp->query_vars[ 'view-subscription' ] = $subscription_id ;

                return self::$template_base . 'view-subscription.php' ;
            }
        }
        return $located ;
    }

    /**
     * Hide Pause action from my Subscriptions table
     * @param bool $action
     * @param int $subscription_id
     * @param int $parent_order_id
     * @return bool
     */
    public static function remove_pause_action( $action, $subscription_id, $parent_order_id ) {
        if ( 'Pending_Cancellation' === get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
            return false ;
        }

        return $action ;
    }

    /**
     * Minimum waiting time for the User to get previlege to Cancel their Subscription.
     * Show Cancel button only when the User has got the previlege
     * 
     * @param bool $action
     * @param int $subscription_id
     * @param int $parent_order_id
     * @return bool
     */
    public static function remove_cancel_action( $action, $subscription_id, $parent_order_id ) {
        $order_date                   = sumosubs_get_order_date( $parent_order_id, true ) ;
        $min_days_user_wait_to_cancel = absint( get_option( 'sumo_min_days_user_wait_to_cancel_their_subscription' ) ) ;

        if ( 0 === $min_days_user_wait_to_cancel ) {
            return $action ;
        }

        if ( $min_days_user_wait_to_cancel > 0 && '' !== $order_date ) {
            $order_time                   = sumo_get_subscription_timestamp( $order_date ) ;
            $min_time_user_wait_to_cancel = $order_time + ($min_days_user_wait_to_cancel * 86400 ) ;

            if ( sumo_get_subscription_timestamp() >= $min_time_user_wait_to_cancel ) {
                return $action ;
            }
        }

        return false ;
    }

    /**
     * Prevent the User placing Automatic Subscription renewal order from Pay for Order page.
     * To do this, remove the Place Order button when Subscription status is in Active or Trial
     * 
     * @param html $button
     * @return html
     */
    public static function remove_place_order_button( $button ) {

        if ( ! $renewal_order_id = sumosubs_get_subscription_renewal_order_in_pay_for_order() ) {
            return $button ;
        }

        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $renewal_order || $renewal_order->has_status( 'failed' ) ) {
            return $button ;
        }

        $subscription_id = sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id ) ;

        if ( 'auto' === sumo_get_payment_type( $subscription_id ) && in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), array( 'Trial', 'Active', 'Pending_Cancellation' ) ) ) {
            $button = '' ;
        }
        return $button ;
    }

    /**
     * Prevent the User placing Automatic Subscription renewal order from Pay for Order page.
     * To do this, display customer notice when Subscription status is in Active or Trial
     * 
     * @param string $gateway_notice
     * @return string
     */
    public static function wc_gateway_notice( $gateway_notice ) {

        if ( ! $renewal_order_id = sumosubs_get_subscription_renewal_order_in_pay_for_order() ) {
            return $gateway_notice ;
        }

        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $renewal_order || $renewal_order->has_status( 'failed' ) ) {
            return $gateway_notice ;
        }

        $subscription_id     = sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id ) ;
        $next_due_date       = sumo_display_subscription_date( get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true ) ) ;
        $display_err_message = 'yes' === get_option( 'sumo_show_hide_err_msg_pay_order_page' ) ;

        if ( $display_err_message && 'auto' === sumo_get_payment_type( $subscription_id ) && in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), array( 'Trial', 'Active', 'Pending_Cancellation' ) ) ) {
            $gateway_notice = str_replace( '#[subscription_number]', '<a href="' . sumo_get_subscription_endpoint_url( $subscription_id ) . '">#' . sumo_get_subscription_number( $subscription_id ) . '</a>', str_replace( '[next_payment_date]', '<b>' . $next_due_date . '</b>', get_option( 'sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ) ) ) ;
        }
        return $gateway_notice ;
    }

    /**
     * Prevent the User placing Automatic Subscription renewal order from Pay for Order page.
     * To do this, hide the payment gateways when Subscription status is in Active or Trial
     * 
     * @param bool $need
     * @param string $gateway_id
     * @return bool
     */
    public static function need_payment_gateway( $need, $gateway_id ) {

        if ( ! $renewal_order_id = sumosubs_get_subscription_renewal_order_in_pay_for_order() ) {
            return $need ;
        }

        $renewal_order = wc_get_order( $renewal_order_id ) ;

        if ( ! $renewal_order || $renewal_order->has_status( 'failed' ) ) {
            return $need ;
        }

        $subscription_id = sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id ) ;

        if ( 'auto' === sumo_get_payment_type( $subscription_id ) && in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), array( 'Trial', 'Active', 'Pending_Cancellation' ) ) ) {
            $need = false ;
        }
        return $need ;
    }

    /**
     * Prevent the User placing Paused/Cancelled Subscription renewal order from Pay for Order page.
     */
    public static function wc_checkout_notice() {

        if ( ! $renewal_order_id = sumosubs_get_subscription_renewal_order_in_pay_for_order() ) {
            return ;
        }
        $subscription_id = sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id ) ;

        switch ( get_post_meta( $subscription_id, 'sumo_get_status', true ) ) {
            case 'Pause':
                if ( 'yes' === get_option( 'sumo_show_hide_err_msg_pay_order_page' ) ) {
                    wc_add_notice( get_option( 'sumo_err_msg_for_paused_in_pay_for_order_page' ), 'error' ) ;
                }
                ?><style>#order_review {display: none;}</style><?php
                break ;
            case 'Pending_Cancellation':
                if ( 'yes' === get_option( 'sumo_show_hide_err_msg_pay_order_page' ) ) {
                    wc_add_notice( get_option( 'sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ), 'error' ) ;
                }
                ?><style>#order_review {display: none;}</style><?php
        }
    }

}

SUMOSubscriptions_My_Account::init() ;
