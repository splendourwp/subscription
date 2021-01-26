<?php

/**
 * Renewed Order - Automatic Email.
 * 
 * @class SUMOSubscriptions_Automatic_Renewed_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Automatic_Renewed_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_renewed_order_automatic' ;
        $this->name           = 'auto-renewed' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Automatic Renewal Success' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription Renewed Order %s Automatic emails are sent to the customers when the automatic subscription is Successfully.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-renewed-order-automatic.php' ;
        $this->template_plain = 'emails/plain/subscription-renewed-order-automatic.php' ;

        $this->subject  = __( '[{site_title}] - Subscription Renewal Successful' , 'sumosubscriptions' ) ;
        $this->heading  = __( 'Subscription Renewal Success' , 'sumosubscriptions' ) ;
        $this->supports = array( 'mail_to_admin' , 'recipient' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Automatic_Renewed_Order_Email() ;
