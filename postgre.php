<?php
/*
Plugin Name: PostgreSQL Gravity Forms Integration
Description: Check reservation codes against a PostgreSQL database when a Gravity Form is submitted and populate results in a field.
Version: 1.0
Author: Rohit Kumar
Author URI: https://iamrohit.net/
*/

// Hook into Gravity Forms to perform the check after submission
add_action('gform_after_submission', 'check_reservation_code', 10, 2);
function check_reservation_code($entry, $form) {
    $form_id = get_option('pgwp_form_id');
    $field_id = get_option('pgwp_field_id');
    $result_field_id = get_option('pgwp_result_field');

    // If current form is not the one we want to check, return early
    if ($form['id'] != $form_id) {
        return;
    }

    $reservation_code = rgar($entry, $field_id);

    // Direct database connection details
    $db_user = '';
    $db_password = '';
    $db_host = '';
    $db_port = '';
    $db_name = '';

    // Connect to PostgreSQL database
    $connection_string = "host={$db_host} port={$db_port} dbname={$db_name} user={$db_user} password={$db_password}";
    $dbconn = pg_connect($connection_string);

    // Check if connection was successful
    if (!$dbconn) {
        error_log("Connection to database failed.");
        return;
    }
    $display_result = get_option('pgwp_display_result');

    // Query the database for the reservation code
    $query = "SELECT * FROM public.\"Prospect\" WHERE \"Offer_Code\" = $1";
    $result = pg_query_params($dbconn, $query, array($reservation_code));

    // Prepare the message depending on whether the code was found or not
    $message = (pg_num_rows($result) > 0) ? get_option('pgwp_found_message') : get_option('pgwp_not_found_message');

    // Update the entry with the result message
    GFAPI::update_entry_field($entry['id'], $result_field_id, $message);

    // Show result message above the confirmation text if option is enabled
  if ($display_result) {
        if (pg_num_rows($result) > 0) {
            // Code found, display the "Found Message" data
            $found_message = get_option('pgwp_found_message');
            echo '<p style="color: green;">' . $found_message . '</p>';
        } else {
            // Code not found, display the "Not Found Message" data
            $not_found_message = get_option('pgwp_not_found_message');
            echo '<p style="color: red;">' . $not_found_message . '</p>';
        }
    }
    pg_close($dbconn); // Close the connection
}

function custom_confirmation_message($confirmation, $form, $entry, $ajax) {
    $result_field_id = get_option('pgwp_result_field');
    $message = rgar($entry, $result_field_id);
    $custom_message = "<div style='color: red;'>{$message}</div>";
    return $custom_message . $confirmation;
}

// Add admin menu page for plugin settings
add_action('admin_menu', 'pgwp_plugin_menu');
function pgwp_plugin_menu() {
    add_menu_page('PostgreSQL Gravity Form Integration Settings', 'PosgresqlWP', 'manage_options', 'pgwp-settings', 'pgwp_plugin_settings_page');
}

// The settings page content
function pgwp_plugin_settings_page() {
    // Check if user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Save settings if POST request is made
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        update_option('pgwp_form_id', sanitize_text_field($_POST['pgwp_form_id']));
        update_option('pgwp_field_id', sanitize_text_field($_POST['pgwp_field_id']));
        update_option('pgwp_result_field', sanitize_text_field($_POST['pgwp_result_field']));
        update_option('pgwp_found_message', sanitize_text_field($_POST['pgwp_found_message']));
        update_option('pgwp_not_found_message', sanitize_text_field($_POST['pgwp_not_found_message']));
        update_option('pgwp_display_result', isset($_POST['pgwp_display_result']) ? '1' : ''); // Save the checkbox value
        echo '<div id="message" class="updated fade"><p>Settings saved.</p></div>';
    }

    // Retrieve current settings
    $form_id = get_option('pgwp_form_id');
    $field_id = get_option('pgwp_field_id');
    $result_field_id = get_option('pgwp_result_field');
    $found_message = get_option('pgwp_found_message');
    $not_found_message = get_option('pgwp_not_found_message');
    $display_result = get_option('pgwp_display_result');

    // Output settings form
    ?>
<div class="wrap">
	<div class="wrap pgwp-admin-gradient">
        <h1>PostgreSQL Gravity Form Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Gravity Form ID:</th>
                    <td><input type="text" name="pgwp_form_id" value="<?php echo esc_attr($form_id); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Reservation Code Field ID:</th>
                    <td><input type="text" name="pgwp_field_id" value="<?php echo esc_attr($field_id); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Result Field ID:</th>
                    <td><input type="text" name="pgwp_result_field" value="<?php echo esc_attr($result_field_id); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Found Message:</th>
                    <td><input type="text" name="pgwp_found_message" value="<?php echo esc_attr($found_message); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Not Found Message:</th>
                    <td><input type="text" name="pgwp_not_found_message" value="<?php echo esc_attr($not_found_message); ?>" /></td>
                </tr>
				  <tr valign="top">
                    <th scope="row">Display Result:</th>
                    <td>
                        <input type="checkbox" name="pgwp_display_result" value="1" <?php checked($display_result, 1); ?> />
                        <label for="pgwp_display_result">Display Reservation Code Result</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
	</div>

    <?php
}

// Activation hook to create default settings
register_activation_hook(__FILE__, 'pgwp_activate');
function pgwp_activate() {
    add_option('pgwp_form_id', '');
    add_option('pgwp_field_id', '');
    add_option('pgwp_result_field', '');
    add_option('pgwp_found_message', 'Code is valid.');
    add_option('pgwp_not_found_message', 'Invalid code.');
    add_option('pgwp_display_result', ''); // Default to not display
}

// Deactivation hook to remove settings
register_deactivation_hook(__FILE__, 'pgwp_deactivate');
function pgwp_deactivate() {
    delete_option('pgwp_form_id');
    delete_option('pgwp_field_id');
    delete_option('pgwp_result_field');
    delete_option('pgwp_found_message');
    delete_option('pgwp_not_found_message');
    delete_option('pgwp_display_result'); // Remove the option on deactivation
}
// Add this function to your existing plugin code
function pgwp_admin_styles() {
    ?>
    <style>
		$image_url = plugins_url('res/logo.png', __FILE__);
    ?>
    <style>
        .pgwp-admin-abstract::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 200px; /* Adjust width as needed */
            background: url('<?php echo $image_url; ?>') no-repeat center center;
            background-size: contain;
        }
        /* Add this to your admin head for styles to be applied */
        .pgwp-admin-gradient {
background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    background-size: 200% 200%;
    animation: GradientShift 15s ease infinite;
    color: #fff;
    padding: 20px;
    margin-top: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
        }
		@keyframes GradientShift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
    </style>
    <?php
}


// Hook the above function into admin_head
add_action('admin_head', 'pgwp_admin_styles');

?>
