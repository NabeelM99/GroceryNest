// Checkout Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize form elements
    const checkoutForm = document.getElementById('checkoutForm');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    
    // Form validation
    function validateForm() {
        const requiredFields = [
            'firstName',
            'lastName', 
            'phone',
            'shipping_address',
            'payment_method'
        ];
        
        let isValid = true;
        const errors = [];
        
        // Check required fields
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field) return;
            
            if (field.type === 'radio') {
                const radioGroup = document.querySelectorAll(`input[name="${fieldName}"]`);
                const checked = Array.from(radioGroup).some(radio => radio.checked);
                if (!checked) {
                    isValid = false;
                    errors.push(`${fieldName.replace('_', ' ')} is required`);
                }
            } else {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    errors.push(`${fieldName.replace('_', ' ')} is required`);
                } else {
                    field.classList.remove('is-invalid');
                }
            }
        });
        
        // Phone number validation
        const phoneField = document.getElementById('phone');
        if (phoneField && phoneField.value.trim()) {
            // More flexible phone validation - accepts Bahrain format and international formats
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{7,20}$/;
            if (!phoneRegex.test(phoneField.value.trim())) {
                isValid = false;
                phoneField.classList.add('is-invalid');
                errors.push('Please enter a valid phone number');
            } else {
                phoneField.classList.remove('is-invalid');
            }
        }
        
        return { isValid, errors };
    }
    

    
    // Handle form submission
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        const validation = validateForm();
        if (!validation.isValid) {
            alert(validation.errors.join(', '));
            return;
        }
        
        // Disable submit button
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        // Show loading modal
        loadingModal.show();
        
        // Prepare form data
        const formData = new FormData();
        formData.append('place_order', '1');
        formData.append('shipping_address', document.getElementById('shipping_address').value.trim());
        formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);
        
        // Add other form fields
        const additionalFields = ['firstName', 'lastName', 'phone', 'email'];
        additionalFields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                formData.append(field, element.value.trim());
            }
        });
        
        // Send order request
        fetch('checkout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingModal.hide();
            
            if (data.success) {
                // Show success modal
                document.getElementById('orderId').textContent = `#${String(data.order_id).padStart(6, '0')}`;
                document.getElementById('orderTotal').textContent = `BHD ${parseFloat(data.total || 0).toFixed(3)}`;
                successModal.show();
                
                // Update cart count in navbar
                updateCartCount(0);
            } else {
                alert(data.message);
                
                // Re-enable submit button
                placeOrderBtn.disabled = false;
                placeOrderBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Place Order';
            }
        })
        .catch(error => {
            loadingModal.hide();
            console.error('Error:', error);
            alert('An error occurred while processing your order. Please try again.');
            
            // Re-enable submit button
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Place Order';
        });
    });
    
    // Real-time form validation
    const formFields = checkoutForm.querySelectorAll('input');
    formFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
    
    function validateField(field) {
        const fieldName = field.name || field.id;
        
        // Remove existing validation classes
        field.classList.remove('is-invalid', 'is-valid');
        
        // Check if field is required
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('is-invalid');
            return false;
        }
        
        // Specific validation for phone
        if (fieldName === 'phone' && field.value.trim()) {
            // More flexible phone validation - accepts Bahrain format and international formats
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{7,20}$/;
            if (!phoneRegex.test(field.value.trim())) {
                field.classList.add('is-invalid');
                return false;
            }
        }
        
        // If field has value and passes validation
        if (field.value.trim()) {
            field.classList.add('is-valid');
        }
        
        return true;
    }
    
    // Payment method selection
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Remove active class from all payment options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('active');
            });
            
            // Add active class to selected option
            if (this.checked) {
                this.closest('.payment-option').classList.add('active');
            }
        });
    });
    
    // Auto-select first payment option if none selected
    if (!document.querySelector('input[name="payment_method"]:checked')) {
        const firstPaymentOption = document.querySelector('input[name="payment_method"]');
        if (firstPaymentOption) {
            firstPaymentOption.checked = true;
            firstPaymentOption.closest('.payment-option').classList.add('active');
        }
    }
    
    // Update cart count function (if exists in navbar)
    function updateCartCount(count) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            if (count === 0) {
                cartCountElement.style.display = 'none';
            }
        }
    }
    
    // Smooth scrolling for form sections
    const sectionHeaders = document.querySelectorAll('.section-header');
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const section = this.closest('.checkout-section');
            const sectionBody = section.querySelector('.section-body');
            
            // Toggle section visibility (for mobile)
            if (window.innerWidth <= 768) {
                sectionBody.style.display = sectionBody.style.display === 'none' ? 'block' : 'none';
            }
        });
    });
    
    // Phone number field - no auto-formatting to avoid conflicts with pre-filled data
    const phoneField = document.getElementById('phone');
    if (phoneField) {
        // Just ensure the field is properly validated without auto-formatting
        phoneField.addEventListener('blur', function() {
            validateField(this);
        });
    }
    

    
    // Success modal event listeners
    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
        // Redirect to orders page after modal is closed
        window.location.href = 'orders.php';
    });
    
    // Initialize tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add loading state to payment options
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Add a small delay to show the selection
            setTimeout(() => {
                const paymentOption = this.closest('.payment-option');
                paymentOption.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    paymentOption.style.transform = 'scale(1)';
                }, 200);
            }, 100);
        });
    });
    
    // Form field focus effects
    formFields.forEach(field => {
        field.addEventListener('focus', function() {
            this.closest('.form-group')?.classList.add('focused');
        });
        
        field.addEventListener('blur', function() {
            this.closest('.form-group')?.classList.remove('focused');
        });
    });
    
    // Keyboard navigation for payment options
    paymentOptions.forEach((option, index) => {
        option.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                const nextIndex = e.key === 'ArrowDown' ? 
                    (index + 1) % paymentOptions.length : 
                    (index - 1 + paymentOptions.length) % paymentOptions.length;
                paymentOptions[nextIndex].focus();
            }
        });
    });
    
    console.log('Checkout page initialized successfully');
}); 