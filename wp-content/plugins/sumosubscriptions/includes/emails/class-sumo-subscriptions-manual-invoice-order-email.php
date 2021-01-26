<?php

/**
 * Invoice Order - Manual Email.
 * 
 * @class SUMOSubscriptions_Manual_Invoice_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Manual_Invoice_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_invoice_order_manual' ;
        $this->name           = 'invoice' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Invoice - Manual' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription invoice order %s Manual emails are sent to the customers when payment has to be made for subscription renewal.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-invoice-order-manual.php' ;
        $this->template_plain = 'emails/plain/subscription-invoice-order-manual.php' ;

        $this->subject = __( '[{site_title}] - Invoice for Subscription Renewal' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Invoice for Subscription Renewal' , 'sumosubscriptions' ) ;

        $this->subject_paid = $this->subject ;
        $this->heading_paid = $this->heading ;

        $this->supports = array ( 'mail_to_admin', 'paid_order' , 'upcoming_mail_info' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Manual_Invoice_Order_Email() ;
