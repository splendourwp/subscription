<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

/**
 * Handle Admin metaboxes.
 * 
 * @class SUMOSubscriptions_Admin_Metaboxes
 * @category Class
 */
class SUMOSubscriptions_Admin_Metaboxes {

    /**
     * SUMOSubscriptions_Admin_Metaboxes constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) ) ;
        add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) ) ;
        add_action( 'admin_head', array( $this, 'set_default_metaboxes_position' ), 99999 ) ;
        add_action( 'post_updated_messages', array( $this, 'display_admin_post_messages' ) ) ;
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 3 ) ;
        add_action( 'init', array( $this, 'revoke_subscription_cancel_request' ) ) ;
    }

    /**
     * Add Metaboxes.
     * @global object $post
     */
    public function add_meta_boxes() {
        global $post ;

        add_meta_box( 'sumosubscription_details', __( 'Subscription Details', 'sumosubscriptions' ), array( $this, 'render_subscription_details' ), 'sumosubscriptions', 'normal', 'high' ) ;
        add_meta_box( 'sumosubscription_actions', __( 'Actions', 'sumosubscriptions' ), array( $this, 'render_subscription_actions' ), 'sumosubscriptions', 'side', 'default' ) ;
        add_meta_box( 'sumosubscription_items', __( 'Subscription Item(s)', 'sumosubscriptions' ), array( $this, 'render_subscription_items' ), 'sumosubscriptions', 'normal', 'default' ) ;
        add_meta_box( 'sumosubscription_log_information', __( 'Log History', 'sumosubscriptions' ), array( $this, 'render_subscription_notes' ), 'sumosubscriptions', 'side', 'default' ) ;
        add_meta_box( 'sumosubscription_recurring_info', __( 'Recurring Information', 'sumosubscriptions' ), array( $this, 'render_subscription_recurring_info' ), 'sumosubscriptions', 'side', 'default' ) ;
        add_meta_box( 'sumosubscription_successful_renewals', __( 'Successful Renewal Orders', 'sumosubscriptions' ), array( $this, 'render_successful_renewal_orders' ), 'sumosubscriptions', 'normal', 'default' ) ;
        add_meta_box( 'sumosubscription_cancel_methods', __( 'Subscription Cancel Methods', 'sumosubscriptions' ), array( $this, 'render_subscription_cancel_methods' ), 'sumosubscriptions', 'side', 'default' ) ;

        if ( sumo_is_subscription_product( $post->ID ) || sumo_is_product_contains_subscription_variations( $post->ID ) ) {
            add_meta_box( 'sumosubscription_synced_next_payment_dates', __( 'Synchronized Payment Dates', 'sumosubscriptions' ), array( $this, 'render_subscription_recurring_info' ), 'product', 'side', 'low' ) ;
            add_meta_box( 'sumosubscription_send_payment_reminder_email', __( 'Send Payment Reminder Email', 'sumosubscriptions' ), array( $this, 'render_payment_reminder_email_actions' ), 'product', 'side', 'low' ) ;
        }
    }

    /**
     * Remove Metaboxes.
     */
    public function remove_meta_boxes() {
        global $post ;

        remove_meta_box( 'submitdiv', 'sumosubscriptions', 'side' ) ;
        remove_meta_box( 'commentsdiv', 'sumosubscriptions', 'normal' ) ;

        if ( 'sumosubscriptions' === get_post_type() ) {
            $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true ) ;
            $parent_order_id     = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true ) ;

