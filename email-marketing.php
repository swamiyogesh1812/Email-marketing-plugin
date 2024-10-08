<?php
/*
Plugin Name: Email Marketing
Description: A plugin to save users' email and name from WordPress and Contact Form 7.
Version: 1.0.2
Author: Yogesh
*/


define('CF7_EMAIL_MARKETING_DIR', plugin_dir_path(__FILE__));

require_once CF7_EMAIL_MARKETING_DIR . 'includes/email-marketing-functions.php';

  
add_action('admin_enqueue_scripts', 'cf7_enqueue_deactivation_alert_script');
function cf7_enqueue_deactivation_alert_script($hook) {
    if ($hook == 'plugins.php') {
        wp_enqueue_script('cf7-deactivation-alert', plugin_dir_url(__FILE__) . 'includes/deactivation-alert.js', array('jquery'), null, true);
        wp_localize_script('cf7-deactivation-alert', 'cf7_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cf7_deactivation_nonce'),
        ));
    }
}

// Handle the AJAX request to delete the table entries
add_action('wp_ajax_cf7_delete_email_marketing_table', 'cf7_delete_email_marketing_table_ajax');

function cf7_delete_email_marketing_table_ajax() {
    check_ajax_referer('cf7_deactivation_nonce', 'security');

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $wpdb->query("TRUNCATE TABLE $table_name");
    wp_send_json_success();
}


function cf7_create_email_marketing_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_marketing';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'cf7_create_email_marketing_table'); 