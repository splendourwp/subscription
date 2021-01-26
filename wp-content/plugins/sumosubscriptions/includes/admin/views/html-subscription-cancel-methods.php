<?php
if ( 'Pending_Cancellation' === $subscription_status ) {
    ?>
    <div>
        <p><?php printf( __( 'Current Cancel Method: <b>%s</b>' , 'sumosubscriptions' ) , ucwords( str_replace( '_' , ' ' , $requested_cancel_method ) ) ) ?></p>
        <p><?php printf( __( 'Subscription will be Cancelled on: <b>%s</b>' , 'sumosubscriptions' ) , $cancel_scheduled_on ) ?></p>
        <a href="<?php echo esc_url_raw( add_query_arg( array ( 'request' => 'revoke_cancel' , '_sumosubsnonce' => wp_create_nonce( $post->ID ) ) ) ) ; ?>" class="sumo_revoke_subscription_cancel_request"><?php _e( 'Revoke Cancel' , 'sumosubscriptions' ) ; ?></a>
    </div>
    <?php
} else {
    $subscription_cancel_methods = array (
        'immediate' => __( 'Cancel immediately' , 'sumosubscriptions' ) ,
            ) ;

    if ( in_array( $subscription_status , array ( 'Trial' , 'Active' , 'Pending_Cancellation' ) ) ) {
        $subscription_cancel_methods = array (
            'immediate'            => __( 'Cancel immediately' , 'sumosubscriptions' ) ,
            'end_of_billing_cycle' => __( 'Cancel at the end of billing cycle' , 'sumosubscriptions' ) ,
            'scheduled_date'       => __( 'Cancel on a scheduled date' , 'sumosubscriptions' )
                ) ;
    }
    ?>
    <select class="sumo_subscription_cancel_method_via wc-enhanced-select wide">
        <option value=""><?php _e( 'Select an option...' , 'sumosubscriptions' ) ; ?></option>
        <?php foreach ( $subscription_cancel_methods as $method_key => $method ): ?>
            <option value="<?php echo $method_key ; ?>"><?php echo $method ; ?></option>
        <?php endforeach ?>
    </select>
    <input type="hidden" id="sumo_subscription_data" data-next_payment_date="<?php echo $next_payment_date ; ?>">
    <input type="text" id="sumo_subscription_cancel_scheduled_on" placeholder="<?php esc_attr_e( 'YYYY-MM-DD' , 'sumosubscriptions' ) ?>"  name="sumo_subscription_cancel_scheduled_on" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display:none;width:150px;"/>
    <input type="button" class="button-primary sumo_submit_subscription_cancel_request" data-subscription_id="<?php echo $post->ID ; ?>" value="<?php _e( 'Save' , 'sumosubscriptions' ) ; ?>" style="display: none;"/>
    <?php
}