            if ( ! in_array( $subscription_status, apply_filters( 'sumosubscriptions_valid_subscription_statuses_to_become_active_subscription', array( 'Active', 'Trial', 'Overdue', 'Suspended', 'Pause', 'Pending', 'Pending_Cancellation', 'Pending_Authorization' ), $post->ID, $parent_order_id ) ) ) {
                remove_meta_box( 'sumosubscription_cancel_methods', 'sumosubscriptions', 'side' ) ;
            }
        }
    }

    /**
     * Set default metaboxes positions
     */
    public function set_default_metaboxes_position() {

        if ( 'sumosubscriptions' === get_post_type() ) {
            if ( ! $user = wp_get_current_user() ) {
                return ;
            }

            if ( false === get_user_option( 'meta-box-order_sumosubscriptions', $user->ID ) ) {
                delete_user_option( $user->ID, 'meta-box-order_sumosubscriptions', true ) ;
                update_user_option( $user->ID, 'meta-box-order_sumosubscriptions', array(
                    'side'     => 'sumosubscription_actions,sumosubscription_cancel_methods,sumosubscription_recurring_info,sumosubscription_log_information',
                    'normal'   => 'sumosubscription_details,slugdiv,sumosubscription_items,sumosubscription_successful_renewals',
                    'advanced' => ''
                        ), true ) ;
            }
            if ( false === get_user_option( 'screen_layout_sumosubscriptions', $user->ID ) ) {
                delete_user_option( $user->ID, 'screen_layout_sumosubscriptions', true ) ;
                update_user_option( $user->ID, 'screen_layout_sumosubscriptions', 'auto', true ) ;
            }
        }
    }

    /**
     * Display updated Subscription post message.
     * @param array $messages
     * @return string
     */
    public function display_admin_post_messages( $messages ) {
        $messages[ 'sumosubscriptions' ] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Subscription updated.', 'sumosubscriptions' ),
            2 => __( 'Custom field updated.', 'sumosubscriptions' ),
            4 => __( 'Subscription updated.', 'sumosubscriptions' ) ) ;

        return $messages ;
    }

    /**
     * Revoke Subscription Cancel request by Admin.
     */
    public function revoke_subscription_cancel_request() {
        if ( isset( $_GET[ '_sumosubsnonce' ], $_GET[ 'post' ], $_GET[ 'request' ] ) ) {
            if ( wp_verify_nonce( $_GET[ '_sumosubsnonce' ], $_GET[ 'post' ] ) && 'revoke_cancel' === $_GET[ 'request' ] ) {
                sumosubs_revoke_cancel_request( $_GET[ 'post' ], __( 'Admin has Revoked the Cancel request.', 'sumosubscriptions' ) ) ;
            }
            wp_safe_redirect( remove_query_arg( array( 'request', '_sumosubsnonce' ) ) ) ;
        }
    }

    /**
     * Meta Box showing Subscription actions.
     * @param object $post The post object.
     */
    public function render_subscription_actions( $post ) {
        $parent_order_id      = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true ) ;
        $renewal_order_id     = get_post_meta( $post->ID, 'sumo_get_renewal_id', true ) ;
        $subscription_status  = 'Pending_Cancellation' === get_post_meta( $post->ID, 'sumo_get_status', true ) ? get_post_meta( $post->ID, 'sumo_subscription_previous_status', true ) : get_post_meta( $post->ID, 'sumo_get_status', true ) ;
        $is_invoice_present   = is_numeric( $renewal_order_id ) && $renewal_order_id > 0 ? true : false ;
        $is_automatic_payment = 'auto' === sumo_get_payment_type( $post->ID ) ? true : false ;

        include('views/html-subscription-actions.php') ;
    }

    /**
     * Meta Box showing Subscription details.
     * @param object $post The post object.
     */
    public function render_subscription_details( $post ) {
        wp_nonce_field( 'sumosubscriptions_save_data', 'sumosubscriptions_meta_nonce' ) ;

        $subscription_plan   = sumo_get_subscription_plan( $post->ID ) ;
        $parent_order_id     = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true ) ;
        $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true ) ;
        $sub_due_date        = get_post_meta( $post->ID, 'sumo_get_next_payment_date', true ) ;
        $trial_end_date      = get_post_meta( $post->ID, 'sumo_get_trial_end_date', true ) ;
        $subscriber_id       = get_post_meta( $post->ID, 'sumo_get_user_id', true ) ;
        $renewal_order_id    = get_post_meta( $post->ID, 'sumo_get_renewal_id', true ) ;
        $product_qty         = ! empty( $subscription_plan[ 'subscription_product_qty' ] ) ? absint( $subscription_plan[ 'subscription_product_qty' ] ) : 1 ;
        $subscription_fee    = SUMO_Order_Subscription::is_subscribed( $post->ID ) ? 0 : sumo_get_recurring_fee( $post->ID, array(), 0, false ) ;
        $payment_method      = ( $payment_method      = sumo_get_subscription_payment_method( $post->ID ) ) ? $payment_method : sumosubs_get_order_payment_method( $parent_order_id ) ;
        $parent_order        = wc_get_order( $parent_order_id ) ;
        $last_renewed_order  = sumo_get_last_renewed_order( $post->ID ) ;

        $valid_subscription_statuses = apply_filters( 'sumosubscriptions_valid_subscription_statuses_to_become_active_subscription', array( 'Active', 'Trial', 'Overdue', 'Suspended', 'Pause', 'Pending', 'Pending_Cancellation', 'Pending_Authorization' ), $post->ID, $parent_order_id ) ;
        $is_read_only_mode           = apply_filters( 'sumosubscriptions_edit_subscription_page_readonly_mode', ('Pending_Cancellation' === $subscription_status ), $post->ID, $parent_order_id ) ? true : false ;

        include('views/html-subscription-details.php') ;
    }

    /**
     * Meta Box showing Subscription items.
     * @param object $post The post object.
     */
    public function render_subscription_items( $post ) {
        $subscription_plan = sumo_get_subscription_plan( $post->ID ) ;
        $order_id          = get_post_meta( $post->ID, 'sumo_get_parent_order_id', true ) ;
        $user_id           = get_post_meta( $post->ID, 'sumo_get_user_id', true ) ;
        $data              = get_post_meta( $order_id ) ;

        if ( $order = wc_get_order( $order_id ) ) {
            include( 'views/html-order-items.php' ) ;
        }
    }

    /**
     * Meta Box showing Subscription next possible forthcoming payment dates.
     * @param object $post
     */
    public function render_subscription_recurring_info( $post ) {

        switch ( get_post_type() ) {
            case 'sumosubscriptions':
                $next_payment_dates  = sumosubs_get_possible_next_payment_dates( $post->ID, 0, true ) ;
                $next_payment_dates  = array_map( 'sumo_display_subscription_date', $next_payment_dates ) ;
                $subscription_status = get_post_meta( $post->ID, 'sumo_get_status', true ) ;

                if ( $next_payment_dates && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ) {
                    $label = 1 === sizeof( $next_payment_dates ) ? __( 'Next Payment on: ', 'sumosubscriptions' ) : __( 'Next Payments on: ', 'sumosubscriptions' ) . '<br>' ;

                    //Display Next Possible forthcoming Payment Dates in Subscription Backend.
                    echo '<b>' . $label . '</b>' . implode( '<br>', $next_payment_dates ) ;
                } else {
                    echo '--' ;
                }
                break ;
            case 'product':
                switch ( sumosubs_get_product_type( $post->ID ) ) {
                    case 'variable':
                        $subscription_variation = sumo_get_available_subscription_variations( $post->ID, 10 ) ;
                        ?>
                        <table>
                            <tbody>
                                <?php
                                foreach ( $subscription_variation as $variation_id ) {
                                    $_variation = wc_get_product( $variation_id ) ;
                                    ?>
                                    <tr>
                                        <th><?php echo $_variation->get_formatted_name() ; ?></th>
                                    <tr>
                                        <td>
                                            <?php
                                            if ( SUMO_Subscription_Synchronization::is_subscription_synced( $variation_id ) ) {
                                                $next_payment_dates = sumosubs_get_possible_next_payment_dates( 0, $variation_id, true ) ;
                                                $next_payment_dates = array_map( 'sumo_display_subscription_date', $next_payment_dates ) ;
                                                echo implode( '<br>', $next_payment_dates ) ;
                                            } else {
                                                echo '--' ;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php
                        break ;
                    default:
                        if ( SUMO_Subscription_Synchronization::is_subscription_synced( $post->ID ) ) {
                            $next_payment_dates = sumosubs_get_possible_next_payment_dates( 0, $post->ID, true ) ;
                            $next_payment_dates = array_map( 'sumo_display_subscription_date', $next_payment_dates ) ;
                            echo implode( '<br>', $next_payment_dates ) ;
                        } else {
                            echo '--' ;
                        }
                        break ;
                }
                break ;
        }
    }

    /**
     * Meta Box showing Subscription renewed order date informations.
     * @param object $post
     */
    public function render_successful_renewal_orders( $post ) {
        $renewal_orders = get_post_meta( $post->ID, 'sumo_get_every_renewal_ids', true ) ;
        $renewed_count  = sumosubs_get_renewed_count( $post->ID ) ;

        include( 'views/html-renewed_orders.php' ) ;
    }

    /**
     * Meta Box showing Subscription Cancel method status
     * @param object $post
     */
    public function render_subscription_cancel_methods( $post ) {
        $subscription_status     = get_post_meta( $post->ID, 'sumo_get_status', true ) ;
        $requested_cancel_method = get_post_meta( $post->ID, 'sumo_subscription_requested_cancel_method', true ) ;
        $next_payment_date       = get_post_meta( $post->ID, 'sumo_get_next_payment_date', true ) ;
        $cancel_scheduled_on     = 'end_of_billing_cycle' === $requested_cancel_method ? $next_payment_date : get_post_meta( $post->ID, 'sumo_subscription_cancellation_scheduled_on', true ) ;

        include( 'views/html-subscription-cancel-methods.php' ) ;
    }

    /**
     * Meta Box showing Subscription payment reminder email whether to send or not to the specific product.
     * @param object $post
     */
    public function render_payment_reminder_email_actions( $post ) {
        $subscription_variation = sumo_get_available_subscription_variations( $post->ID, 10 ) ;

        include( 'views/html-subscription-reminder-email-actions.php' ) ;
    }

    /**
     * Meta Box showing Subscription log information.
     * @param object $post The post object.
     */
    public function render_subscription_notes( $post ) {
        $notes = sumosubs_get_subscription_notes( array(
            'subscription_id' => $post->ID,
                ) ) ;

        include( 'views/html-subscription-notes.php' ) ;
    }

    /**
     * Save subscription metabox data.
     * @param int $post_id The post ID.
     * @param object $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function save_meta_boxes( $post_id, $post, $update ) {
        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return ;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return ;
        }

        // Check the nonce
        if ( ! isset( $_POST[ 'sumosubscriptions_meta_nonce' ] ) || empty( $_POST[ 'sumosubscriptions_meta_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'sumosubscriptions_meta_nonce' ], 'sumosubscriptions_save_data' ) ) {
            return ;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST[ 'post_ID' ] ) || $_POST[ 'post_ID' ] != $post_id ) {
            return ;
        }

        // Check user has permission to edit
        if ( 'sumosubscriptions' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
            return ;
        }

        $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true ) ;
        $next_payment_date   = get_post_meta( $post_id, 'sumo_get_next_payment_date', true ) ;
        $parent_order_id     = get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) ;
        $renewal_order_id    = get_post_meta( $post_id, 'sumo_get_renewal_id', true ) ;
        $buyer_email         = get_post_meta( $post_id, 'sumo_buyer_email', true ) ;
        $payment_method      = ( $payment_method      = sumo_get_subscription_payment_method( $post_id ) ) ? $payment_method : sumosubs_get_order_payment_method( $parent_order_id ) ;

        //Subscription Buyer email updation by the Admin
        if ( isset( $_POST[ 'subscription_buyer_email' ] ) && ! empty( $_POST[ 'subscription_buyer_email' ] ) ) {
            $new_email_address = $_POST[ 'subscription_buyer_email' ] != $buyer_email ? $_POST[ 'subscription_buyer_email' ] : '' ;

            if ( ! filter_var( $new_email_address, FILTER_VALIDATE_EMAIL ) === false ) {

                update_post_meta( $post_id, 'sumo_buyer_email', $new_email_address ) ;

                $note = sprintf( __( 'Admin has Changed the Subscription Buyer Email to %s. Customer will be notified via email by this Mail ID only.', 'sumosubscriptions' ), $new_email_address ) ;

                sumo_add_subscription_note( $note, $post_id, sumo_note_status( $subscription_status ), __( 'Buyer Email Changed Manually', 'sumosubscriptions' ) ) ;
            }
        }
        //Recurring Fee updation by the Admin
        if ( isset( $_POST[ 'subscription_recurring_fee' ] ) && ! SUMO_Order_Subscription::is_subscribed( $post_id ) ) {
            $new_renewal_fee = wc_format_decimal( $_POST[ 'subscription_recurring_fee' ] ) ;
            $old_renewal_fee = sumo_get_recurring_fee( $post_id, array(), 0, false ) ;

            if ( is_numeric( $new_renewal_fee ) && $new_renewal_fee != $old_renewal_fee ) {

                if ( 'auto' === sumo_get_payment_type( $post_id ) && in_array( $payment_method, array( 'sumo_paypal_preapproval', 'sumosubscription_paypal_adaptive', 'paypal' ) ) ) {
                    //Warning !! Do not update the renewal fee. Preapproved amount should not be greater than the Admin entered fee. It results in payment error.
                } else {
                    update_post_meta( $post_id, 'sumo_get_updated_renewal_fee', $new_renewal_fee ) ;

                    $note = sprintf( __( 'Admin has Changed the Subscription Renewal Fee from %s to %s.', 'sumosubscriptions' ), sumo_format_subscription_price( $old_renewal_fee, array( 'currency' => sumosubs_get_order_currency( $parent_order_id ) ) ), sumo_format_subscription_price( $new_renewal_fee, array( 'currency' => sumosubs_get_order_currency( $parent_order_id ) ) ) ) ;

                    sumo_add_subscription_note( $note, $post_id, sumo_note_status( $subscription_status ), __( 'Renewal Fee Changed Manually', 'sumosubscriptions' ) ) ;
                }
            }
        }
        //Next Due Date updation by the Admin
        if ( ! SUMO_Subscription_Synchronization::is_subscription_synced( $post_id ) && in_array( $subscription_status, array( 'Active', 'Trial' ) ) ) {
            if ( isset( $_POST[ 'subscription_next_due_date' ] ) && ! empty( $_POST[ 'subscription_next_due_date' ] ) ) {
                $new_renewal_timestamp = sumo_get_subscription_timestamp( $_POST[ 'subscription_next_due_date' ] . ' ' . ( int ) $_POST[ 'subscription_due_hour' ] . ':' . ( int ) $_POST[ 'subscription_due_minute' ] . ':' . ( int ) date( 's', sumo_get_subscription_timestamp( $next_payment_date ) ) ) ;

                if ( $new_renewal_timestamp > sumo_get_subscription_timestamp() ) {
                    if ( $new_renewal_timestamp != sumo_get_subscription_timestamp( $next_payment_date ) ) {

                        $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
                        $cron_event->unset_events( array(
                            'create_renewal_order',
                            'automatic_pay',
                            'notify_overdue',
                            'notify_suspend',
                            'notify_cancel',
                            'switch_to_manual_pay_mode'
                        ) ) ;

                        $new_renewal_date = sumo_get_subscription_date( $new_renewal_timestamp ) ;

                        SUMOSubscriptions_Order::set_next_payment_date( $post_id, $new_renewal_date ) ;

                        $note = sprintf( __( 'Admin has Changed the Subscription Due Date to %s.', 'sumosubscriptions' ), $new_renewal_date ) ;

                        sumo_add_subscription_note( $note, $post_id, sumo_note_status( $subscription_status ), __( 'Due date Changed Manually', 'sumosubscriptions' ) ) ;
                    }
                } else {
                    sumo_add_subscription_note( __( 'Subscription Due Date cannot be changed. Since the Date you have specified is Invalid.', 'sumosubscriptions' ), $post_id, 'failure', __( 'Due Date Change', 'sumosubscriptions' ) ) ;
                }
            }
        }

        //Subscription Status updation by the Admin
        if ( isset( $_POST[ 'subscription_status' ] ) ) {
            $new_status = $_POST[ 'subscription_status' ] !== $subscription_status ? $_POST[ 'subscription_status' ] : '' ;

            switch ( $new_status ) {
                case 'Pause':
                    sumo_pause_subscription( $post_id, '', 'admin' ) ;
                    //Trigger after Subscription gets Paused
                    do_action( 'sumosubscriptions_pause_subscription', $post_id, $parent_order_id ) ;
                    break ;
                case 'Resume':
                    sumo_resume_subscription( $post_id, 'admin' ) ;
                    //Trigger after Subscription gets Resumed
                    do_action( 'sumosubscriptions_active_subscription', $post_id, $parent_order_id ) ;
                    break ;
                case 'Activate-Trial':
                    if ( sumosubs_free_trial_awaiting_admin_approval( $post_id ) ) {
                        SUMOSubscriptions_Order::maybe_activate_subscription( $post_id, $parent_order_id, 'pending', 'free-trial' ) ;
                    }
                    break ;
                case 'Active':
                    //Trigger when the subscription is manualy activated.
                    $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
                    $cron_event->unset_events() ;

                    SUMOSubscriptions_Order::maybe_activate_subscription( $post_id, $parent_order_id, 'pending', 'Active', true ) ;
                    break ;
                default :
                    do_action( 'sumosubscriptions_manual_' . strtolower( $new_status ) . '_subscription', $post_id, $parent_order_id, $subscription_status ) ;
                    break ;
            }
        }

        // Schedule the subscription.
        if ( sumo_subscription_awaiting_admin_approval( $post_id ) && ! empty( $_POST[ 'subscription_start_date' ] ) ) {
            $hh                     = ! empty( $_POST[ 'subscription_start_hour' ] ) ? $_POST[ 'subscription_start_hour' ] : '00' ;
            $mm                     = ! empty( $_POST[ 'subscription_start_minute' ] ) ? $_POST[ 'subscription_start_minute' ] : '00' ;
            $subcription_start_time = sumo_get_subscription_timestamp( $_POST[ 'subscription_start_date' ] . ' ' . $hh . ':' . $mm ) ;

            if ( $subcription_start_time < sumo_get_subscription_timestamp() ) {
                return ;
            }

            $cron_event = new SUMO_Subscription_Cron_Event( $post_id ) ;
            $cron_event->unset_events() ;
            $cron_event->schedule_to_start_subscription( $subcription_start_time ) ;

            $existing_scheduled_time = get_post_meta( $post_id, 'sumo_subcription_activation_scheduled_on', true ) ;
            if ( '' !== $existing_scheduled_time ) {
                sumo_add_subscription_note( sprintf( __( 'Subscription activation rescheduled from %s to %s.', 'sumosubscriptions' ), sumo_get_subscription_date( $existing_scheduled_time ), sumo_get_subscription_date( $subcription_start_time ) ), $post_id, sumo_note_status( 'Pending' ), __( 'Subscription Activation Rescheduled', 'sumosubscriptions' ) ) ;
            } else {
                sumo_add_subscription_note( sprintf( __( 'Subscription is scheduled to activate on %s.', 'sumosubscriptions' ), sumo_get_subscription_date( $subcription_start_time ) ), $post_id, sumo_note_status( 'Pending' ), __( 'Subscription Activation Scheduled', 'sumosubscriptions' ) ) ;
            }

            update_post_meta( $post_id, 'sumo_subcription_activation_scheduled_on', $subcription_start_time ) ;
        }

        // Trigger Manual Subscription Emails
        if ( isset( $_POST[ 'subscription_action' ] ) && ! empty( $_POST[ 'subscription_action' ] ) ) {
            $action = wc_clean( $_POST[ 'subscription_action' ] ) ;

            if ( strstr( $action, 'send_email_' ) ) {
                // Ensure gateways are loaded in case they need to insert data into the emails
                WC()->payment_gateways() ;
                WC()->shipping() ;

                $template_id = str_replace( 'send_email_', '', $action ) ;

                $invoice_order_id = in_array( $template_id, array(
                            'subscription_suspended_order_manual',
                            'subscription_suspended_order_automatic',
                            'subscription_overdue_order_manual',
                            'subscription_overdue_order_automatic',
                            'subscription_invoice_order_manual',
                            'subscription_pending_authorization',
                            'subscription_renewed_order_automatic' ) ) ? $renewal_order_id : null ;

                // Trigger mailer.
                sumo_trigger_subscription_email( $template_id, $invoice_order_id, $post_id, true ) ;
            }
        }
    }

}

new SUMOSubscriptions_Admin_Metaboxes() ;
