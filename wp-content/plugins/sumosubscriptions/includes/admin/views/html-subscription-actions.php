<ul class="order_actions submitbox">
    <li class="wide" id="subscription_action">
        <select name="subscription_action" class="wc-enhanced-select wide">
            <option value=""><?php _e( 'Actions' , 'sumosubscriptions' ) ; ?></option>
            <optgroup label="<?php _e( 'Resend subscription emails' , 'sumosubscriptions' ) ; ?>">
                <?php
                switch( $subscription_status ) {
                    case 'Pending':
                        $available_emails = array( 'subscription_new_order' ) ;
                        break ;
                    case 'Expired':
                        $available_emails = array( 'subscription_expired_order' ) ;
                        break ;
                    case 'Cancelled':
                        $available_emails = array( 'subscription_cancel_order' ) ;
                        break ;
                    case 'Active':
                        if( $is_invoice_present ) {
                            if( $is_automatic_payment ) {
                                $available_emails = array( 'subscription_new_order' , 'subscription_processing_order' , 'subscription_renewed_order_automatic' , 'subscription_completed_order' ) ;
                            } else {
                                $available_emails = array( 'subscription_new_order' , 'subscription_processing_order' , 'subscription_invoice_order_manual' , 'subscription_completed_order' ) ;
                            }
                        } else {
                            $available_emails = array( 'subscription_new_order' , 'subscription_processing_order' , 'subscription_completed_order' ) ;
                        }
                        break ;
                    case 'Pause':
                        $available_emails = array( 'subscription_pause_order' ) ;
                        break ;
                    case 'Trial':
                        if( $is_invoice_present ) {
                            if( $is_automatic_payment ) {
                                $available_emails = array( 'subscription_new_order' , 'subscription_renewed_order_automatic' ) ;
                            } else {
                                $available_emails = array( 'subscription_new_order' , 'subscription_invoice_order_manual' ) ;
                            }
                        } else {
                            $available_emails = array( 'subscription_new_order' ) ;
                        }
                        break ;
                    case 'Pending_Authorization':
                        $available_emails = array( 'subscription_pending_authorization' ) ;
                        break ;
                    case 'Overdue':
                        if( $is_automatic_payment ) {
                            $available_emails = array( 'subscription_overdue_order_automatic' ) ;
                        } else {
                            $available_emails = array( 'subscription_overdue_order_manual' ) ;
                        }
                        break ;
                    case 'Suspended':
                        if( $is_automatic_payment ) {
                            $available_emails = array( 'subscription_suspended_order_automatic' ) ;
                        } else {
                            $available_emails = array( 'subscription_suspended_order_manual' ) ;
                        }
                        break ;
                    default :
                        $available_emails = array() ;
                        do_action( 'sumosubscriptions_admin_send_manual_subscription_email' , $post->ID , $parent_order_id , $subscription_status ) ;
                        break ;
                }

                $mails = WC()->mailer()->get_emails() ;
                if( is_array( $mails ) && $mails ) {
                    foreach( $mails as $mail ) {
                        if( isset( $mail->id ) && in_array( $mail->id , $available_emails ) ) {
                            echo '<option value="send_email_' . esc_attr( $mail->id ) . '">' . esc_html( $mail->title ) . '</option>' ;
                        }
                    }
                }
                ?>
            </optgroup>
        </select>
    </li>
    <li class="wide">
        <div id="delete-action">
            <?php
            if( current_user_can( 'delete_post' , $post->ID ) ) {
                if( ! EMPTY_TRASH_DAYS ) {
                    $delete_text = __( 'Delete Permanently' , 'sumosubscriptions' ) ;
                } else {
                    $delete_text = __( 'Move to Trash' , 'sumosubscriptions' ) ;
                }
                ?>
                <a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ) ; ?>"><?php echo $delete_text ; ?></a>
                <?php
            }
            ?>
        </div>
        <input type="submit" class="button save_subscription save_order button-primary tips" name="save" value="<?php printf( __( 'Save %s' , 'sumosubscriptions' ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" data-tip="<?php printf( __( 'Save/update the %s' , 'sumosubscriptions' ) , get_post_type_object( $post->post_type )->labels->singular_name ) ; ?>" />
    </li>
</ul>