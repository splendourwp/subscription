<?php

/**
 * Subscription Cancel - Request Submitted.
 * 
 * @class SUMOSubscriptions_Cancel_Request_Submitted_Email
 * @category Class
 */
class SUMOSubscriptions_Cancel_Request_Submitted_Email extends SUMO_Abstract_Subscription_Email {

    /**
     * Constructor.
     * 
     * @access public
     */
    function __construct() {
        $this->id             = 'subscription_cancel_request_submitted' ;
        $this->name           = 'cancel-request-submitted' ;
        $this->customer_email = true ;
        $this->title          = __( 'Subscription Cancel - Request Submitted', 'sumosubscriptions' ) ;
        $this->description    = addslashes( sprintf( __( 'Subscription cancel %s Request submitted emails are sent to the customers when they have submitted a request to cancel their subscription.', 'sumosubscriptions' ), '--' ) ) ;

        $this->template_html  = 'emails/subscription-cancel-request-submitted.php' ;
        $this->template_plain = 'emails/plain/subscription-cancel-request-submitted.php' ;

        $this->subject = __( '[{site_title}] - Subscription Cancel Request Submitted', 'sumosubscriptions' ) ;
        $this->heading = __( 'Subscription Cancel Request Submitted', 'sumosubscriptions' ) ;

        $this->supports = array( 'mail_to_admin', 'cancel_method_requested' ) ;

        // Call parent constuctor
        parent::__construct() ;
    }

}

return new SUMOSubscriptions_Cancel_Request_Submitted_Email() ;
