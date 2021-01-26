/* global sumosubscriptions_single_product_page */

jQuery( function ( $ ) {
    // sumosubscriptions_single_product_page is required to continue, ensure the object exists
    if ( typeof sumosubscriptions_single_product_page === 'undefined' ) {
        return false;
    }

    /**
     * Check if a node is blocked for processing.
     *
     * @param {JQuery Object} $node
     * @return {bool} True if the DOM Element is UI Blocked, false if not.
     */
    var is_blocked = function ( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
    };

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function ( $node ) {
        if ( !is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            } );
        }
    };

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function ( $node ) {
        $node.removeClass( 'processing' ).unblock();
    };

    var $product_div = $( '.summary, .entry-summary' ).closest( 'div' );
    var $product_variation_div = $( '.variations_form' ).closest( 'form' );

    var single_product = {
        /**
         * Init single product
         */
        init: function () {
            this.populate();

            $( document ).on( 'found_variation.wc-variation-form', { variationForm: this }, this.onFoundVariation );
            $( document ).on( 'click.wc-variation-form', '.reset_variations', { variationForm: this }, this.resetVariationData );
            $( document ).on( 'change', '.variations select', this.toggleVariations );
            $( document ).on( 'click', '#sumosubs_subscribe_optional_trial', this.toggleOptionalPlanSubscribedByUser );
            $( document ).on( 'click', '#sumosubs_subscribe_optional_signup', this.toggleOptionalPlanSubscribedByUser );
        },
        populate: function ( id, type ) {
            id = id || sumosubscriptions_single_product_page.product_id;
            type = type || sumosubscriptions_single_product_page.product_type;

            single_product.product_id = id;
            single_product.product_type = type;

            single_product.block_field = $product_div;
            single_product.html_plan_field = 'p.price';
            single_product.default_plan = $( 'p.price' ).html();

            if ( single_product.product_type === 'variation' ) {
                single_product.block_field = $product_variation_div;
                single_product.html_plan_field = 'span.price';
                single_product.default_plan = $( 'div.woocommerce-variation-price, span.price' ).html();
            }
        },
        getSingleAddToCartVariationData: function () {
            var $hidden_datas = $( 'form' ).find( '#sumosubs_single_variation_data' ).data();

            if ( 'undefined' !== typeof $hidden_datas ) {
                var beforeVariationData = '',
                        afterVariationData = '';

                $.each( $hidden_datas, function ( context, data ) {
                    switch ( context ) {
                        case 'add_to_cart_label_' + single_product.variation_id:
                            $( '.single_add_to_cart_button' ).addClass( 'sumosubs_single_variation_subscribe_button' ).text( data );
                            break;
                        case 'synced_next_payment_date_' + single_product.variation_id:
                            afterVariationData += data;
                            break;
                        case 'plan_message_' + single_product.variation_id:
                        case 'subscription_restricted_message_' + single_product.variation_id:
                        case 'optional_plan_fields_for_user_' + single_product.variation_id:
                            beforeVariationData += data;
                    }
                } );

                if ( '' !== beforeVariationData || '' !== afterVariationData ) {
                    if ( '' !== beforeVariationData ) {
                        $( 'span#sumosubs_before_single_variation' ).html( beforeVariationData );
                    }
                    if ( '' !== afterVariationData ) {
                        $( 'span#sumosubs_after_single_variation' ).html( afterVariationData );
                    }
                }
            }
        },
        onFoundVariation: function ( evt, variation ) {
            single_product.variation_id = variation.variation_id;
            single_product.resetVariationData();

            if ( '' !== single_product.variation_id ) {
                single_product.getSingleAddToCartVariationData();
            }
        },
        toggleVariations: function () {
            single_product.variation_id = $( 'input[name="variation_id"]' ).val();

            if ( '' !== single_product.variation_id ) {
                $.each( $( 'form' ).find( '#sumosubs_single_variations' ).data( 'variations' ), function ( index, variation_id ) {
                    if ( variation_id == single_product.variation_id ) {
                        single_product.getSingleAddToCartVariationData();
                    }
                } );
            } else {
                single_product.resetVariationData();
            }
        },
        resetVariationData: function ( evt, variation ) {
            $( 'span#sumosubs_before_single_variation, span#sumosubs_after_single_variation' ).html( '' );
            $( '.single_add_to_cart_button' ).removeClass( 'sumosubs_single_variation_subscribe_button' ).text( sumosubscriptions_single_product_page.default_add_to_cart_text );
        },
        toggleOptionalPlanSubscribedByUser: function ( evt ) {
            var $this = $( evt.currentTarget );

            if ( 'undefined' === typeof $this.data( 'product_id' ) ) {
                return false;
            }
            single_product.populate( $this.data( 'product_id' ), $this.data( 'product_type' ) );
            single_product.saveSubscribedOptionalPlan();
        },
        saveSubscribedOptionalPlan: function () {

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( single_product.block_field );

            $.ajax( {
                type: 'POST',
                url: sumosubscriptions_single_product_page.wp_ajax_url,
                dataType: 'json',
                data: {
                    action: 'sumosubscription_get_subscribed_optional_plans_by_user',
                    security: sumosubscriptions_single_product_page.get_product_nonce,
                    product_id: single_product.product_id,
                    selected_plans: Array(
                            $( '#sumosubs_subscribe_optional_trial' ).is( ':checked' ) ? $( '#sumosubs_subscribe_optional_trial' ).data( 'plan' ) : '',
                            $( '#sumosubs_subscribe_optional_signup' ).is( ':checked' ) ? $( '#sumosubs_subscribe_optional_signup' ).data( 'plan' ) : ''
                            )
                },
                success: function ( data ) {
                    if ( data === null ) {
                        $( single_product.html_plan_field ).html( single_product.default_plan );
                    } else {
                        if ( data.subscribed_plan !== '' ) {
                            if ( $( 'span#sumosubs_plan_message' ).length > 0 ) {
                                $( 'span#sumosubs_plan_message' ).html( data.subscribed_plan );
                            }
                        }
                        if ( data.next_payment_sync_on !== '' ) {
                            $( 'p#sumosubs_initial_synced_payment_date' ).html( data.next_payment_sync_on )
                        }
                    }
                },
                complete: function () {
                    unblock( single_product.block_field );
                }
            } );
        },
    };

    single_product.init();
} );