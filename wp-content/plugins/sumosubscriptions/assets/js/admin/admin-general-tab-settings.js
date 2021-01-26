/* global sumosubscriptions_general_tab_settings, wp */

jQuery( function( $ ) {

    // sumosubscriptions_general_tab_settings is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_general_tab_settings === 'undefined' ) {
        return false ;
    }

    $( '#sumo_coupon_in_renewal_order_applicable_for' ).closest( 'tr' ).show() ;
    $( '#sumo_selected_users_for_renewal_order_discount' ).closest( 'tr' ).hide() ;
    $( '#sumo_selected_user_roles_for_renewal_order_discount' ).closest( 'tr' ).hide() ;
    $( '#sumo_apply_coupon_discount' ).closest( 'tr' ).show() ;
    $( '#no_of_sumo_selected_renewal_order_discount' ).closest( 'tr' ).hide() ;
    $( '#sumosubs_disable_auto_payment_gateways' ).closest( 'tr' ).hide() ;
    $( '#sumo_paypal_payment_option' ).closest( 'tr' ).show() ;
    $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
    $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).show() ;
    $( '#sumo_charge_shipping_only_in_renewals_when_subtotal_zero' ).closest( 'tr' ).hide() ;
    $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;

    if ( $( '#sumo_shipping_option' ).is( ':checked' ) ) {
        $( '#sumo_charge_shipping_only_in_renewals_when_subtotal_zero' ).closest( 'tr' ).show() ;
    }

    if ( $( '#sumo_apply_coupon_discount' ).val() === '2' ) {
        $( '#no_of_sumo_selected_renewal_order_discount' ).closest( 'tr' ).show() ;
    }

    if ( $.inArray( $( '#sumo_coupon_in_renewal_order_applicable_for' ).val() , Array( 'include_users' , 'exclude_users' ) ) !== - 1 ) {
        $( '#sumo_selected_users_for_renewal_order_discount' ).closest( 'tr' ).show() ;
    } else if ( $.inArray( $( '#sumo_coupon_in_renewal_order_applicable_for' ).val() , Array( 'include_user_role' , 'exclude_user_role' ) ) !== - 1 ) {
        $( '#sumo_selected_user_roles_for_renewal_order_discount' ).closest( 'tr' ).show() ;
    }

    if ( $( '#sumo_paypal_payment_option' ).is( ':checked' ) ) {
        $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).show() ;

        if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
            $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
        }
        if ( $( '#sumo_force_auto_manual_paypal_adaptive' ).val() === '1' ) {
            $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
            $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
        }
    }

    if ( $( '#sumosubs_accept_manual_payment_gateways' ).is( ':checked' ) ) {
        $( '#sumosubs_disable_auto_payment_gateways' ).closest( 'tr' ).show() ;

        if ( $( '#sumosubs_disable_auto_payment_gateways' ).is( ':checked' ) ) {
            $( '#sumo_paypal_payment_option' ).closest( 'tr' ).hide() ;
            $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
            $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
            $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
        }
    }

    $( '#sumo_shipping_option' ).change( function() {
        $( '#sumo_charge_shipping_only_in_renewals_when_subtotal_zero' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_charge_shipping_only_in_renewals_when_subtotal_zero' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_apply_coupon_discount' ).change( function() {
        $( '#no_of_sumo_selected_renewal_order_discount' ).closest( 'tr' ).hide() ;
        $( '#no_of_sumo_selected_renewal_order_discount' ).val( '' ) ;

        if ( $( this ).val() === '2' ) {
            $( '#no_of_sumo_selected_renewal_order_discount' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumo_coupon_in_renewal_order_applicable_for' ).change( function() {
        $( '#sumo_selected_users_for_renewal_order_discount' ).closest( 'tr' ).hide() ;
        $( '#sumo_selected_user_roles_for_renewal_order_discount' ).closest( 'tr' ).hide() ;

        if ( $.inArray( this.value , Array( 'include_users' , 'exclude_users' ) ) !== - 1 ) {
            $( '#sumo_selected_users_for_renewal_order_discount' ).closest( 'tr' ).show() ;
        } else if ( $.inArray( this.value , Array( 'include_user_role' , 'exclude_user_role' ) ) !== - 1 ) {
            $( '#sumo_selected_user_roles_for_renewal_order_discount' ).closest( 'tr' ).show() ;
        }
    } ) ;

    $( '#sumosubs_accept_manual_payment_gateways' ).change( function() {
        $( '#sumosubs_disable_auto_payment_gateways' ).closest( 'tr' ).hide() ;
        $( '#sumo_paypal_payment_option' ).closest( 'tr' ).show() ;
        $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).show() ;
        $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;

        if ( $( '#sumo_paypal_payment_option' ).is( ':checked' ) ) {
            $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).show() ;

            if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
            }
            if ( $( '#sumo_force_auto_manual_paypal_adaptive' ).val() === '1' ) {
                $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
            }
        }

        if ( this.checked ) {
            $( '#sumosubs_disable_auto_payment_gateways' ).closest( 'tr' ).show() ;

            if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
            }
            if ( $( '#sumosubs_disable_auto_payment_gateways' ).is( ':checked' ) ) {
                $( '#sumo_paypal_payment_option' ).closest( 'tr' ).hide() ;
                $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
                $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
            }
        }
    } ) ;

    $( '#sumosubs_disable_auto_payment_gateways' ).change( function() {
        $( '#sumo_paypal_payment_option' ).closest( 'tr' ).show() ;
        $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).show() ;
        $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
        $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;

        if ( $( '#sumo_paypal_payment_option' ).is( ':checked' ) ) {
            $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).show() ;

            if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
            }
            if ( $( '#sumo_force_auto_manual_paypal_adaptive' ).val() === '1' ) {
                $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
            }
        }

        if ( this.checked ) {
            $( '#sumo_paypal_payment_option' ).closest( 'tr' ).hide() ;
            $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
            $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
            $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
        }
    } ) ;

    $( '#sumo_paypal_payment_option' ).change( function() {
        $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).hide() ;
        $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).show() ;
        $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_force_auto_manual_paypal_adaptive' ).closest( 'tr' ).show() ;

            if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
            }
            if ( $( '#sumo_force_auto_manual_paypal_adaptive' ).val() === '1' ) {
                $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
            }
        }
    } ) ;

    $( '#sumo_force_auto_manual_paypal_adaptive' ).change( function() {
        $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).hide() ;
        $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;

        if ( this.value === '2' ) {
            $( '#sumo_include_paypal_subscription_api_option' ).closest( 'tr' ).show() ;

            if ( $( '#sumo_include_paypal_subscription_api_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;
            }
        }
    } ) ;

    $( '#sumo_include_paypal_subscription_api_option' ).change( function() {
        $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            if ( $( '#sumo_paypal_payment_option' ).is( ':checked' ) ) {
                $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).show() ;

                if ( $( '#sumo_force_auto_manual_paypal_adaptive' ).val() === '1' ) {
                    $( '#sumo_show_paypal_std_gateway_for_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide() ;
                }
            }
        }
    } ) ;

    if ( sumosubscriptions_general_tab_settings.is_lower_wc_version ) {
        $( '#sumo_selected_user_roles_for_renewal_order_discount' ).chosen() ;
    } else {
        $( '#sumo_selected_user_roles_for_renewal_order_discount' ).select2() ;
    }
} ) ;
