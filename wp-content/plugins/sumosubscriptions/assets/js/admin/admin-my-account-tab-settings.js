/* global sumosubscriptions_my_account_tab_settings, wp */

jQuery( function( $ ) {

    // sumosubscriptions_my_account_tab_settings is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_my_account_tab_settings === 'undefined' ) {
        return false ;
    }

    $( '#sumo_settings_max_no_of_pause' ).closest( 'tr' ).hide() ;
    $( '#sumo_sync_pause_resume_option' ).closest( 'tr' ).hide() ;
    $( '#sumo_settings_max_duration_of_pause' ).closest( 'tr' ).hide() ;
    $( '#sumo_min_days_user_wait_to_cancel_their_subscription' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_product_or_category_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).hide() ;
    $( '#sumo_hide_resubscribe_button_when' ).closest( 'tr' ).hide() ;
    $( '#sumo_allow_user_to_select_resume_date' ).closest( 'tr' ).hide() ;

    if ( $( '#sumo_allow_subscribers_to_resubscribe' ).is( ':checked' ) ) {
        $( '#sumo_hide_resubscribe_button_when' ).closest( 'tr' ).show() ;
    }

    if ( $( '#sumo_pause_resume_option' ).is( ':checked' ) ) {
        $( '#sumo_settings_max_no_of_pause' ).closest( 'tr' ).show() ;
        $( '#sumo_sync_pause_resume_option' ).closest( 'tr' ).show() ;
        $( '#sumo_settings_max_duration_of_pause' ).closest( 'tr' ).show() ;
        $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).closest( 'tr' ).show() ;
        $( '#sumo_allow_user_to_select_resume_date' ).closest( 'tr' ).show() ;

        switch ( $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).val() ) {
            case 'included_users':
            case 'excluded_users':
                $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_user_role':
            case 'excluded_user_role':
                $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).show() ;
                break ;
        }
    }

    if ( $( '#sumo_cancel_option' ).is( ':checked' ) ) {
        $( '#sumo_min_days_user_wait_to_cancel_their_subscription' ).closest( 'tr' ).show() ;
        $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).closest( 'tr' ).show() ;
        $( '#sumo_subscription_cancel_by_product_or_category_filter' ).closest( 'tr' ).show() ;
        $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).closest( 'tr' ).show() ;

        switch ( $( '#sumo_subscription_cancel_by_product_or_category_filter' ).val() ) {
            case 'included_products':
            case 'excluded_products':
                $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_categories':
            case 'excluded_categories':
                $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).show() ;
                break ;
        }
        switch ( $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).val() ) {
            case 'included_users':
            case 'excluded_users':
                $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_user_role':
            case 'excluded_user_role':
                $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).show() ;
                break ;
        }
    }

    $( '#sumo_allow_subscribers_to_resubscribe' ).change( function() {
        $( '#sumo_hide_resubscribe_button_when' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_hide_resubscribe_button_when' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_pause_resume_option' ).change( function() {
        $( '#sumo_settings_max_no_of_pause' ).closest( 'tr' ).hide() ;
        $( '#sumo_settings_max_duration_of_pause' ).closest( 'tr' ).hide() ;
        $( '#sumo_settings_max_no_of_pause' ).val( '0' ) ;
        $( '#sumo_settings_max_duration_of_pause' ).val( '10' ) ;
        $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_allow_user_to_select_resume_date' ).closest( 'tr' ).hide() ;
        $( '#sumo_sync_pause_resume_option' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_settings_max_no_of_pause' ).closest( 'tr' ).show() ;
            $( '#sumo_settings_max_duration_of_pause' ).closest( 'tr' ).show() ;
            $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).closest( 'tr' ).show() ;
            $( '#sumo_allow_user_to_select_resume_date' ).closest( 'tr' ).show() ;
            $( '#sumo_sync_pause_resume_option' ).closest( 'tr' ).show() ;

            switch ( $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).val() ) {
                case 'included_users':
                case 'excluded_users':
                    $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).show() ;
                    break ;
                case 'included_user_role':
                case 'excluded_user_role':
                    $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).show() ;
                    break ;
            }
        }
    } ) ;

    $( '#sumo_cancel_option' ).change( function() {
        $( '#sumo_min_days_user_wait_to_cancel_their_subscription' ).closest( 'tr' ).hide() ;
        $( '#sumo_min_days_user_wait_to_cancel_their_subscription' ).val( '' ) ;
        $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_product_or_category_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_min_days_user_wait_to_cancel_their_subscription' ).closest( 'tr' ).show() ;
            $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).closest( 'tr' ).show() ;
            $( '#sumo_subscription_cancel_by_product_or_category_filter' ).closest( 'tr' ).show() ;
            $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).closest( 'tr' ).show() ;

            switch ( $( '#sumo_subscription_cancel_by_product_or_category_filter' ).val() ) {
                case 'included_products':
                case 'excluded_products':
                    $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).show() ;
                    break ;
                case 'included_categories':
                case 'excluded_categories':
                    $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).show() ;
                    break ;
            }
            switch ( $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).val() ) {
                case 'included_users':
                case 'excluded_users':
                    $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).show() ;
                    break ;
                case 'included_user_role':
                case 'excluded_user_role':
                    $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).show() ;
                    break ;
            }
        }
    } ) ;

    $( '#sumo_subscription_cancel_by_product_or_category_filter' ).change( function() {
        $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).hide() ;

        switch ( this.value ) {
            case 'included_products':
            case 'excluded_products':
                $( '#sumo_subscription_cancel_by_product_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_categories':
            case 'excluded_categories':
                $( '#sumo_subscription_cancel_by_category_filter' ).closest( 'tr' ).show() ;
                break ;
        }
    } ) ;
    $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).change( function() {
        $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).hide() ;

        switch ( $( '#sumo_subscription_cancel_by_user_or_userrole_filter' ).val() ) {
            case 'included_users':
            case 'excluded_users':
                $( '#sumo_subscription_cancel_by_user_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_user_role':
            case 'excluded_user_role':
                $( '#sumo_subscription_cancel_by_userrole_filter' ).closest( 'tr' ).show() ;
                break ;
        }
    } ) ;
    $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).change( function() {
        $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).hide() ;
        $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).hide() ;

        switch ( $( '#sumo_subscription_pause_by_user_or_userrole_filter' ).val() ) {
            case 'included_users':
            case 'excluded_users':
                $( '#sumo_subscription_pause_by_user_filter' ).closest( 'tr' ).show() ;
                break ;
            case 'included_user_role':
            case 'excluded_user_role':
                $( '#sumo_subscription_pause_by_userrole_filter' ).closest( 'tr' ).show() ;
                break ;
        }
    } ) ;

    if ( sumosubscriptions_my_account_tab_settings.is_lower_wc_version ) {
        $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).chosen( ) ;
        $( '#sumo_subscription_cancel_by_userrole_filter' ).chosen() ;
        $( '#sumo_subscription_pause_by_userrole_filter' ).chosen() ;
        $( '#sumo_subscription_cancel_by_category_filter' ).chosen() ;
        $( '#sumo_hide_resubscribe_button_when' ).chosen() ;
    } else {
        $( '#sumo_subscription_cancel_methods_available_to_subscriber' ).select2( ) ;
        $( '#sumo_subscription_cancel_by_userrole_filter' ).select2() ;
        $( '#sumo_subscription_pause_by_userrole_filter' ).select2() ;
        $( '#sumo_subscription_cancel_by_category_filter' ).select2() ;
        $( '#sumo_hide_resubscribe_button_when' ).select2() ;
    }
} ) ;
