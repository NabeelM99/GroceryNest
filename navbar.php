<?php if (!isset($navbar_only)): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GroceryNest - Online Grocery Shopping</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/index_feat_cat.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/products.css">
</head>
<?php endif; ?>


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
                    <a class="nav-link active" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php#features1">Features</a>
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
                <?php else: ?>
                    <!-- Customer Navigation -->
                    <a href="wishlist.php" class="btn btn-outline-danger position-relative">
                        <i class="fas fa-heart"></i>
                    </a>
                    <a href="cart.php" class="btn btn-success position-relative">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <?php if (isset($_SESSION['activeUser'])): ?>
                        <!-- Logged in user -->
                        <a href="profile.php" class="btn btn-outline-secondary" id="profileBtn">
                            <i class="fas fa-user"></i>
                        </a>
                        <script>
                        // Add click event to profile button to show loading screen
                        document.getElementById('profileBtn').addEventListener('click', function(e) {
                            // Only show loading screen if not already on profile page
                            if (!window.location.href.includes('profile.php')) {
                                const loadingScreen = document.createElement('div');
                                loadingScreen.className = 'loading-screen';
                                loadingScreen.style.position = 'fixed';
                                loadingScreen.style.top = '0';
                                loadingScreen.style.left = '0';
                                loadingScreen.style.width = '100%';
                                loadingScreen.style.height = '100%';
                                loadingScreen.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                                loadingScreen.style.display = 'flex';
                                loadingScreen.style.alignItems = 'center';
                                loadingScreen.style.justifyContent = 'center';
                                loadingScreen.style.zIndex = '9999';
                                loadingScreen.innerHTML = '<div class="loading-spinner"></div>';
                                document.body.appendChild(loadingScreen);
                                
                                // Add styles for the loading spinner
                                const style = document.createElement('style');
                                style.textContent = `
                                    @keyframes spin {
                                        to { transform: rotate(360deg); }
                                    }
                                    .loading-spinner {
                                        width: 50px;
                                        height: 50px;
                                        border: 5px solid rgba(255, 255, 255, 0.3);
                                        border-radius: 50%;
                                        border-top-color: #fff;
                                        animation: spin 1s ease-in-out infinite;
                                    }
                                `;
                                document.head.appendChild(style);
                            }
                        });
                        </script>
                    <?php else: ?>
                        <!-- Not logged in -->
                        <a href="login_form.php" class="btn btn-outline-info">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>