<?php

/**
 * Subscription Cancel - Request Revoked.
 * 
 * @class SUMOSubscriptions_Cancel_Request_Revoked_Email
 * @category Class
 */
class SUMOSubscriptions_Cancel_Request_Revoked_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_cancel_request_revoked' ;
        $this->name           = 'cancel-request-revoked' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Cancel - Request Revoked', 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription cancel %s Request revoked emails are sent to the customers when they revoke the cancel request.', 'sumosubscriptions' ), '--' ) ) ;

        $this->template_html  = 'emails/subscription-cancel-request-revoked.php' ;
        $this->template_plain = 'emails/plain/subscription-cancel-request-revoked.php' ;

        $this->subject = __( '[{site_title}] - Subscription Cancel Request Revoked', 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Cancel Request Revoked', 'sumosubscriptions' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Cancel_Request_Revoked_Email() ;
