<?php

/**
 * Expiry Reminder Email.
 * 
 * @class SUMOSubscriptions_Expiry_Reminder_Email
 * @category Class
 */
class SUMOSubscriptions_Expiry_Reminder_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_expiry_reminder' ;
        $this->name           = 'expiry_remider' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Expiry Reminder', 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription Expiry Reminder emails are sent to the customers before their subscription is going to expire.', 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-expiry-reminder.php' ;
        $this->template_plain = 'emails/plain/subscription-expiry-reminder.php' ;

        $this->subject = __( '[{site_title}] - Subscription Expiry Reminder', 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Expiry Reminder', 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Expiry_Reminder_Email() ;

