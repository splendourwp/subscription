<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}
global $wp ;
if ( ! empty( $subscriptions ) ) {
    if ( 'show' === get_option( 'sumosubs_show_pagination_and_search', 'show' ) ) {
        ?>
        <p class="sumo_my_subscriptions-filter" style="display:inline-table">
            <?php _e( 'Search:', 'sumosubscriptions' ) ?>
            <input id="filter" type="text" style="width: 40%"/>&nbsp;
            <?php _e( 'Page Size:', 'sumosubscriptions' ) ?>
            <input id="change-page-size" type="number" min="5" step="5" value="5" style="width: 25%"/>
        </p>
        <?php
    }
    ?>            
    <table class="shop_table shop_table_responsive my_account_orders sumosubscriptions_footable" data-filter="#filter" data-page-size="5" data-page-previous-text="prev" data-filter-text-only="true" data-page-next-text="next" style="width: 100%">
        <thead>
            <tr>
                <th class="sumosubscriptions-subsc-number"><span class="nobr"><?php _e( 'ID', 'sumosubscriptions' ) ; ?></span></th>
                <th class="sumosubscriptions-subsc-title"><span class="nobr"><?php _e( 'Product', 'sumosubscriptions' ) ; ?></span></th>
                <th class="sumosubscriptions-subsc-plan"><span class="nobr"><?php _e( 'Plan', 'sumosubscriptions' ) ; ?></span></th>
                <th class="sumosubscriptions-subsc-status"><span class="nobr"><?php _e( 'Status', 'sumosubscriptions' ) ; ?></span></th>
                <th data-sort-ignore="true">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $subscriptions as $subscription_id ) :
                //may be used in View Subscription page
                $wp->query_vars[ 'view-subscription' ] = $subscription_id ;
                ?>
                <tr class="sumosubscriptions-data">
                    <td class="sumosubscriptions-subsc-number" data-title="<?php _e( 'Subscription Number', 'sumosubscriptions' ) ; ?>">
                        <?php echo sumo_display_subscription_ID( $subscription_id ) ; ?>
                    </td>
                    <td class="sumosubscriptions-subsc-title" data-title="<?php _e( 'Subscription Title', 'sumosubscriptions' ) ; ?>">
                        <?php echo sumo_display_subscription_name( $subscription_id, false, true ) ; ?>
                    </td>
                    <td class="sumosubscriptions-subsc-plan" data-title="<?php _e( 'Subscription Message', 'sumosubscriptions' ) ; ?>">
                        <?php echo sumo_display_subscription_plan( $subscription_id ) ; ?>
                        <?php
                        $subscription_plan                     = sumo_get_subscription_plan( $subscription_id, 0, 0, false ) ;
                        if ( SUMO_Subscription_Coupon::subscription_contains_recurring_coupon( $subscription_plan ) ) {
                            echo '<p>' . SUMO_Subscription_Coupon::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_plan[ 'subscription_fee' ], $subscription_plan[ 'subscription_product_qty' ], sumosubs_get_order_currency( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) ) ) . '</p>' ;
                        }
                        ?>
                    </td>
                    <td class="sumosubscriptions-subsc-status" data-title="<?php _e( 'Subscription Status', 'sumosubscriptions' ) ; ?>">
                        <?php echo sumo_display_subscription_status( $subscription_id ) ; ?>
                    </td>
                    <td class="sumosubscriptions-view">
                        <a href="<?php echo sumo_get_subscription_endpoint_url( $subscription_id ) ; ?>" class="button view" data-action="view"><?php _e( 'View', 'sumosubscriptions' ) ; ?></a>
                    </td>
                </tr>
            <?php endforeach ; ?>
        </tbody>
    </table>
    <?php
    if ( 'show' === get_option( 'sumosubs_show_pagination_and_search', 'show' ) ) {
        ?><div class="pagination pagination-centered"></div><?php
    }
} else {
    ?>
    <div class="sumosubscription_not_found woocommerce-Message woocommerce-Message--info woocommerce-info">
        <p>
            <?php _e( "You don't have any subscription.", 'sumosubscriptions' ) ; ?>
        </p>
    </div>
    <?php
}