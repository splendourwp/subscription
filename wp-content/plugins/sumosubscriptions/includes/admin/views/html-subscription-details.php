<div class="panel-wrap sumosubscriptions">
    <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Subscription', 'sumosubscriptions' ) : esc_attr( $post->post_title ) ; ?>" />
    <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ) ; ?>" />
    <div id="order_data" class="panel">
        <h2 style='float: left;'><?php echo esc_html( sprintf( __( '%s #%s details', 'sumosubscriptions' ), get_post_type_object( $post->post_type )->labels->singular_name, sumo_get_subscription_number( $post->ID ) ) ) ; ?></h2>
        <?php echo sumo_display_subscription_status( $post->ID ) ; ?>
        <p>
            <?php echo sumo_display_subscription_plan( $post->ID ) ; ?>
        </p>
        <p>
            <?php
            if ( SUMO_Subscription_Coupon::subscription_contains_recurring_coupon( $subscription_plan ) && SUMO_Subscription_Coupon::is_coupon_applicable_for_renewal_by_user( $subscription_plan ) ) {
                $subscription_fee_before_discount = 0 ;
                if ( $subscription_plan[ 'subscription_fee' ] > 0 ) {
                    $subscription_fee_before_discount = $subscription_plan[ 'subscription_fee' ] / $subscription_plan[ 'subscription_product_qty' ] ;
                }

                $subscription_fee = SUMO_Subscription_Coupon::get_recurring_discount_amount( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_fee_before_discount, $subscription_plan[ 'subscription_product_qty' ] ) ;
                echo SUMO_Subscription_Coupon::get_recurring_discount_amount_to_display( $subscription_plan[ 'subscription_discount' ][ 'coupon_code' ], $subscription_fee_before_discount, $subscription_plan[ 'subscription_product_qty' ], sumosubs_get_order_currency( $parent_order_id ) ) ;
            }
            ?>
        </p>
        <p class="order_number"><?php
            if ( $payment_method ) {
                $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array() ;

                printf( __( 'Payment via %s', 'sumosubscriptions' ), ( isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ) ) ) ;

                $transaction_id = null ;
                if ( $last_renewed_order ) {
                    $transaction_id = $last_renewed_order->get_transaction_id() ;
                } else if ( $parent_order ) {
                    $transaction_id = $parent_order->get_transaction_id() ;
                }

                if ( $transaction_id ) {
                    if ( isset( $payment_gateways[ $payment_method ] ) && ( $url = $payment_gateways[ $payment_method ]->get_transaction_url( $last_renewed_order ? $last_renewed_order : $parent_order ) ) ) {
                        echo ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)' ;
                    } else {
                        echo ' (' . esc_html( $transaction_id ) . ')' ;
                    }
                }
                echo '. ' ;
            }
            if ( $ip_address = get_post_meta( $parent_order_id, '_customer_ip_address', true ) ) {
                echo __( 'Customer IP', 'sumosubscriptions' ) . ': ' . esc_html( $ip_address ) ;
            }
            ?>
        </p>
        <?php
        if ( $trial_end_date ):
            $is_trial_ended = 'Trial' === $subscription_status || ( sumo_get_subscription_timestamp() <= sumo_get_subscription_timestamp( $trial_end_date )) ? false : true ;
            ?>
            <div id="sumosubscription_trial_info" class="postbox <?php echo postbox_classes( 'sumosubscription_trial_info', get_current_screen()->id ) ?>">
                <button class="handlediv button-link" aria-expanded="true" type="button">
                    <span class="screen-reader-text"><?php _e( 'Trial Information', 'sumosubscriptions' ) ?></span>
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <h2 class="hndle">
                    <span><?php _e( 'Trial Information', 'sumosubscriptions' ) ?></span>
                </h2>
                <div class="inside">
                    <p class="form-field form-field-wide"><label for="order_date"><?php $is_trial_ended ? _e( 'Ended On: ', 'sumosubscriptions' ) : _e( 'Ends On: ', 'sumosubscriptions' ) ?></label>
                        <?php echo '<b>' . sumo_display_subscription_date( $trial_end_date ) . '</b>' ; ?>
                    </p>
                </div>
            </div>
        <?php endif ; ?>
        <div class="order_data_column_container">
            <div class="order_data_column">
                <h4>
                    <?php _e( 'Parent Order', 'sumosubscriptions' ) ; ?>
                </h4>
                <p class="form-field form-field-wide">
                    <?php
                    _e( "<a href=" . admin_url( "post.php?post={$parent_order_id}&action=edit" ) . ">#{$parent_order_id}</a>" ) ;
                    ?>
                </p><br>
                <h4>
                    <?php _e( 'General Details', 'sumosubscriptions' ) ; ?>
                </h4>
                <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Subscription Start date:', 'sumosubscriptions' ) ?></label>
                    <?php
                    if ( ! $is_read_only_mode && sumo_subscription_awaiting_admin_approval( $post->ID ) ) {
                        $subscription_scheduled_date = get_post_meta( $post->ID, 'sumo_subcription_activation_scheduled_on', true ) ;

                        if ( $subscription_scheduled_date ) {
                            echo __( 'Scheduled On', 'sumosubscriptions' ) . ' : ' . '<b>' . sumo_display_subscription_date( $subscription_scheduled_date ) . '</b>' ;
                        }
                        ?>
                        <button class="subscription_start_schedule button"><?php echo $subscription_scheduled_date ? __( 'Reschedule', 'sumosubscriptions' ) : __( 'Schedule', 'sumosubscriptions' ) ; ?></button><br>
                        <span class="subscription_start_schedule_date_picker" style="display: none;">
                            <input type="text" class="date-picker" placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ) ?>"
                                   name="subscription_start_date" id="subscription_start_date" maxlength="6" style="width:180px;" 
                                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            @<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'sumosubscriptions' ) ?>" 
                                    name="subscription_start_hour" id="subscription_start_hour" max="23" min="0" maxlength="2" size="2"
                                    pattern="\-?\d+(\.\d{0,})?" />
                            :<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'sumosubscriptions' ) ?>" 
                                    name="subscription_start_minute" id="subscription_start_minute" max="59" min="0" maxlength="2" size="2"
                                    pattern="\-?\d+(\.\d{0,})?" />
                        </span>
                        <?php
                    } else {
                        echo '<b>' . sumo_display_start_date( $post->ID ) . '</b>' ;
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Subscription End date:', 'sumosubscriptions' ) ?></label>
                    <?php
                    echo '<b>' . sumo_display_end_date( $post->ID ) . '</b>' ;
                    ?>
                </p>
                <?php
                if ( in_array( $subscription_status, array( 'Active', 'Trial', 'Pending_Cancellation' ) ) ) :
                    ?> 
                    <p class="form-field form-field-wide"><label for="order_date"><?php _e( 'Subscription Next Due date:', 'sumosubscriptions' ) ?></label> 
                        <?php
                        if ( '--' === $sub_due_date ) {
                            echo '<b>---</b>' ;
                        } else {
                            ?>
                            <input type="text" class="<?php echo $is_read_only_mode || SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ? '' : 'date-picker' ; ?>"
                                   required placeholder="<?php esc_attr_e( 'YYYY-MM-DD', 'sumosubscriptions' ) ?>"
                                   name="subscription_next_due_date" id="subscription_next_due_date" maxlength="10" style="width:180px;" 
                                   value="<?php echo sumo_get_subscription_date( $sub_due_date, 0, true ) ; ?>" 
                                   <?php echo $is_read_only_mode || SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : '' ; ?> 
                                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            @<input type="number" class="hour" required placeholder="<?php esc_attr_e( 'h', 'sumosubscriptions' ) ?>" 
                                    name="subscription_due_hour" id="subscription_due_hour" max="23" min="0" maxlength="2" size="2" 
                                    value="<?php echo date_i18n( 'H', sumo_get_subscription_timestamp( $sub_due_date ) ) ; ?>" 
                                    <?php echo $is_read_only_mode || SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : '' ; ?> 
                                    pattern="\-?\d+(\.\d{0,})?" />
                            :<input type="number" class="minute" required placeholder="<?php esc_attr_e( 'm', 'sumosubscriptions' ) ?>" 
                                    name="subscription_due_minute" id="subscription_due_minute" max="59" min="0" maxlength="2" size="2" 
                                    value="<?php echo date_i18n( 'i', sumo_get_subscription_timestamp( $sub_due_date ) ) ; ?>" 
                                    <?php echo $is_read_only_mode || SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ? 'readonly' : '' ; ?> 
                                    pattern="\-?\d+(\.\d{0,})?" />
                                    <?php
                                }
                                ?>
                    </p>
                    <?php
                endif ;
                ?>
                <p class="form-field form-field-wide">
                    <?php
                    if ( in_array( $subscription_status, $valid_subscription_statuses ) ) {
                        ?>
                        <label for="order_status"><?php _e( 'Subscription Status:', 'sumosubscriptions' ) ?></label>
                        <select class="wc-enhanced-select" id="subscription_status" name="subscription_status">
                            <option value=""><?php echo str_replace( '_', ' ', $subscription_status ) ; ?></option>
                            <?php
                            if ( apply_filters( 'sumosubscriptions_admin_can_change_subscription_statuses', ( ($awaiting_free_trial_admin_approval = sumosubs_free_trial_awaiting_admin_approval( $post->ID )) || ($awaiting_admin_approval            = sumo_subscription_awaiting_admin_approval( $post->ID )) || ! in_array( $subscription_status, array( 'Pending', 'Overdue', 'Suspended', 'Pending_Cancellation', 'Pending_Authorization' ) ) ), $post->ID ) ) {
                                ?>
                                <optgroup label="<?php _e( 'Change to', 'sumosubscriptions' ) ; ?>">
                                    <?php
                                    switch ( $subscription_status ) {
                                        case 'Trial':
                                        case 'Active':
                                            $statuses = array( 'Pause' => 'Pause' ) ;
                                            break ;
                                        case 'Pause':
                                            $statuses = array( 'Resume' => 'Resume' ) ;
                                            break ;
                                        case 'Pending':
                                            $statuses = array() ;

                                            if ( $awaiting_free_trial_admin_approval ) {
                                                $statuses[ 'Activate-Trial' ] = 'Activate Trial' ;
                                            } else if ( $awaiting_admin_approval ) {
                                                $statuses[ 'Active' ] = 'Active' ;
                                            }
                                            break ;
                                        default :
                                            $statuses = array() ;
                                            break ;
                                    }
                                    $statuses = apply_filters( 'sumosubscriptions_edit_subscription_statuses', $statuses, $post->ID, $parent_order_id, $subscription_status ) ;

                                    if ( is_array( $statuses ) && $statuses ) {
                                        foreach ( $statuses as $status => $status_name ) {
                                            echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, $subscription_status, false ) . '>' . esc_html( $status_name ) . '</option>' ;
                                        }
                                    }
                                    ?>
                                </optgroup>
                                <?php
                            }
                            ?>
                        </select>
                        <?php
                    } else {
                        echo '<b>' . __( 'This Subscription cannot be changed to any other status !!', 'sumosubscriptions' ) . '</b>' ;
                    }
                    ?>
                </p>
                <p class="form-field form-field-wide">
                    <label for="customer_user"><?php _e( 'Customer:', 'sumosubscriptions' ) ; ?></label>
                    <input type="text" class="subscription_buyer_email" required id="subscription_buyer_email" name="subscription_buyer_email" placeholder="<?php esc_attr_e( 'Buyer Email Address', 'sumosubscriptions' ) ; ?>" value="<?php echo get_post_meta( $post->ID, 'sumo_buyer_email', true ) ; ?>" <?php echo $is_read_only_mode ? 'readonly' : '' ; ?> data-allow_clear="true" />
                </p>
                <?php
                if ( ! in_array( sumosubs_get_order_status( $renewal_order_id ), array( 'completed', 'processing', '' ) ) ) :
                    ?>
                    <div class="view_unpaid_renewal_order" style="text-align : right;">
                        <a href="#" id="sumo_view_unpaid_renewal_order"><?php _e( 'View Unpaid Renewal Order', 'sumosubscriptions' ) ?></a>
                        <p id="sumo_unpaid_renewal_order" style="font-weight: bolder;">
                            <a href="<?php echo admin_url( 'post.php?post=' . $renewal_order_id . '&action=edit' ) ; ?>" title="Order #<?php echo $renewal_order_id ; ?>">#<?php echo $renewal_order_id ; ?><br></a>
                        </p>
                    </div>
                    <?php
                endif ;
                ?>
                <p class="form-field form-field-wide">
                    <?php
                    if ( $parent_order && in_array( $subscription_status, $valid_subscription_statuses ) && ! SUMO_Order_Subscription::is_subscribed( $post->ID ) ) {
                        if ( 'auto' === sumo_get_payment_type( $post->ID ) && in_array( $payment_method, array( 'sumo_paypal_preapproval', 'sumosubscription_paypal_adaptive', 'paypal' ) ) ) {
                            $is_read_only_mode = true ;
                        }
                        ?>
                        <label for="customer_user"><?php printf( __( 'Renewal Price: (%s)', 'sumosubscriptions' ), get_woocommerce_currency_symbol( sumosubs_get_order_currency( $parent_order ) ) ) ?></label>
                        <input type="text" style="width:90%" required name="subscription_recurring_fee" placeholder="<?php esc_attr_e( 'Enter the Price', 'sumosubscriptions' ) ; ?>" value="<?php echo $subscription_fee ; ?>" <?php echo $is_read_only_mode ? 'readonly' : '' ; ?> data-allow_clear="true" />&nbsp;<span>x<?php echo $product_qty ; ?></span>
                        <?php
                    }
                    ?>
                </p>
                <?php do_action( 'sumosubscriptions_admin_after_general_details', $post->ID ) ; ?>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php _e( 'Billing Details', 'sumosubscriptions' ) ; ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $parent_order && $parent_order->get_formatted_billing_address() ) {
                        echo '<p><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $parent_order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</p>' ;
                    } else {
                        echo '<p class="none_set"><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong> ' . __( 'No billing address set.', 'sumosubscriptions' ) . '</p>' ;
                    }
                    ?>
                </div>
            </div>
            <div class="order_data_column">
                <h4>
                    <?php SUMO_Subscription_Shipping::is_updated( $subscriber_id ) ? _e( 'Old Shipping Details', 'sumosubscriptions' ) : _e( 'Shipping Details', 'sumosubscriptions' ) ; ?>
                </h4>
                <div class="address">
                    <?php
                    if ( $parent_order && $parent_order->get_formatted_shipping_address() ) {
                        echo '<p><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $parent_order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>' ;
                    } else {
                        echo '<p class="none_set"><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong> ' . __( 'No shipping address set.', 'sumosubscriptions' ) . '</p>' ;
                    }
                    ?>
                </div>
            </div>
            <?php if ( SUMO_Subscription_Shipping::is_updated( $subscriber_id ) ) { ?>
                <div class="order_data_column">
                    <h4>
                        <?php _e( 'Current Shipping Details', 'sumosubscriptions' ) ; ?>
                    </h4>
                    <div class="address">
                        <?php
                        $formatted_address = WC()->countries->get_formatted_address( SUMO_Subscription_Shipping::get_address( $subscriber_id ) ) ;

                        if ( $formatted_address ) {
                            echo '<p><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong>' . wp_kses( $formatted_address, array( 'br' => array() ) ) . '</p>' ;
                        } else {
                            echo '<p class="none_set"><strong>' . __( 'Address', 'sumosubscriptions' ) . ':</strong> ' . __( 'Shipping Address not changed so for.', 'sumosubscriptions' ) . '</p>' ;
                        }
                        ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="clear"></div>
    </div>
</div>
