<?php
switch ( sumosubs_get_product_type( $post->ID ) ) {
    case 'variable':
        ?>
        <table>
            <tbody>
                <?php
                foreach ( $subscription_variation as $variation_id ) {
                    $_variation = wc_get_product( $variation_id ) ;
                    $selected   = get_post_meta( $variation_id , 'sumosubs_send_payment_reminder_email' , true ) ;
                    ?>
                    <tr>
                        <th><?php echo $_variation->get_formatted_name() ; ?></th>
                    </tr>
                    <tr>
                        <td>
                            <div>
                                <input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo $variation_id ; ?>][auto]" value="yes" <?php echo checked( 'yes' , ('' === $selected || (isset( $selected[ 'auto' ] ) && 'yes' === $selected[ 'auto' ]) ? 'yes' : '' ) ) ; ?>/>
                                <label>
                                    <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubscriptions_automatic_charging_reminder_email' ) ; ?>"><?php _e( 'Subscription Automatic Renewal Reminder' , 'sumosubscriptions' ) ; ?></a>
                                </label>
                            </div>
                            <div>
                                <input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo $variation_id ; ?>][manual]" value="yes" <?php echo checked( 'yes' , ('' === $selected || (isset( $selected[ 'manual' ] ) && 'yes' === $selected[ 'manual' ]) ? 'yes' : '' ) ) ; ?>/>
                                <label>
                                    <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubscriptions_manual_invoice_order_email' ) ; ?>"><?php _e( 'Subscription Invoice - Manual' , 'sumosubscriptions' ) ; ?></a>
                                </label>
                            </div>
                            <input name="sumo_subscription_product_ids[]" type="hidden" value="<?php echo $variation_id ; ?>">
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
        break ;
    default:
        $selected = get_post_meta( $post->ID , 'sumosubs_send_payment_reminder_email' , true ) ;
        ?>
        <div>
            <input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo $post->ID ; ?>][auto]" value="yes" <?php echo checked( 'yes' , ('' === $selected || (isset( $selected[ 'auto' ] ) && 'yes' === $selected[ 'auto' ]) ? 'yes' : '' ) ) ; ?>/>
            <label>
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubscriptions_automatic_charging_reminder_email' ) ; ?>"><?php _e( 'Subscription Automatic Renewal Reminder' , 'sumosubscriptions' ) ; ?></a>
            </label>
        </div>
        <div>
            <input type="checkbox" name="sumosubs_send_payment_reminder_email[<?php echo $post->ID ; ?>][manual]" value="yes" <?php echo checked( 'yes' , ('' === $selected || (isset( $selected[ 'manual' ] ) && 'yes' === $selected[ 'manual' ]) ? 'yes' : '' ) ) ; ?>/>
            <label>
                <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=email&section=sumosubscriptions_manual_invoice_order_email' ) ; ?>"><?php _e( 'Subscription Invoice - Manual' , 'sumosubscriptions' ) ; ?></a>
            </label>
        </div>
        <input name="sumo_subscription_product_ids[]" type="hidden" value="<?php echo $post->ID ; ?>">
        <?php
        break ;
}