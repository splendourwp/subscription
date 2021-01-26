/* global sumosubscriptions_bulk_action_tab_settings */

jQuery( function ( $ ) {

    // sumosubscriptions_bulk_action_tab_settings is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_bulk_action_tab_settings === 'undefined' ) {
        return false ;
    }

    var subscription_bulk_action_checkbox_events = {
        /**
         * Perform Subscription Bulk Action Checkbox events.
         */
        init : function ( ) {

            this.triggerOnPageLoad( ) ;

            $( document ).on( 'change' , '#sumo_bulk_update_enable_subscription_checkbox' , this.toggleSubscriptionStatusCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_trial_period_checkbox' , this.toggleTrialStatusCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_signup_fee_checkbox' , this.toggleSignupStatusCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_fee_type_checkbox' , this.toggleTrialTypeCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_trial_fee_value_checkbox' , this.toggleTrialFeeCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_signup_fee_value_checkbox' , this.toggleSignupFeeCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_subscription_duration_checkbox' , this.toggleSubscriptionDurationCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_trial_duration_checkbox' , this.toggleTrialDurationCheckbox ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_recurring_cycle_checkbox' , this.toggleRecurringCycleCheckbox ) ;
        } ,
        triggerOnPageLoad : function ( ) {

            this.getSubscriptionCheckboxStatus( $( '#sumo_bulk_update_enable_subscription_checkbox' ).is( ':checked' ) ) ;
            this.getTrialCheckboxStatus( $( '#sumo_bulk_update_trial_period_checkbox' ).is( ':checked' ) ) ;
            this.getSignupCheckboxStatus( $( '#sumo_bulk_update_signup_fee_checkbox' ).is( ':checked' ) ) ;
            this.getTrialTypeCheckboxStatus( $( '#sumo_bulk_update_fee_type_checkbox' ).is( ':checked' ) ) ;
            this.getTrialFeeCheckboxStatus( $( '#sumo_bulk_update_trial_fee_value_checkbox' ).is( ':checked' ) ) ;
            this.getSignupFeeCheckbox( $( '#sumo_bulk_update_signup_fee_value_checkbox' ).is( ':checked' ) ) ;
            this.getSubscriptionDurationCheckboxStatus( $( '#sumo_bulk_update_subscription_duration_checkbox' ).is( ':checked' ) ) ;
            this.getTrialDurationCheckboxStatus( $( '#sumo_bulk_update_trial_duration_checkbox' ).is( ':checked' ) ) ;
            this.getRecurringCycleCheckboxStatus( $( '#sumo_bulk_update_recurring_cycle_checkbox' ).is( ':checked' ) ) ;
        } ,
        toggleSubscriptionStatusCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getSubscriptionCheckboxStatus( $is_checked ) ;
        } ,
        toggleTrialStatusCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getTrialCheckboxStatus( $is_checked ) ;
        } ,
        toggleSignupStatusCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getSignupCheckboxStatus( $is_checked ) ;
        } ,
        toggleTrialTypeCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getTrialTypeCheckboxStatus( $is_checked ) ;
        } ,
        toggleTrialFeeCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getTrialFeeCheckboxStatus( $is_checked ) ;
        } ,
        toggleSignupFeeCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getSignupFeeCheckbox( $is_checked ) ;
        } ,
        toggleSubscriptionDurationCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getSubscriptionDurationCheckboxStatus( $is_checked ) ;
        } ,
        toggleTrialDurationCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getTrialDurationCheckboxStatus( $is_checked ) ;
        } ,
        toggleRecurringCycleCheckbox : function ( evt ) {
            var $is_checked = $( evt.currentTarget ).is( ':checked' ) ;

            subscription_bulk_action_checkbox_events.getRecurringCycleCheckboxStatus( $is_checked ) ;
        } ,
        getSubscriptionCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_enable_subscription' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_enable_subscription' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getSubscriptionDurationCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            this.getDurationCheckboxStatus( $is_checked , 'subscription' ) ;
        } ,
        getTrialDurationCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            this.getDurationCheckboxStatus( $is_checked , 'trial' ) ;
        } ,
        getDurationCheckboxStatus : function ( $is_checked , $type ) {
            $is_checked = $is_checked || '' ;
            $type = $type || '' ;

            $( '#sumo_bulk_update_' + $type + '_duration' ).prop( 'disabled' , true ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_days' ).prop( 'disabled' , true ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_weeks' ).prop( 'disabled' , true ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_months' ).prop( 'disabled' , true ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_years' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_' + $type + '_duration' ).removeAttr( 'disabled' ) ;
                $( '#sumo_bulk_update_' + $type + '_duration_value_days' ).removeAttr( 'disabled' ) ;
                $( '#sumo_bulk_update_' + $type + '_duration_value_weeks' ).removeAttr( 'disabled' ) ;
                $( '#sumo_bulk_update_' + $type + '_duration_value_months' ).removeAttr( 'disabled' ) ;
                $( '#sumo_bulk_update_' + $type + '_duration_value_years' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getTrialCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_trial_period' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_trial_period' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getSignupCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_signup_fee' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_signup_fee' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getTrialTypeCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_fee_type' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_fee_type' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getTrialFeeCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_trial_fee_value' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_trial_fee_value' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getSignupFeeCheckbox : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_signup_fee_value' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_signup_fee_value' ).removeAttr( 'disabled' ) ;
            }
        } ,
        getRecurringCycleCheckboxStatus : function ( $is_checked ) {
            $is_checked = $is_checked || '' ;

            $( '#sumo_bulk_update_recurring_cycle' ).prop( 'disabled' , true ) ;

            if ( $is_checked ) {
                $( '#sumo_bulk_update_recurring_cycle' ).removeAttr( 'disabled' ) ;
            }
        }
    } ;

    var subscription_bulk_action_toggle_events = {
        /**
         * Perform Subscription Bulk Action Toggle events.
         */
        init : function ( ) {

            this.triggerOnPageLoad( ) ;

            $( document ).on( 'change' , '#sumo_bulk_update_select_product_category' , this.toggleProductOrCategory ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_subscription_duration' , this.toggleSubscriptionDuration ) ;
            $( document ).on( 'change' , '#sumo_bulk_update_trial_duration' , this.toggleTrialDuration ) ;
        } ,
        triggerOnPageLoad : function ( ) {

            this.onLoadProductOrCategorySelector( ) ;

            this.getProductOrCategoryType( $( '#sumo_bulk_update_select_product_category' ).val( ) ) ;
            this.getSubscriptionDuration( $( '#sumo_bulk_update_subscription_duration' ).val( ) ) ;
            this.getTrialDuration( $( '#sumo_bulk_update_trial_duration' ).val( ) ) ;
        } ,
        onLoadProductOrCategorySelector : function ( ) {

            if ( sumosubscriptions_bulk_action_tab_settings.is_lower_wc_version ) {
                $( '.sumo_select_particular_category' ).chosen( ) ;

                $( 'select.sumo_select_particular_products' ).ajaxChosen( {
                    method : 'GET' ,
                    url : sumosubscriptions_bulk_action_tab_settings.wp_ajax_url ,
                    dataType : 'json' ,
                    afterTypeDelay : 100 ,
                    data : {
                        action : 'woocommerce_json_search_products_and_variations' ,
                        security : sumosubscriptions_bulk_action_tab_settings.wp_create_nonce
                    }
                } , function ( data ) {
                    var terms = { } ;
                    $.each( data , function ( i , val ) {
                        terms[i] = val ;
                    } ) ;
                    return terms ;
                } ) ;
            } else {
                $( '.sumo_select_particular_category' ).select2( ) ;
            }
        } ,
        toggleProductOrCategory : function ( evt ) {
            var $type = $( evt.currentTarget ).val( ) ;

            subscription_bulk_action_toggle_events.getProductOrCategoryType( $type ) ;
        } ,
        toggleSubscriptionDuration : function ( evt ) {
            var $duration = $( evt.currentTarget ).val( ) ;

            subscription_bulk_action_toggle_events.getSubscriptionDuration( $duration ) ;
        } ,
        toggleTrialDuration : function ( evt ) {
            var $duration = $( evt.currentTarget ).val( ) ;

            subscription_bulk_action_toggle_events.getTrialDuration( $duration ) ;
        } ,
        getProductOrCategoryType : function ( $type ) {
            $type = $type || '' ;

            $( '#sumo_select_particular_products' ).closest( 'tr' ).hide( ) ;
            $( '#sumo_select_particular_category' ).closest( 'tr' ).hide( ) ;

            switch ( $type ) {
                case '2':
                    $( '#sumo_select_particular_products' ).closest( 'tr' ).show( ) ;
                    $( '#sumo_select_particular_category' ).closest( 'tr' ).hide( ) ;
                    break ;
                case '4':
                    $( '#sumo_select_particular_products' ).closest( 'tr' ).hide( ) ;
                    $( '#sumo_select_particular_category' ).closest( 'tr' ).show( ) ;
                    break ;
            }
        } ,
        getSubscriptionDuration : function ( $duration ) {
            $duration = $duration || '' ;

            this.getDuration( $duration , 'subscription' ) ;
        } ,
        getTrialDuration : function ( $duration ) {
            $duration = $duration || '' ;

            this.getDuration( $duration , 'trial' ) ;
        } ,
        getDuration : function ( $duration , $type ) {
            $duration = $duration || '' ;
            $type = $type || '' ;

            $( '#sumo_bulk_update_' + $type + '_duration_value_days' ).closest( 'tr' ).hide( ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_weeks' ).closest( 'tr' ).hide( ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_months' ).closest( 'tr' ).hide( ) ;
            $( '#sumo_bulk_update_' + $type + '_duration_value_years' ).closest( 'tr' ).hide( ) ;

            switch ( $duration ) {
                case 'W':
                    $( '#sumo_bulk_update_' + $type + '_duration_value_weeks' ).closest( 'tr' ).show( ) ;
                    break ;
                case 'M':
                    $( '#sumo_bulk_update_' + $type + '_duration_value_months' ).closest( 'tr' ).show( ) ;
                    break ;
                case 'Y':
                    $( '#sumo_bulk_update_' + $type + '_duration_value_years' ).closest( 'tr' ).show( ) ;
                    break ;
                default:
                    $( '#sumo_bulk_update_' + $type + '_duration_value_days' ).closest( 'tr' ).show( ) ;
                    break ;
            }
        }
    } ;

    var subscription_bulk_action_onSubmit = {
        /**
         * Bulk Update the Subscription data's.
         */
        init : function ( ) {

            $( '.sumosubscription_loading' ).css( 'display' , 'none' ) ;

            $( document ).on( 'click' , '#sumo_subscription_bulk_update' , this.onSubmit ) ;
        } ,
        onSubmit : function () {
            $( '.sumosubscription_loading' ).css( 'display' , 'inline-block' ) ;

            var $is_bulk_update = $( this ).attr( 'data-is_bulk_update' ) ;

            $.ajax( {
                type : 'POST' ,
                url : sumosubscriptions_bulk_action_tab_settings.wp_ajax_url ,
                data : subscription_bulk_action_onSubmit.getData( 'sumosubscription_bulk_update_product_meta' , $is_bulk_update ) ,
                success : function ( data ) {
                    console.log( data ) ;

                    if ( data !== 'success' ) {
                        var j = 1 ;
                        var i , j , id , chunk = 10 ;

                        for ( i = 0 , j = data.length ; i < j ; i += chunk ) {
                            id = data.slice( i , i + chunk ) ;
                            subscription_bulk_action_onSubmit.optimizeData( id ) ;
                        }

                        $.when( subscription_bulk_action_onSubmit.optimizeData( ) ).done( function () {
                            location.reload( true ) ;
                        } ) ;
                    } else if ( data.replace( /\s/g , '' ) === 'success' ) {
                        location.reload( true ) ;
                    }
                } ,
                dataType : 'json' ,
                async : false
            } ) ;
            return false ;
        } ,
        optimizeData : function ( id ) {
            id = id || '' ;

            return $.ajax( {
                type : 'POST' ,
                url : sumosubscriptions_bulk_action_tab_settings.wp_ajax_url ,
                data : subscription_bulk_action_onSubmit.getData( 'sumosubscription_optimize_bulk_updation_of_product_meta' , false , id ) ,
                success : function ( data ) {
                    console.log( data ) ;
                } ,
                dataType : 'json' ,
                async : false
            } ) ;
        } ,
        getData : function ( action , $is_bulk_update , id ) {
            action = action || '' ;
            id = id || '' ;
            $is_bulk_update = $is_bulk_update || '' ;

            return ( {
                action : action ,
                ids : id ,
                is_bulk_update : $is_bulk_update ,
                security : $is_bulk_update ? sumosubscriptions_bulk_action_tab_settings.update_nonce : sumosubscriptions_bulk_action_tab_settings.optimization_nonce ,
                select_type : $( '#sumo_bulk_update_select_product_category' ).val( ) ,
                selected_products : $( '#sumo_select_particular_products' ).val( ) ,
                selected_category : $( '#sumo_select_particular_category' ).val( ) ,
                enable_subscription : $( '#sumo_bulk_update_enable_subscription' ).val( ) ,
                update_enable_subscription : $( '#sumo_bulk_update_enable_subscription_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_subscription_duration : $( '#sumo_bulk_update_subscription_duration_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_trial_period : $( '#sumo_bulk_update_trial_period_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_fee_type : $( '#sumo_bulk_update_fee_type_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_fee_value : $( '#sumo_bulk_update_trial_fee_value_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_trial_duration : $( '#sumo_bulk_update_trial_duration_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_signup_fee : $( '#sumo_bulk_update_signup_fee_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_signup_fee_value : $( '#sumo_bulk_update_signup_fee_value_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                update_recurring_cycle : $( '#sumo_bulk_update_recurring_cycle_checkbox' ).is( ':checked' ) ? '1' : '2' ,
                subscription_duration : $( '#sumo_bulk_update_subscription_duration' ).val( ) ,
                subscription_value_days : $( '#sumo_bulk_update_subscription_duration_value_days' ).val( ) ,
                subscription_value_weeks : $( '#sumo_bulk_update_subscription_duration_value_weeks' ).val( ) ,
                subscription_value_months : $( '#sumo_bulk_update_subscription_duration_value_months' ).val( ) ,
                subscription_value_years : $( '#sumo_bulk_update_subscription_duration_value_years' ).val( ) ,
                trial_period : $( '#sumo_bulk_update_trial_period' ).val( ) ,
                trial_fee_type : $( '#sumo_bulk_update_fee_type' ).val( ) ,
                trial_fee_value : $( '#sumo_bulk_update_trial_fee_value' ).val( ) ,
                trial_duration : $( '#sumo_bulk_update_trial_duration' ).val( ) ,
                trial_value_days : $( '#sumo_bulk_update_trial_duration_value_days' ).val( ) ,
                trial_value_weeks : $( '#sumo_bulk_update_trial_duration_value_weeks' ).val( ) ,
                trial_value_months : $( '#sumo_bulk_update_trial_duration_value_months' ).val( ) ,
                trial_value_years : $( '#sumo_bulk_update_trial_duration_value_years' ).val( ) ,
                signup_fee : $( '#sumo_bulk_update_signup_fee' ).val( ) ,
                signup_fee_value : $( '#sumo_bulk_update_signup_fee_value' ).val( ) ,
                recurring_cycle : $( '#sumo_bulk_update_recurring_cycle' ).val( )
            } ) ;
        }
    } ;

    subscription_bulk_action_checkbox_events.init( ) ;
    subscription_bulk_action_toggle_events.init( ) ;
    subscription_bulk_action_onSubmit.init( ) ;
} ) ;