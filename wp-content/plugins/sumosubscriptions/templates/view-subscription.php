<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$subscription_id     = isset( $_GET[ 'subscription-id' ] ) ? $_GET[ 'subscription-id' ] : $subscription_id ;
$subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true ) ;
$parent_order_id     = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) ;
$next_payment_date   = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true ) ;
$cancel_requested_by = get_post_meta( $subscription_id, 'sumo_subscription_cancel_method_requested_by', true ) ;
$subscription_data   = get_post_meta( $subscription_id, 'sumo_subscription_product_details', true ) ;
$synced              = SUMO_Subscription_Synchronization::is_subscription_synced( $subscription_id ) ? 'yes' : '' ;

if ( $synced ) {
    $show_pause = 'yes' === get_option( 'sumo_sync_pause_resume_option', 'no' ) ;
} else {
    $show_pause = 'yes' === get_option( 'sumo_pause_resume_option' ) ;
}

do_action( 'sumosubscriptions_before_view_subscription_table', $subscription_id, $parent_order_id ) ;
?>
<table class="sumo_subscription_details" data-subscription_id="<?php echo $subscription_id ; ?>" data-subscription_status="<?php echo $subscription_status ; ?>" data-next_payment_date="<?php echo $next_payment_date ; ?>" data-is_synced="<?php echo $synced ; ?>">
    <tr class="subscription_status">
        <td><?php _e( 'Subscription Status', 'sumosubscriptions' ) ?></td>
        <td>:</td>
        <td><?php echo sumo_display_subscription_status( $subscription_id ) ?></td>
    </tr>
    <?php
    if ( $show_pause && sumosubs_is_subscription_eligible_for_pause( $subscription_id ) && apply_filters( 'sumosubscriptions_my_subscription_table_pause_action', true, $subscription_id, $parent_order_id ) ):
        ?>
        <tr class="subscription_pause_r_resume">
            <td><?php _e( 'Pause/Resume', 'sumosubscriptions' ) ?></td>
            <td>:</td>
            <td>
                <?php
                if ( 'Pause' === $subscription_status ) {
                    $auto_resume_on = get_post_meta( $subscription_id, 'sumo_subscription_auto_resume_scheduled_on', true ) ;

                    if ( ! empty( $auto_resume_on ) ) {
                        printf( __( 'Your Subscription will be Automatically Resume on <code>%s</code>/', 'sumosubscriptions' ), $auto_resume_on )
                        ?>
                        <input type="button" class="button subscription-action" data-action="resume" value="<?php _e( 'Resume Now', 'sumosubscriptions' ) ; ?>" />
                        <?php
                    } else {
                        ?>
                        <input type="button" class="button subscription-action" data-action="resume" value="<?php _e( 'Resume', 'sumosubscriptions' ) ; ?>" />
                        <?php
                    }
                } else {
                    if ( 'yes' === get_option( 'sumo_allow_user_to_select_resume_date', 'no' ) ) {
                        ?>
                        <input type="button" class="button subscription-action" data-action="pause" data-resume_before="<?php echo absint( get_option( 'sumo_settings_max_duration_of_pause', '10' ) ) > 0 ? sumo_get_subscription_date( '+' . get_option( 'sumo_settings_max_duration_of_pause', '10' ) . ' days' ) : '' ; ?>" value="<?php _e( 'Pause', 'sumosubscriptions' ) ; ?>" />
                        <?php
                    } else {
                        ?>
                        <input type="button" class="button subscription-action" data-action="pause-submit" data-resume_before="<?php echo absint( get_option( 'sumo_settings_max_duration_of_pause', '10' ) ) > 0 ? sumo_get_subscription_date( '+' . get_option( 'sumo_settings_max_duration_of_pause', '10' ) . ' days' ) : '' ; ?>" value="<?php _e( 'Pause', 'sumosubscriptions' ) ; ?>" />
                        <?php
                    }
                }
                ?>
            </td>
        </tr>
        <?php if ( 'Pause' !== $subscription_status && 'yes' === get_option( 'sumo_allow_user_to_select_resume_date', 'no' ) ) { ?>
            <tr class="subscription_resume_date" style="display:none">
                <td><?php _e( 'Resume Date', 'sumosubscriptions' ) ?></td>
                <td>:</td>
                <td>
                    <input type="text" id="auto-resume-subscription-on" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ) ?>" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"/>
                    <input type="button" class="button subscription-action" id="subscription-pause-submit" data-action="pause-submit" value="<?php _e( 'Submit', 'sumosubscriptions' ) ; ?>"/>
                </td>
            </tr>
        <?php } ?>
    <?php endif ; ?>
    <?php if ( 'yes' === get_option( 'sumo_cancel_option' ) && $cancel_requested_by !== 'admin' && sumosubs_is_subscription_eligible_for_cancel( $subscription_id ) && apply_filters( 'sumosubscriptions_my_subscription_table_cancel_action', true, $subscription_id, $parent_order_id ) ) : ?>
        <tr class="subscription_cancel">
            <td><?php _e( 'Cancel/Revoke', 'sumosubscriptions' ) ?></td>
            <td>:</td>
            <td>
                <?php
                switch ( $subscription_status ) :
                    case 'Trial':
                    case 'Active':
                        $subscription_cancel_methods = sumosubs_get_subscription_cancel_methods() ;

                        if ( ! empty( $subscription_cancel_methods ) ) :
                            ?>
                            <input type="button" class="button subscription-action" data-action="cancel" value="<?php _e( 'Cancel', 'sumosubscriptions' ) ; ?>" />
                            <select id="subscription-cancel-selector" style="display:none">
                                <?php foreach ( $subscription_cancel_methods as $method_key => $method ): ?>
                                    <option value="<?php echo $method_key ; ?>"><?php echo $method ; ?></option>
                                <?php endforeach ; ?>
                            </select>
                            <input type="text" id="subscription-cancel-scheduled-on" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ) ?>" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" style="display:none"/>
                            <input type="button" class="button subscription-action" id="subscription-cancel-submit" data-action="cancel-submit" value="<?php _e( 'Submit', 'sumosubscriptions' ) ; ?>" style="display:none"/>
                            <?php
                        endif ;
                        break ;
                    case 'Pending_Cancellation':
                        switch ( $requested_cancel_method = get_post_meta( $subscription_id, 'sumo_subscription_requested_cancel_method', true ) ) :
                            case 'end_of_billing_cycle':
                                _e( 'Your Subscription will be Cancelled at End of this Billing Cycle.', 'sumosubscriptions' )
                                ?>
                                <input type="button" class="button subscription-action" data-action="cancel-revoke" value="<?php _e( 'Revoke Cancel Request', 'sumosubscriptions' ) ; ?>"/>
                                <?php
                                break ;
                            case 'scheduled_date':
                                printf( __( 'Your Subscription will be Cancelled on <code>%s</code>.', 'sumosubscriptions' ), get_post_meta( $subscription_id, 'sumo_subscription_cancellation_scheduled_on', true ) )
                                ?>
                                <input type="button" class="button subscription-action" data-action="cancel-revoke" value="<?php _e( 'Revoke Cancel Request', 'sumosubscriptions' ) ; ?>"/>
                                <?php
                                break ;
                        endswitch ;
                        break ;
                    case 'Pending':
                    case 'Pause':
                    case 'Suspended':
                    case 'Overdue':
                    case 'Pending_Authorization':
                        ?>
                        <input type="button" class="button subscription-action" data-action="cancel" value="<?php _e( 'Cancel', 'sumosubscriptions' ) ; ?>" />
                        <?php
                        break ;
                endswitch ;
                ?>
            </td>
        </tr>
    <?php endif ; ?>
    <?php
    if ( 'yes' === get_option( 'sumo_allow_subscribers_to_turnoff_auto_payments' ) && 'auto' === sumo_get_payment_type( $subscription_id ) && in_array( $subscription_status, array( 'Active' ) ) ) :
        ?>
        <tr class="subscription_turnoff_auto">
            <td><?php _e( 'Turn off Automatic', 'sumosubscriptions' ) ; ?></td>
            <td>:</td>
            <td><input type="button" class="button subscription-action" data-action="turnoff-auto" value="<?php _e( 'Turn Off automatic', 'sumosubscriptions' ) ; ?>" /></td>
        </tr>
    <?php endif ; ?>
    <?php if ( SUMO_Subscription_Resubscribe::can_subscriber_resubscribe( $subscription_id ) ) : ?>
        <tr class="subscription_resubscribe">
            <td><?php _e( 'Resubscribe ', 'sumosubscriptions' ) ; ?></td>
            <td>:</td>
            <td><input type="button" class="button subscription-action" data-action="resubscribe" value="<?php _e( 'Resubscribe', 'sumosubscriptions' ) ; ?>" /></td>
        </tr>
    <?php endif ; ?>
    <?php if ( 'yes' === get_option( 'sumo_switch_variation_subscription_option' ) && ($variation_switcher = SUMO_Subscription_Variation_Switcher::display( $subscription_id )) ) : ?>
        <tr class="subscription_variation_swapper">
            <td><?php _e( 'Switch Subscription Variation', 'sumosubscriptions' ) ?></td>
            <td>:</td>
            <td><?php echo $variation_switcher ; ?></td>
        </tr>
    <?php endif ; ?>    
    <?php if ( 'yes' === get_option( 'sumo_allow_subscribers_to_change_shipping_address' ) && wc_shipping_enabled() && ! in_array( $subscription_status, array( 'Pending_Cancellation', 'Cancelled', 'Expired', 'Failed' ) ) ) : ?>
        <tr class="change_subscription_shipping_address">
            <td><?php _e( 'Change Shipping Address ', 'sumosubscriptions' ) ; ?></td>
            <td>:</td>
            <td><a href="<?php echo SUMO_Subscription_Shipping::get_shipping_endpoint_url( $subscription_id ) ; ?>" class="button view" ><?php _e( 'Change Shipping Address', 'sumosubscriptions' ) ; ?></a></td>
        </tr>
    <?php endif ; ?>
    <br>
    <tr class="subscription_product_title" style="margin-top: 20px;">
        <td><b><?php _e( 'Subscribed Product ', 'sumosubscriptions' ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php echo sumo_display_subscription_name( $subscription_id, true, true ) ; ?>
            <?php
            if ( SUMO_Subscription_Upgrade_or_Downgrade::can_switch( $subscription_id ) ) :
                ?><a href="<?php echo SUMO_Subscription_Upgrade_or_Downgrade::get_switch_url( $subscription_id ) ; ?>" class="button" ><?php echo SUMO_Subscription_Upgrade_or_Downgrade::get_switch_button_text() ; ?></a>
            <?php endif ; ?>
        </td>
    </tr>
    <?php if ( 'yes' === get_option( 'sumo_allow_subscribers_to_change_qty' ) && ! SUMO_Order_Subscription::is_subscribed( $subscription_id ) && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ): ?> 
        <tr class="subscription_product_quantity" >
            <td><b><?php _e( 'Subscribed Product Qty', 'sumosubscriptions' ) ?></b></td>
            <td>:</td>
            <td>
                <input type="number" name="subscription_qty" id="subscription_qty" min="1" value="<?php echo $subscription_data[ 'product_qty' ] ; ?>" />
                <button class="button subscription-action" data-action="quantity-change"><?php _e( 'Update', 'sumosubscriptions' ) ; ?></button>
            </td>
        </tr>
    <?php endif ; ?>
    <tr class="subscription_plan_message">
        <td><b><?php _e( 'Current Subscription Plan ', 'sumosubscriptions' ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php echo sumo_display_subscription_plan( $subscription_id ) ; ?>
            <?php
            $subscription_plan = sumo_get_subscription_plan( $subscription_id, 0, 0, false ) ;
            if ( SUMO_Subscription_Coupon::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                echo '<p>' . SUMO_Subscription_Coupon::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_plan[ 'subscription_fee' ], $subscription_plan[ 'subscription_product_qty' ], sumosubs_get_order_currency( $parent_order_id ) ) . '</p>' ;
            }
            ?>
        </td>
    </tr>
    <tr class="subscription_start_date">
        <td><b><?php _e( 'Subscription Start Date ', 'sumosubscriptions' ) ; ?></b></td>
        <td>:</td>
        <td><?php echo sumo_display_start_date( $subscription_id ) ; ?></td>
    </tr>
    <tr class="subscription_due_date">
        <td><b><?php _e( 'Subscription Next Due Date ', 'sumosubscriptions' ) ; ?></b></td>
        <td>:</td>
        <td><?php echo sumo_display_next_due_date( $subscription_id ) ; ?></td>
    </tr>
    <tr class="subscription_end_date">
        <td><b><?php _e( 'Subscription End Date ', 'sumosubscriptions' ) ; ?></b></td>
        <td>:</td>
        <td><?php echo sumo_display_end_date( $subscription_id ) ; ?></td>
    </tr>
</table>
<?php if ( 'show' === get_option( 'sumosubs_show_activity_logs', 'show' ) ): ?>
    <table class="subscription_activity_logs">
        <tr> 
            <td style="font-weight: bold"><?php _e( 'Activity Logs ', 'sumosubscriptions' ) ; ?></td>
        </tr>
        <tr>
            <td>
                <?php
                if ( $subscription_notes = sumosubs_get_subscription_notes( array(
                    'subscription_id' => $subscription_id,
                        ) )
                ) {
                    foreach ( $subscription_notes as $index => $note ) :
                        if ( $index < 3 ) {
                            echo '<style type="text/css">.default_subscription_notes' . $index . '{display:block;}</style>' ;
                        }

                        switch ( ! empty( $note->meta[ 'comment_status' ][ 0 ] ) ? $note->meta[ 'comment_status' ][ 0 ] : '' ) :
                            case 'success':
                                ?>
                                <div class="sumo_alert_box _success default_subscription_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            case 'pending':
                                ?>
                                <div class="sumo_alert_box warning default_subscription_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            case 'failure':
                                ?>
                                <div class="sumo_alert_box error default_subscription_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                                <?php
                                break ;
                            default :
                                ?>
                                <div class="sumo_alert_box notice default_subscription_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                            <?php
                        endswitch ;
                    endforeach ;

                    if ( ! empty( $index ) && $index > 3 ) {
                        ?>
                        <a data-flag="more" id="prevent-more-subscription-notes" style="cursor: pointer;"> <?php _e( 'Show More', 'sumosubscriptions' ) ; ?></a>
                        <?php
                    }
                } else {
                    ?>
                    <div class="sumo_alert_box notice">
                        <span><?php _e( 'No Activities Yet.', 'sumosubscriptions' ) ; ?></span>
                    </div>
                    <?php
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
endif ;
do_action( 'sumosubscriptions_after_view_subscription_table', $subscription_id, $parent_order_id ) ;

