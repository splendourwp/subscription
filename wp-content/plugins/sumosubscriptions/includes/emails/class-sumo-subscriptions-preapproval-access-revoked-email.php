<?php

/**
 * Automatic Subscription Preapproval Access Revoked Email.
 * 
 * @class SUMOSubscriptions_Preapproval_Access_Revoked_Email
 * @category Class
 */
class SUMOSubscriptions_Preapproval_Access_Revoked_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_preapproval_access_revoked' ;
        $this->name           = 'preapproval-access-revoked' ;
        $this->customer_email = true ;
        $this->title          = __( 'Automatic Subscription Preapproval Access Revoked' , 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Automatic Subscription Preapproval Access Revoked %s This email will be sent when the user has cancelled their Automatic Subscription Preapproval and Subscription is changed to Manual payment mode.' , 'sumosubscriptions' ) , '--' ) ) ;

        $this->template_html  = 'emails/subscription-preapproval-access-revoked.php' ;
        $this->template_plain = 'emails/plain/subscription-preapproval-access-revoked.php' ;

        $this->subject = __( '[{site_title}] - Preapproval Access Revoked' , 'sumosubscriptions' ) ;
        $this->heading = __( 'Preapproval Access Revoked' , 'sumosubscriptions' ) ;

        $this->supports = array ( 'mail_to_admin', 'upcoming_mail_info' ) ;

        // Call parent constructor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Preapproval_Access_Revoked_Email() ;
