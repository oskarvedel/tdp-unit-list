<?php

function handle_booking_form()
{
    $unit_id = $_POST['unit_id'];

    // Optional: Check for nonce for security
    // if (!isset($_POST['my_nonce_field']) || !wp_verify_nonce($_POST['my_nonce_field'], 'my_nonce_action')) {
    //     wp_die('Security check failed');
    // }

    // Check if the form data is set
    if (!isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['move_in_date'])) {
        // Handle the case where the form fields are not set (redirect or display an error message)
        wp_die('Form submission error: Fields are not set.');
    }
    if (!isset($unit_id)) {
        // Handle the case where the first name is not set (redirect or display an error message)
        trigger_error('Booking Form submission error: Unit ID is not set.', E_USER_ERROR);
    }

    // Sanitize each form field
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $booking_link = sanitize_text_field($_POST['booking_link']);

    // Validate each form field
    if (empty($_POST['move_in_date'])) {
        // Handle the error appropriately
        wp_die('Please fill all required fields.');
    }

    if ($_POST['move_in_date'] == "future") {
        $move_in_date_unknown = true;
        $move_in_date = new DateTime('3000-01-01');
        $move_in_date = $move_in_date->format('Y-m-d');
    } else {
        $move_in_date_unknown = false;
        $move_in_date = new DateTime($_POST['move_in_date']);
        $move_in_date = $move_in_date->format('Y-m-d');
    }

    // Validate each form field
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($move_in_date)) {
        // Handle the error appropriately
        wp_die('Please fill all required fields.');
    }
    //get information about the unit
    $unit_price = get_post_meta($unit_id, 'price', true);

    $rel_lokation = get_post_meta($unit_id, 'rel_lokation', true);

    $supplier_email = get_post_meta($rel_lokation['ID'], 'email_address', true);

    $lokation_name = $rel_lokation['post_title'];

    $department_address = get_post_meta($rel_lokation['ID'], 'address', true);

    $supplier_booking_email_disabled = get_post_meta($rel_lokation['ID'], 'supplier_booking_email_disabled', true);
    $direct_booking_active = get_post_meta($rel_lokation['ID'], 'direct_booking_active', true);

    //create a new post of the "booking" type
    $booking_post_id = wp_insert_post(array(
        'post_title' => 'ERROR: booking post title not set',
        'post_type' => 'booking',
        'post_status' => 'publish',
        'meta_input' => array(
            'time_of_booking' => date('Y-m-d H:i:s'),
            'customer_first_name' => $first_name,
            'customer_last_name' => $last_name,
            'customer_email_address' => $email,
            'customer_phone' => $phone,
            'move_in_date' => $move_in_date,
            'move_in_date_unknown' => $move_in_date_unknown,
            'supplier_booking_email_disabled' => $supplier_booking_email_disabled,
            'direct_booking_active' => $direct_booking_active,
            'unit_link' => $unit_id,
            'booking_link' => $booking_link,
            'supplier_email_address' => $supplier_email,
            'department_name' => $lokation_name,
            'rel_lokation' => $rel_lokation['ID'],
            'unit_price' => $unit_price,
            'department_address' => $department_address,
        )
    ));

    //update booking post title
    $rel_type = get_post_meta($unit_id, 'rel_type', true);
    $size = "";
    $size = get_post_meta($rel_type['ID'], 'm2', true);
    $sizeunit = "m2";
    if (!$size) {
        $size = get_post_meta($rel_type['ID'], 'm3', true);
        $sizeunit = "m3";
    }
    $size = str_replace('.', ',', $size);

    $post_title = 'Booking #' . $booking_post_id . ': (' . $first_name . ' ' . $last_name . ') - ' . $rel_type['post_title'] . ' (Unit #' . $unit_id . ' - ' . $size . ' ' . $sizeunit . ')';
    wp_update_post(array(
        'ID' => $booking_post_id,
        'post_title' => $post_title,
    ));

    // Redirect to a thank you page or back to the form with a success message
    // wp_redirect(home_url('/thank-you')); // Replace '/thank-you' with your desired redirect URL

    //return success message, include the booking id
    echo json_encode(array(
        'success' => true,
        'booking_id' => $booking_post_id,
    ));

    exit();
}

add_action('wp_ajax_nopriv_booking_form_action', 'handle_booking_form');
add_action('wp_ajax_booking_form_action', 'handle_booking_form');
