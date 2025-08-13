<?php
/**
 * Uninstall script for Kadence Action Network Integration
 * 
 * This file is executed when the plugin is deleted from WordPress.
 * It cleans up all plugin data including options and post meta.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete global options
delete_option('kadence_an_api_key');

// Delete post meta from all Kadence forms
$forms = get_posts(array(
    'post_type' => 'kadence_form',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($forms as $form) {
    delete_post_meta($form->ID, '_kadence_an_endpoint');
    delete_post_meta($form->ID, '_kadence_an_tags');
    delete_post_meta($form->ID, '_kadence_an_management_url');
    delete_post_meta($form->ID, '_kadence_an_validation_settings');
    delete_post_meta($form->ID, '_kadence_an_custom_validation');
}

// Delete log file if it exists
$log_file = plugin_dir_path(__FILE__) . 'kadence-an-log.txt';
if (file_exists($log_file)) {
    unlink($log_file);
}

// Clear any transients
delete_transient('kadence_an_validation_settings');

// Log the uninstall
error_log('[Kadence AN] Plugin uninstalled - all data cleaned up'); 