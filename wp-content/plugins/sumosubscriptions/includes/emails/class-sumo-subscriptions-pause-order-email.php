<?php

/**
 * Pause Order Email.
 * 
 * @class SUMOSubscriptions_Pause_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Pause_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_pause_order' ;
        $this->name           = 'paused' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Paused' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( __( 'Subscription paused emails are sent to the subscribers when a subscription is paused by site administrator/customer (if they are allowed to do so).' , 'sumosubscriptions' ) ) ;

        $this->template_html  = 'emails/subscription-pause-order.php' ;
        $this->template_plain = 'emails/plain/subscription-pause-order.php' ;

        $this->subject = __( '[{site_title}] - Subscription Paused' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Paused' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Pause_Order_Email() ;
