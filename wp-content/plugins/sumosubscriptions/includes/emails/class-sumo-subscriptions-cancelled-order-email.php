<?php

/**
 * Cancelled Order Email.
 * 
 * @class SUMOSubscriptions_Cancelled_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Cancelled_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_cancel_order' ;
        $this->name           = 'cancelled' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Cancel - Success' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription cancel %s Success emails are sent to the customers when the subscription is cancelled completely.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-cancelled-order.php' ;
        $this->template_plain = 'emails/plain/subscription-cancelled-order.php' ;

        $this->subject = __( '[{site_title}] - Subscription Cancelled' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Cancelled' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Cancelled_Order_Email() ;
