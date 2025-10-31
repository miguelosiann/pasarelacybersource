/**
 * Payment Checkout JavaScript
 * Handles form validation and card number formatting
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCheckoutForm();
});

function initializeCheckoutForm() {
    const form = document.getElementById('payment-form');
    const cardNumber = document.getElementById('card_number');
    const state = document.getElementById('state');
    const submitBtn = document.getElementById('submit-btn');

    // Format card number as user types
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
            
            // Auto-detect card type
            detectCardType(value);
        });

        // Only allow numbers and spaces
        cardNumber.addEventListener('keypress', function(e) {
            if (!/[0-9\s]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    // State - force uppercase and 2 letters max
    if (state) {
        state.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Only allow letters
        state.addEventListener('keypress', function(e) {
            if (!/[a-zA-Z]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        });
    }
}

/**
 * Detect card type based on number
 */
function detectCardType(cardNumber) {
    const cardTypeSelect = document.getElementById('card_type');
    const cleanNumber = cardNumber.replace(/\s/g, '');

    if (!cardTypeSelect || cleanNumber.length < 2) return;

    // Card type patterns
    if (/^4/.test(cleanNumber)) {
        cardTypeSelect.value = 'visa';
    } else if (/^5[1-5]/.test(cleanNumber) || /^2[2-7]/.test(cleanNumber)) {
        cardTypeSelect.value = 'mastercard';
    } else if (/^3[47]/.test(cleanNumber)) {
        cardTypeSelect.value = 'amex';
    }
}

/**
 * Validate form before submission
 */
function validateForm() {
    let isValid = true;
    const errors = [];

    // Validate card number
    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
    if (!isValidCardNumber(cardNumber)) {
        errors.push('Número de tarjeta inválido');
        isValid = false;
    }

    // Validate expiration
    const month = document.getElementById('expiration_month').value;
    const year = document.getElementById('expiration_year').value;
    if (!isValidExpiration(month, year)) {
        errors.push('Fecha de expiración inválida');
        isValid = false;
    }

    // Validate CVV
    const cvv = document.getElementById('cvv').value;
    const cardType = document.getElementById('card_type').value;
    if (!isValidCVV(cvv, cardType)) {
        errors.push('CVV inválido');
        isValid = false;
    }

    // Show errors if any
    if (!isValid) {
        showValidationErrors(errors);
    }

    return isValid;
}

/**
 * Validate card number using Luhn algorithm
 */
function isValidCardNumber(cardNumber) {
    if (!/^\d{13,19}$/.test(cardNumber)) {
        return false;
    }

    // Luhn algorithm
    let sum = 0;
    let isEven = false;

    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i), 10);

        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }

        sum += digit;
        isEven = !isEven;
    }

    return (sum % 10) === 0;
}

/**
 * Validate expiration date
 */
function isValidExpiration(month, year) {
    if (!month || !year) return false;

    const expDate = new Date(parseInt(year), parseInt(month) - 1);
    const today = new Date();
    today.setDate(1); // Set to first day of month for comparison

    return expDate >= today;
}

/**
 * Validate CVV
 */
function isValidCVV(cvv, cardType) {
    if (cardType === 'amex') {
        return /^\d{4}$/.test(cvv);
    }
    return /^\d{3}$/.test(cvv);
}

/**
 * Show validation errors
 */
function showValidationErrors(errors) {
    // Remove existing error alerts
    const existingAlerts = document.querySelectorAll('.validation-alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create new error alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show validation-alert';
    alertDiv.setAttribute('role', 'alert');

    let errorHTML = '<strong>Por favor corrija los siguientes errores:</strong><ul class="mb-0 mt-2">';
    errors.forEach(error => {
        errorHTML += `<li>${error}</li>`;
    });
    errorHTML += '</ul>';
    errorHTML += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

    alertDiv.innerHTML = errorHTML;

    // Insert before form
    const form = document.getElementById('payment-form');
    form.parentNode.insertBefore(alertDiv, form);

    // Scroll to error
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Format currency input
 */
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limit to 2 decimal places
    if (parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    
    input.value = value;
}

// Auto-fill test data (for development only)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('Development mode: Auto-fill available');
    console.log('Test card: 4111 1111 1111 1111');
}

