<?php

/**
 * Abstract Subscription Email
 * 
 * @abstract SUMO_Abstract_Subscription_Email
 */
abstract class SUMO_Abstract_Subscription_Email extends WC_Email {

    /**
     * @var array Supports
     */
    public $supports = array( 'mail_to_admin' ) ;

    /**
     * @var int Subscription post ID 
     */
    public $subscription_id = 0 ;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->template_base = SUMO_SUBSCRIPTIONS_TEMPLATE_PATH ;

        // Call WC_Email constuctor
        parent::__construct() ;
    }

    /**
     * Populate the Email
     * 
     * @param int $order_id
     * @param int $subscription_id
     * @param string $to
     */
    public function populate( $order_id, $subscription_id, $to ) {
        $this->subscription_id = absint( $subscription_id ) ;
        $this->order_id        = absint( $order_id ) ;
        $this->object          = wc_get_order( $this->order_id ) ;

        if ( ! empty( $to ) ) {
            $this->recipient = $to ;
        } else if ( $this->object ) {
            $this->recipient = $this->object->get_billing_email() ;
        } else {
            $this->recipient = null ;
        }
    }

    /**
     * Get valid recipients.
     *
     * @return string
     */
    public function get_recipient() {
        $recipient = '' ;
        if ( $this->supports( 'recipient' ) ) {
            $recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) ) ;
        } else if ( $this->supports( 'mail_to_admin' ) && 'yes' === $this->get_option( 'mail_to_admin' ) ) {
            $recipient = get_option( 'admin_email' ) ;
        }

        if ( is_null( $this->recipient ) || '' === $this->recipient ) {
            $this->recipient = $recipient ;
        } else if ( '' !== $recipient ) {
            $this->recipient .= ', ' ;
            $this->recipient .= $recipient ;
        }

        return parent::get_recipient() ;
    }

    /**
     * Check this Email supported feature.
     * @param string $type
     * 
     * @return boolean
     */
    public function supports( $type = '' ) {
        return in_array( $type, $this->supports ) ;
    }

    /**
     * Trigger.
     * 
     * @return bool on Success
     */
    public function trigger( $order_id, $subscription_id, $to ) {
        if ( ! $this->is_enabled() ) {
            return false ;
        }

        $this->populate( $order_id, $subscription_id, $to ) ;

        $this->find[ 'order-number' ] = '{order_number}' ;
        $this->find[ 'order-date' ]   = '{order_date}' ;

        $this->replace[ 'order-number' ] = $this->order_id ;
        $this->replace[ 'order-date' ]   = $this->format_date( sumosubs_get_order_date( $this->object ) ) ;

        $recipient = $this->get_recipient() ;
        $sent      = false ;

        if ( $recipient ) {
            $sent = $this->send( $recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ) ;

            if ( $sent ) {
                do_action( 'sumosubs_email_sent', $this ) ;
            } else {
                do_action( 'sumosubs_email_failed_to_sent', $this ) ;
            }
        }

        return $sent ;
    }

    /**
     * Format subscription date to display.
     * @param int|string $date
     * @return string
     */
    public function format_date( $date = '' ) {
        return sumo_display_subscription_date( $date ) ;
    }

    /**
     * Retrieve Email Template from the Template path or Plugin default path
     * 
     * @param string $template
     * @param boolean $plain_text
     * @return string
     */
    public function _get_template( $template, $plain_text = false ) {
        $supports = array() ;

        if ( $this->supports( 'upcoming_mail_info' ) ) {
            $upcoming_mail = sumo_get_upcoming_mail_scheduler_after_due_exceeds( $this->subscription_id ) ;
            $supports      = array(
                'upcoming_mail_date'   => $this->format_date( $upcoming_mail[ 'upcoming_mail_date' ] ),
                'upcoming_mail_status' => $upcoming_mail[ 'upcoming_mail_status' ],
                    ) ;
        }

        if ( $this->supports( 'pay_link' ) ) {
            $supports = array_merge( array(
                'payment_link' => $this->get_option( 'enable_pay_link' )
                    ), $supports ) ;
        }

        if ( $this->supports( 'cancel_method_requested' ) ) {
            $requested_cancel_method = get_post_meta( $this->subscription_id, 'sumo_subscription_requested_cancel_method', true ) ;
            $supports                = array_merge( array(
                'requested_cancel_method' => ucwords( str_replace( '_', ' ', $requested_cancel_method ) ),
                'cancel_scheduled_on'     => $this->format_date( 'end_of_billing_cycle' === $requested_cancel_method ? get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ) : get_post_meta( $this->subscription_id, 'sumo_subscription_cancellation_scheduled_on', true ) )
                    ), $supports ) ;
        }

        if ( $this->supports( 'payment_charging_date' ) ) {
            $supports = array_merge( array(
                'payment_charging_date' => $this->format_date( get_post_meta( $this->subscription_id, 'sumo_get_next_payment_date', true ) )
                    ), $supports ) ;
        }

        ob_start() ;

        sumosubscriptions_get_template( $template, array_merge( array(
            'order'          => $this->object,
            'post_id'        => $this->subscription_id,
            'email_heading'  => $this->get_heading(),
            'sent_to_admin'  => true,
            'plain_text'     => $plain_text,
            'admin_template' => 'subscription_new_order_old_subscribers' === $this->id,
            'email'          => $this,
                        ), $supports ) ) ;

        return ob_get_clean() ;
    }

    /**
     * Get content HTMl.
     *
     * @return string
     */
    public function get_content_html() {
        return $this->_get_template( $this->template_html ) ;
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return $this->_get_template( $this->template_plain, true ) ;
    }

    /**
     * Display form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'sumosubscriptions' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'sumosubscriptions' ),
                'default' => 'yes'
            ) ) ;

        if ( $this->supports( 'recipient' ) ) {
            $this->form_fields = array_merge( $this->form_fields, array(
                'recipient' => array(
                    'title'       => __( 'Recipient(s)', 'sumosubscriptions' ),
                    'type'        => 'text',
                    'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'sumosubscriptions' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                    'placeholder' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ) ) ) ;
        }

        $this->form_fields = array_merge( $this->form_fields, array(
            'subject' => array(
                'title'       => __( 'Email Subject', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Defaults to <code>%s</code>', 'sumosubscriptions' ), $this->subject ),
                'placeholder' => '',
                'default'     => ''
            ),
            'heading' => array(
                'title'       => __( 'Email Heading', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Defaults to <code>%s</code>', 'sumosubscriptions' ), $this->heading ),
                'placeholder' => '',
                'default'     => ''
            ) ) ) ;

        if ( $this->supports( 'paid_order' ) ) {
            $this->form_fields = array_merge( $this->form_fields, array(
                'subject_paid' => array(
                    'title'       => __( 'Email Subject (paid)', 'sumosubscriptions' ),
                    'type'        => 'text',
                    'description' => sprintf( __( 'Defaults to <code>%s</code>', 'sumosubscriptions' ), $this->subject_paid ),
                    'placeholder' => '',
                    'default'     => ''
                ),
                'heading_paid' => array(
                    'title'       => __( 'Email Heading (paid)', 'sumosubscriptions' ),
                    'type'        => 'text',
                    'description' => sprintf( __( 'Defaults to <code>%s</code>', 'sumosubscriptions' ), $this->heading_paid ),
                    'placeholder' => '',
                    'default'     => ''
                ) ) ) ;
        }

        if ( $this->supports( 'pay_link' ) ) {
            $this->form_fields = array_merge( $this->form_fields, array(
                'enable_pay_link' => array(
                    'title'   => __( 'Enable Payment Link in Mail', 'sumosubscriptions' ),
                    'type'    => 'checkbox',
                    'default' => 'yes'
                ) ) ) ;
        }

        if ( $this->supports( 'mail_to_admin' ) ) {
            $this->form_fields = array_merge( $this->form_fields, array(
                'mail_to_admin' => array(
                    'title'   => __( 'Send Email to Admin', 'sumosubscriptions' ),
                    'type'    => 'checkbox',
                    'default' => 'no'
                ) ) ) ;
        }

        $this->form_fields = array_merge( $this->form_fields, array(
            'email_type' => array(
                'title'       => __( 'Email type', 'sumosubscriptions' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'sumosubscriptions' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options()
            ) ) ) ;
    }

}
