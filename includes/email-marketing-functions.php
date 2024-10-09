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
            $table_name = $wpdb->prefix . 'email_marketing';
            $email_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE email = %s",
                $email
            ));
            if ($email_exists == 0) {
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

// Register AJAX actions for logged-in and logged-out users
add_action('wp_ajax_delete_email_marketing_entries', 'delete_email_marketing_entries');

function delete_email_marketing_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';

    // Check if entry IDs are set
    if (isset($_POST['entry_ids']) && !empty($_POST['entry_ids'])) {
        $ids_to_delete = implode(',', array_map('intval', $_POST['entry_ids']));
        $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids_to_delete)");

        // Send a JSON response back to the client
        wp_send_json_success('Selected entries have been deleted.');
    } else {
        wp_send_json_error('No entries selected for deletion.');
    }
}
function cf7_display_email_marketing_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    echo '<div class="wrap">';
    echo '<div style="display: flex; align-items: center;">';
    echo '<h1>Email Marketing Entries</h1>';

    // Export to Excel button
    echo '<form method="get" action="' . admin_url('admin-post.php') . '">';
    echo '<input type="hidden" name="action" value="export_email_marketing">';
    echo '<input type="submit" name="export_email_marketing" class="button button-primary" value="Export to Excel" style="margin-left: 15px;">';
    echo '</form>';
    echo '</div>';

    // Bulk action dropdown and apply button
    echo '<form id="email-marketing-form" method="post">';
    echo '<div class="tablenav top">';
    echo '<div class="alignleft actions">';
    echo '<select name="bulk_action">';
    echo '<option value="">Bulk actions</option>';
    echo '<option value="move_to_trash">Move to Trash</option>';
    echo '</select>';
    echo '<input type="button" id="apply-bulk-action" class="button action" value="Apply">';
    echo '</div>';
    echo '</div>';

    // Table for displaying entries
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 50px;"><input type="checkbox" id="select-all" style=" margin: 0;"></th>'; 
    echo '<th>First Name</th>';
    echo '<th>Last Name</th>';
    echo '<th>Email</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody id="email-marketing-entries">';

    if (!empty($results)) {
        foreach ($results as $row) {
            echo '<tr id="entry-' . esc_attr($row->id) . '">';
            echo '<td><input type="checkbox" name="entry_ids[]" value="' . esc_attr($row->id) . '"></td>'; 
            echo '<td>' . esc_html($row->first_name) . '</td>';
            echo '<td>' . esc_html($row->last_name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No entries found.</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</form>';

    // JavaScript for handling AJAX requests
    echo '<script>
    document.getElementById("select-all").addEventListener("click", function(event) {
        var checkboxes = document.querySelectorAll(\'input[name="entry_ids[]"]\');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = event.target.checked;
        }
    });

    document.getElementById("apply-bulk-action").addEventListener("click", function() {
        var bulkAction = document.querySelector(\'select[name="bulk_action"]\').value;
        var selectedEntries = document.querySelectorAll(\'input[name="entry_ids[]"]:checked\');
        var entryIds = [];

        selectedEntries.forEach(function(entry) {
            entryIds.push(entry.value);
        });

        if (bulkAction === "move_to_trash" && entryIds.length > 0) {
            var formData = new FormData();
            formData.append("action", "delete_email_marketing_entries");
            formData.append("entry_ids", entryIds);

            fetch(ajaxurl, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    entryIds.forEach(function(id) {
                        var row = document.getElementById("entry-" + id);
                        if (row) {
                            row.remove(); // Remove the row from the table
                        }
                    });
                    alert(data.data);
                } else {
                    alert(data.data);
                }
            });
        } else {
            alert("No entries selected or invalid action.");
        }
    });
    </script>';

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
    $email_exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE email = %s",
        $email
    ));

    if ($email_exists > 0) {
        error_log('Email already exists: ' . $email);
        return;
    }
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

// Handle the export request via admin-post.php action
add_action('admin_post_export_email_marketing', 'cf7_export_email_marketing_data');

function cf7_export_email_marketing_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $results = $wpdb->get_results("SELECT first_name, last_name, email FROM $table_name ORDER BY id DESC", ARRAY_A);

    if (!empty($results)) {
        ob_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="email_marketing_entries.csv"');
        header('Cache-Control: max-age=0');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('First Name', 'Last Name', 'Email'));
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    } else {
        wp_die('No data available to export.');
    }
}


?>
