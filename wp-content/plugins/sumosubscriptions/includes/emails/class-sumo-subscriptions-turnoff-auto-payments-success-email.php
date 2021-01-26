<?php

/**
 * Subscription Turn off Automatic Payments - Success Email.
 * 
 * @class SUMOSubscriptions_Turnoff_Auto_Payments_Success_Email
 * @category Class
 */
class SUMOSubscriptions_Turnoff_Auto_Payments_Success_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_turnoff_automatic_payments_success' ;
        $this->name           = 'turnoff-automatic-payments-success' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Turn off Automatic Payments - Success' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription Turnoff Automatic Payments %s Success emails are sent to the customers when they disable automatic charging for subscription renewals.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/turnoff-automatic-payments-success.php' ;
        $this->template_plain = 'emails/plain/turnoff-automatic-payments-success.php' ;

        $this->subject = __( '[{site_title}] - Subscription Automatic Charging Turned off' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Automatic Charging Turned off' , 'sumosubscriptions' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Turnoff_Auto_Payments_Success_Email() ;
