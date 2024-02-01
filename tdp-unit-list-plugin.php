<?php

/**
 * Plugin Name: tdp-unit-list
 * Version: 1.0
 */

require_once dirname(__FILE__) . '/unit-list.php';
require_once dirname(__FILE__) . '/tdp-common-unit-list.php';

function custom_date_picker_scripts()
{
    wp_enqueue_script('custom-date-picker-js', plugins_url('/js/booking_form.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'custom_date_picker_scripts');

//add a admin plugin button to generate default unit lists
function add_generate_unit_lists_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=generate_unit_lists')) . '">Generate unit lists</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-unit-list/tdp-unit-list-plugin.php', 'add_generate_unit_lists_button');

function handle_generate_unit_lists()
{
    generate_default_unit_list_for_all_gd_places();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_generate_unit_lists', 'handle_generate_unit_lists');

function get_default_archive_page_unit_list()
{
    global $post;
    $default_unit_list = '';

    if ($post) {
        //get the id of the post
        $id = $post->ID;
        $default_unit_list = get_post_meta($id, 'default_archive_page_unit_list', true);
    }

    echo $default_unit_list;
}
// Register the shortcode.
add_shortcode("default_archive_page_unit_list", "get_default_archive_page_unit_list");

function get_default_department_page_unit_list()
{
    $current_pod = pods();
    $default_unit_list = '';
    if ($current_pod && $current_pod->exists()) {
        //get the id of the pod
        $id = $current_pod->field("id");
        $default_unit_list = get_post_meta($id, 'default_department_page_unit_list', true);
    }

    echo $default_unit_list;
}
// Register the shortcode.
add_shortcode("default_department_page_unit_list", "get_default_department_page_unit_list");
