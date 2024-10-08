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

// Display the email marketing data in WordPress dashboard and add the export button
function cf7_display_email_marketing_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    echo '<div class="wrap">';
    echo '<div style="display: flex; align-items: center;">';
    echo '<h1>Email Marketing Entries</h1>';
    echo '<form method="get" action="' . admin_url('admin-post.php') . '">';
    echo '<input type="hidden" name="action" value="export_email_marketing">';
    echo '<input type="submit" name="export_email_marketing" class="button button-primary" value="Export to Excel" style="margin-left: 15px;">';
    echo '</form>';
    echo '</div>'; 
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
        echo '<tr><td colspan="3">No entries found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Handle the export request via admin-post.php action
add_action('admin_post_export_email_marketing', 'cf7_export_email_marketing_data');

function cf7_export_email_marketing_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $results = $wpdb->get_results("SELECT first_name, last_name, email FROM $table_name ORDER BY id DESC", ARRAY_A);

    if (!empty($results)) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="email_marketing_entries.xls"');
        header('Cache-Control: max-age=0');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('First Name', 'Last Name', 'Email'), "\t");
        foreach ($results as $row) {
            fputcsv($output, $row, "\t");
        }
        fclose($output);
        exit;
    } else {
        wp_die('No data available to export.');
    }
}
?>
