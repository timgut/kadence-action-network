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
            console.log('loadCustomValidators called');
            console.log('this.customValidationCode:', this.customValidationCode);
            console.log('this.customValidationCode length:', this.customValidationCode ? this.customValidationCode.length : 0);
            
            if (this.customValidationCode) {
                try {
                    console.log('Loading custom validation code:', this.customValidationCode);
                    
                    // Extract function name from the code
                    const functionMatch = this.customValidationCode.match(/function\s+(\w+)\s*\(/);
                    if (functionMatch) {
                        const functionName = functionMatch[1];
                        console.log('Found function name:', functionName);
                        
                        // Create the function in global scope
                        const functionCode = this.customValidationCode.replace(/^function\s+\w+\s*\(/, 'function(');
                        const globalFunction = new Function('return ' + functionCode);
                        window[functionName] = globalFunction();
                        
                        console.log('Function added to window:', functionName);
                    }
                    
                    console.log('Custom validators loaded for form:', this.formId);
                    console.log('Available validate functions:', Object.keys(window).filter(key => key.startsWith('validate')));
                } catch (error) {
                    console.error('Error loading custom validators:', error);
                }
            } else {
                console.log('No custom validation code to load');
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
                console.log('Form not found with any selector for ID:', this.formId);
                console.log('Tried selectors:', [
                    `[data-form-id="${this.formId}"]`,
                    `#kb-adv-form-${this.formId}-cpt-id`,
                    `.kb-advanced-form[id*="${this.formId}"]`,
                    `form input[name="post_id"][value="${this.formId}"]`
                ]);
                return;
            }
            
            console.log('Found form for validation:', form);
            console.log('Form HTML:', form.outerHTML.substring(0, 500) + '...');

            // Remove HTML5 validation attributes temporarily
            this.removeHTML5Validation(form);

            // Simple approach: Just override the submit button click
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            console.log('Found submit buttons:', submitButtons.length);
            
            submitButtons.forEach(button => {
                console.log('Attaching click handler to button:', button);
                
                button.addEventListener('click', (e) => {
                    console.log('BUTTON: Submit button clicked, validating first');
                    
                    if (!this.validateForm()) {
                        console.log('BUTTON: Validation failed, preventing submission');
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        this.displayErrors();
                        return false;
                    } else {
                        console.log('BUTTON: Validation passed, allowing submission');
                        // Restore HTML5 validation
                        this.restoreHTML5Validation(form);
                        // Let the original submission proceed
                        return true;
                    }
                }, true);
            });
            
            console.log('Form submission override installed for form:', this.formId);
        }

        removeHTML5Validation(form) {
            console.log('Removing HTML5 validation from form');
            const fields = form.querySelectorAll('input, select, textarea');
            console.log('Found', fields.length, 'form fields');
            
            fields.forEach(field => {
                const originalType = field.getAttribute('type') || 'text';
                const originalRequired = field.hasAttribute('required');
                const originalPattern = field.getAttribute('pattern');
                
                console.log('Field:', field.name, 'Type:', originalType, 'Required:', originalRequired, 'Pattern:', originalPattern);
                
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
            
            console.log('HTML5 validation attributes removed');
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
                console.log('validateForm: Form not found');
                return true;
            }
            
            console.log('validateForm: Found form, validation settings:', this.validationSettings);
            console.log('validateForm: Form fields:', form.querySelectorAll('input, select, textarea'));

            // Validate each configured field
            Object.keys(this.validationSettings).forEach(fieldName => {
                console.log('validateForm: Checking field:', fieldName);
                
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
                    console.log('validateForm: Field not found:', fieldName);
                    return;
                }
                
                console.log('validateForm: Found field:', fieldName, 'Value:', field.value);

                const value = field.value;
                const validationType = this.validationSettings[fieldName].validation_type;
                const customErrorMessage = this.validationSettings[fieldName].error_message;

                // Run validation
                let error = this.runValidation(value, fieldName, validationType);
                console.log('validateForm: Validation result for', fieldName, ':', error);
                
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
                    console.log('validateForm: Added error for', fieldName, ':', error);
                }
            });

            console.log('validateForm: Total errors:', this.errors.length);
            return this.errors.length === 0;
        }

        runValidation(value, fieldName, validationType) {
            console.log('runValidation called:', { value, fieldName, validationType });
            
            // Check if it's a built-in validator
            if (Validators[validationType]) {
                console.log('Using built-in validator:', validationType);
                return Validators[validationType](value, fieldName);
            }

            // Check if it's a custom validator
            if (validationType === 'custom') {
                const customValidatorName = `validate${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`;
                console.log('Looking for custom validator:', customValidatorName);
                console.log('Available global functions:', Object.keys(window).filter(key => key.startsWith('validate')));
                
                if (typeof window[customValidatorName] === 'function') {
                    console.log('Found custom validator function:', customValidatorName);
                    const result = window[customValidatorName](value, fieldName);
                    console.log('Custom validator result:', result);
                    return result;
                } else {
                    console.log('Custom validator function not found:', customValidatorName);
                }
            }

            console.log('No validator found, returning null');
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
            
            console.log('Showed error for field:', field.name, 'Message:', message);
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

            let error = this.runValidation(value, fieldName, validationType);
            
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
            const error = this.runValidation(value, fieldName, validationType);
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
            
            console.log('Cleared error for field:', field.name);
        }
    }

    // Initialize validation when DOM is ready
    function initValidation() {
        console.log('Kadence AN Validation: DOM ready');
        console.log('Available validation settings:', window.kadenceANValidationSettings);
        
        // Look for forms with validation settings
        document.querySelectorAll('[data-form-id]').forEach(element => {
            const formId = element.dataset.formId;
            console.log('Found form with data-form-id:', formId);
            
            // Check if this form has validation settings
            if (window.kadenceANValidationSettings && window.kadenceANValidationSettings[formId]) {
                console.log('Initializing validation for form:', formId);
                const settings = window.kadenceANValidationSettings[formId];
                console.log('Form validation settings:', settings);
                new KadenceFormValidator(formId, settings.validation, settings.custom);
            } else {
                console.log('No validation settings found for form:', formId);
            }
        });
        
        // Look for Kadence forms by their ID pattern
        document.querySelectorAll('.kb-advanced-form').forEach(element => {
            const formId = element.id;
            console.log('Found Kadence form with ID:', formId);
            
            // Extract form ID from Kadence's ID format: kb-adv-form-{formId}-cpt-id
            if (formId && formId.match(/kb-adv-form-(\d+)-cpt-id/)) {
                const extractedFormId = formId.match(/kb-adv-form-(\d+)-cpt-id/)[1];
                console.log('Extracted form ID from Kadence form:', extractedFormId);
                
                if (window.kadenceANValidationSettings && window.kadenceANValidationSettings[extractedFormId]) {
                    console.log('Initializing validation for Kadence form:', extractedFormId);
                    const settings = window.kadenceANValidationSettings[extractedFormId];
                    console.log('Form validation settings:', settings);
                    new KadenceFormValidator(extractedFormId, settings.validation, settings.custom);
                } else {
                    console.log('No validation settings found for Kadence form:', extractedFormId);
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