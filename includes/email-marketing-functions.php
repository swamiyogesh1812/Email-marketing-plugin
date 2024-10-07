<?php
// Save email marketing data from Contact Form 7
add_action('wpcf7_before_send_mail', 'cf7_save_email_marketing_data');

function cf7_save_email_marketing_data($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();
        if (isset($data['marketing']) && !empty($data['marketing'])) {
            global $wpdb;

            $first_name = sanitize_text_field($data['Firstname']); 
            $last_name = sanitize_text_field($data['Lastname']);  
            $email = sanitize_email($data['email-0']);             

            // Insert data into the custom table
            $table_name = $wpdb->prefix . 'email_marketing'; 
            $wpdb->insert(
                $table_name,
                array(
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'email'      => $email,
                    'date'       => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
    }
}


// Handle the AJAX request to delete the table entries
add_action('wp_ajax_cf7_delete_email_marketing_table', 'cf7_delete_email_marketing_table_ajax');

// Add a menu item to display the email marketing entries
add_action('admin_menu', 'cf7_email_marketing_menu');
function cf7_email_marketing_menu() {
    add_menu_page(
        'Email Marketing Entries', 
        'Email Marketing', 
        'manage_options', 
        'cf7-email-marketing',
        'cf7_display_email_marketing_data',
        'dashicons-email-alt', 
        6 
    );
}

// Display the email marketing data in wordpress dashboard
function cf7_display_email_marketing_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    echo '<div class="wrap">';
    echo '<h1>Email Marketing Entries</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>First Name</th>';
    echo '<th>Last Name</th>';
    echo '<th>Email</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if (!empty($results)) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->first_name) . '</td>';
            echo '<td>' . esc_html($row->last_name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No entries found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

//paid services form data save

add_action('wp_loaded', 'cf7_global_form_submission');

function cf7_global_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['marketing'])) {
        $first_name = isset($_POST['fname']) ? sanitize_text_field($_POST['fname']) : '';
        $last_name = isset($_POST['lname']) ? sanitize_text_field($_POST['lname']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (is_email($email)) {
            cf7_save_marketing_data($first_name, $last_name, $email);
        } else {
            error_log('Invalid email address: ' . $email); 
        }
    }
} 

function cf7_save_marketing_data($first_name, $last_name, $email) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $result = $wpdb->insert(
        $table_name,
        array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'date'       => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s')
    );
    if ($result === false) {
        error_log('Database insert failed: ' . $wpdb->last_error);
    }
}
 

?>
