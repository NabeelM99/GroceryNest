<?php
session_start();
require_once 'project_connection.php';

// Handle AJAX signout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signout') {
    // Clear all session variables
    session_unset();
    // Destroy the session
    session_destroy();
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => 'index.php?signout=1']);
    exit;
}

// Redirect to login if not authenticated
if (!isset($_SESSION['userId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'redirect' => 'login_form.php']);
    exit;
}

// Fetch user info from both user and customer tables
$user_id = $_SESSION['userId'];

// Get user basic info
$stmt = $conn->prepare("SELECT u.Username, u.Email, u.Type, u.created_at, c.Fname, c.Lname, c.Profile_pic 
                        FROM user u 
                        LEFT JOIN customer c ON u.id = c.UID 
                        WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found, redirect to login
    session_destroy();
    header("Location: login_form.php");
    exit;
}

// Get first and last name
$firstName = $user['Fname'] ?? 'User';
$lastName = $user['Lname'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
$email = $user['Email'];
$username = $user['Username'];
$userType = $user['Type'];
$profilePic = $user['Profile_pic'] ?? 'default.jpg';

// Cart summary
$stmt = $conn->prepare("SELECT SUM(ci.quantity) as cartCount, SUM(p.price * ci.quantity) as cartTotal
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    JOIN products p ON ci.product_id = p.id
    WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);
$cartCount = $cart['cartCount'] ?? 0;
$cartTotal = $cart['cartTotal'] ?? 0.00;

// Wishlist summary
$stmt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$stmt->execute([$user_id]);
$wishlistCount = $stmt->fetchColumn();

// Order history count
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$orderCount = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - GroceryNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/signout.css">
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container py-5">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar me-4">
                        <?php if ($profilePic && $profilePic !== 'default.jpg' && file_exists("uploads/profiles/$profilePic")): ?>
                            <img src="uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                                 alt="Profile" class="rounded-circle" width="64" height="64">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2 class="mb-1"><?= htmlspecialchars($fullName) ?></h2>
                        <p class="mb-1 opacity-75">@<?= htmlspecialchars($username) ?></p>
                        <p class="mb-0 opacity-75"><?= htmlspecialchars($email) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="user-type-badge"><?= htmlspecialchars($userType) ?></span>
                <p class="mb-0 mt-2 opacity-75">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Activity Summary -->
        <div class="col-lg-8">
            <div class="card stat-card">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Activity Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Cart Summary -->
                        <div class="col-md-6">
                            <div class="activity-card p-3 bg-primary bg-opacity-10 rounded">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:45px;height:45px;">
                                            <i class="fas fa-shopping-cart text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Shopping Cart</h6>
                                            <small class="text-muted"><?= $cartCount ?> <?= $cartCount == 1 ? "item" : "items" ?></small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">$<?= number_format($cartTotal, 2) ?></div>
                                        <span class="badge bg-primary"><?= $cartCount ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wishlist Summary -->
                        <div class="col-md-6">
                            <div class="activity-card p-3 bg-danger bg-opacity-10 rounded">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-danger bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:45px;height:45px;">
                                            <i class="fas fa-heart text-danger"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Wishlist</h6>
                                            <small class="text-muted">Saved items</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger"><?= $wishlistCount ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Orders Summary -->
                        <div class="col-md-6">
                            <div class="activity-card p-3 bg-success bg-opacity-10 rounded">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:45px;height:45px;">
                                            <i class="fas fa-box text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Orders</h6>
                                            <small class="text-muted">Total orders</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?= $orderCount ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Type -->
                        <div class="col-md-6">
                            <div class="activity-card p-3 bg-warning bg-opacity-10 rounded">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:45px;height:45px;">
                                            <i class="fas fa-user-tag text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Account Type</h6>
                                            <small class="text-muted">Current status</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning"><?= $userType ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card stat-card">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-primary btn-action">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                        <a href="products.php" class="btn btn-outline-success btn-action">
                            <i class="fas fa-shopping-basket me-2"></i>Browse Products
                        </a>
                        <?php if ($cartCount > 0): ?>
                        <a href="cart.php" class="btn btn-outline-info btn-action">
                            <i class="fas fa-shopping-cart me-2"></i>View Cart
                        </a>
                        <?php endif; ?>
                        <?php if ($wishlistCount > 0): ?>
                        <a href="wishlist.php" class="btn btn-outline-danger btn-action">
                            <i class="fas fa-heart me-2"></i>View Wishlist
                        </a>
                        <?php endif; ?>
                        <a href="contact.php" class="btn btn-outline-secondary btn-action">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Account Management -->
    <div class="card stat-card mt-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h5 class="card-title mb-0">
                <i class="fas fa-cogs me-2 text-secondary"></i>
                Account Management
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="editprofile.php" class="btn btn-outline-primary w-100 btn-action">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="orders.php" class="btn btn-outline-success w-100 btn-action">
                        <i class="fas fa-history me-2"></i>Order History
                    </a>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-danger w-100 btn-action" onclick="showSignoutModal()">
                        <i class="fas fa-sign-out-alt me-2"></i>Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Signout Confirmation Modal -->
<div class="signout-overlay" id="signoutOverlay">
    <div class="signout-modal" id="signoutModal">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h2 class="signout-title">Confirm Sign Out</h2>
        <p class="text-muted mb-4">Are you sure you want to sign out of your account? You will lose access to your cart and wishlist items.</p>
        
        <div class="user-info-modal">
            <i class="fas fa-user text-success me-2"></i>
            <strong>Signed in as:</strong> <?= htmlspecialchars($username) ?>
        </div>
        
        <div class="d-flex justify-content-center flex-wrap">
            <button type="button" class="btn btn-signout-confirm" onclick="confirmSignout()" id="signoutBtn">
                <span class="loading-spinner" id="loadingSpinner"></span>
                <i class="fas fa-sign-out-alt me-2" id="signoutIcon"></i>
                <span id="signoutText">Yes, Sign Out</span>
            </button>
            
            <button type="button" class="btn btn-cancel-modal" onclick="hideSignoutModal()">
                <i class="fas fa-times me-2"></i>Cancel
            </button>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted">You will be redirected to the home page</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
// Initialize AOS
AOS.init({
    duration: 800, 
    once: true, 
    offset: 100, 
    easing: 'ease-out',
    delay: 0
});

// Signout Modal Functions
function showSignoutModal() {
    const overlay = document.getElementById('signoutOverlay');
    const modal = document.getElementById('signoutModal');
    document.body.classList.add('modal-active');
    
    // Show overlay
    overlay.style.display = 'flex';
    
    // Trigger animations after a small delay
    setTimeout(() => {
        overlay.classList.add('show');
        setTimeout(() => {
            modal.classList.add('show');
        }, 100);
    }, 10);
    
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
}

function hideSignoutModal() {
    const overlay = document.getElementById('signoutOverlay');
    const modal = document.getElementById('signoutModal');
    
    // Reverse animations
    modal.classList.remove('show');
    setTimeout(() => {
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.classList.remove('modal-active');
            document.body.style.overflow = '';
        }, 300);
    }, 200);
}

async function confirmSignout() {
    const signoutBtn = document.getElementById('signoutBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const signoutIcon = document.getElementById('signoutIcon');
    const signoutText = document.getElementById('signoutText');
    
    // Show loading state
    signoutBtn.disabled = true;
    loadingSpinner.style.display = 'inline-block';
    signoutIcon.style.display = 'none';
    signoutText.textContent = 'Signing out...';
    
    try {
        // Send request to signout.php
        const response = await fetch('signout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=signout'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success state
            signoutText.textContent = 'Signed out!';
            loadingSpinner.style.display = 'none';
            signoutIcon.style.display = 'inline';
            signoutIcon.className = 'fas fa-check me-2';
            signoutBtn.classList.remove('btn-signout-confirm');
            signoutBtn.classList.add('btn-success');
            
            // Redirect after short delay
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1500);
        } else {
            throw new Error('Signout failed');
        }
    } catch (error) {
        // Show error state
        signoutText.textContent = 'Error occurred';
        loadingSpinner.style.display = 'none';
        signoutIcon.style.display = 'inline';
        signoutIcon.className = 'fas fa-exclamation-triangle me-2';
        signoutBtn.classList.remove('btn-signout-confirm');
        signoutBtn.classList.add('btn-warning');
        
        // Reset after delay
        setTimeout(() => {
            signoutBtn.disabled = false;
            signoutIcon.className = 'fas fa-sign-out-alt me-2';
            signoutText.textContent = 'Yes, Sign Out';
            signoutBtn.classList.remove('btn-warning');
            signoutBtn.classList.add('btn-signout-confirm');
        }, 2000);
    }
}

// Close modal when clicking outside
document.getElementById('signoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideSignoutModal();
    }
});

// Close modal when pressing Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('signoutOverlay').classList.contains('show')) {
        hideSignoutModal();
    }
});
</script>
</body>
</html>