<?php

/**
 * Processing Order Email.
 * 
 * @class SUMOSubscriptions_Processing_Order_Email
 * @category Class
 */
class SUMOSubscriptions_Processing_Order_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_processing_order' ;
        $this->name           = 'processing' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Processing Order' , 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription processing order emails are sent to the customers when a subscription new order becomes processing.' , 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-processing-order.php' ;
        $this->template_plain = 'emails/plain/subscription-processing-order.php' ;

        $this->subject = __( 'Your {site_title} Subscription order receipt from {order_date}' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Thank you for your Order' , 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Processing_Order_Email() ;
