<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Emails class.
 */
class SUMOSubscriptions_Emails {

    /**
     * Email notification classes
     *
     * @var WC_Email[]
     */
    protected $emails = array() ;

    /**
     * Available email notification classes to load
     * 
     * @var WC_Email::id => WC_Email class
     */
    protected $email_classes = array(
        'subscription_new_order'                   => 'SUMOSubscriptions_New_Order_Email',
        'subscription_new_order_old_subscribers'   => 'SUMOSubscriptions_New_Order_Old_Subscribers_Email',
        'subscription_processing_order'            => 'SUMOSubscriptions_Processing_Order_Email',
        'subscription_completed_order'             => 'SUMOSubscriptions_Completed_Order_Email',
        'subscription_pause_order'                 => 'SUMOSubscriptions_Pause_Order_Email',
        'subscription_invoice_order_manual'        => 'SUMOSubscriptions_Manual_Invoice_Order_Email',
        'subscription_expiry_reminder'             => 'SUMOSubscriptions_Expiry_Reminder_Email',
        'subscription_automatic_charging_reminder' => 'SUMOSubscriptions_Automatic_Charging_Reminder_Email',
        'subscription_renewed_order_automatic'     => 'SUMOSubscriptions_Automatic_Renewed_Order_Email',
        'auto_to_manual_subscription_renewal'      => 'SUMOSubscriptions_Auto_to_Manual_Subscription_Renewal_Email',
        'subscription_overdue_order_automatic'     => 'SUMOSubscriptions_Automatic_Overdue_Order_Email',
        'subscription_overdue_order_manual'        => 'SUMOSubscriptions_Manual_Overdue_Order_Email',
        'subscription_suspended_order_automatic'   => 'SUMOSubscriptions_Automatic_Suspended_Order_Email',
        'subscription_suspended_order_manual'      => 'SUMOSubscriptions_Manual_Suspended_Order_Email',
        'subscription_preapproval_access_revoked'  => 'SUMOSubscriptions_Preapproval_Access_Revoked_Email',
        'turnoff_automatic_payments_success'       => 'SUMOSubscriptions_Turnoff_Auto_Payments_Success_Email',
        'subscription_pending_authorization'       => 'SUMOSubscriptions_Pending_Authorization_Email',
        'subscription_cancelled_order'             => 'SUMOSubscriptions_Cancelled_Order_Email',
        'subscription_cancel_request_submitted'    => 'SUMOSubscriptions_Cancel_Request_Submitted_Email',
        'subscription_cancel_request_revoked'      => 'SUMOSubscriptions_Cancel_Request_Revoked_Email',
        'subscription_expired_order'               => 'SUMOSubscriptions_Expired_Order_Email'
            ) ;

    /**
     * The single instance of the class
     *
     * @var SUMOSubscriptions_Emails
     */
    protected static $_instance = null ;

    /**
     * Main SUMOSubscriptions_Emails Instance.
     * Ensures only one instance of SUMOSubscriptions_Emails is loaded or can be loaded.
     * 
     * @return SUMOSubscriptions_Emails Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self() ;
        }
        return self::$_instance ;
    }

    /**
     * Init the email class hooks in all emails that can be sent.
     */
    public function init() {
        add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) ) ;
        add_filter( 'woocommerce_template_directory', array( $this, 'set_template_directory' ), 10, 2 ) ;
        add_filter( 'woocommerce_template_path', array( $this, 'set_template_path' ) ) ;
        add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'wc_email_handler' ), 99, 2 ) ;
        add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'wc_email_handler' ), 99, 2 ) ;
        add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'wc_email_handler' ), 99, 2 ) ;
    }

    /**
     * Load our email classes.
     */
    public function add_email_classes( $emails ) {
        if ( ! empty( $this->emails ) ) {
            return $emails + $this->emails ;
        }

        // Include email classes.
        include_once 'abstracts/abstract-sumo-subscriptions-email.php' ;

        foreach ( $this->email_classes as $id => $class ) {
            $file_name = 'class-' . strtolower( str_replace( '_', '-', str_replace( 'SUMOSubscriptions', 'sumo-subscriptions', $class ) ) ) ;
            $path      = SUMO_SUBSCRIPTIONS_PLUGIN_DIR . "includes/emails/{$file_name}.php" ;

            if ( is_readable( $path ) ) {
                $this->emails[ $class ] = include( $path ) ;
            }
        }

        return $emails + $this->emails ;
    }

    /**
     * Check if we need to send WC emails
     */
    public function wc_email_handler( $bool, $order ) {
        if ( ! $order = wc_get_order( $order ) ) {
            return $bool ;
        }

        $disabled_wc_order_emails = get_option( 'sumosubs_disabled_wc_order_emails', array() ) ;

        if ( ! empty( $disabled_wc_order_emails ) && sumo_is_order_contains_subscriptions( sumosubs_get_order_id( $order ) ) ) {
            if ( 'woocommerce_email_enabled_new_order' === current_filter() ) {
                if ( in_array( 'new', $disabled_wc_order_emails ) ) {
                    return false ;
                }
            } else if ( $order->has_status( $disabled_wc_order_emails ) ) {
                return false ;
            }
        }

        return $bool ;
    }

    /**
     * Set our email templates directory.
     * 
     * @return string
     */
    public function set_template_directory( $template_directory, $template ) {
        $templates = array_map( array( $this, 'get_template_name' ), array_keys( $this->email_classes ) ) ;

        foreach ( $templates as $name ) {
            if ( in_array( $template, array(
                        "emails/{$name}.php",
                        "emails/plain/{$name}.php",
                    ) )
            ) {
                return untrailingslashit( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR ) ;
            }
        }
        return $template_directory ;
    }

    /**
     * Set our template path.
     *
     * @return string
     */
    public function set_template_path( $path ) {
        if ( isset( $_POST[ 'template_html_code' ] ) || isset( $_POST[ 'template_plain_code' ] ) ) {
            if ( isset( $_GET[ 'section' ] ) && in_array( $_GET[ 'section' ], array_map( 'strtolower', array_values( $this->email_classes ) ) ) ) {
                $path = SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR ;
            }
        }

        return $path ;
    }

    /**
     * Get the template name from email ID
     */
    public function get_template_name( $id ) {
        return str_replace( '_', '-', $id ) ;
    }

    /**
     * Load WC Mailer.
     */
    public function load_mailer() {
        WC()->mailer() ;
    }

    /**
     * Are emails available
     *
     * @return WC_Email class
     */
    public function available() {
        $this->load_mailer() ;

        return ! empty( $this->emails ) ? true : false ;
    }

    /**
     * Return the email class
     *
     * @return WC_Email class
     */
    public function get_email_class( $id ) {
        $id = strtolower( $id ) ;

        return isset( $this->email_classes[ $id ] ) ? $this->email_classes[ $id ] : null ;
    }

    /**
     * Return the emails
     *
     * @return WC_Email[]
     */
    public function get_emails() {
        $this->load_mailer() ;

        return $this->emails ;
    }

    /**
     * Return the email
     *
     * @return WC_Email
     */
    public function get_email( $id ) {
        $this->load_mailer() ;
        $class = $this->get_email_class( $id ) ;

        return isset( $this->emails[ $class ] ) ? $this->emails[ $class ] : null ;
    }

}

SUMOSubscriptions_Emails::instance()->init() ;
