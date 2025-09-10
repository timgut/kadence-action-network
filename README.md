# Kadence Action Network Integration

A WordPress plugin that integrates Kadence Blocks Pro forms with Action Network's API, providing custom validation and seamless data submission.

## Features

### Action Network Integration
- **Webhook Support**: Automatically submits form data to Action Network via webhooks
- **Custom Endpoints**: Configure unique Action Network endpoints per form
- **Tag Management**: Add tags to submissions for better organization
- **Custom Fields**: Support for custom form fields mapped to Action Network
- **API Authentication**: Secure API key management

### Advanced Form Validation
- **HTML5 Override**: Replace Kadence's default HTML5 validation with custom JavaScript validation
- **Built-in Validators**:
  - Required field validation
  - Email format validation
  - US ZIP code validation
  - Phone number validation
  - URL format validation
  - Number validation
  - Minimum/maximum length validation
  - Date validation
- **Custom Validators**: Write custom JavaScript validation functions
- **Real-time Validation**: Instant feedback as users type
- **Custom Error Messages**: Personalized error messages per field

### Admin Interface
- **Per-Form Settings**: Configure Action Network settings for each form individually
- **Validation Management**: Easy-to-use interface for setting up validation rules
- **Global API Settings**: Centralized Action Network API key management

## Installation

1. Upload the plugin files to `/wp-content/plugins/kadence-action-network-integration/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Kadence Action Network to configure your API key
4. Edit any Kadence form to configure Action Network settings and validation rules

## Configuration

### Global Settings
1. Navigate to **Settings > Kadence Action Network**
2. Enter your Action Network API key
3. Save settings

### Per-Form Configuration
1. Edit any Kadence Advanced Form
2. Scroll down to the "Action Network Integration" meta box
3. Configure:
   - **AN Endpoint URL**: Your Action Network form endpoint (e.g., `https://actionnetwork.org/api/v2/forms/your-form-id`). The plugin will automatically append `/submissions` if not present.
   - **Tags**: Comma-separated tags for submissions
   - **Management URL**: Reference URL for administrative purposes
4. In the "Webhook Configuration" section, copy the webhook URL for use in your Kadence form settings

### Validation Setup
1. In the form's meta box, go to "Form Validation Settings"
2. Add field validation rules:
   - **Field Name**: Match your Kadence form field IDs
   - **Validation Type**: Choose from built-in or custom validators
   - **Validation Parameter**: For min/max length validators
   - **Error Message**: Custom error message (optional)
3. Add custom validation functions in the JavaScript code area

## Usage Examples

### Basic Form Setup
```javascript
// Example custom validation function
function validateAge(value, fieldName) {
    if (!value) return null; // Let required validator handle empty values
    
    const age = parseInt(value);
    if (age < 18) {
        return 'You must be at least 18 years old.';
    }
    if (age > 120) {
        return 'Please enter a valid age.';
    }
    return null; // Validation passes
}
```

### Field Mapping
The plugin automatically maps these standard fields to Action Network:
- `first_name` → `given_name`
- `last_name` → `family_name`
- `email` → `email_addresses`
- `phone` → `phone_numbers`
- `postal_code` → `postal_addresses`

Custom fields are automatically included in the `custom_fields` section.

## Webhook Configuration

### In Your Kadence Form Settings:
1. Add a "Webhook" action
2. Set the URL to the webhook URL shown in the plugin's meta box
3. Set the method to POST
4. The plugin will automatically handle the submission

### Cross-Environment Setup:
The plugin provides both a full webhook URL and a domain-agnostic path:

- **Full URL**: `https://yoursite.com/wp-json/kadence-an/v1/submit` (for current environment)
- **Path**: `wp-json/kadence-an/v1/submit` (for other environments)

When setting up webhooks in different environments (dev, staging, production), use the path and prepend your domain:
- Development: `https://dev.yoursite.com/wp-json/kadence-an/v1/submit`
- Staging: `https://staging.yoursite.com/wp-json/kadence-an/v1/submit`
- Production: `https://yoursite.com/wp-json/kadence-an/v1/submit`

## Troubleshooting

### Common Issues

**Forms not appearing in editor:**
- Ensure Kadence Blocks Pro is activated
- Check that the form blocks are whitelisted in your theme
- Verify your license key is valid

**Validation not working:**
- Check browser console for JavaScript errors
- Ensure field names match exactly between form and validation settings
- Verify custom validation functions are properly formatted

**Action Network submissions failing:**
- Check your API key is correct
- Verify the endpoint URL is valid
- Check the plugin's log file for detailed error messages

### Logging

The plugin creates a log file at:
`/wp-content/plugins/kadence-action-network-integration/kadence-an-log.txt`

This file contains detailed information about:
- Form submissions
- Action Network API calls
- Validation errors
- Configuration issues

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Kadence Blocks Pro 2.0+
- Action Network account with API access

## Support

For support and feature requests, please contact the plugin developer.

## Changelog

### Version 1.0.0
- Initial release
- Action Network webhook integration
- Custom validation system
- Admin interface for configuration
- Support for custom fields and tags

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin requires Kadence Blocks Pro to function. The free version of Kadence Blocks does not include the Advanced Form functionality. 