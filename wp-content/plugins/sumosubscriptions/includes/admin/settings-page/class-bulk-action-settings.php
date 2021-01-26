<?php

/**
 * Bulk Action Settings.
 * 
 * @class SUMOSubscriptions_Bulk_Action_Settings
 * @category Class
 */
class SUMOSubscriptions_Bulk_Action_Settings extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_Bulk_Action_Settings constructor.
     */
    public function __construct() {

        $this->id            = 'bulk_action' ;
        $this->label         = __( 'Bulk Action' , 'sumosubscriptions' ) ;
        $this->custom_fields = array (
            'get_tab_description' ,
            'get_product_selector' ,
            'get_product_status' ,
            'get_subscription_duration' ,
            'get_subscription_day_duration' ,
            'get_subscription_week_duration' ,
            'get_subscription_month_duration' ,
            'get_subscription_year_duration' ,
            'get_subscription_trial_status' ,
            'get_trial_type' ,
            'get_trial_fee' ,
            'get_trial_duration' ,
            'get_trial_day_duration' ,
            'get_trial_week_duration' ,
            'get_trial_month_duration' ,
            'get_trial_year_duration' ,
            'get_subscription_signup_status' ,
            'get_signup_fee' ,
            'get_recurring' ,
            'get_bulk_save_button' ,
                ) ;
        $this->settings      = $this->get_settings() ;
        $this->init() ;

        add_action( 'sumosubscriptions_submit_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
        add_action( 'sumosubscriptions_reset_' . $this->id , array ( $this , 'remove_submit_and_reset' ) ) ;
    }

    /**
     * Get settings array.
     * @return array
     */
    public function get_settings() {
        global $current_section ;

        return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings' , array (
            array (
                'name' => __( 'Subscription Product Settings Bulk Update' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_bulk_update_setting'
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_tab_description' )
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_renewal_bulk_update_setting' ) ,
            array (
                'name' => __( 'Product Bulk Update' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => 'sumo_product_bulk_update_setting'
            ) ,
            array (
                'name'    => __( 'Select Products/Categories' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_bulk_update_select_product_category' ,
                'newids'  => 'sumo_bulk_update_select_product_category' ,
                'type'    => 'select' ,
                'std'     => '1' ,
                'default' => '1' ,
                'options' => array (
                    '1' => __( 'All Products' , 'sumosubscriptions' ) ,
                    '2' => __( 'Selected Products' , 'sumosubscriptions' ) ,
                    '3' => __( 'All Categories' , 'sumosubscriptions' ) ,
                    '4' => __( 'Selected Categories' , 'sumosubscriptions' ) ,
                )
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_product_selector' ) ,
            ) ,
            array (
                'name'    => __( 'Select Particular Categories' , 'sumosubscriptions' ) ,
                'id'      => 'sumo_select_particular_category' ,
                'css'     => 'min-width:350px;' ,
                'class'   => 'sumo_select_particular_category' ,
                'newids'  => 'sumo_select_particular_category' ,
                'type'    => 'multiselect' ,
                'options' => sumosubs_category_list() ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_product_status' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_day_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_week_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_month_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_year_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_trial_status' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_type' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_fee' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_day_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_week_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_month_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_trial_year_duration' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_subscription_signup_status' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_signup_fee' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_recurring' ) ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_bulk_save_button' ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_product_bulk_update_setting' ) ,
            array ( 'type' => 'sectionend' , 'id' => 'sumo_bulk_update_setting' ) ,
                ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_tab_description() {
        ?>
        <tr>
            <?php echo _e( 'Using these settings you can customize/modify the subscription information in your site.' , 'sumosubscriptions' ) ; ?>
        <br><br>
        <?php echo _e( '<b>Note:</b> The subscription field can be edited only when its corresponding update checkbox is enabled.' , 'sumosubscriptions' ) ; ?>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_product_selector() {

        sumosubs_wc_search_field( array (
            'class'       => 'wc-product-search' ,
            'id'          => 'sumo_select_particular_products' ,
            'type'        => 'product' ,
            'action'      => 'woocommerce_json_search_products_and_variations' ,
            'title'       => __( 'Select Particular Product(s)' , 'sumosubscriptions' ) ,
            'placeholder' => __( 'Search for a product&hellip;' , 'sumosubscriptions' ) ,
            'selected'    => false ,
        ) ) ;
    }

    /**
     * Custom type field.
     */
    public function get_product_status() {
        ?>
        <tr>
            <th>
                <?php _e( 'SUMO Subscriptions' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_enable_subscription" style="width:95px;">
                    <option value="1"><?php _e( 'Enable' , 'sumosubscriptions' ) ; ?></option>
                    <option value="2"><?php _e( 'Disable' , 'sumosubscriptions' ) ; ?></option>
                </select>
                <input id="sumo_bulk_update_enable_subscription_checkbox" type="checkbox"/><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Subscription Duration' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_subscription_duration" style="width:95px;">
                    <?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ): ?>
                        <option value="<?php echo $period ; ?>"><?php echo $label ; ?></option>
                    <?php endforeach ; ?>
                </select>
                <input id="sumo_bulk_update_subscription_duration_checkbox" type="checkbox"/><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_day_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Subscription Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_subscription_duration_value_days" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 90 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s day' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s days' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_week_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Subscription Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_subscription_duration_value_weeks" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 52 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s week' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s weeks' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_month_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Subscription Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_subscription_duration_value_months" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 24 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s month' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s months' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_year_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Subscription Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_subscription_duration_value_years" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 10 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s year' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s years' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_trial_status() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Period' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_period" style="width:95px;">
                    <option value="1"><?php _e( 'Enable' , 'sumosubscriptions' ) ; ?></option>
                    <option value="2"><?php _e( 'Disable' , 'sumosubscriptions' ) ; ?></option>
                </select>
                <input id="sumo_bulk_update_trial_period_checkbox" type="checkbox"/><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_type() {
        ?>
        <tr>
            <th>
                <?php _e( 'Select Trial Type' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_fee_type" style="width:95px;">
                    <option value="free"><?php _e( 'Free Trial' , 'sumosubscriptions' ) ; ?></option>
                    <option value="paid"><?php _e( 'Paid Trial' , 'sumosubscriptions' ) ; ?></option>
                </select>
                <input id="sumo_bulk_update_fee_type_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_fee() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Fee' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <input id="sumo_bulk_update_trial_fee_value" type="text" style="width:150px;"/>
                <input id="sumo_bulk_update_trial_fee_value_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Duration' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_duration" style="width:95px;">
                    <?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ): ?>
                        <option value="<?php echo $period ; ?>"><?php echo $label ; ?></option>
                    <?php endforeach ; ?>                    
                </select>
                <input id="sumo_bulk_update_trial_duration_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_day_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_duration_value_days" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 90 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s day' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s days' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_week_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_duration_value_weeks" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 52 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s week' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s weeks' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_month_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_duration_value_months" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 24 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s month' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s months' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_trial_year_duration() {
        ?>
        <tr>
            <th>
                <?php _e( 'Trial Duration Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_trial_duration_value_years" style="width:95px;">
                    <?php for ( $i = 1 ; $i <= 5 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 1 ) {
                                printf( __( '%s year' , 'sumosubscriptions' ) , $i ) ;
                            } else {
                                printf( __( '%s years' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_subscription_signup_status() {
        ?>
        <tr>
            <th>
                <?php _e( 'Sign up Fee' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_signup_fee" style="width:95px;">
                    <option value="1"><?php _e( 'Enable' , 'sumosubscriptions' ) ; ?></option>
                    <option value="2"><?php _e( 'Disable' , 'sumosubscriptions' ) ; ?></option>
                </select>
                <input id="sumo_bulk_update_signup_fee_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_signup_fee() {
        ?>
        <tr>
            <th>
                <?php _e( 'Sign up Value' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <input id="sumo_bulk_update_signup_fee_value" type="text" style="width:150px;"/>
                <input id="sumo_bulk_update_signup_fee_value_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_recurring() {
        ?>
        <tr>
            <th>
                <?php _e( 'Recurring Cycle' , 'sumosubscriptions' ) ; ?>
            </th>
            <td>
                <select id="sumo_bulk_update_recurring_cycle" style="width:95px;">
                    <?php for ( $i = 0 ; $i <= 52 ; $i ++ ) { ?>
                        <option value="<?php echo $i ; ?>"><?php
                            if ( $i === 0 ) {
                                _e( 'Indefinite' , 'sumosubscriptions' ) ;
                            } else {
                                printf( __( '%s Installments' , 'sumosubscriptions' ) , $i ) ;
                            }
                            ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <input id="sumo_bulk_update_recurring_cycle_checkbox" type="checkbox" /><?php _e( 'Update' , 'sumosubscriptions' ) ; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom type field.
     */
    public function get_bulk_save_button() {
        ?>
        <tr>
            <td>
                <input type="submit" id="sumo_subscription_bulk_update" data-is_bulk_update ="true" class="button-primary" value="Save and Update" />
                <img class="sumosubscription_loading" src="<?php echo SUMO_SUBSCRIPTIONS_PLUGIN_URL . '/assets/images/update.gif' ; ?>" style="width:32px;height:32px;position:absolute"/>
            </td>
        </tr>
        <?php
    }

    public function remove_submit_and_reset() {
        return false ;
    }

}

return new SUMOSubscriptions_Bulk_Action_Settings() ;
