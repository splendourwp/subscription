<?php
if ( ! $renewed_count ) {
    ?>
    <div>
        <?php _e( 'No orders renewed' , 'sumosubscriptions' ) ; ?>
    </div>
    <?php
} else {
    ?>
    <div class="inside">
        <table class="sumosubscriptions_footable" data-filter="#filter" data-page-size="5" data-page-previous-text="prev" data-filter-text-only="true" data-page-next-text="next" style="width: 100%" >
            <thead>
                <tr align="center" style="font-weight:bold;">
                    <td><?php _e( 'Order Number' , 'sumosubscriptions' ) ; ?></td>
                    <td><?php _e( 'Order Status Updated On' , 'sumosubscriptions' ) ; ?></td>
                </tr>
            </thead>
            <?php
            foreach ( $renewal_orders as $renewal_order_id ) :
                if ( in_array( sumosubs_get_order_status( $renewal_order_id ) , array ( 'completed' , 'processing' ) ) ) :
                    $renewed_date = sumo_display_renewed_order_date( $renewal_order_id ) ;
                    ?>
                    <tr align="center">
                        <td>
                            <?php _e( "<a href=post.php?post=$renewal_order_id&action=edit>#$renewal_order_id</a><br>" , 'sumosubscriptions' ) ; ?>
                        </td>
                        <td>
                            <?php _e( "$renewed_date<br>" , 'sumosubscriptions' ) ; ?>
                        </td>
                    </tr>
                    <?php
                endif ;
            endforeach ;
            ?>
        </table>
        <div class="pagination pagination-centered"></div>
    </div>
    <a href="edit.php?post_type=sumosubscriptions"><?php _e( 'Back to List' , 'sumosubscriptions' ) ; ?></a>
    <?php
}

