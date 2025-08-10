<?php
session_start();
include 'project_connection.php';

// Handle registration errors
$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 1: $error = "Missing required fields"; break;
        case 2: $error = "Validation failed - check your inputs"; break;
        case 3: $error = "Username or email already exists"; break;
        case 4: $error = "Database error - please try again"; break;
        default: $error = "An error occurred";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="js/reg_validation.js"></script>
    <meta charset="UTF-8">
    <title>Register | GroceryNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/registration.css">
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <i class="fas fa-shopping-basket fa-2x mb-2 text-success"></i>
            <h2 class="register-title">GroceryNest Register</h2>
            <p class="text-muted mb-0">Create your account below.</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="error-msg mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="register_form" method="POST" action="reg_login.php" autocomplete="off" novalidate>
            <input type="hidden" name="country_code" value="+973">
            <input type="hidden" name="mobile" value="00000000">
            <input type="hidden" name="address" value="000">
            <input type="hidden" name="block" value="000">

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-user"></i>
                    <input type="text" class="form-control" name="name" id="name" placeholder="First Name" 
                           required oninput="liveValidateName()">
                    <label for="name">First Name</label>
                </div>
                <span class="validation-msg" id="name_msg"></span>
            </div>

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-user"></i>
                    <input type="text" class="form-control" name="lname" id="lname" placeholder="Last Name" 
                           required oninput="liveValidateLastName()">
                    <label for="lname">Last Name</label>
                </div>
                <span class="validation-msg" id="lname_msg"></span>
            </div>

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-envelope"></i>
                    <input type="email" class="form-control" name="mail" id="mail" placeholder="Email Address" 
                           required oninput="liveValidateEmail()">
                    <label for="mail">Email Address</label>
                </div>
                <span class="validation-msg" id="mail_msg"></span>
            </div>

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-user-tag"></i>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Username" 
                           required oninput="liveValidateUsername()">
                    <label for="username">Username</label>
                </div>
                <span class="validation-msg" id="username_msg"></span>
            </div>

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-lock"></i>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" 
                           required oninput="liveValidatePassword()">
                    <label for="password">Password</label>
                </div>
                <span class="validation-msg" id="password_msg"></span>
            </div>

            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-lock"></i>
                    <input type="password" class="form-control" name="cnfm_password" id="cnfm_password" placeholder="Confirm Password" 
                           required oninput="liveValidateConfirmPassword()">
                    <label for="cnfm_password">Confirm Password</label>
                </div>
                <span class="validation-msg" id="cnfm_password_msg"></span>
            </div>

            <button type="submit" name="register_user" class="btn btn-register w-100 py-2 mb-3">
                <i class="fas fa-user-plus me-2"></i>Register
            </button>

            <div class="text-center">
                <a href="login_form.php" class="text-success text-decoration-none">Already have an account? Login</a>
            </div>
        </form>
    </div>
    
    <script>
        // Live validation functions
        function updateValidationMsg(elementId, isValid, message) {
            const msgElement = document.getElementById(elementId);
            msgElement.textContent = message;
            msgElement.className = `validation-msg ${isValid ? 'valid' : 'invalid'}`;
        }

        function liveValidateName() {
            const name = document.getElementById("name").value.trim();
            const pattern = /^([a-zA-Z]+\s)*[a-zA-Z]+$/;
            const isValid = name.length === 0 || pattern.test(name);
            updateValidationMsg("name_msg", isValid, 
                name.length === 0 ? "" : (isValid ? "✓ Looks good!" : "✗ Only letters and spaces allowed"));
        }

        function liveValidateLastName() {
            const lname = document.getElementById("lname").value.trim();
            const pattern = /^([a-zA-Z]+\s)*[a-zA-Z]+$/;
            const isValid = lname.length === 0 || pattern.test(lname);
            updateValidationMsg("lname_msg", isValid, 
                lname.length === 0 ? "" : (isValid ? "✓ Looks good!" : "✗ Only letters and spaces allowed"));
        }

        function liveValidateEmail() {
            const mail = document.getElementById("mail").value.trim();
            const pattern = /^[a-zA-Z0-9._-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,5}$/;
            const isValid = mail.length === 0 || pattern.test(mail);
            updateValidationMsg("mail_msg", isValid, 
                mail.length === 0 ? "" : (isValid ? "✓ Valid email format" : "✗ Invalid email format"));
        }

        function liveValidateUsername() {
            const username = document.getElementById("username").value.trim();
            const pattern = /^[a-zA-Z0-9]\w{4,19}$/;
            const isValid = username.length === 0 || pattern.test(username);
            updateValidationMsg("username_msg", isValid, 
                username.length === 0 ? "" : (isValid ? "✓ Valid username" : "✗ 5-20 chars, letters/numbers/_ only"));
        }

        function liveValidatePassword() {
            const password = document.getElementById("password").value;
            const pattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/;
            const isValid = password.length === 0 || pattern.test(password);
            updateValidationMsg("password_msg", isValid, 
                password.length === 0 ? "" : (isValid ? "✓ Strong password!" : "✗ At least 6 chars, 1 uppercase, 1 lowercase, 1 digit"));
        }

        function liveValidateConfirmPassword() {
            const password = document.getElementById("password").value;
            const cnfmPassword = document.getElementById("cnfm_password").value;
            const isValid = cnfmPassword.length === 0 || password === cnfmPassword;
            updateValidationMsg("cnfm_password_msg", isValid, 
                cnfmPassword.length === 0 ? "" : (isValid ? "✓ Passwords match!" : "✗ Passwords do not match"));
        }
    </script>
</body>
</html>