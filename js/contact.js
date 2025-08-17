// Form validation
const forms = document.querySelectorAll('form[novalidate]');
forms.forEach(form => {
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

// Auto-dismiss alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
});

// Initialize AOS
AOS.init({
    duration: 800, 
    once: true, 
    offset: 100, 
    easing: 'ease-out',
    delay: 0
});

