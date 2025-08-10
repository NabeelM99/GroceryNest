<?php
session_start();

// Redirect admin users to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    header("Location: admin_dashboard.php");
    exit;
}
require_once 'project_connection.php';

// Set JSON header for AJAX requests
if (isset($_GET['toggle'])) {
    header('Content-Type: application/json');
    
    // Check authentication
    if (!isset($_SESSION['userId'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please login to manage your wishlist'
        ]);
        exit;
    }

    $user_id = $_SESSION['userId'];
    $product_id = (int)$_GET['toggle'];

    try {
        $checkStmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // Remove from wishlist
            $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE id = ?");
            $deleteStmt->execute([$exists['id']]);
            
            echo json_encode([
                'status' => 'removed',
                'message' => 'Product removed from wishlist',
                'icon' => 'far fa-heart'
            ]);
        } else {
            // Add to wishlist
            $insertStmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
            $insertStmt->execute([$user_id, $product_id]);
            
            echo json_encode([
                'status' => 'added',
                'message' => 'Product added to wishlist!',
                'icon' => 'fas fa-heart'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error occurred'
        ]);
    }
    exit;
}

// Handle quantity updates (if quantity column exists)
if (isset($_GET['update_quantity'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['userId'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please login first']);
        exit;
    }

    $user_id = $_SESSION['userId'];
    $product_id = (int)$_GET['update_quantity'];
    $action = $_GET['action'] ?? '';
    
    try {
        // Check if quantity column exists
        $columnsStmt = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'quantity'");
        $hasQuantityColumn = $columnsStmt->rowCount() > 0;
        
        if ($hasQuantityColumn) {
            // First check if item exists in wishlist
            $checkStmt = $conn->prepare("SELECT id, quantity FROM wishlist WHERE user_id = ? AND product_id = ?");
            $checkStmt->execute([$user_id, $product_id]);
            $item = $checkStmt->fetch();
            
            if ($item) {
                $current_quantity = $item['quantity'] ?? 1;
                $new_quantity = $current_quantity;
                
                if ($action === 'increase') {
                    $new_quantity = min($current_quantity + 1, 99); // Max 99
                } elseif ($action === 'decrease') {
                    $new_quantity = max($current_quantity - 1, 1); // Min 1
                }
                
                if ($new_quantity !== $current_quantity) {
                    $updateStmt = $conn->prepare("UPDATE wishlist SET quantity = ? WHERE id = ?");
                    $updateStmt->execute([$new_quantity, $item['id']]);
                }
                
                echo json_encode([
                    'status' => 'success',
                    'new_quantity' => $new_quantity,
                    'message' => 'Quantity updated'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Item not found in wishlist']);
            }
        } else {
            // Quantity column doesn't exist, return error
            echo json_encode([
                'status' => 'error', 
                'message' => 'Quantity feature not available. Please add quantity column to wishlist table.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
    }
    exit;
}

// Regular page load functionality
$user_id = $_SESSION['userId'] ?? null;

if (!$user_id) {
    $_SESSION['redirect_url'] = 'wishlist.php';
    header("Location: login_form.php");
    exit;
}

// Get wishlist items with product details
try {
    // Check if quantity column exists
    $columnsStmt = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'quantity'");
    $hasQuantityColumn = $columnsStmt->rowCount() > 0;
    
    if ($hasQuantityColumn) {
        $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.original_price, p.image, p.description, 
                               COALESCE(w.quantity, 1) as quantity,
                               w.created_at
                              FROM wishlist w
                              JOIN products p ON w.product_id = p.id
                              WHERE w.user_id = ?
                              ORDER BY w.created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.original_price, p.image, p.description, 
                               1 as quantity,
                               w.created_at
                              FROM wishlist w
                              JOIN products p ON w.product_id = p.id
                              WHERE w.user_id = ?
                              ORDER BY w.created_at DESC");
    }
    
    $stmt->execute([$user_id]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $wishlistItems = [];
}

// Calculate totals
$totalItems = array_sum(array_column($wishlistItems, 'quantity'));
$totalValue = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $wishlistItems));

// Get user info for header
$stmt = $conn->prepare("SELECT u.Username, c.Fname, c.Lname FROM user u LEFT JOIN customer c ON u.id = c.UID WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to format price
function formatPrice($price) {
    return number_format($price, 3);
}

// Function to calculate discount percentage
function getDiscountPercentage($original, $current) {
    if ($original > $current) {
        return round((($original - $current) / $original) * 100);
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - GroceryNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/wishlist.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="wishlist-container">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="wishlist-header" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="wishlist-title">
                            <i class="fas fa-heart me-3"></i>
                            My Wishlist
                        </h1>
                        <p class="wishlist-subtitle">
                            Welcome back, <?php echo htmlspecialchars($user_info['Fname'] ?? $user_info['Username']); ?>! 
                            Save your favorite products for later.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="wishlist-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= count($wishlistItems) ?></span>
                                <span class="stat-label">Wishlist Items</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php if (!empty($wishlistItems)): ?>
            <div class="container">
                <div class="row g-4" id="wishlistGrid">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="col-md-2 col-sm-6 col-12" data-aos="fade-up" data-aos-delay="<?= (array_search($item, $wishlistItems) % 4) * 100 ?>">
                            <div class="product-card" data-product-id="<?= $item['id'] ?>">
                                <!-- Remove button outside image container -->
                                <button class="favorite-btn remove-wishlist" data-product-id="<?= $item['id'] ?>" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <div class="product-image-container">
                                    <img src="Images/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="product-image" 
                                         loading="lazy">
                                    
                                    <div class="badge-container">
                                        <?php 
                                        $discountPercent = getDiscountPercentage($item['original_price'], $item['price']);
                                        if ($discountPercent > 0): 
                                        ?>
                                            <div class="discount-badge"><?= $discountPercent ?>% OFF</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-title"><?= htmlspecialchars($item['name']) ?></div>
                                
                                <?php 
                                // Check if quantity column exists for display
                                $showQuantityControls = false;
                                try {
                                    $columnsStmt = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'quantity'");
                                    $showQuantityControls = $columnsStmt->rowCount() > 0;
                                } catch (PDOException $e) {
                                    $showQuantityControls = false;
                                }
                                ?>
                                
                                <?php if ($showQuantityControls): ?>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn quantity-decrease" 
                                                data-product-id="<?= $item['id'] ?>" 
                                                <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="quantity-display" data-product-id="<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn quantity-increase" 
                                                data-product-id="<?= $item['id'] ?>"
                                                <?= $item['quantity'] >= 99 ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="d-none">
                                        <span class="quantity-display" data-product-id="<?= $item['id'] ?>">1</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-price-actions">
                                    <div class="price-section">
                                        <div class="item-total" data-product-id="<?= $item['id'] ?>">
                                            <span class="ah-currency">BHD</span>
                                            <span class="price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                            <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                                <span class="original-price">BHD <?= formatPrice($item['original_price'] * $item['quantity']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="add-to-cart-btn add-to-cart-from-wishlist" 
                                            data-product-id="<?= $item['id'] ?>">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-success btn-lg" id="addAllToCart">
                    <i class="fas fa-shopping-cart me-2"></i>Add All to Cart
                </button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h5>Your wishlist is empty</h5>
                <p class="text-muted">Start adding products to your wishlist to see them here</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
    

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/wishlist.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>
</html>