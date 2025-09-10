# Changelog

All notable changes to the Kadence Action Network Integration plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2.1] - 2025-09-10

### Added
- **Enhanced Webhook Debugging**: Comprehensive logging to diagnose webhook submission issues
- **Request Analysis**: Detailed logging of JSON params, body params, headers, and raw request data

## [1.1.2] - 2025-09-10

### Added
- **HTTP Basic Auth Support**: Added toggle settings in form meta box for development environments
- **Development Mode**: Easy enable/disable HTTP Basic Auth for webhook requests

## [1.1.1] - 2025-09-10

### Fixed
- **Logging System**: Restored missing logging calls that were accidentally removed during update checker implementation

## [1.1.0] - 2025-09-10

### Added
- **Admin Log Viewer**: Complete log viewing interface accessible from plugin settings
- **Log Filtering**: Filter logs by text content and log level (error, warning, success, info)
- **Log Management**: Clear logs and download log files directly from admin interface
- **Pagination**: View 25, 50, or 100 log entries per page for better performance
- **Smart Log Parsing**: Automatic detection of log levels and metadata extraction
- **Color-coded Log Display**: Visual distinction between different log types
- **Statistics Dashboard**: Overview of total entries and log statistics
- **Enhanced Settings Page**: Professional layout with quick actions and help sections
- **Contextual Navigation**: Easy access to logs from form meta boxes and settings page

### Improved
- **User Experience**: Logs accessible from plugin settings instead of main sidebar
- **Admin Interface**: Cleaner, more organized settings page with helpful guidance
- **Remote Server Support**: No more need for SSH/FTP access to view log files
- **Debugging Workflow**: Streamlined process for monitoring form submissions and troubleshooting

### Technical Details
- AJAX-powered log loading for better performance
- Responsive design for mobile and tablet compatibility
- WordPress admin styling consistency
- Secure nonce verification for all log management actions
- Efficient log parsing with regex pattern matching

## [1.0.0] - 2024-08-06

### Added
- Initial release of Kadence Action Network Integration
- Action Network webhook integration for Kadence Blocks Pro forms
- Custom JavaScript validation system to override HTML5 validation
- Built-in validators: required, email, US ZIP, phone, URL, number, min/max length, date
- Custom validation function support
- Real-time validation with instant feedback
- Per-form Action Network configuration (endpoint, tags, management URL)
- Global API key management
- Admin interface for validation rule management
- Comprehensive logging system
- Custom error messages per field
- Validation parameter support for min/max length validators
- Professional admin styling
- Uninstall cleanup script
- Comprehensive documentation

### Features
- **Action Network Integration**: Seamless form submission to Action Network via webhooks
- **Advanced Validation**: Replace Kadence's HTML5 validation with custom JavaScript validation
- **Flexible Configuration**: Configure settings per form with easy-to-use admin interface
- **Custom Validators**: Write custom JavaScript validation functions for complex validation needs
- **Real-time Feedback**: Instant validation feedback as users type
- **Professional UI**: Clean, modern admin interface with responsive design
- **Comprehensive Logging**: Detailed logging for debugging and monitoring
- **Clean Uninstall**: Complete data cleanup when plugin is removed

### Technical Details
- jQuery-free implementation using native JavaScript
- WordPress REST API integration
- Proper WordPress coding standards
- Security best practices (nonce verification, sanitization)
- Responsive design for mobile compatibility
- Error handling and graceful degradation 