/**
 * Kadence Action Network Form Validation
 * Custom validation system to override Kadence's HTML5 validation
 */

(function() {
    'use strict';

    // Built-in validation functions
    const Validators = {
        required: function(value, fieldName) {
            if (!value || value.trim() === '') {
                return 'This field is required.';
            }
            return null;
        },

        email: function(value, fieldName) {
            // Check if field is required (has required attribute or is in required validation)
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return 'Please enter a valid email address.';
            }
            return null;
        },

        us_zip: function(value, fieldName) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            const zipRegex = /^\d{5}(-\d{4})?$/;
            if (!zipRegex.test(value)) {
                return 'Please enter a valid US ZIP code (5 or 9 digits).';
            }
            return null;
        },

        phone: function(value, fieldName) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            // Remove all non-digit characters for validation
            const digitsOnly = value.replace(/\D/g, '');
            if (digitsOnly.length < 10 || digitsOnly.length > 11) {
                return 'Please enter a valid phone number.';
            }
            return null;
        },

        url: function(value, fieldName) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            try {
                new URL(value);
                return null;
            } catch (e) {
                return 'Please enter a valid URL.';
            }
        },

        number: function(value, fieldName) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            if (isNaN(value) || value === '') {
                return 'Please enter a valid number.';
            }
            return null;
        },

        min_length: function(value, fieldName, minLength = 3) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            if (value.length < minLength) {
                return `This field must be at least ${minLength} characters long.`;
            }
            return null;
        },

        max_length: function(value, fieldName, maxLength = 255) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            if (value.length > maxLength) {
                return `This field cannot exceed ${maxLength} characters.`;
            }
            return null;
        },

        date: function(value, fieldName) {
            // Check if field is required
            const field = document.querySelector(`[name="${fieldName}"]`);
            const isRequired = field && (field.hasAttribute('required') || field.getAttribute('data-original-required') === 'true');
            
            if (!value || value.trim() === '') {
                if (isRequired) {
                    return 'This field is required.';
                }
                return null; // Not required, so empty is OK
            }
            
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return 'Please enter a valid date.';
            }
            return null;
        }
    };

    // Main validation engine
    class KadenceFormValidator {
        constructor(formId, validationSettings, customValidationCode) {
            this.formId = formId;
            this.validationSettings = validationSettings || {};
            this.customValidationCode = customValidationCode || '';
            this.errors = [];
            this.init();
        }

        init() {
            // Load custom validation functions
            this.loadCustomValidators();
            
            // Override Kadence's form submission
            this.overrideFormSubmission();
            
            // Add real-time validation
            this.addRealTimeValidation();
        }

        loadCustomValidators() {
            if (this.customValidationCode) {
                try {
                    // Extract function name from the code
                    const functionMatch = this.customValidationCode.match(/function\s+(\w+)\s*\(/);
                    if (functionMatch) {
                        const functionName = functionMatch[1];
                        
                        // Create the function in global scope
                        const functionCode = this.customValidationCode.replace(/^function\s+\w+\s*\(/, 'function(');
                        const globalFunction = new Function('return ' + functionCode);
                        window[functionName] = globalFunction();
                    }
                } catch (error) {
                    console.error('Error loading custom validators:', error);
                }
            }
        }

        overrideFormSubmission() {
            // Try multiple selectors to find the form
            let form = document.querySelector(`[data-form-id="${this.formId}"]`);
            
            if (!form) {
                // Try Kadence's form ID format: kb-adv-form-{formId}-cpt-id
                form = document.getElementById(`kb-adv-form-${this.formId}-cpt-id`);
            }
            
            if (!form) {
                // Try form with class containing the form ID
                form = document.querySelector(`.kb-advanced-form[id*="${this.formId}"]`);
            }
            
            if (!form) {
                // Try finding form by post_id input value
                const postIdInput = document.querySelector(`input[name="post_id"][value="${this.formId}"]`);
                if (postIdInput) {
                    form = postIdInput.closest('form');
                }
            }
            
            if (!form) {
                return;
            }

            // Remove HTML5 validation attributes temporarily
            this.removeHTML5Validation(form);

            // Simple approach: Just override the submit button click
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            submitButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    if (!this.validateForm()) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        this.displayErrors();
                        return false;
                    } else {
                        // Restore HTML5 validation
                        this.restoreHTML5Validation(form);
                        // Let the original submission proceed
                        return true;
                    }
                }, true);
            });
        }

        removeHTML5Validation(form) {
            const fields = form.querySelectorAll('input, select, textarea');
            
            fields.forEach(field => {
                const originalType = field.getAttribute('type') || 'text';
                const originalRequired = field.hasAttribute('required');
                const originalPattern = field.getAttribute('pattern');
                
                field.removeAttribute('required');
                field.removeAttribute('pattern');
                field.removeAttribute('type'); // Temporarily remove email, tel, etc.
                field.setAttribute('data-original-type', originalType);
                field.setAttribute('data-original-required', originalRequired);
                field.setAttribute('data-original-pattern', originalPattern);
                field.setAttribute('type', 'text');
                
                // Also remove novalidate from form to prevent HTML5 validation
                form.setAttribute('novalidate', 'novalidate');
            });
        }

        restoreHTML5Validation(form) {
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                const originalType = field.getAttribute('data-original-type');
                const originalRequired = field.getAttribute('data-original-required');
                const originalPattern = field.getAttribute('data-original-pattern');
                
                if (originalType) {
                    field.setAttribute('type', originalType);
                    field.removeAttribute('data-original-type');
                }
                if (originalRequired === 'true') {
                    field.setAttribute('required', 'required');
                    field.removeAttribute('data-original-required');
                }
                if (originalPattern) {
                    field.setAttribute('pattern', originalPattern);
                    field.removeAttribute('data-original-pattern');
                }
            });
        }

        validateForm() {
            this.errors = [];
            let form = document.querySelector(`[data-form-id="${this.formId}"]`);
            if (!form) {
                // Try the same selectors as in overrideFormSubmission
                form = document.getElementById(`kb-adv-form-${this.formId}-cpt-id`);
            }
            if (!form) {
                form = document.querySelector(`.kb-advanced-form[id*="${this.formId}"]`);
            }
            if (!form) {
                const postIdInput = document.querySelector(`input[name="post_id"][value="${this.formId}"]`);
                if (postIdInput) {
                    form = postIdInput.closest('form');
                }
            }
            
            if (!form) {
                return true;
            }

            // Validate each configured field
            Object.keys(this.validationSettings).forEach(fieldName => {
                // Try multiple selectors for the field
                let field = form.querySelector(`[name="${fieldName}"]`);
                if (!field) {
                    field = form.querySelector(`[data-field="${fieldName}"]`);
                }
                if (!field) {
                    field = form.querySelector(`#${fieldName}`);
                }
                if (!field) {
                    field = form.querySelector(`input[name*="${fieldName}"]`);
                }
                
                if (!field) {
                    return;
                }

                const value = field.value;
                const validationType = this.validationSettings[fieldName].validation_type;
                const customErrorMessage = this.validationSettings[fieldName].error_message;
                const validationParam = this.validationSettings[fieldName].validation_param; // Get validation parameter

                // Run validation
                let error = this.runValidation(value, fieldName, validationType, validationParam);
                
                // Use custom error message if provided
                if (error && customErrorMessage) {
                    error = customErrorMessage;
                }

                if (error) {
                    this.errors.push({
                        field: field,
                        message: error,
                        fieldName: fieldName
                    });
                }
            });

            return this.errors.length === 0;
        }

        runValidation(value, fieldName, validationType, validationParam = null) {
            // Check if it's a built-in validator
            if (Validators[validationType]) {
                // Handle validators that accept parameters
                if (validationType === 'min_length' && validationParam) {
                    return Validators[validationType](value, fieldName, parseInt(validationParam));
                }
                if (validationType === 'max_length' && validationParam) {
                    return Validators[validationType](value, fieldName, parseInt(validationParam));
                }
                return Validators[validationType](value, fieldName);
            }

            // Check if it's a custom validator
            if (validationType === 'custom') {
                const customValidatorName = `validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`;
                
                if (typeof window[customValidatorName] === 'function') {
                    return window[customValidatorName](value, fieldName);
                }
            }

            return null;
        }

        displayErrors() {
            // Clear previous errors
            this.clearErrors();

            // Display new errors
            this.errors.forEach(error => {
                this.showFieldError(error.field, error.message);
            });

            // Scroll to first error
            if (this.errors.length > 0) {
                this.errors[0].field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        showFieldError(field, message) {
            // Clear any existing errors for this field first
            this.clearFieldError(field);
            
            // Create error element
            const errorElement = document.createElement('div');
            errorElement.className = 'kadence-an-validation-error';
            errorElement.style.cssText = 'color: #dc3232; font-size: 12px; margin-top: 5px; display: block;';
            errorElement.textContent = message;

            // Add error styling to field
            field.style.borderColor = '#dc3232';
            field.classList.add('kadence-an-error');

            // Insert error message after field
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }

        clearErrors() {
            // Remove error styling
            document.querySelectorAll('.kadence-an-error').forEach(field => {
                field.style.borderColor = '';
                field.classList.remove('kadence-an-error');
            });

            // Remove error messages
            document.querySelectorAll('.kadence-an-validation-error').forEach(error => {
                error.remove();
            });
        }

        addRealTimeValidation() {
            let form = document.querySelector(`[data-form-id="${this.formId}"]`);
            if (!form) {
                form = document.getElementById(`kb-adv-form-${this.formId}-cpt-id`);
            }
            if (!form) {
                form = document.querySelector(`.kb-advanced-form[id*="${this.formId}"]`);
            }
            if (!form) return;

            // Add blur event listeners for real-time validation
            Object.keys(this.validationSettings).forEach(fieldName => {
                let field = form.querySelector(`[name="${fieldName}"]`);
                if (!field) {
                    field = form.querySelector(`[data-field="${fieldName}"]`);
                }
                if (!field) return;

                field.addEventListener('blur', () => {
                    this.validateField(field, fieldName);
                });

                field.addEventListener('input', () => {
                    // Clear error on input if field is valid
                    if (this.isFieldValid(field, fieldName)) {
                        this.clearFieldError(field);
                    }
                });
            });
        }

        validateField(field, fieldName) {
            const value = field.value;
            const validationType = this.validationSettings[fieldName].validation_type;
            const customErrorMessage = this.validationSettings[fieldName].error_message;
            const validationParam = this.validationSettings[fieldName].validation_param; // Get validation parameter

            let error = this.runValidation(value, fieldName, validationType, validationParam);
            
            if (error && customErrorMessage) {
                error = customErrorMessage;
            }

            if (error) {
                this.showFieldError(field, error);
            } else {
                this.clearFieldError(field);
            }
        }

        isFieldValid(field, fieldName) {
            const value = field.value;
            const validationType = this.validationSettings[fieldName].validation_type;
            const validationParam = this.validationSettings[fieldName].validation_param; // Get validation parameter
            const error = this.runValidation(value, fieldName, validationType, validationParam);
            return !error;
        }

        clearFieldError(field) {
            // Remove error styling from the field
            field.style.borderColor = '';
            field.classList.remove('kadence-an-error');
            
            // Remove all error elements for this field (in case multiple were created)
            const errorElements = field.parentNode.querySelectorAll('.kadence-an-validation-error');
            errorElements.forEach(errorElement => {
                errorElement.remove();
            });
        }
    }

    // Initialize validation when DOM is ready
    function initValidation() {
        // Look for forms with validation settings
        document.querySelectorAll('[data-form-id]').forEach(element => {
            const formId = element.dataset.formId;
            
            // Check if this form has validation settings
            if (window.kadenceANValidationSettings && window.kadenceANValidationSettings[formId]) {
                const settings = window.kadenceANValidationSettings[formId];
                new KadenceFormValidator(formId, settings.validation, settings.custom);
            }
        });
        
        // Look for Kadence forms by their ID pattern
        document.querySelectorAll('.kb-advanced-form').forEach(element => {
            const formId = element.id;
            
            // Extract form ID from Kadence's ID format: kb-adv-form-{formId}-cpt-id
            if (formId && formId.match(/kb-adv-form-(\d+)-cpt-id/)) {
                const extractedFormId = formId.match(/kb-adv-form-(\d+)-cpt-id/)[1];
                
                if (window.kadenceANValidationSettings && window.kadenceANValidationSettings[extractedFormId]) {
                    const settings = window.kadenceANValidationSettings[extractedFormId];
                    new KadenceFormValidator(extractedFormId, settings.validation, settings.custom);
                }
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initValidation);
    } else {
        initValidation();
    }

})(); 