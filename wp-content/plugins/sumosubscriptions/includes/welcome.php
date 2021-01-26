<?php

class SUMOSubscriptions_Welcome {

    protected static $welcome_page = 'sumosubscriptions-welcome-page' ;

    public static function init() {
        add_action( 'admin_menu' , __CLASS__ . '::add_welcome_page' ) ;
        add_action( 'admin_init' , __CLASS__ . '::redirect' ) ;
        add_action( 'admin_head' , __CLASS__ . '::remove_welcome_page' ) ;
    }

    public static function is_welcome() {
        return isset( $_GET[ 'page' ] ) && self::$welcome_page === $_GET[ 'page' ] ;
    }

    public static function load() {
        set_transient( 'sumosubscriptions_welcome_screen' , true , 30 ) ;
    }

    public static function add_welcome_page() {
        add_dashboard_page( 'Welcome To SUMO Subscriptions' , 'Welcome To SUMO Subscriptions' , 'read' , self::$welcome_page , 'SUMOSubscriptions_Welcome::render' ) ;
    }

    public static function render() {
        ob_start() ;
        sumosubscriptions_get_template( 'subscription-welcome-page.php' ) ;
        ob_get_contents() ;
    }

    public static function redirect() {
        if ( ! get_transient( 'sumosubscriptions_welcome_screen' ) ) {
            return ;
        }

        delete_transient( 'sumosubscriptions_welcome_screen' ) ;
        wp_safe_redirect( add_query_arg( array ( 'page' => self::$welcome_page ) , admin_url( 'admin.php' ) ) ) ;
    }

    public static function remove_welcome_page() {
        remove_submenu_page( 'index.php' , self::$welcome_page ) ;
    }

}

SUMOSubscriptions_Welcome::init() ;
