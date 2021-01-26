<?php

/**
 * Register new Payment Gateway id of PayPal Reference Transactions.
 * 
 * @class SUMO_Paypal_Reference_Txns_Gateway
 * @category Class
 */
class SUMO_Paypal_Reference_Txns_Gateway extends WC_Payment_Gateway {

    /** @protected SUMO_Reference_Txns_Subscription_Handler */
    protected $reference_handler ;

    /**
     * SUMO_Paypal_Reference_Txns_Gateway constructor.
     */
    public function __construct() {
        $this->id                  = 'sumo_paypal_reference_txns' ;
        $this->icon                = SUMO_SUBSCRIPTIONS_PLUGIN_URL . '/assets/images/paypalpre.jpg' ;
        $this->has_fields          = true ;
        $this->method_title        = 'SUMO Subscriptions - PayPal Reference Transactions' ;
        $this->method_description  = __( 'SUMO Subscriptions - PayPal Reference Transactions is a part of Express Checkout that provides option to create Recurring Profile' , 'sumosubscriptions' ) ;
        $this->init_form_fields() ;
        $this->init_settings() ;
        $this->enabled             = $this->get_option( 'enabled' , 'no' ) ;
        $this->title               = $this->get_option( 'title' ) ;
        $this->description         = $this->get_option( 'description' ) ;
        $this->sandbox             = 'yes' === $this->get_option( 'testmode' , 'no' ) ;
        $this->api_user            = $this->get_option( 'api_user' ) ; // API User ID goes here
        $this->api_pwd             = $this->get_option( 'api_pwd' ) ; // API Password goes here
        $this->api_signature       = $this->get_option( 'api_signature' ) ; // API Signature goes here
        $this->dev_debug_enabled   = 'yes' === $this->get_option( 'dev_debug_enabled' , 'no' ) ;
        $this->user_roles_for_dev  = $this->get_option( 'user_roles_for_dev' ) ;
        $this->custom_payment_page = array (
            'style'        => $this->get_option( 'page_style' , get_option( 'sumo_customize_paypal_checkout_page_style' , '' ) ) ,
            'logo'         => $this->get_option( 'page_logo' , get_option( 'sumo_customize_paypal_checkout_page_logo_attachment_id' , '' ) ) ,
            'border_color' => $this->get_option( 'page_border_color' , get_option( 'sumo_customize_paypal_checkout_page_border_color_value' , '' ) ) ,
                ) ;

        if ( $this->sandbox ) {
            $this->endpoint  = 'https://api-3t.sandbox.paypal.com/nvp' ;
            $this->token_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout' ;
        } else {
            $this->endpoint  = 'https://api-3t.paypal.com/nvp' ;
            $this->token_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout' ;
        }

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id , array ( $this , 'process_admin_options' ) ) ;
        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id , array ( $this , 'save_data' ) ) ;

        include_once('inc/class-reference-txns-api.php') ;
        include_once('inc/class-reference-txns-subscription-handler.php') ;

        $this->reference_handler = new SUMO_Reference_Txns_Subscription_Handler( $this ) ;
    }

    /**
     * Get option keys which are available
     */
    public function _get_option_keys() {
        return array (
            'enabled'            => 'enabled' ,
            'title'              => 'title' ,
            'description'        => 'description' ,
            'testmode'           => 'testmode' ,
            'api_user'           => 'sumo_api_user' ,
            'api_pwd'            => 'sumo_api_pwd' ,
            'api_signature'      => 'sumo_api_signature' ,
            'dev_debug_enabled'  => 'dev_debug_mode_enabled' ,
            'user_roles_for_dev' => 'selected_user_roles_for_dev' ,
            'page_style'         => 'payment_page_style_name' ,
            'page_logo'          => 'payment_page_logo' ,
            'page_border_color'  => 'payment_page_border_color' ,
                ) ;
    }

    /**
     * Return the name of the old option in the WP DB.
     *
     * @return string
     */
    public function _get_old_option_key() {
        return $this->plugin_id . 'sumosubscription_paypal_reference_transactions_settings' ;
    }

    /**
     * Check for an old option and get option from DB.
     *
     * @param  string $key Option key.
     * @param  mixed  $empty_value Value when empty.
     * @return string The value specified for the option or a default value for the option.
     */
    public function get_option( $key , $empty_value = null ) {
        $new_options = get_option( $this->get_option_key() , null ) ;

        if ( isset( $new_options[ $key ] ) ) {
            return parent::get_option( $key , $empty_value ) ;
        }

        $old_options = get_option( $this->_get_old_option_key() , false ) ;

        if ( false === $old_options || ! is_array( $old_options ) ) {
            return parent::get_option( $key , $empty_value ) ;
        }

        foreach ( $this->_get_option_keys() as $current_key => $maybeOld_key ) {
            if ( $key !== $current_key ) {
                continue ;
            }

            if ( is_array( $maybeOld_key ) ) {
                foreach ( $maybeOld_key as $_key ) {
                    if ( isset( $old_options[ $_key ] ) ) {
                        $this->settings[ $key ] = $old_options[ $_key ] ;
                    }
                }
            } else {
                if ( isset( $old_options[ $maybeOld_key ] ) ) {
                    $this->settings[ $key ] = $old_options[ $maybeOld_key ] ;
                }
            }
        }

        return parent::get_option( $key , $empty_value ) ;
    }

    /**
     * Admin Settings For PayPal Reference Transactions.
     */
    public function init_form_fields() {
        $this->form_fields = array (
            'enabled'                => array (
                'title'   => __( 'Enable/Disable' , 'sumosubscriptions' ) ,
                'type'    => 'checkbox' ,
                'label'   => __( 'Enable PayPal Reference Transactions' , 'sumosubscriptions' ) ,
                'default' => 'no'
            ) ,
            'title'                  => array (
                'title'       => __( 'Title' , 'sumosubscriptions' ) ,
                'type'        => 'text' ,
                'description' => __( 'This controls the title which the user see during checkout.' , 'sumosubscriptions' ) ,
                'default'     => __( 'SUMO Subscriptions - PayPal Reference Transactions' , 'sumosubscriptions' ) ,
                'desc_tip'    => true ,
            ) ,
            'description'            => array (
                'title'   => __( 'Description' , 'sumosubscriptions' ) ,
                'type'    => 'textarea' ,
                'default' => __( 'Pay using PayPal Reference Transactions' , 'sumosubscriptions' )
            ) ,
            'testmode'               => array (
                'title'       => __( 'PayPal Reference Transactions sandbox' , 'sumosubscriptions' ) ,
                'type'        => 'checkbox' ,
                'label'       => __( 'Enable PayPal Reference Transactions sandbox' , 'sumosubscriptions' ) ,
                'default'     => 'no' ,
                'description' => sprintf( __( 'PayPal Reference Transactions sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.' , 'sumosubscriptions' ) , 'https://developer.paypal.com/' ) ,
            ) ,
            'api_user'               => array (
                'title'       => __( 'API User ID' , 'sumosubscriptions' ) ,
                'type'        => 'text' ,
                'description' => __( 'Enter API Username to perform Reference Transactions' , 'sumosubscriptions' ) ,
                'default'     => '' ,
                'desc_tip'    => true ,
            ) ,
            'api_pwd'                => array (
                'title'       => __( 'API Password' , 'sumosubscriptions' ) ,
                'type'        => 'password' ,
                'description' => __( 'Enter API Password' , 'sumosubscriptions' ) ,
                'default'     => '' ,
                'desc_tip'    => true ,
            ) ,
            'api_signature'          => array (
                'title'       => __( 'API Signature' , 'sumosubscriptions' ) ,
                'type'        => 'text' ,
                'description' => __( 'Enter API Signature' , 'sumosubscriptions' ) ,
                'default'     => '' ,
                'desc_tip'    => true ,
            ) ,
            'dev_debug_enabled'      => array (
                'title'       => __( 'Developer Debug Mode' , 'sumosubscriptions' ) ,
                'label'       => __( 'Enable' , 'sumosubscriptions' ) ,
                'type'        => 'checkbox' ,
                'description' => __( 'If Reference Transactions is not enabled for your API credentials, this gateway will not be displayed on the Checkout page. For testing purpose, if you want this gateway to be displayed for specific userrole(s) then, Enable this checkbox.' , 'sumosubscriptions' ) ,
                'default'     => 'no' ,
            ) ,
            'user_roles_for_dev'     => array (
                'title'   => __( 'Select User Role(s)' , 'sumosubscriptions' ) ,
                'type'    => 'multiselect' ,
                'options' => sumosubs_user_roles() ,
                'default' => array () ,
            ) ,
            'customize_payment_page' => array (
                'title' => __( 'Customize Payment Page' , 'sumosubscriptions' ) ,
                'type'  => 'title' ,
            ) ,
            'page_style'             => array (
                'title'    => __( 'Page Style Name' , 'sumosubscriptions' ) ,
                'type'     => 'text' ,
                'default'  => get_option( 'sumo_customize_paypal_checkout_page_style' , '' ) ,
                'desc_tip' => __( 'Already created page style name in PayPal account should be given here.' , 'sumosubscriptions' ) ,
            ) ,
            'page_logo'              => array (
                'type' => 'page_logo_finder' ,
            ) ,
            'page_border_color'      => array (
                'title'     => __( 'Choose PayPal Checkout Page Border Color' , 'sumosubscriptions' ) ,
                'type'      => 'color' ,
                'css'       => 'width:90px' ,
                'default'   => ($get_option = get_option( 'sumo_customize_paypal_checkout_page_border_color_value' , '' )) ? '#' . $get_option : '' ,
                'desc_tip'  => __( 'The border color for PayPal checkout page.' , 'sumosubscriptions' ) ,
            ) ) ;
    }

    /**
     * Processes and saves options.
     *
     * @return bool was anything saved?
     */
    public function process_admin_options() {
        $saved       = parent::process_admin_options() ;
        $old_options = get_option( $this->_get_old_option_key() , false ) ;

        if ( false === $old_options || ! is_array( $old_options ) ) {
            return $saved ;
        }

        foreach ( $this->settings as $saved_key => $saved_val ) {
            foreach ( $this->_get_option_keys() as $key => $maybeOld_key ) {
                if ( $saved_key !== $key ) {
                    continue ;
                }

                if ( is_array( $maybeOld_key ) ) {
                    foreach ( $maybeOld_key as $_key ) {
                        if ( isset( $old_options[ $_key ] ) ) {
                            $old_options[ $_key ] = $saved_val ;
                        }
                    }
                } else {
                    if ( isset( $old_options[ $maybeOld_key ] ) ) {
                        $old_options[ $maybeOld_key ] = $saved_val ;
                    }
                }
            }
        }
        //Save the new options in the old gateway id. Maybe used when revert back to previous version
        update_option( $this->_get_old_option_key() , $old_options ) ;

        return $saved ;
    }

    /**
     * Generate PayPal payment page logo finder html.
     *
     * @return string
     */
    public function generate_page_logo_finder_html() {
        $attachment_id = $this->custom_payment_page[ 'logo' ] ;

        ob_start() ;
        ?>
        <tr>
            <th>
                <?php _e( 'Select PayPal Checkout Page Logo' , 'sumosubscriptions' ) ; ?>
                <span class="woocommerce-help-tip" data-tip="<?php _e( 'Selected logo will be displayed in PayPal payment page.' , 'sumosubscriptions' ) ; ?>"></span>
            </th>
            <td>
                <div class='logo-preview-wrapper'>
                    <span id="logo_attachment" style="padding: 0px 2px"><?php echo wp_get_attachment_image( $attachment_id , array ( 90 , 60 ) ) ; ?></span>
                    <img id='logo-preview' src='' width='90' height='60' style="display: none;">

                    <input type='hidden' name='logo_attachment_id' id='logo_attachment_id' value="<?php echo esc_attr( $attachment_id ) ; ?>">
                    <input type="button" id="upload_logo_button" data-choose="<?php esc_attr_e( 'Choose a Logo' , 'sumosubscriptions' ) ; ?>" data-update="<?php esc_attr_e( 'Add Logo' , 'sumosubscriptions' ) ; ?>" data-saved_attachment="<?php echo esc_attr( $attachment_id ) ; ?>" class="button" value="<?php _e( 'Upload Logo' , 'sumosubscriptions' ) ; ?>" />

                    <?php if ( $attachment_id ): ?>
                        <input type="submit" name="delete_logo" class="button" value="<?php _e( 'Delete Logo' , 'sumosubscriptions' ) ; ?>" />
                    <?php endif ; ?>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean() ;
    }

    /**
     * Save custom fields data.
     */
    public function save_data( $posted ) {

        if ( isset( $_POST[ 'logo_attachment_id' ] ) && isset( $posted[ 'page_logo' ] ) ) {
            $posted[ 'page_logo' ] = $_POST[ 'logo_attachment_id' ] ;
        }
        if ( isset( $_POST[ 'delete_logo' ] ) && isset( $posted[ 'page_logo' ] ) ) {
            $posted[ 'page_logo' ] = '' ;
        }
        return $posted ;
    }

    /**
     * Create Callback Url.
     * @param int $order_id The Order post ID
     * @return string
     */
    public function create_callback_url( $order_id ) {
        $request_url = WC()->api_request_url( 'sumo_subscription_reference_transactions' ) ;

        $url = esc_url_raw( add_query_arg( array (
            'order_id' => $order_id ,
            'action'   => 'sumosubscription_do_express_checkout' ,
                        ) , $request_url ) ) ;
        return $url ;
    }

    /**
     * Process of PayPal Reference Transactions.
     * @param int $order_id The Order post ID
     * @return array
     * @throws Exception
     */
    public function process_payment( $order_id ) {

        try {
            if ( ! $order = wc_get_order( $order_id ) ) {
                throw new Exception( __( 'Something went wrong !!' , 'sumosubscriptions' ) ) ;
            }

            $success_url = $this->create_callback_url( $order_id ) ;
            $cancel_url  = str_replace( '&amp;' , '&' , $order->get_cancel_order_url() ) ;

            if ( $order->get_total() <= 0 && ! SUMO_Subscription_PaymentGateways::customer_has_chosen_auto_payment_mode_in( $this->id ) ) {
                // Complete payment 
                $order->payment_complete() ;

                // Reduce stock levels
                sumosubs_reduce_order_stock( $order_id ) ;

                // Remove cart
                WC()->cart->empty_cart() ;

                $redirect_url = $this->get_return_url( $order ) ;
            } else {
                $this->reference_handler->set_order_id( $order_id ) ;
                $data = $this->reference_handler->setExpressCheckout( $success_url , $cancel_url ) ;

                if ( ! isset( $data[ 'ACK' ] ) || 'Failure' === $data[ 'ACK' ] ) {
                    $long_message = $this->reference_handler->get_error_message( $data ) ;

                    throw new Exception( "$long_message" ) ;
                }

                // Reduce stock levels
                sumosubs_reduce_order_stock( $order_id ) ;

                // Remove cart
                WC()->cart->empty_cart() ;

                $redirect_url = esc_url_raw( add_query_arg( array ( 'token' => $data[ 'TOKEN' ] ) , $this->token_url ) ) ;
            }

            return array (
                'result'   => 'success' ,
                'redirect' => $redirect_url
                    ) ;
        } catch ( Exception $e ) {
            if ( ! empty( $e ) ) {
                wc_add_notice( $e->getMessage() , 'error' ) ;
            }
        }

        // If we reached this point then there were errors
        return array (
            'result'   => 'failure' ,
            'redirect' => $this->get_return_url( $order )
                ) ;
    }

}

return new SUMO_Paypal_Reference_Txns_Gateway() ;
