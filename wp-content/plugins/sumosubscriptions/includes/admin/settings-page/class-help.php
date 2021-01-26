<?php

/**
 * Help.
 * 
 * @class SUMOSubscriptions_Help
 * @category Class
 */
class SUMOSubscriptions_Help extends SUMO_Abstract_Subscription_Settings {

    /**
     * SUMOSubscriptions_Help constructor.
     */
    public function __construct() {

        $this->id            = 'help' ;
        $this->label         = __( 'Help' , 'sumosubscriptions' ) ;
        $this->custom_fields = array (
            'get_compatible_plugins' ,
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
                'name' => __( 'Documentation' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => '_sumo_subscription_documentation' ,
                'desc' => __( 'The documentation file can be found inside the documentation folder  which you will find when you unzip the downloaded zip file.' , 'sumosubscriptions' ) ,
            ) ,
            array (
                'name' => __( 'Compatibility' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
            ) ,
            array (
                'type' => $this->get_custom_field_type( 'get_compatible_plugins' )
            ) ,
            array (
                'name' => __( 'Help' , 'sumosubscriptions' ) ,
                'type' => 'title' ,
                'id'   => '_sumo_subscription_help_setting' ,
                'desc' => __( 'If you need Help, please <a href="http://support.fantasticplugins.com" target="_blank" > register and open a support ticket</a>' , 'sumosubscriptions' ) ,
            ) ,
            array ( 'type' => 'sectionend' , 'id' => '_sumo_subscription_help_setting' ) ,
            array ( 'type' => 'sectionend' , 'id' => '_sumo_subscription_documentation' ) ,
                ) ) ;
    }

    public function remove_submit_and_reset() {
        return false ;
    }

    /**
     * Custom type field.
     */
    public function get_compatible_plugins() {
        $compatible_plugins = array (
            "<a href='http://fantasticplugins.com/sumo-memberships/'>SUMO Memberships</a>" ,
            "<a href='http://fantasticplugins.com/sumo-donations/'>SUMO Donations</a>" ,
            "<a href='http://fantasticplugins.com/sumo-reward-points/'>SUMO Reward Points</a>" ,
            "<a href='http://fantasticplugins.com/sumo-affiliates/'>SUMO Affiliates</a>"
                ) ;
        echo __( 'The following Plugins are compatible with SUMO Subscriptions Plugin.' , 'sumosubscriptions' ) ;
        ?>
        <br><br>
        <?php foreach ( $compatible_plugins as $index => $plugin ) { ?>
            <ol>
                <?php
                $i = ++ $index ;
                echo __( "$i. " . "$plugin" , 'sumosubscriptions' ) . '<br>' ;
                ?>
            </ol>
            <?php
        }
    }

}

return new SUMOSubscriptions_Help() ;
