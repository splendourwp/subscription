<?php

/**
 * Subscription Automatic Renewal Reminder.
 * 
 * @class SUMOSubscriptions_Automatic_Charging_Reminder_Email
 * @category Class
 */
class SUMOSubscriptions_Automatic_Charging_Reminder_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_automatic_charging_reminder' ;
        $this->name           = 'automatic-charging-reminder' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Automatic Renewal Reminder' , 'sumosubscriptions' ) ;
        $this->description    = __( 'Subscription Automatic Renewal Reminder emails are sent to the customers before charging for the subcription renewal using the preapproved payment gateway.' , 'sumosubscriptions' ) ;

        $this->template_html  = 'emails/subscription-automatic-charging-reminder.php' ;
        $this->template_plain = 'emails/plain/subscription-automatic-charging-reminder.php' ;

        $this->subject = __( '[{site_title}] - Subscription Automatic Charging Reminder' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Automatic Charging Reminder' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'payment_charging_date' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Automatic_Charging_Reminder_Email() ;
