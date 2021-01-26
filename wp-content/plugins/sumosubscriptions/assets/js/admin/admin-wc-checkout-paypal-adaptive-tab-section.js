/* global sumosubscriptions_wc_checkout_paypal_adaptive_section_settings */

jQuery( function ( $ ) {
    // sumosubscriptions_wc_checkout_paypal_adaptive_section_settings is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_wc_checkout_paypal_adaptive_section_settings === 'undefined' ) {
        return false ;
    }

    $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP_value' ).closest( 'tr' ).hide() ;
    $( '#woocommerce_sumo_paypal_preapproval_validity_period_value' ).closest( 'tr' ).hide() ;

    if ( $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP' ).is( ':checked' ) ) {
        $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP_value' ).closest( 'tr' ).show() ;
    }

    if ( $( '#woocommerce_sumo_paypal_preapproval_validity_period' ).is( ':checked' ) ) {
        $( '#woocommerce_sumo_paypal_preapproval_validity_period_value' ).closest( 'tr' ).show() ;
    }

    $( document ).on( 'change' , '#woocommerce_sumo_paypal_preapproval_maxTAOAP' , function () {
        $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP_value' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP_value' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( document ).on( 'change' , '#woocommerce_sumo_paypal_preapproval_validity_period' , function () {
        $( '#woocommerce_sumo_paypal_preapproval_validity_period_value' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#woocommerce_sumo_paypal_preapproval_validity_period_value' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( document ).on( 'submit' , '#mainform' , function () {
        var is_valid_to_save = true ;

        if ( $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP' ).is( ':checked' ) ) {
            if ( $( '#woocommerce_sumo_paypal_preapproval_maxTAOAP_value' ).val() === '' ) {
                is_valid_to_save = false ;
            }
        }

        if ( $( '#woocommerce_sumo_paypal_preapproval_validity_period' ).is( ':checked' ) ) {
            if ( $( '#woocommerce_sumo_paypal_preapproval_validity_period_value' ).val() === '' ) {
                is_valid_to_save = false ;
            }
        }

        if ( !is_valid_to_save ) {
            alert( sumosubscriptions_wc_checkout_paypal_adaptive_section_settings.admin_notice ) ;
            return false ;
        }

    } ) ;
} ) ;