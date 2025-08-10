function validateLoginForm() {
    let valid = true;

    const mail = document.getElementById('mail').value.trim();
    const password = document.getElementById('password').value;

    const mailPattern = /^[a-zA-Z0-9._-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,5}$/;

    // Email validation
    if (!mailPattern.test(mail)) {
        document.getElementById("mail_msg").textContent = "Invalid email format.";
        document.getElementById("mail_msg").className = "validation-msg invalid";
        valid = false;
    } else {
        document.getElementById("mail_msg").textContent = "✓ Valid email format";
        document.getElementById("mail_msg").className = "validation-msg valid";
    }

    // Password validation
    if (password.length < 6) {
        document.getElementById("password_msg").textContent = "Password must be at least 6 characters.";
        document.getElementById("password_msg").className = "validation-msg invalid";
        valid = false;
    } else {
        document.getElementById("password_msg").textContent = "✓ Password entered";
        document.getElementById("password_msg").className = "validation-msg valid";
    }

    return valid;
}

// Add form submission validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login_form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
});