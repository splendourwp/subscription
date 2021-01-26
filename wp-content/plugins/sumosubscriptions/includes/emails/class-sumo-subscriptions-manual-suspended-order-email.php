<?php

/**
 * Suspended Order - Manual Email.
 * 
 * @class SUMOSubscriptions_Manual_Suspended_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Manual_Suspended_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_suspended_order_manual' ;
        $this->name           = 'suspended' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Suspended - Manual' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription suspended order %s emails are sent to the customer when the amount for the subscription renewal has not been paid within the suspend period.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-suspended-order-manual.php' ;
        $this->template_plain = 'emails/plain/subscription-suspended-order-manual.php' ;

        $this->subject = __( '[{site_title}] - Subscription Suspended' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Suspended' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'upcoming_mail_info' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Manual_Suspended_Order_Email() ;
