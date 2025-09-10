<?php
/*
Plugin Name: Kadence Action Network Integration
Description: Sends Kadence Blocks Pro form submissions to Action Network via their REST API with advanced validation capabilities.
Version: 1.1.2
Author: Tim Gutowski
License: GPL v2 or later
Text Domain: kadence-action-network
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define plugin constants
define( 'KADENCE_AN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'KADENCE_AN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'KADENCE_AN_VERSION' ) or define( 'KADENCE_AN_VERSION', '1.1.2' );
define( 'KADENCE_AN_GITHUB_REPO', 'timgut/kadence-action-network' );

// Simple GitHub Update Checker Class
class Kadence_AN_GitHub_Updater {
    private $plugin_file;
    private $github_repo;
    private $current_version;
    
    public function __construct( $plugin_file, $github_repo, $current_version ) {
        $this->plugin_file = $plugin_file;
        $this->github_repo = $github_repo;
        $this->current_version = $current_version;
        
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
    }
    
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ( $remote_version && version_compare( $this->current_version, $remote_version, '<' ) ) {
            $plugin_slug = plugin_basename( $this->plugin_file );
            $transient->response[ $plugin_slug ] = (object) array(
                'slug' => dirname( $plugin_slug ),
                'plugin' => $plugin_slug,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_repo}",
                'package' => "https://github.com/{$this->github_repo}/archive/refs/heads/main.zip",
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => get_bloginfo( 'version' ),
                'requires_php' => '7.4',
                'compatibility' => new stdClass(),
            );
        }
        
        return $transient;
    }
    
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }
        
        $plugin_slug = plugin_basename( $this->plugin_file );
        
        if ( $args->slug !== dirname( $plugin_slug ) ) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ( ! $remote_version ) {
            return $result;
        }
        
        $result = new stdClass();
        $result->name = 'Kadence Action Network Integration';
        $result->slug = dirname( $plugin_slug );
        $result->version = $remote_version;
        $result->tested = get_bloginfo( 'version' );
        $result->requires = '5.0';
        $result->requires_php = '7.4';
        $result->last_updated = date( 'Y-m-d' );
        $result->sections = array(
            'description' => 'Sends Kadence Blocks Pro form submissions to Action Network via their REST API with advanced validation capabilities.',
            'changelog' => 'See the changelog in the plugin files for detailed information about updates.',
        );
        $result->download_link = "https://github.com/{$this->github_repo}/archive/refs/heads/main.zip";
        
        return $result;
    }
    
    private function get_remote_version() {
        $cache_key = 'kadence_an_remote_version';
        $cached_version = get_transient( $cache_key );
        
        if ( $cached_version !== false ) {
            return $cached_version;
        }
        
        $response = wp_remote_get( "https://api.github.com/repos/{$this->github_repo}/releases/latest", array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress Plugin Update Checker'
            )
        ) );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['tag_name'] ) ) {
            $version = ltrim( $data['tag_name'], 'v' );
            set_transient( $cache_key, $version, HOUR_IN_SECONDS );
            return $version;
        }
        
        return false;
    }
}

// Initialize the updater
new Kadence_AN_GitHub_Updater( 
    __FILE__, 
    KADENCE_AN_GITHUB_REPO, 
    KADENCE_AN_VERSION 
);

// Activation hook
register_activation_hook( __FILE__, 'kadence_an_activate_plugin' );
function kadence_an_activate_plugin() {
    // Activation logic here (if needed)
}

// Include admin settings page
if ( is_admin() ) {
    require_once KADENCE_AN_PLUGIN_PATH . 'admin-settings.php';
    
    // Enqueue admin styles
    add_action('admin_enqueue_scripts', 'kadence_an_enqueue_admin_styles');
    
    // Add admin menu links
    add_action('admin_menu', 'kadence_an_add_admin_menu_links');
}

function kadence_an_enqueue_admin_styles($hook) {
    // Only load on kadence_form post type
    global $post_type;
    if ($post_type === 'kadence_form' || strpos($hook, 'kadence') !== false) {
        wp_enqueue_style(
            'kadence-an-admin',
            KADENCE_AN_PLUGIN_URL . 'css/admin-style.css',
            array(),
            KADENCE_AN_VERSION
        );
    }
}

function kadence_an_add_admin_menu_links() {
    // Only add the submenu item under Tools for easy access (no main sidebar menu)
    add_management_page(
        'Kadence AN Logs',
        'Kadence AN Logs',
        'manage_options',
        'kadence-an-logs-tools',
        'kadence_an_render_logs_page'
    );
}

// Enqueue validation JavaScript
add_action('wp_enqueue_scripts', 'kadence_an_enqueue_validation_scripts');
function kadence_an_enqueue_validation_scripts() {
    wp_enqueue_script(
        'kadence-an-validation',
        KADENCE_AN_PLUGIN_URL . 'js/form-validation.js',
        array(), // Removed jQuery dependency
        KADENCE_AN_VERSION,
        true
    );
    
    // Pass validation settings to JavaScript
    wp_localize_script('kadence-an-validation', 'kadenceANValidationSettings', kadence_an_get_validation_settings());
}

function kadence_an_get_validation_settings() {
    $settings = array();
    
    // Get all Kadence forms with validation settings
    $forms = get_posts(array(
        'post_type' => 'kadence_form',
        'numberposts' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($forms as $form) {
        $validation_settings = get_post_meta($form->ID, '_kadence_an_validation_settings', true);
        $custom_validation = get_post_meta($form->ID, '_kadence_an_custom_validation', true);
        
        if (!empty($validation_settings)) {
            // Convert validation settings to the format expected by JavaScript
            $validation_rules = array();
            foreach ($validation_settings as $field) {
                $validation_rules[$field['field_name']] = array(
                    'validation_type' => $field['validation_type'],
                    'validation_param' => $field['validation_param'] ?? '',
                    'error_message' => $field['error_message']
                );
            }
            
            $settings[$form->ID] = array(
                'validation' => $validation_rules,
                'custom' => $custom_validation
            );
        }
    }
    
    return $settings;
}

// Register REST API endpoint for Kadence form submissions
add_action( 'rest_api_init', function() {
    register_rest_route( 'kadence-an/v1', '/submit', array(
        'methods'  => 'POST',
        'callback' => 'kadence_an_handle_form_submission',
        'permission_callback' => '__return_true', // You may want to add better security
    ));
});

// --- Kadence Form Action Network Settings Meta Box ---
add_action('add_meta_boxes', function() {
    add_meta_box(
        'kadence_an_settings',
        'Action Network Integration',
        'kadence_an_render_settings_metabox',
        'kadence_form',
        'side', // Changed back to 'side' for sidebar placement
        'default'
    );
});

function kadence_an_render_settings_metabox($post) {
    $endpoint = get_post_meta($post->ID, '_kadence_an_endpoint', true);
    $tags = get_post_meta($post->ID, '_kadence_an_tags', true);
    $management_url = get_post_meta($post->ID, '_kadence_an_management_url', true);
    $validation_settings = get_post_meta($post->ID, '_kadence_an_validation_settings', true);
    $custom_validation = get_post_meta($post->ID, '_kadence_an_custom_validation', true);
    
    // HTTP Basic Auth settings
    $http_auth_enabled = get_post_meta($post->ID, '_kadence_an_http_auth_enabled', true);
    $http_auth_username = get_post_meta($post->ID, '_kadence_an_http_auth_username', true);
    $http_auth_password = get_post_meta($post->ID, '_kadence_an_http_auth_password', true);
    
    // Default validation settings if empty
    if (empty($validation_settings)) {
        $validation_settings = array();
    }
    
    wp_nonce_field('kadence_an_save_settings', 'kadence_an_settings_nonce');
    ?>
    
    <div class="kadence-an-meta-box">
        <!-- Action Network Settings -->
        <h4>Action Network Configuration</h4>
        <p><label for="kadence_an_endpoint"><strong>AN Endpoint URL</strong></label><br />
        <input type="text" name="kadence_an_endpoint" id="kadence_an_endpoint" value="<?php echo esc_attr($endpoint); ?>" style="width:100%" placeholder="https://actionnetwork.org/api/v2/forms/your-form-id" />
        <span class="kadence-an-help-text">Enter the Action Network form endpoint URL. "/submissions" will be automatically appended if not present.</span></p>
        
        <p><label for="kadence_an_tags"><strong>Tags (comma-separated)</strong></label><br />
        <input type="text" name="kadence_an_tags" id="kadence_an_tags" value="<?php echo esc_attr($tags); ?>" style="width:100%" /></p>
        
        <p><label for="kadence_an_management_url"><strong>Action Network Form Management URL</strong></label><br />
        <input type="text" name="kadence_an_management_url" id="kadence_an_management_url" value="<?php echo esc_attr($management_url); ?>" style="width:100%" placeholder="https://actionnetwork.org/forms/your-form-id" />
        <span class="kadence-an-help-text">This URL is for administrative reference only and will not be used in API requests.</span></p>
        
        <p><a href="<?php echo admin_url('admin.php?page=kadence-an-logs'); ?>" class="button button-secondary" target="_blank">
            <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 5px;"></span>
            View Form Logs
        </a>
        <span class="kadence-an-help-text">View submission logs and debug information for this form.</span></p>
    </div>
    
    <hr class="kadence-an-divider">
    
    <div class="kadence-an-meta-box">
        <!-- HTTP Basic Auth Settings -->
        <h4>HTTP Basic Auth (Development Only)</h4>
        <p class="kadence-an-help-text">Enable this only during development when HTTP Basic Auth is active. Disable when going live.</p>
        
        <p><label><input type="checkbox" name="kadence_an_http_auth_enabled" value="1" <?php checked($http_auth_enabled, '1'); ?> /> Enable HTTP Basic Auth for webhook requests</label></p>
        
        <div id="http-auth-settings" style="<?php echo $http_auth_enabled ? '' : 'display: none;'; ?>">
            <p><label for="kadence_an_http_auth_username"><strong>Username</strong></label><br />
            <input type="text" name="kadence_an_http_auth_username" id="kadence_an_http_auth_username" value="<?php echo esc_attr($http_auth_username); ?>" style="width:100%" placeholder="afscmedev" /></p>
            
            <p><label for="kadence_an_http_auth_password"><strong>Password</strong></label><br />
            <input type="password" name="kadence_an_http_auth_password" id="kadence_an_http_auth_password" value="<?php echo esc_attr($http_auth_password); ?>" style="width:100%" placeholder="trilogy" /></p>
        </div>
    </div>
    
    <hr class="kadence-an-divider">
    
    <div class="kadence-an-meta-box">
        <!-- Webhook Configuration -->
        <h4>Webhook Configuration</h4>
        <p class="kadence-an-help-text">Use this webhook URL in your Kadence form settings:</p>
        
        <p><label><strong>Full Webhook URL:</strong></label><br />
        <input type="text" value="<?php echo esc_attr(kadence_an_get_webhook_url()); ?>" readonly style="width: 100%; background: #f9f9f9; font-family: monospace; font-size: 12px;" onclick="this.select();" />
        <span class="kadence-an-help-text">Click to select and copy this URL for your current environment.</span></p>
        
        <p><label><strong>Webhook Path (domain-agnostic):</strong></label><br />
        <input type="text" value="wp-json/kadence-an/v1/submit" readonly style="width: 100%; background: #f9f9f9; font-family: monospace; font-size: 12px;" onclick="this.select();" />
        <span class="kadence-an-help-text">Use this path when setting up webhooks in other environments. Just prepend your domain.</span></p>
    </div>
    
    <div class="kadence-an-meta-box">
        <!-- Validation Settings -->
        <h4>Form Validation Settings</h4>
        <p class="kadence-an-help-text">Configure custom validation for form fields. Leave empty to use Kadence's default HTML validation.</p>
        
        <!-- Field Validation Mapping -->
        <div id="field-validation-mapping">
            <h5>Field Validation Rules</h5>
            <p class="kadence-an-help-text">Map form fields to validation types. Field names should match your Kadence form field IDs.</p>
            
            <div id="validation-fields">
                <?php
                if (!empty($validation_settings)) {
                    foreach ($validation_settings as $index => $field) {
                        ?>
                        <div class="validation-field-row">
                            <label><strong>Field Name:</strong></label><br>
                            <input type="text" name="validation_settings[<?php echo $index; ?>][field_name]" value="<?php echo esc_attr($field['field_name']); ?>" style="width: 100%; margin-bottom: 5px;" placeholder="e.g., email, phone, zip_code" />
                            
                            <label><strong>Validation Type:</strong></label><br>
                            <select name="validation_settings[<?php echo $index; ?>][validation_type]" style="width: 100%; margin-bottom: 5px;">
                                <option value="">Use HTML5 validation</option>
                                <option value="required" <?php selected($field['validation_type'], 'required'); ?>>Required field</option>
                                <option value="email" <?php selected($field['validation_type'], 'email'); ?>>Email format</option>
                                <option value="us_zip" <?php selected($field['validation_type'], 'us_zip'); ?>>US ZIP code</option>
                                <option value="phone" <?php selected($field['validation_type'], 'phone'); ?>>Phone number</option>
                                <option value="url" <?php selected($field['validation_type'], 'url'); ?>>URL format</option>
                                <option value="number" <?php selected($field['validation_type'], 'number'); ?>>Number</option>
                                <option value="min_length" <?php selected($field['validation_type'], 'min_length'); ?>>Minimum length</option>
                                <option value="max_length" <?php selected($field['validation_type'], 'max_length'); ?>>Maximum length</option>
                                <option value="date" <?php selected($field['validation_type'], 'date'); ?>>Date</option>
                                <option value="custom" <?php selected($field['validation_type'], 'custom'); ?>>Custom function</option>
                            </select>
                            
                            <label><strong>Validation Parameter:</strong></label><br>
                            <input type="text" name="validation_settings[<?php echo $index; ?>][validation_param]" value="<?php echo esc_attr($field['validation_param'] ?? ''); ?>" style="width: 100%; margin-bottom: 5px;" placeholder="e.g., 10 for min_length, 255 for max_length" />
                            
                            <label><strong>Error Message:</strong></label><br>
                            <input type="text" name="validation_settings[<?php echo $index; ?>][error_message]" value="<?php echo esc_attr($field['error_message']); ?>" style="width: 100%; margin-bottom: 5px;" placeholder="Custom error message (optional)" />
                            
                            <button type="button" class="button remove-validation-field">Remove Field</button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" class="button" id="add-validation-field">Add Field Validation Rule</button>
        </div>
        
        <!-- Custom Validation Functions -->
        <div style="margin-top: 20px;">
            <h5>Custom Validation Functions</h5>
            <p class="kadence-an-help-text">Define custom JavaScript validation functions. Use 'custom' validation type above to reference these functions.</p>
            <textarea name="kadence_an_custom_validation" id="kadence_an_custom_validation" rows="8" style="width: 100%; font-family: monospace;" placeholder="// Example custom validation function:
function validateCustomField(value, fieldName) {
    if (value.length < 5) {
        return 'Field must be at least 5 characters long';
    }
    return null; // Return null if validation passes
}"><?php echo esc_textarea($custom_validation); ?></textarea>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var fieldIndex = <?php echo !empty($validation_settings) ? max(array_keys($validation_settings)) + 1 : 0; ?>;
        
        // Toggle HTTP Auth settings visibility
        $('input[name="kadence_an_http_auth_enabled"]').change(function() {
            if ($(this).is(':checked')) {
                $('#http-auth-settings').show();
            } else {
                $('#http-auth-settings').hide();
            }
        });
        
        $('#add-validation-field').click(function() {
            var fieldHtml = '<div class="validation-field-row">' +
                '<label><strong>Field Name:</strong></label><br>' +
                '<input type="text" name="validation_settings[' + fieldIndex + '][field_name]" style="width: 100%; margin-bottom: 5px;" placeholder="e.g., email, phone, zip_code" />' +
                '<label><strong>Validation Type:</strong></label><br>' +
                '<select name="validation_settings[' + fieldIndex + '][validation_type]" style="width: 100%; margin-bottom: 5px;">' +
                '<option value="">Use HTML5 validation</option>' +
                '<option value="required">Required field</option>' +
                '<option value="email">Email format</option>' +
                '<option value="us_zip">US ZIP code</option>' +
                '<option value="phone">Phone number</option>' +
                '<option value="url">URL format</option>' +
                '<option value="number">Number</option>' +
                '<option value="min_length">Minimum length</option>' +
                '<option value="max_length">Maximum length</option>' +
                '<option value="date">Date</option>' +
                '<option value="custom">Custom function</option>' +
                '</select>' +
                '<label><strong>Validation Parameter:</strong></label><br>' +
                '<input type="text" name="validation_settings[' + fieldIndex + '][validation_param]" style="width: 100%; margin-bottom: 5px;" placeholder="e.g., 10 for min_length, 255 for max_length" />' +
                '<label><strong>Error Message:</strong></label><br>' +
                '<input type="text" name="validation_settings[' + fieldIndex + '][error_message]" style="width: 100%; margin-bottom: 5px;" placeholder="Custom error message (optional)" />' +
                '<button type="button" class="button remove-validation-field">Remove Field</button>' +
                '</div>';
            
            $('#validation-fields').append(fieldHtml);
            fieldIndex++;
        });
        
        $(document).on('click', '.remove-validation-field', function() {
            $(this).closest('.validation-field-row').remove();
        });
    });
    </script>
    <?php
}

add_action('save_post_kadence_form', function($post_id) {
    kadence_an_log("save_post_kadence_form triggered for post_id: $post_id");
    
    // Only check nonce if we have POST data (first save)
    if (!empty($_POST) && (!isset($_POST['kadence_an_settings_nonce']) || !wp_verify_nonce($_POST['kadence_an_settings_nonce'], 'kadence_an_save_settings'))) {
        kadence_an_log('Nonce verification failed or nonce not set');
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Only save if we have POST data (first save)
    if (empty($_POST)) {
        kadence_an_log('No POST data, skipping meta box save');
        return;
    }
    
    kadence_an_log('Saving meta box data for post_id: ' . $post_id);
    
    if (isset($_POST['kadence_an_endpoint'])) {
        $endpoint = sanitize_text_field($_POST['kadence_an_endpoint']);
        update_post_meta($post_id, '_kadence_an_endpoint', $endpoint);
        kadence_an_log('Saved endpoint: ' . $endpoint);
    }
    if (isset($_POST['kadence_an_tags'])) {
        $tags = sanitize_text_field($_POST['kadence_an_tags']);
        update_post_meta($post_id, '_kadence_an_tags', $tags);
        kadence_an_log('Saved tags: ' . $tags);
    }
    if (isset($_POST['kadence_an_management_url'])) {
        $management_url = sanitize_text_field($_POST['kadence_an_management_url']);
        update_post_meta($post_id, '_kadence_an_management_url', $management_url);
        kadence_an_log('Saved management URL: ' . $management_url);
    }
    
    // Save HTTP Basic Auth settings
    if (isset($_POST['kadence_an_http_auth_enabled'])) {
        update_post_meta($post_id, '_kadence_an_http_auth_enabled', '1');
        kadence_an_log('HTTP Basic Auth enabled for form ' . $post_id);
    } else {
        update_post_meta($post_id, '_kadence_an_http_auth_enabled', '0');
        kadence_an_log('HTTP Basic Auth disabled for form ' . $post_id);
    }
    
    if (isset($_POST['kadence_an_http_auth_username'])) {
        $username = sanitize_text_field($_POST['kadence_an_http_auth_username']);
        update_post_meta($post_id, '_kadence_an_http_auth_username', $username);
        kadence_an_log('Saved HTTP Auth username: ' . $username);
    }
    
    if (isset($_POST['kadence_an_http_auth_password'])) {
        $password = sanitize_text_field($_POST['kadence_an_http_auth_password']);
        update_post_meta($post_id, '_kadence_an_http_auth_password', $password);
        kadence_an_log('Saved HTTP Auth password');
    }
    
    // Save validation settings
    if (isset($_POST['validation_settings']) && is_array($_POST['validation_settings'])) {
        $validation_settings = array();
        foreach ($_POST['validation_settings'] as $field) {
            if (!empty($field['field_name']) && !empty($field['validation_type'])) {
                $validation_settings[] = array(
                    'field_name' => sanitize_text_field($field['field_name']),
                    'validation_type' => sanitize_text_field($field['validation_type']),
                    'validation_param' => sanitize_text_field($field['validation_param'] ?? ''),
                    'error_message' => sanitize_text_field($field['error_message'])
                );
            }
        }
        update_post_meta($post_id, '_kadence_an_validation_settings', $validation_settings);
        kadence_an_log('Saved validation settings: ' . count($validation_settings) . ' rules');
    }
    
    // Save custom validation functions
    if (isset($_POST['kadence_an_custom_validation'])) {
        // Preserve JavaScript syntax by using raw input
        $custom_validation = $_POST['kadence_an_custom_validation'];
        update_post_meta($post_id, '_kadence_an_custom_validation', $custom_validation);
        kadence_an_log('Saved custom validation functions');
    }
    
    kadence_an_log('Meta box save completed');
});

// Simple file logger for debugging
function kadence_an_log($message) {
    $log_file = KADENCE_AN_PLUGIN_PATH . 'kadence-an-log.txt';
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    $log_entry = "[$timestamp] $message\n";
    // Try to write to the file
    $result = @file_put_contents($log_file, $log_entry, FILE_APPEND);
    // Also log to PHP error log (visible in server logs/console)
    error_log('[Kadence AN] ' . $message);
    // If file write failed, log an error
    if ($result === false) {
        error_log('[Kadence AN] ERROR: Could not write to log file: ' . $log_file);
    }
}

// Helper function to get the webhook URL for the current site
function kadence_an_get_webhook_url() {
    return get_rest_url(null, 'kadence-an/v1/submit');
}

function kadence_an_handle_form_submission( $request ) {
    // Log that the webhook was hit
    kadence_an_log('=== WEBHOOK HIT ===');
    kadence_an_log('Request method: ' . $request->get_method());
    kadence_an_log('Request headers: ' . print_r($request->get_headers(), true));
    kadence_an_log('Request body: ' . $request->get_body());
    
    $params = $request->get_json_params();
    if ( empty( $params ) ) {
        // Try to get params from form data (application/x-www-form-urlencoded or multipart/form-data)
        $params = $request->get_body_params();
        kadence_an_log('Using body params instead of JSON params');
    }
    if ( empty( $params ) ) {
        kadence_an_log('No data received in submission.');
        return new WP_Error( 'no_data', 'No data received', array( 'status' => 400 ) );
    }
    
    // Log the received submission data
    kadence_an_log('Received form-encoded submission: ' . print_r($params, true));
    // Try to get form ID from params
    $form_id = $params['post_id']; // ?? $params['_kb_adv_form_post_id'] ?? $params['post_id'] ?? null;
    // Always get endpoint from post meta, not from params
    $endpoint = null;
    if ($form_id) {
        $meta_endpoint = get_post_meta($form_id, '_kadence_an_endpoint', true);
        if ($meta_endpoint) {
            $endpoint = $meta_endpoint;
        }
    }
    if (!$endpoint) {
        kadence_an_log('The form with ID ' . $form_id . ' does not have an endpoint, so data cannot be sent to AN.');
        return new WP_Error( 'no_endpoint', 'No Action Network endpoint configured for this form', array( 'status' => 400 ) );
    }
    
    kadence_an_log('Using AN endpoint: ' . $endpoint);

    // Automatically append "/submissions" to the endpoint if not already present
    if (!str_ends_with($endpoint, '/submissions')) {
        $endpoint = rtrim($endpoint, '/') . '/submissions';
    }

    // Get API key from WordPress options
    $api_key = get_option('kadence_an_api_key');
    if (!$api_key) {
        kadence_an_log('No Action Network API key configured. Please set it in Settings > Kadence Action Network.');
        return new WP_Error( 'no_api_key', 'Action Network API key not configured', array( 'status' => 500 ) );
    }

    // Get tags from post meta
    $tags = array();
    if ($form_id) {
        $meta_tags = get_post_meta($form_id, '_kadence_an_tags', true);
        if ($meta_tags) {
            $tags = array_map('trim', explode(',', $meta_tags));
        }
    }

    // Example: Map Kadence fields to AN fields (customize as needed)
    $first_name = $params['first_name'] ?? '';
    $last_name  = $params['last_name'] ?? '';
    $email      = $params['email'] ?? '';
    $phone      = $params['phone'] ?? '';
    $postal_code = $params['postal_code'] ?? '';

    // Handle custom fields sent as custom[fieldname]
    $custom_fields = array();
    if (isset($params['custom']) && is_array($params['custom'])) {
        foreach ($params['custom'] as $fieldname => $value) {
            $custom_fields[$fieldname] = $value;
        }
    }

    $person = array(
        'family_name' => $last_name,
        'given_name' => $first_name,
        'postal_addresses' => array(array('postal_code' => $postal_code)),
        'email_addresses' => array(array('address' => $email)),
        'phone_numbers' => array(array('number' => $phone)),
    );
    if (!empty($custom_fields)) {
        $person['custom_fields'] = $custom_fields;
    }

    $payload = array(
        'person' => $person,
        'add_tags' => $tags,
    );

    kadence_an_log('Sending to AN: ' . wp_json_encode($payload));

    $response = wp_remote_post( $endpoint, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'OSDI-API-Token' => $api_key,
        ),
        'body' => wp_json_encode($payload),
        'timeout' => 20,
    ));

    if ( is_wp_error( $response ) ) {
        kadence_an_log('AN API error: ' . $response->get_error_message());
        return new WP_Error( 'an_api_error', $response->get_error_message(), array( 'status' => 500 ) );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    
    if ( $code < 200 || $code >= 300 ) {
        kadence_an_log("AN API error ($code): $body");
        return new WP_Error( 'an_api_error', $body, array( 'status' => $code ) );
    }

    kadence_an_log("AN response ($code): $body");
    return new WP_REST_Response( array( 'success' => true ), 200 );
}
