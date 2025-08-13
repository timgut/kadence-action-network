# Changelog

All notable changes to the Kadence Action Network Integration plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-XX

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