<?php

if( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle the Customer Placed Subscription
 * 
 * @class SUMO_Subscription
 * @category Class
 */
class SUMO_Subscription {

    public $id ;
    public $subscribed_product ;
    public $trial                        = array(
        'optional'               => null ,
        'forced'                 => null ,
        'type'                   => null ,
        'fee'                    => null ,
        'duration_period'        => null ,
        'duration_period_length' => null ,
            ) ;
    public $signup                       = array(
        'optional' => null ,
        'forced'   => null ,
        'fee'      => null ,
            ) ;
    public $sync                         = array(
        'type'                                    => null ,
        'subscribed_after_sync_date_type'         => null ,
        'xtra_time_to_charge_full_fee'            => null ,
        'cutoff_time_to_not_renew_nxt_subs_cycle' => null ,
        'start_year'                              => null ,
        'duration_period'                         => null ,
        'duration_period_length'                  => null ,
            ) ;
    public $additional_digital_downloads = array(
        'products' => null ,
            ) ;
    public $payment_reminder_email_for   = array(
        'auto'   => 'yes' ,
        'manual' => 'yes' ,
            ) ;
    public $recurring_amount ;
    public $subscribed_qty               = 1 ;
    public $coupons ;
    public $installments ;
    public $subscription ;
    public $item_meta ;

    public function __construct( $subscription ) {
        if( $subscription instanceof SUMO_Subscription ) {
            $this->id           = $subscription->id ;
            $this->subscription = get_post( $this->id ) ;
            $this->populate( $subscription->item_meta ) ;
        } else if( is_numeric( $subscription ) ) {
            $this->id           = absint( $subscription ) ;
            $this->subscription = get_post( $this->id ) ;
            $this->populate() ;
        }
    }

    protected function populate( $item_meta = null ) {
        if( is_null( $item_meta ) ) {
            $this->item_meta = SUMO_Subscription_Factory::get_subscription( $this ) ;
        } else {
            $this->item_meta = $item_meta ;
        }

        $this->get_subscribed_product() ;
        $this->get_recurring_amount() ;
        $this->get_trial() ;
        $this->get_signup() ;
        $this->get_sync() ;
        $this->get_coupons() ;
        $this->get_installments() ;
        $this->get_subscribed_qty() ;
        $this->get_additional_digital_downloads() ;
        $this->send_payment_reminder_email_for() ;
    }

    public function get_id() {
        return $this->id ;
    }

    public function get_duration_period() {
        return $this->item_meta[ 'subperiod' ] ;
    }

    public function get_duration_period_length() {
        return $this->item_meta[ 'subperiodvalue' ] ;
    }

    public function get_trial( $context = '' ) {
        if( $this->has_trial() ) {
            $this->trial = array(
                'optional'               => '3' === $this->item_meta[ 'trial_selection' ] ,
                'forced'                 => '1' === $this->item_meta[ 'trial_selection' ] ,
                'type'                   => ('paid' === $this->item_meta[ 'fee_type' ] && is_numeric( $this->item_meta[ 'trialfee' ] ) && $this->item_meta[ 'trialfee' ] > 0) ? 'paid' : 'free' ,
                'fee'                    => ('paid' === $this->item_meta[ 'fee_type' ] && is_numeric( $this->item_meta[ 'trialfee' ] ) && $this->item_meta[ 'trialfee' ] > 0) ? $this->item_meta[ 'trialfee' ] : '' ,
                'duration_period'        => $this->item_meta[ 'trialperiod' ] ,
                'duration_period_length' => $this->item_meta[ 'trialperiodvalue' ] ,
                    ) ;
        }
        return '' === $context ? $this->trial : $this->trial[ $context ] ;
    }

    public function get_signup( $context = '' ) {
        if( $this->has_signup() ) {
            $this->signup = array(
                'optional' => '3' === $this->item_meta[ 'signusumoee_selection' ] ,
                'forced'   => '1' === $this->item_meta[ 'signusumoee_selection' ] ,
                'fee'      => $this->item_meta[ 'signup_fee' ] ,
                    ) ;
        }
        return '' === $context ? $this->signup : $this->signup[ $context ] ;
    }

    public function get_sync( $context = '' ) {
        if( $this->is_synced() ) {
            $this->sync = array(
                'type'                                    => $this->item_meta[ 'synchronize_mode' ] ,
                'subscribed_after_sync_date_type'         => $this->item_meta[ 'subscribed_after_sync_date_type' ] ,
                'xtra_time_to_charge_full_fee'            => absint( $this->item_meta[ 'xtra_time_to_charge_full_fee' ] ) ,
                'cutoff_time_to_not_renew_nxt_subs_cycle' => absint( $this->item_meta[ 'cutoff_time_to_not_renew_nxt_subs_cycle' ] ) ,
                'start_year'                              => $this->item_meta[ 'synchronize_start_year' ] ,
                'duration_period'                         => null ,
                'duration_period_length'                  => (in_array( $this->get_duration_period() , array( 'M' , 'Y' ) ) && $this->item_meta[ 'synchronization_period_value' ] > 0 ) ? $this->item_meta[ 'synchronization_period_value' ] : '' ,
                    ) ;

            if( 'W' === $this->get_duration_period() ) {
                $this->sync[ 'duration_period' ] = $this->item_meta[ 'synchronization_period' ] > 0 ? $this->item_meta[ 'synchronization_period' ] : '' ;
            } else if( '1' === $this->item_meta[ 'synchronize_mode' ] ) {
                if( 'M' === $this->get_duration_period() ) {
                    $this->sync[ 'duration_period' ] = ((0 === 12 % $this->get_duration_period_length() || '24' === $this->get_duration_period_length()) && $this->item_meta[ 'synchronization_period_value' ] > 0) ? $this->item_meta[ 'synchronization_period' ] : '' ;
                } else if( 'Y' === $this->get_duration_period() ) {
                    $this->sync[ 'duration_period' ] = $this->item_meta[ 'synchronization_period_value' ] > 0 ? $this->item_meta[ 'synchronization_period' ] : '' ;
                }
            }
        }
        return '' === $context ? $this->sync : $this->sync[ $context ] ;
    }

    public function get_additional_digital_downloads( $context = '' ) {
        if( $this->has_additional_digital_downloads() ) {
            $this->additional_digital_downloads = array(
                'products' => $this->item_meta[ 'downloadable_products' ] ,
                    ) ;
        }
        return '' === $context ? $this->additional_digital_downloads : $this->additional_digital_downloads[ $context ] ;
    }

    public function send_payment_reminder_email_for() {
        if( '' !== $this->item_meta[ 'send_payment_reminder_email' ] ) {
            $this->payment_reminder_email_for = $this->item_meta[ 'send_payment_reminder_email' ] ;
        }
        return $this->payment_reminder_email_for ;
    }

    public function get_recurring_amount() {
        $this->recurring_amount = sumo_get_recurring_fee( $this->id ) ;
        return $this->recurring_amount ;
    }

    public function get_coupons() {
        $this->coupons = ! empty( $this->item_meta[ 'subscription_discount' ][ 'coupon_code' ] ) ? $this->item_meta[ 'subscription_discount' ][ 'coupon_code' ] : '' ;
        return $this->coupons ;
    }

    public function get_installments() {
        $this->installments = absint( $this->item_meta[ 'instalment' ] ) ;
        return $this->installments ;
    }

    public function get_subscribed_qty() {
        $this->subscribed_qty = absint( $this->item_meta[ 'product_qty' ] ) ;
        return $this->subscribed_qty ;
    }

    public function get_subscribed_product() {
        $this->subscribed_product = absint( $this->item_meta[ 'productid' ] ) ;
        return $this->subscribed_product ;
    }

    public function get_parent_id() {
        return absint( $this->item_meta[ 'variation_product_level_id' ] ) ;
    }

    public function exists() {
        return $this->subscription && 'sumosubscriptions' === get_post_type( $this->subscription ) && 'publish' === $this->subscription->post_status ? true : false ;
    }

    public function has_trial() {
        return in_array( $this->item_meta[ 'trial_selection' ] , array( '1' , '3' ) ) ;
    }

    public function has_signup() {
        return (in_array( $this->item_meta[ 'signusumoee_selection' ] , array( '1' , '3' ) ) && is_numeric( $this->item_meta[ 'signup_fee' ] ) && $this->item_meta[ 'signup_fee' ] >= 0) ;
    }

    public function is_synced() {
        return '1' === $this->item_meta[ 'synchronization_status' ] ;
    }

    public function has_additional_digital_downloads() {
        return '1' === $this->item_meta[ 'additional_digital_downloads_status' ] ;
    }

}
