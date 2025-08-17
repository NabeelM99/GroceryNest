
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
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="stylesheet" href="css/login.css">
    
    
    
    
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-spinner"></div>
    </div>
    

    

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#" data-aos="fade-right">
                GroceryNest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#offers">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <!-- Search Bar -->
                <form class="d-flex me-3" action="products.php" method="get">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" placeholder="Search products..." style="border-radius: 25px 0 0 25px;">
                        <button class="btn btn-outline-success" type="submit" style="border-radius: 0 25px 25px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="d-flex align-items-center gap-2">
                    <?php if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin'): ?>
                        <!-- Admin Navigation -->
                        <a href="admin_dashboard.php" class="btn btn-success">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                        </a>
                        <a href="signout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <!-- Customer Navigation -->
                        <a href="wishlist.php" class="btn btn-outline-warning position-relative">
                            <i class="fas fa-heart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                0
                            </span>
                        </a>
                        <a href="cart.php" class="btn btn-primary position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                                0
                            </span>
                        </a>
                        <?php if (isset($_SESSION['activeUser'])): ?>
                            <!-- Logged in user -->
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user"></i>
                            </a>
                            <a href="signout.php" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        <?php else: ?>
                            <!-- Not logged in -->
                            <a href="login_form.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt"></i>
                            </a>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    
    <div class="login-card" data-aos="fade-up" data-aos-duration="800" data-aos-easing="ease-in-out">
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
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS with the same settings as the home page
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100
            });
            
            // Add loading animation class to body
            document.body.classList.add('aos-animate');
            
            // Page loading animation
            window.addEventListener('load', function() {
                const progressBar = document.getElementById('progressBar');
                const loadingScreen = document.getElementById('loadingScreen');
                
                // Simulate progress (faster loading)
                let width = 0;
                const interval = setInterval(function() {
                    if (width >= 100) {
                        clearInterval(interval);
                        loadingScreen.style.opacity = '0';
                        setTimeout(() => {
                            loadingScreen.style.display = 'none';
                        }, 200);
                    } else {
                        width += 2; // Move faster
                        progressBar.style.width = width + '%';
                    }
                }, 5);
            });
        });
    </script>
</body>
</html>