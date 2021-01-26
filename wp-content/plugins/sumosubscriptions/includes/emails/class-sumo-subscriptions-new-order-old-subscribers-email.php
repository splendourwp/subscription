<?php

/**
 * New Order - Old Subscriber Email.
 * 
 * @class SUMOSubscriptions_New_Order_Old_Subscribers_Email
 * @category Class
 */
class SUMOSubscriptions_New_Order_Old_Subscribers_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_new_order_old_subscribers' ;
        $this->name           = 'new-order' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription New Order - Old Subscribers' , 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription new order emails are sent to the customers when a subscription new order has been generated.' , 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-new-order-old-subscribers.php' ;
        $this->template_plain = 'emails/plain/subscription-new-order-old-subscribers.php' ;

        $this->subject = __( '[{site_title}] - New Subscription Order (#{order_number}) - {order_date}' , 'sumosubscriptions' ) ;
        $this->heading = __( 'New Subscription Order' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_New_Order_Old_Subscribers_Email() ;
