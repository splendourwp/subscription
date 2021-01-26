/* global sumosubscriptions_stripe */

jQuery( function( $ ) {
    'use strict' ;

    // sumosubscriptions_stripe is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_stripe === 'undefined' ) {
        return false ;
    }

    var sumo_stripe = {
        stripeClient : null,
        stripeElements : null,
        stripeCard : null,
        stripeExp : null,
        stripeCVC : null,
        styles : {
            base : {
                color : '#32325d',
                lineHeight : '18px',
                fontFamily : '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing : 'antialiased',
                fontSize : '16px',
                '::placeholder' : {
                    color : '#aab7c4'
                }
            },
            invalid : {
                color : '#fa755a',
                iconColor : '#fa755a'
            }
        },
        init : function() {

            if ( $( 'form#order_review' ).length ) {
                this.form = $( 'form#order_review' ) ;
            } else if ( $( 'form#add_payment_method' ).length ) {
                this.form = $( 'form#add_payment_method' ) ;
            } else {
                this.form = $( 'form.checkout' ) ;
            }

            if ( 0 === this.form.length ) {
                return false ;
            }

            this.initElements() ;

            if ( $( 'form#order_review' ).length || $( 'form#add_payment_method' ).length ) {
                $( document.body ).on( 'payment_method_selected', this.maybeToggleSavedPm ) ;
                this.form.on( 'submit', this.createPaymentMethod ) ;
                this.mayBeMountElements() ;
                this.onVerifyIntentHash() ;
            } else {
                this.form.on( 'checkout_place_order', this.createPaymentMethod ) ;
                $( document.body ).on( 'updated_checkout', this.mayBeMountElements ) ;
                window.addEventListener( 'hashchange', this.onVerifyIntentHash ) ;
            }

            $( document.body ).on( 'checkout_error', this.onCheckoutErr ) ;
        },
        isStripeChosen : function() {
            if ( sumosubscriptions_stripe.payment_method === $( '.payment_methods input[name="payment_method"]:checked' ).val() ) {
                return true ;
            }
            return false ;
        },
        initElements : function() {
            if ( 'inline_cc_form' === sumosubscriptions_stripe.checkoutmode ) {
                sumo_stripe.stripeCard = sumo_stripe.stripeElements.create( 'card', { style : sumo_stripe.styles, hidePostalCode : true } ) ;

                sumo_stripe.stripeCard.addEventListener( 'change', sumo_stripe.onChangeElements ) ;
            } else {
                sumo_stripe.stripeCard = sumo_stripe.stripeElements.create( 'cardNumber', { style : sumo_stripe.styles } ) ;
                sumo_stripe.stripeExp = sumo_stripe.stripeElements.create( 'cardExpiry', { style : sumo_stripe.styles } ) ;
                sumo_stripe.stripeCVC = sumo_stripe.stripeElements.create( 'cardCvc', { style : sumo_stripe.styles } ) ;

                sumo_stripe.stripeCard.addEventListener( 'change', sumo_stripe.onChangeElements ) ;
                sumo_stripe.stripeExp.addEventListener( 'change', sumo_stripe.onChangeElements ) ;
                sumo_stripe.stripeCVC.addEventListener( 'change', sumo_stripe.onChangeElements ) ;
            }
        },
        onChangeElements : function( event ) {
            sumo_stripe.reset() ;

            if ( event.brand ) {
                sumo_stripe.updateCardBrand( event.brand ) ;
            }

            if ( event.error ) {
                sumo_stripe.throwErr( event.error ) ;
            }
        },
        mayBeMountElements : function() {
            if ( sumo_stripe.stripeCard ) {
                sumo_stripe.unmountElements() ;

                if ( $( '#wc-sumo_stripe-cc-form' ).length ) {
                    sumo_stripe.mountElements() ;
                }
            }
        },
        maybeToggleSavedPm : function() {
            // Loop over gateways with saved payment methods
            var $saved_payment_methods = $( 'ul.woocommerce-SavedPaymentMethods' ) ;

            $saved_payment_methods.each( function() {
                $( this ).wc_tokenization_form() ;
            } ) ;
        },
        updateCardBrand : function( brand ) {
            var brandClass = {
                'visa' : 'sumosubsc-stripe-visa-brand',
                'mastercard' : 'sumosubsc-stripe-mastercard-brand',
                'amex' : 'sumosubsc-stripe-amex-brand',
                'discover' : 'sumosubsc-stripe-discover-brand',
                'diners' : 'sumosubsc-stripe-diners-brand',
                'jcb' : 'sumosubsc-stripe-jcb-brand',
                'unknown' : 'sumosubsc-stripe-credit-card-brand'
            } ;

            var imageElement = $( '.sumosubsc-stripe-card-brand' ),
                    imageClass = 'sumosubsc-stripe-credit-card-brand' ;

            if ( brand in brandClass ) {
                imageClass = brandClass[ brand ] ;
            }

            $.each( brandClass, function( i, el ) {
                imageElement.removeClass( el ) ;
            } ) ;

            imageElement.addClass( imageClass ) ;
        },
        mountElements : function() {
            if ( 'inline_cc_form' === sumosubscriptions_stripe.checkoutmode ) {
                sumo_stripe.stripeCard.mount( '#sumosubsc-stripe-card-element' ) ;
            } else {
                sumo_stripe.stripeCard.mount( '#sumosubsc-stripe-card-element' ) ;
                sumo_stripe.stripeExp.mount( '#sumosubsc-stripe-exp-element' ) ;
                sumo_stripe.stripeCVC.mount( '#sumosubsc-stripe-cvc-element' ) ;
            }
        },
        unmountElements : function() {
            if ( 'inline_cc_form' === sumosubscriptions_stripe.checkoutmode ) {
                sumo_stripe.stripeCard.unmount( '#sumosubsc-stripe-card-element' ) ;
            } else {
                sumo_stripe.stripeCard.unmount( '#sumosubsc-stripe-card-element' ) ;
                sumo_stripe.stripeExp.unmount( '#sumosubsc-stripe-exp-element' ) ;
                sumo_stripe.stripeCVC.unmount( '#sumosubsc-stripe-cvc-element' ) ;
            }
        },
        hasPaymentMethod : function() {
            return sumo_stripe.form.find( 'input[name="sumosubsc_stripe_pm"]' ).length > 0 ? true : false ;
        },
        savedPaymentMethodChosen : function() {
            return $( '#payment_method_sumo_stripe' ).is( ':checked' )
                    && $( 'input[name="wc-sumo_stripe-payment-token"]' ).is( ':checked' )
                    && 'new' !== $( 'input[name="wc-sumo_stripe-payment-token"]:checked' ).val() ;
        },
        createPaymentMethod : function() {

            if ( ! sumo_stripe.isStripeChosen() ) {
                sumo_stripe.reset() ;
                return true ;
            }

            if ( sumo_stripe.savedPaymentMethodChosen() ) {
                sumo_stripe.reset() ;
                return true ;
            }

            sumo_stripe.reset( 'no' ) ;

            if ( sumo_stripe.hasPaymentMethod() ) {
                return true ;
            }

            sumo_stripe.reset() ;
            sumo_stripe.blockFormOnSubmit() ;
            sumo_stripe.stripeClient.createPaymentMethod( 'card', sumo_stripe.stripeCard ).then( sumo_stripe.handlePaymentMethodResponse ) ;
            return false ;
        },
        handlePaymentMethodResponse : function( response ) {
            if ( response.error ) {
                sumo_stripe.throwErr( response.error ) ;
            } else {
                sumo_stripe.form.append( '<input type="hidden" class="sumosubsc-stripe-paymentMethod" name="sumosubsc_stripe_pm" value="' + response.paymentMethod.id + '"/>' ) ;
                sumo_stripe.form.submit() ;
            }
        },
        onVerifyIntentHash : function() {
            var hash = window.location.hash.match( /^#?confirm-sumo-stripe-intent-([^:]+):(.+):(.+):(.+)$/ ) ;

            if ( ! hash || 5 > hash.length ) {
                return ;
            }

            var intentClientSecret = hash[1],
                    intentObj = hash[2],
                    endpoint = hash[3],
                    redirectURL = decodeURIComponent( hash[4] ) ;

            //Allow only when the endpoint contains either 'checkout' or 'pay-for-order' or 'add-payment-method'
            if ( 'checkout' !== endpoint && 'pay-for-order' !== endpoint && 'add-payment-method' !== endpoint ) {
                return ;
            }

            sumo_stripe.blockFormOnSubmit() ;
            window.location.hash = '' ;

            if ( 'setup_intent' === intentObj ) {
                sumo_stripe.onConfirmSi( intentClientSecret, redirectURL, endpoint ) ;
            } else if ( 'payment_intent' === intentObj ) {
                sumo_stripe.onConfirmPi( intentClientSecret, redirectURL, endpoint ) ;
            }
            return ;
        },
        onConfirmSi : function( intentClientSecret, redirectURL, endpoint ) {

            sumo_stripe.stripeClient.handleCardSetup( intentClientSecret )
                    .then( function( response ) {
                        if ( response.error ) {
                            throw response.error ;
                        }

                        //Allow only when the Intent succeeded 
                        if ( ! response.setupIntent || 'succeeded' !== response.setupIntent.status ) {
                            return ;
                        }

                        window.location = redirectURL ;
                    } )
                    .catch( function( error ) {
                        sumo_stripe.reset() ;

                        if ( 'pay-for-order' === endpoint || 'add-payment-method' === endpoint ) {
                            return window.location = redirectURL ;
                        }

                        sumo_stripe.throwErr( error ) ;

                        // Report back to the server.
                        $.get( redirectURL + '&is_ajax' ) ;
                    } ) ;
        },
        onConfirmPi : function( intentClientSecret, redirectURL, endpoint ) {

            sumo_stripe.stripeClient.handleCardPayment( intentClientSecret )
                    .then( function( response ) {
                        if ( response.error ) {
                            throw response.error ;
                        }

                        //Allow only when the Intent succeeded 
                        if ( ! response.paymentIntent || 'succeeded' !== response.paymentIntent.status ) {
                            return ;
                        }

                        window.location = redirectURL ;
                    } )
                    .catch( function( error ) {
                        sumo_stripe.reset() ;

                        if ( 'pay-for-order' === endpoint || 'add-payment-method' === endpoint ) {
                            return window.location = redirectURL ;
                        }

                        sumo_stripe.throwErr( error ) ;

                        // Report back to the server.
                        $.get( redirectURL + '&is_ajax' ) ;
                    } ) ;
        },
        onCheckoutErr : function() {
            sumo_stripe.reset( 'yes', 'no' ) ;
        },
        blockFormOnSubmit : function() {
            if ( ! sumo_stripe.form ) {
                return ;
            }

            sumo_stripe.form.block( {
                message : null,
                overlayCSS : {
                    background : '#fff',
                    opacity : 0.6
                }
            } ) ;
        },
        throwErr : function( error ) {
            var message = error.message ;

            // Notify users that the email is invalid.
            if ( 'email_invalid' === error.code ) {
                message = sumosubscriptions_stripe.email_invalid ;
            } else if (
                    /*
                     * Customers do not need to know the specifics of the below type of errors
                     * therefore return a generic localizable error message.
                     */
                    'invalid_request_error' === error.type ||
                    'api_connection_error' === error.type ||
                    'api_error' === error.type ||
                    'authentication_error' === error.type ||
                    'rate_limit_error' === error.type
                    )
            {
                message = sumosubscriptions_stripe.invalid_request_error ;
            }

            if ( 'card_error' === error.type && sumosubscriptions_stripe.hasOwnProperty( error.code ) ) {
                message = sumosubscriptions_stripe[ error.code ] ;
            }

            if ( 'validation_error' === error.type && sumosubscriptions_stripe.hasOwnProperty( error.code ) ) {
                message = sumosubscriptions_stripe[ error.code ] ;
            }

            sumo_stripe.reset() ;
            console.log( error.message ) ; // Leave for troubleshooting.

            if ( $( '.woocommerce-SavedPaymentMethods' ).length ) {
                var $selected_saved_pm = $( 'input[name="wc-sumo_stripe-payment-token"]' ).filter( ':checked' ).closest( '.woocommerce-SavedPaymentMethods-token' ) ;

                if ( $selected_saved_pm.length && $selected_saved_pm.find( '.sumosubsc-stripe-card-errors' ).length ) {
                    $selected_saved_pm.find( '.sumosubsc-stripe-card-errors' ).html( '<ul class="woocommerce_error woocommerce-error"><li /></ul>' ) ;
                    $selected_saved_pm.find( '.sumosubsc-stripe-card-errors' ).find( 'li' ).text( message ) ;
                } else {
                    $( '#wc-sumo_stripe-cc-form' ).find( '.sumosubsc-stripe-card-errors' ).html( '<ul class="woocommerce_error woocommerce-error"><li /></ul>' ) ;
                    $( '#wc-sumo_stripe-cc-form' ).find( '.sumosubsc-stripe-card-errors' ).find( 'li' ).text( message ) ;
                }
            } else {
                $( '.sumosubsc-stripe-card-errors' ).html( '<ul class="woocommerce_error woocommerce-error"><li /></ul>' ) ;
                $( '.sumosubsc-stripe-card-errors' ).find( 'li' ).text( message ) ;
            }

            if ( $( '.sumosubsc-stripe-card-errors' ).length ) {
                $( 'html, body' ).animate( {
                    scrollTop : ( $( '.sumosubsc-stripe-card-errors' ).offset().top - 200 )
                }, 200 ) ;
            }

            if ( sumo_stripe.form ) {
                sumo_stripe.form.removeClass( 'processing' ) ;
                sumo_stripe.form.unblock() ;
            }
        },
        reset : function( remove_pm, remove_notices ) {
            remove_pm = remove_pm || 'yes' ;
            remove_notices = remove_notices || 'yes' ;

            $( '.sumosubsc-stripe-card-errors' ).text( '' ) ;

            if ( 'yes' === remove_pm && 'no' === remove_notices ) {
                $( 'input.sumosubsc-stripe-paymentMethod' ).remove() ;
            } else if ( 'no' === remove_pm && 'yes' === remove_notices ) {
                $( 'div.woocommerce-notices-wrapper, div.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove() ;
            } else {
                $( 'input.sumosubsc-stripe-paymentMethod, div.woocommerce-notices-wrapper, div.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove() ;
            }
        },
    } ;

    try {
        // Create a Stripe client.
        sumo_stripe.stripeClient = Stripe( sumosubscriptions_stripe.key ) ;
        // Create an instance of Elements.
        sumo_stripe.stripeElements = sumo_stripe.stripeClient.elements() ;
        // Init
        sumo_stripe.init() ;
    } catch ( error ) {
        console.log( error ) ;
        return false ;
    }
} ) ;
