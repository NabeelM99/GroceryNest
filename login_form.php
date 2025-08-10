
<?php
session_start();
include 'project_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check for admin login from database
    $admin_stmt = $conn->prepare("SELECT id, Username, Password, Type FROM user WHERE Email = ? AND Type = 'Admin'");
    $admin_stmt->execute([$email]);
    $admin_user = $admin_stmt->fetch();

    if ($admin_user && password_verify($password, $admin_user['Password'])) {
        $_SESSION['activeUser'] = $admin_user['Username'];
        $_SESSION['userId'] = $admin_user['id'];
        $_SESSION['userType'] = $admin_user['Type'];
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($admin_user) {
        // Admin email exists but password is wrong
        $error = "Incorrect admin password.";
    }


    $stmt = $conn->prepare("SELECT id, Username, Password, Type FROM user WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['Password'])) {
            $_SESSION['activeUser'] = $user['Username'];
            $_SESSION['userId'] = $user['id'];
            $_SESSION['userType'] = $user['Type'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="js/login_validation.js"></script>
    <meta charset="UTF-8">
    <title>Login | GroceryNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/login.css">
    
    
</head>
<body>
    
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-shopping-basket fa-2x mb-2 text-success"></i>
            <h2 class="login-title">GroceryNest Login</h2>
            <p class="text-muted mb-0">Welcome back! Please login to your account.</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="error-msg mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form id="login_form" method="POST" onsubmit="return validateLoginForm();" autocomplete="off" novalidate>
            <div class="form-field-container">
                <div class="form-floating">
                    <i class="fa fa-envelope"></i>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Email Address" 
                           required oninput="liveValidateEmail()">
                    <label for="email">Email Address</label>
                </div>
                <span class="validation-msg" id="email_msg"></span>
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

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                <label class="form-check-label" for="remember_me">
                    Remember me
                </label>
            </div>

            <button type="submit" name="login_user" class="btn btn-login w-100 py-2 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>

            <div class="text-center">
                <a href="registration_form.php" class="text-success text-decoration-none">Don't have an account? Register</a>
            </div>
             <div class="text-center mt-3">
            <a href="index.php" class="text-secondary text-decoration-none">Back to Home</a>
        </div>

        </form>
        </div>
    </div>
</body>
</html>