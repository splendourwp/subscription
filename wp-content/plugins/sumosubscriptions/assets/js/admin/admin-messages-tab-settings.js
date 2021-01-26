
jQuery( function ( $ ) {

    $( '#sumo_active_subsc_per_product_in_product_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_active_subsc_through_site_in_product_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_active_trial_per_product_in_cart_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_active_trial_through_site_in_cart_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_err_msg_for_paused_in_pay_for_order_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ).closest( 'tr' ).hide() ;
    $( '#sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ).closest( 'tr' ).hide() ;
    $( '#sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ).closest( 'tr' ).hide() ;
    $( '#sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ).closest( 'tr' ).hide() ;
    $( '#sumo_cancel_dialog_message' ).closest( 'tr' ).hide() ;
    $( '#sumo_cancel_at_the_end_of_billing_dialog_message' ).closest( 'tr' ).hide() ;
    $( '#sumo_cancel_on_the_scheduled_date_dialog_message' ).closest( 'tr' ).hide() ;
    $( '#sumo_revoking_cancel_confirmation_dialog_message' ).closest( 'tr' ).hide() ;

    if ( $( '#sumo_show_hide_err_msg_product_page' ).is( ':checked' ) ) {
        $( '#sumo_active_subsc_per_product_in_product_page' ).closest( 'tr' ).show() ;
        $( '#sumo_active_subsc_through_site_in_product_page' ).closest( 'tr' ).show() ;
        $( '#sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ).closest( 'tr' ).show() ;
        $( '#sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ).closest( 'tr' ).show() ;
    }
    if ( $( '#sumo_show_hide_err_msg_cart_page' ).is( ':checked' ) ) {
        $( '#sumo_active_trial_per_product_in_cart_page' ).closest( 'tr' ).show() ;
        $( '#sumo_active_trial_through_site_in_cart_page' ).closest( 'tr' ).show() ;
    }
    if ( $( '#sumo_show_hide_err_msg_pay_order_page' ).is( ':checked' ) ) {
        $( '#sumo_err_msg_for_paused_in_pay_for_order_page' ).closest( 'tr' ).show() ;
        $( '#sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ).closest( 'tr' ).show() ;
        $( '#sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ).closest( 'tr' ).show() ;
    }
    if ( $( '#sumo_display_dialog_upon_cancel' ).is( ':checked' ) ) {
        $( '#sumo_cancel_dialog_message' ).closest( 'tr' ).show() ;
        $( '#sumo_cancel_at_the_end_of_billing_dialog_message' ).closest( 'tr' ).show() ;
        $( '#sumo_cancel_on_the_scheduled_date_dialog_message' ).closest( 'tr' ).show() ;
    }
    if ( $( '#sumo_display_dialog_upon_revoking_cancel' ).is( ':checked' ) ) {
        $( '#sumo_revoking_cancel_confirmation_dialog_message' ).closest( 'tr' ).show() ;
    }

    $( '#sumo_show_hide_err_msg_product_page' ).change( function () {
        $( '#sumo_active_subsc_per_product_in_product_page' ).closest( 'tr' ).hide() ;
        $( '#sumo_active_subsc_through_site_in_product_page' ).closest( 'tr' ).hide() ;
        $( '#sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ).closest( 'tr' ).hide() ;
        $( '#sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_active_subsc_per_product_in_product_page' ).closest( 'tr' ).show() ;
            $( '#sumo_active_subsc_through_site_in_product_page' ).closest( 'tr' ).show() ;
            $( '#sumo_err_msg_for_add_to_cart_non_subscription_with_subscription' ).closest( 'tr' ).show() ;
            $( '#sumo_err_msg_for_add_to_cart_subscription_with_non_subscription' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_show_hide_err_msg_cart_page' ).change( function () {
        $( '#sumo_active_trial_per_product_in_cart_page' ).closest( 'tr' ).hide() ;
        $( '#sumo_active_trial_through_site_in_cart_page' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_active_trial_per_product_in_cart_page' ).closest( 'tr' ).show() ;
            $( '#sumo_active_trial_through_site_in_cart_page' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_show_hide_err_msg_pay_order_page' ).change( function () {
        $( '#sumo_err_msg_for_paused_in_pay_for_order_page' ).closest( 'tr' ).hide() ;
        $( '#sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ).closest( 'tr' ).hide() ;
        $( '#sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_err_msg_for_paused_in_pay_for_order_page' ).closest( 'tr' ).show() ;
            $( '#sumo_err_msg_for_pending_cancellation_in_pay_for_order_page' ).closest( 'tr' ).show() ;
            $( '#sumo_err_msg_if_user_paying_active_auto_subscription_renewal_order' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_display_dialog_upon_cancel' ).change( function () {
        $( '#sumo_cancel_dialog_message' ).closest( 'tr' ).hide() ;
        $( '#sumo_cancel_at_the_end_of_billing_dialog_message' ).closest( 'tr' ).hide() ;
        $( '#sumo_cancel_on_the_scheduled_date_dialog_message' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_cancel_dialog_message' ).closest( 'tr' ).show() ;
            $( '#sumo_cancel_at_the_end_of_billing_dialog_message' ).closest( 'tr' ).show() ;
            $( '#sumo_cancel_on_the_scheduled_date_dialog_message' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_display_dialog_upon_revoking_cancel' ).change( function () {
        $( '#sumo_revoking_cancel_confirmation_dialog_message' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_revoking_cancel_confirmation_dialog_message' ).closest( 'tr' ).show() ;
        }
    } ) ;

} ) ;