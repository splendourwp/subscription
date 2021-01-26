<?php

/**
 * Suspended Order - Automatic Email.
 * 
 * @class SUMOSubscriptions_Automatic_Suspended_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Automatic_Suspended_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_suspended_order_automatic' ;
        $this->name           = 'suspended' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Suspended Order - Automatic' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription Suspended Order %s emails are sent to the customer and the amount for the subscription renewal has not been paid within the suspend period.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-suspended-order-automatic.php' ;
        $this->template_plain = 'emails/plain/subscription-suspended-order-automatic.php' ;

        $this->subject = __( '[{site_title}] - Subscription Suspended' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Suspended' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'pay_link' , 'upcoming_mail_info' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Automatic_Suspended_Order_Email() ;
