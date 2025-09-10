<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'kadence_an_add_settings_page' );
add_action( 'admin_init', 'kadence_an_register_settings' );

function kadence_an_add_settings_page() {
    // Main settings page
    add_options_page(
        'Kadence Action Network Integration',
        'Kadence Action Network',
        'manage_options',
        'kadence-an-settings',
        'kadence_an_render_settings_page'
    );
    
    // Log viewer page
    add_options_page(
        'Kadence AN Logs',
        'Kadence AN Logs',
        'manage_options',
        'kadence-an-logs',
        'kadence_an_render_logs_page'
    );
}

function kadence_an_register_settings() {
    register_setting( 'kadence_an_settings_group', 'kadence_an_api_key' );
    register_setting( 'kadence_an_settings_group', 'kadence_an_log_settings' );
}

// AJAX handlers for log management
add_action( 'wp_ajax_kadence_an_get_logs', 'kadence_an_ajax_get_logs' );
add_action( 'wp_ajax_kadence_an_clear_logs', 'kadence_an_ajax_clear_logs' );
add_action( 'wp_ajax_kadence_an_download_logs', 'kadence_an_ajax_download_logs' );

function kadence_an_ajax_get_logs() {
    check_ajax_referer( 'kadence_an_logs_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $page = intval( $_POST['page'] ?? 1 );
    $per_page = intval( $_POST['per_page'] ?? 50 );
    $filter = sanitize_text_field( $_POST['filter'] ?? '' );
    $log_level = sanitize_text_field( $_POST['log_level'] ?? 'all' );
    
    $logs = kadence_an_get_log_entries( $page, $per_page, $filter, $log_level );
    
    wp_send_json_success( $logs );
}

function kadence_an_ajax_clear_logs() {
    check_ajax_referer( 'kadence_an_logs_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $log_file = KADENCE_AN_PLUGIN_PATH . 'kadence-an-log.txt';
    $result = file_put_contents( $log_file, '' );
    
    if ( $result !== false ) {
        wp_send_json_success( array( 'message' => 'Logs cleared successfully' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to clear logs' ) );
    }
}

function kadence_an_ajax_download_logs() {
    check_ajax_referer( 'kadence_an_logs_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $log_file = KADENCE_AN_PLUGIN_PATH . 'kadence-an-log.txt';
    
    if ( ! file_exists( $log_file ) ) {
        wp_die( 'Log file not found' );
    }
    
    $filename = 'kadence-an-logs-' . date( 'Y-m-d-H-i-s' ) . '.txt';
    
    header( 'Content-Type: text/plain' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . filesize( $log_file ) );
    
    readfile( $log_file );
    exit;
}

function kadence_an_get_log_entries( $page = 1, $per_page = 50, $filter = '', $log_level = 'all' ) {
    $log_file = KADENCE_AN_PLUGIN_PATH . 'kadence-an-log.txt';
    
    if ( ! file_exists( $log_file ) ) {
        return array(
            'entries' => array(),
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        );
    }
    
    $content = file_get_contents( $log_file );
    $lines = explode( "\n", $content );
    
    // Filter and parse log entries
    $entries = array();
    foreach ( $lines as $line ) {
        if ( empty( trim( $line ) ) ) continue;
        
        // Parse log entry
        $entry = kadence_an_parse_log_entry( $line );
        if ( ! $entry ) continue;
        
        // Apply filters
        if ( $log_level !== 'all' && $entry['level'] !== $log_level ) continue;
        if ( $filter && stripos( $entry['message'], $filter ) === false ) continue;
        
        $entries[] = $entry;
    }
    
    // Reverse to show newest first
    $entries = array_reverse( $entries );
    
    $total = count( $entries );
    $pages = ceil( $total / $per_page );
    $offset = ( $page - 1 ) * $per_page;
    $entries = array_slice( $entries, $offset, $per_page );
    
    return array(
        'entries' => $entries,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page
    );
}

function kadence_an_parse_log_entry( $line ) {
    // Parse timestamp and message: [2025-08-06 21:39:03] message content
    if ( ! preg_match( '/^\[([^\]]+)\]\s*(.*)$/', $line, $matches ) ) {
        return null;
    }
    
    $timestamp = $matches[1];
    $message = $matches[2];
    
    // Determine log level based on message content
    $level = 'info';
    if ( stripos( $message, 'error' ) !== false || stripos( $message, 'failed' ) !== false ) {
        $level = 'error';
    } elseif ( stripos( $message, 'warning' ) !== false ) {
        $level = 'warning';
    } elseif ( stripos( $message, 'success' ) !== false || stripos( $message, 'response (200)' ) !== false ) {
        $level = 'success';
    }
    
    // Extract additional info
    $form_id = null;
    $endpoint = null;
    $response_code = null;
    
    if ( preg_match( '/post_id[:\s]+(\d+)/', $message, $matches ) ) {
        $form_id = $matches[1];
    }
    
    if ( preg_match( '/endpoint[:\s]+(https?:\/\/[^\s]+)/', $message, $matches ) ) {
        $endpoint = $matches[1];
    }
    
    if ( preg_match( '/response \((\d+)\)/', $message, $matches ) ) {
        $response_code = intval( $matches[1] );
    }
    
    return array(
        'timestamp' => $timestamp,
        'message' => $message,
        'level' => $level,
        'form_id' => $form_id,
        'endpoint' => $endpoint,
        'response_code' => $response_code,
        'raw' => $line
    );
}

function kadence_an_render_logs_page() {
    ?>
    <div class="wrap">
        <h1>Kadence Action Network Logs</h1>
        
        <div class="kadence-an-logs-container">
            <!-- Log Controls -->
            <div class="kadence-an-logs-controls">
                <div class="kadence-an-logs-filters">
                    <input type="text" id="log-filter" placeholder="Filter logs..." style="width: 200px;">
                    <select id="log-level-filter">
                        <option value="all">All Levels</option>
                        <option value="error">Errors Only</option>
                        <option value="warning">Warnings Only</option>
                        <option value="success">Success Only</option>
                        <option value="info">Info Only</option>
                    </select>
                    <select id="logs-per-page">
                        <option value="25">25 per page</option>
                        <option value="50" selected>50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                    <button type="button" id="refresh-logs" class="button">Refresh</button>
                </div>
                
                <div class="kadence-an-logs-actions">
                    <button type="button" id="clear-logs" class="button button-secondary">Clear Logs</button>
                    <button type="button" id="download-logs" class="button button-secondary">Download Logs</button>
                </div>
            </div>
            
            <!-- Log Statistics -->
            <div class="kadence-an-logs-stats">
                <div class="kadence-an-stat-box">
                    <span class="stat-label">Total Entries:</span>
                    <span class="stat-value" id="total-entries">-</span>
                </div>
                <div class="kadence-an-stat-box">
                    <span class="stat-label">Errors:</span>
                    <span class="stat-value" id="error-count">-</span>
                </div>
                <div class="kadence-an-stat-box">
                    <span class="stat-label">Success Rate:</span>
                    <span class="stat-value" id="success-rate">-</span>
                </div>
            </div>
            
            <!-- Log Entries -->
            <div class="kadence-an-logs-content">
                <div id="logs-loading" class="kadence-an-loading" style="display: none;">
                    <span class="spinner is-active"></span> Loading logs...
                </div>
                <div id="logs-container"></div>
            </div>
            
            <!-- Pagination -->
            <div class="kadence-an-logs-pagination">
                <div id="logs-pagination"></div>
            </div>
        </div>
    </div>
    
    <style>
    .kadence-an-logs-container {
        max-width: 1200px;
    }
    
    .kadence-an-logs-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .kadence-an-logs-filters {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .kadence-an-logs-actions {
        display: flex;
        gap: 10px;
    }
    
    .kadence-an-logs-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .kadence-an-stat-box {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .stat-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .stat-value {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }
    
    .kadence-an-logs-content {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-height: 400px;
    }
    
    .kadence-an-log-entry {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        font-family: monospace;
        font-size: 13px;
        line-height: 1.4;
    }
    
    .kadence-an-log-entry:last-child {
        border-bottom: none;
    }
    
    .kadence-an-log-entry.error {
        background-color: #fef7f7;
        border-left: 4px solid #dc3232;
    }
    
    .kadence-an-log-entry.warning {
        background-color: #fff8e5;
        border-left: 4px solid #ffb900;
    }
    
    .kadence-an-log-entry.success {
        background-color: #f0f8ff;
        border-left: 4px solid #00a32a;
    }
    
    .kadence-an-log-entry.info {
        background-color: #f8f9fa;
        border-left: 4px solid #72aee6;
    }
    
    .log-timestamp {
        color: #666;
        font-weight: bold;
        margin-right: 10px;
    }
    
    .log-level {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        margin-right: 10px;
    }
    
    .log-level.error {
        background: #dc3232;
        color: white;
    }
    
    .log-level.warning {
        background: #ffb900;
        color: white;
    }
    
    .log-level.success {
        background: #00a32a;
        color: white;
    }
    
    .log-level.info {
        background: #72aee6;
        color: white;
    }
    
    .log-message {
        color: #333;
    }
    
    .log-details {
        margin-top: 5px;
        font-size: 11px;
        color: #666;
    }
    
    .kadence-an-logs-pagination {
        margin-top: 20px;
        text-align: center;
    }
    
    .kadence-an-loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .kadence-an-no-logs {
        text-align: center;
        padding: 40px;
        color: #666;
        font-style: italic;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let currentPage = 1;
        let currentFilter = '';
        let currentLogLevel = 'all';
        let currentPerPage = 50;
        
        // Load initial logs
        loadLogs();
        
        // Event handlers
        $('#refresh-logs').click(function() {
            loadLogs();
        });
        
        $('#log-filter').on('input', function() {
            currentFilter = $(this).val();
            currentPage = 1;
            loadLogs();
        });
        
        $('#log-level-filter').change(function() {
            currentLogLevel = $(this).val();
            currentPage = 1;
            loadLogs();
        });
        
        $('#logs-per-page').change(function() {
            currentPerPage = parseInt($(this).val());
            currentPage = 1;
            loadLogs();
        });
        
        $('#clear-logs').click(function() {
            if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                clearLogs();
            }
        });
        
        $('#download-logs').click(function() {
            downloadLogs();
        });
        
        function loadLogs() {
            $('#logs-loading').show();
            $('#logs-container').empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kadence_an_get_logs',
                    nonce: '<?php echo wp_create_nonce( 'kadence_an_logs_nonce' ); ?>',
                    page: currentPage,
                    per_page: currentPerPage,
                    filter: currentFilter,
                    log_level: currentLogLevel
                },
                success: function(response) {
                    $('#logs-loading').hide();
                    
                    if (response.success) {
                        displayLogs(response.data.entries);
                        updateStats(response.data);
                        updatePagination(response.data);
                    } else {
                        $('#logs-container').html('<div class="kadence-an-no-logs">Error loading logs: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    $('#logs-loading').hide();
                    $('#logs-container').html('<div class="kadence-an-no-logs">Error loading logs. Please try again.</div>');
                }
            });
        }
        
        function displayLogs(entries) {
            if (entries.length === 0) {
                $('#logs-container').html('<div class="kadence-an-no-logs">No logs found matching your criteria.</div>');
                return;
            }
            
            let html = '';
            entries.forEach(function(entry) {
                html += '<div class="kadence-an-log-entry ' + entry.level + '">';
                html += '<span class="log-timestamp">[' + entry.timestamp + ']</span>';
                html += '<span class="log-level ' + entry.level + '">' + entry.level + '</span>';
                html += '<span class="log-message">' + escapeHtml(entry.message) + '</span>';
                
                if (entry.form_id || entry.endpoint || entry.response_code) {
                    html += '<div class="log-details">';
                    if (entry.form_id) html += 'Form ID: ' + entry.form_id + ' | ';
                    if (entry.response_code) html += 'Response: ' + entry.response_code + ' | ';
                    if (entry.endpoint) html += 'Endpoint: ' + entry.endpoint;
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            $('#logs-container').html(html);
        }
        
        function updateStats(data) {
            // Calculate stats from all entries (we'd need to get all entries for accurate stats)
            $('#total-entries').text(data.total);
            // For now, just show basic info - could be enhanced to show error counts, etc.
        }
        
        function updatePagination(data) {
            if (data.pages <= 1) {
                $('#logs-pagination').empty();
                return;
            }
            
            let html = '';
            
            // Previous button
            if (currentPage > 1) {
                html += '<button class="button" onclick="goToPage(' + (currentPage - 1) + ')">&laquo; Previous</button> ';
            }
            
            // Page numbers
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(data.pages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<button class="button button-primary" disabled>' + i + '</button> ';
                } else {
                    html += '<button class="button" onclick="goToPage(' + i + ')">' + i + '</button> ';
                }
            }
            
            // Next button
            if (currentPage < data.pages) {
                html += '<button class="button" onclick="goToPage(' + (currentPage + 1) + ')">Next &raquo;</button>';
            }
            
            $('#logs-pagination').html(html);
        }
        
        function clearLogs() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kadence_an_clear_logs',
                    nonce: '<?php echo wp_create_nonce( 'kadence_an_logs_nonce' ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Logs cleared successfully');
                        loadLogs();
                    } else {
                        alert('Error clearing logs: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error clearing logs. Please try again.');
                }
            });
        }
        
        function downloadLogs() {
            window.open(ajaxurl + '?action=kadence_an_download_logs&nonce=<?php echo wp_create_nonce( 'kadence_an_logs_nonce' ); ?>');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Global function for pagination
        window.goToPage = function(page) {
            currentPage = page;
            loadLogs();
        };
    });
    </script>
    <?php
}

function kadence_an_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Kadence Action Network Integration</h1>
        
        <!-- Quick Actions Section -->
        <div class="kadence-an-quick-actions" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h2 style="margin-top: 0;">Quick Actions</h2>
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=kadence-an-logs'); ?>" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-list-view"></span>
                    View Form Submission Logs
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=kadence_form'); ?>" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-forms"></span>
                    Manage Kadence Forms
                </a>
                <span style="color: #666; font-size: 14px;">
                    <span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 5px;"></span>
                    Monitor form submissions, debug issues, and manage logs
                </span>
            </div>
        </div>
        
        <!-- Main Settings Form -->
        <div class="kadence-an-settings-form" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px;">
            <h2>Plugin Configuration</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'kadence_an_settings_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Action Network API Key</th>
                        <td>
                            <input type="text" name="kadence_an_api_key" value="<?php echo esc_attr( get_option('kadence_an_api_key') ); ?>" size="50" />
                            <p class="description">Enter your Action Network API key. You can find this in your Action Network account settings.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- Help Section -->
        <div class="kadence-an-help-section" style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-top: 20px;">
            <h3 style="margin-top: 0; color: #0073aa;">Need Help?</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="margin-bottom: 10px;">📋 Form Setup</h4>
                    <p style="margin: 0; color: #666;">Configure your Kadence forms with Action Network endpoints in the form editor.</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 10px;">📊 Monitor Submissions</h4>
                    <p style="margin: 0; color: #666;">Use the logs viewer to track form submissions and debug any issues.</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 10px;">🔧 Troubleshooting</h4>
                    <p style="margin: 0; color: #666;">Check the logs for error messages and API response codes.</p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .kadence-an-quick-actions h2,
    .kadence-an-settings-form h2,
    .kadence-an-help-section h3 {
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .kadence-an-quick-actions .button {
        text-decoration: none;
        font-weight: 500;
    }
    
    .kadence-an-quick-actions .button:hover {
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .kadence-an-quick-actions > div {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .kadence-an-help-section > div {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    <?php
} 