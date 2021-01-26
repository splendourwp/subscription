<?php

/**
 * Expired Order Email.
 * 
 * @class SUMOSubscriptions_Expired_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Expired_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_expired_order' ;
        $this->name           = 'expired' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Expired' , 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription emails are sent to the customers when a subscription is expired.' , 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-expired-order.php' ;
        $this->template_plain = 'emails/plain/subscription-expired-order.php' ;

        $this->subject = __( '[{site_title}] - Subscription Expired' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Expired' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Expired_Order_Email() ;

