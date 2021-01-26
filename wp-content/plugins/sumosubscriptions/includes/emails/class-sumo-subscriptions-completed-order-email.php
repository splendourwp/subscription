<?php

/**
 * Completed Order Email.
 * 
 * @class SUMOSubscriptions_Completed_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Completed_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_completed_order' ;
        $this->name           = 'completed' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Completed Order' , 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription new order emails are sent to the customers when a subscription new order has been completed.' , 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-completed-order.php' ;
        $this->template_plain = 'emails/plain/subscription-completed-order.php' ;

        $this->subject = __( 'Your {site_title} order from {order_date} is Complete' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Your Subscription order is Complete' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Completed_Order_Email() ;
