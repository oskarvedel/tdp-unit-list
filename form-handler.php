<?php

function handle_booking_form()
{
    // xdebug_break();

    // Optional: Check for nonce for security
    // if ( ! isset( $_POST['my_nonce_field'] ) || ! wp_verify_nonce( $_POST['my_nonce_field'], 'my_nonce_action' ) ) {
    //    wp_die( 'Security check failed' );
    // }

    //log to the browser console
    // echo '<script>console.log("hello")</script>';

    trigger_error('form handler ran', E_USER_NOTICE);

    // Check if the form data is set
    if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['phone'])) {
        // Sanitize each form field
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);

        // Validate the data (example for a text field)
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            // Handle the error appropriately
            wp_die('Please fill all required fields.');
        }

        // Process the form data.
        // Here, we are just sending the form data to the admin's email
        $to = get_option('admin_email'); // Gets the admin's email from WordPress settings
        $subject = 'New Form Submission';
        $message = 'A new form has been submitted with the following data: '
            . 'First Name: ' . esc_html($first_name)
            . ', Last Name: ' . esc_html($last_name)
            . ', Email: ' . esc_html($email)
            . ', Phone: ' . esc_html($phone);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if (wp_mail($to, $subject, $message, $headers)) {
            // Handle the success message accordingly
            trigger_error('Email sent successfully', E_USER_NOTICE);
        } else {
            // Handle the error accordingly
            trigger_error('Email could not be sent', E_USER_ERROR);
        }

        // Redirect to a thank you page or back to the form with a success message
        // wp_redirect(home_url('/thank-you')); // Replace '/thank-you' with your desired redirect URL
        exit();
    } else {
        // Handle the case where the form fields are not set (redirect or display an error message)
        wp_die('Form submission error: Fields are not set.');
    }
}

add_action('wp_ajax_nopriv_booking_form_action', 'handle_booking_form');
add_action('wp_ajax_booking_form_action', 'handle_booking_form');
