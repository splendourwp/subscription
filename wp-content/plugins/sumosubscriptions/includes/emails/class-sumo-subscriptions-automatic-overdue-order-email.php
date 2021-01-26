<?php

/**
 * Overdue Order - Automatic Email.
 * 
 * @class SUMOSubscriptions_Automatic_Overdue_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Automatic_Overdue_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_overdue_order_automatic' ;
        $this->name           = 'overdue' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Payment Overdue - Automatic' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription Overdue Order %s emails are sent to the customer when the amount for the subscription renewal has not been paid within the overdue period.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-overdue-order-automatic.php' ;
        $this->template_plain = 'emails/plain/subscription-overdue-order-automatic.php' ;

        $this->subject = __( '[{site_title}] - Subscription Payment Overdue' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Payment Overdue' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'pay_link' , 'upcoming_mail_info' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Automatic_Overdue_Order_Email() ;
