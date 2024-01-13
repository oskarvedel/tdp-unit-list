<?php

/**
 * Plugin Name: tdp-unit-list
 * Version: 1.0
 */

require_once dirname(__FILE__) . '/unit-list.php';
require_once dirname(__FILE__) . '/tdp-common-unit-list.php';
require_once dirname(__FILE__) . '/form-handler.php';

function custom_date_picker_scripts()
{
    wp_enqueue_script('custom-date-picker-js', plugins_url('/js/script2.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'custom_date_picker_scripts');


// error_reporting(E_ALL);
// ini_set('display_errors', 1);
