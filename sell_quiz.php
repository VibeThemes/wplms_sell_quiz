<?php
/*
Plugin Name: WPLMS SELL QUIZ
Plugin URI: http://www.Vibethemes.com
Description: A simple WordPress plugin to sell quiz
Version: 1.1
Author: VibeThemes
Author URI: http://www.vibethemes.com
License: GPL2
Text Domain: wplms-sell-quiz
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( !defined( 'WPLMS_SELL_QUIZ_VERSION' ) ){
    define('WPLMS_SELL_QUIZ_VERSION','1.1');
}

if( !defined('WPLMS_SELL_QUIZ_ITEM_NAME')){
  define( 'WPLMS_SELL_QUIZ_ITEM_NAME', 'WPLMS Sell Quiz' );
}

include_once 'includes/class.updater.php';
include_once 'includes/class.config.php';
include_once 'includes/class.init.php';

function Wplms_Sell_Quiz_Plugin_Updater() {
    $license_key = trim( get_option( 'wplms_sell_quiz_license_key' ) );
    $edd_updater = new Wplms_Sell_Quiz_Plugin_Updater( 'https://wplms.io', __FILE__, array(
            'version'   => WPLMS_SELL_QUIZ_VERSION,               
            'license'   => $license_key,        
            'item_name' => WPLMS_SELL_QUIZ_ITEM_NAME,    
            'author'    => 'VibeThemes' 
        )
    );
}
add_action( 'admin_init', 'Wplms_Sell_Quiz_Plugin_Updater', 0 );