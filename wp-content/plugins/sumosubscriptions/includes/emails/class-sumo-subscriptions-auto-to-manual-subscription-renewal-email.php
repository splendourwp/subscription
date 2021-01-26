<?php

/**
 * Automatic Subscription Renewal - Manual Subscription Renewal Email.
 * 
 * @class SUMOSubscriptions_Auto_to_Manual_Subscription_Renewal_Email
 * @category Class
 */
class SUMOSubscriptions_Auto_to_Manual_Subscription_Renewal_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'auto_to_manual_subscription_renewal' ;
        $this->name           = 'automatic-to-manual-renewal' ;
        $this->customer_email = true ;
        $this->title          = __( 'Automatic Subscription Renewal to Manual Subscription Renewal' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Automatic Subscription Renewal to Manual Subscription Renewal %s This email will be sent when the Subscription System is unable to charge for Subscription renewal within Suspend status and Subscription is changed to Manual payment mode.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/auto-to-manual-subscription-renewal.php' ;
        $this->template_plain = 'emails/plain/auto-to-manual-subscription-renewal.php' ;

        $this->subject = __( '[{site_title}] - Subscription Charging Method Changed' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Charging Method Changed' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'upcoming_mail_info' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Auto_to_Manual_Subscription_Renewal_Email() ;
