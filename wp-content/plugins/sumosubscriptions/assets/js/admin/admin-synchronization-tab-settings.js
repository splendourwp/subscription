
jQuery( function ( $ ) {

    $( '#sumo_subscription_synchronize_mode' ).closest( 'tr' ).hide() ;
    $( '#sumo_synchronized_next_payment_date_option' ).closest( 'tr' ).hide() ;
    $( '#sumosubs_payment_for_synced_period' ).closest( 'tr' ).hide() ;
    $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).hide() ;
    $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).hide() ;

    if ( $( '#sumo_synchronize_check_option' ).is( ':checked' ) ) {
        $( '#sumo_subscription_synchronize_mode' ).closest( 'tr' ).show() ;
        $( '#sumo_synchronized_next_payment_date_option' ).closest( 'tr' ).show() ;
        $( '#sumosubs_payment_for_synced_period' ).closest( 'tr' ).show() ;

        if ( $( '#sumosubs_payment_for_synced_period' ).val() === 'prorate' ) {
            $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).show() ;
            $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).show() ;
        }
    }

    $( '#sumo_synchronize_check_option' ).change( function () {
        $( '#sumo_subscription_synchronize_mode' ).closest( 'tr' ).hide() ;
        $( '#sumo_synchronized_next_payment_date_option' ).closest( 'tr' ).hide() ;
        $( '#sumosubs_payment_for_synced_period' ).closest( 'tr' ).hide() ;
        $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).hide() ;
        $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).hide() ;

        if ( this.checked ) {
            $( '#sumo_subscription_synchronize_mode' ).closest( 'tr' ).show() ;
            $( '#sumo_synchronized_next_payment_date_option' ).closest( 'tr' ).show() ;
            $( '#sumosubs_payment_for_synced_period' ).closest( 'tr' ).show() ;

            if ( $( '#sumosubs_payment_for_synced_period' ).val() === 'prorate' ) {
                $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).show() ;
                $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).show() ;
            }
        }
    } ) ;

    $( '#sumosubs_payment_for_synced_period' ).change( function () {
        $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).hide() ;
        $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).hide() ;

        if ( this.value === 'prorate' ) {
            $( '#sumo_prorate_payment_for_selection' ).closest( 'tr' ).show() ;
            $( 'input:radio[name=sumo_prorate_payment_on_selection]' ).closest( 'tr' ).show() ;
        }
    } ) ;
} ) ;
