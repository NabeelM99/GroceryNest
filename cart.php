<?php
session_start();

// Redirect admin users to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    header("Location: admin_dashboard.php");
    exit;
}

require_once 'project_connection.php';

// Handle AJAX requests for adding items to cart
if (isset($_GET['add'])) {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please login to add items to cart'
        ]);
        exit;
    }

    $user_id = $_SESSION['userId'];
    $product_id = (int)$_GET['add'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

    try {
        // First, get or create a cart for the user
        $cartStmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
        $cartStmt->execute([$user_id]);
        $cart = $cartStmt->fetch();

        if (!$cart) {
            // Create new cart
            $createCartStmt = $conn->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
            $createCartStmt->execute([$user_id]);
            $cart_id = $conn->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if item already exists in cart
        $checkStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $checkStmt->execute([$cart_id, $product_id]);
        $existingItem = $checkStmt->fetch();

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $updateStmt->execute([$newQuantity, $existingItem['id']]);
            
            echo json_encode([
                'status' => 'updated',
                'message' => 'Cart updated successfully!'
            ]);
        } else {
            // Add new item to cart
            $insertStmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $insertStmt->execute([$cart_id, $product_id, $quantity]);
            
            echo json_encode([
                'status' => 'added',
                'message' => 'Product added to cart!'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['userId'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please login']);
        exit;
    }

    $cart_item_id = (int)$_POST['cart_item_id'];
    $new_quantity = (int)$_POST['quantity'];

    if ($new_quantity < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1']);
        exit;
    }

    try {
        // Update quantity
        $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $updateStmt->execute([$new_quantity, $cart_item_id]);

        // Get updated item details for response
        $stmt = $conn->prepare("SELECT ci.quantity, p.price, (p.price * ci.quantity) as subtotal
                              FROM cart_items ci
                              JOIN products p ON ci.product_id = p.id
                              WHERE ci.id = ?");
        $stmt->execute([$cart_item_id]);
        $item = $stmt->fetch();

        // Calculate new cart total
        $user_id = $_SESSION['userId'];
        $totalStmt = $conn->prepare("SELECT SUM(p.price * ci.quantity) as subtotal, COUNT(*) as item_count
                                   FROM cart_items ci
                                   JOIN cart c ON ci.cart_id = c.id
                                   JOIN products p ON ci.product_id = p.id
                                   WHERE c.user_id = ?");
        $totalStmt->execute([$user_id]);
        $totalResult = $totalStmt->fetch();
        
        // Calculate tax (10%)
        $subtotal = $totalResult['subtotal'] ?? 0;
        $tax_rate = 0.10; // 10%
        $tax_amount = $subtotal * $tax_rate;
        $total = $subtotal + $tax_amount;

        echo json_encode([
            'status' => 'success',
            'item_subtotal' => number_format($item['price'] * $new_quantity, 3, '.', ''),
            'subtotal' => number_format($subtotal, 3, '.', ''),
            'tax_amount' => number_format($tax_amount, 3, '.', ''),
            'total' => number_format($total, 3, '.', ''),
            'item_count' => (int)($totalResult['item_count'] ?? 0)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['userId'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please login']);
        exit;
    }

    $cart_item_id = (int)$_POST['cart_item_id'];

    try {
        // Remove item
        $deleteStmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
        $deleteStmt->execute([$cart_item_id]);

        // Calculate new cart total
        $user_id = $_SESSION['userId'];
        $totalStmt = $conn->prepare("SELECT SUM(p.price * ci.quantity) as subtotal, COUNT(*) as item_count
                                   FROM cart_items ci
                                   JOIN cart c ON ci.cart_id = c.id
                                   JOIN products p ON ci.product_id = p.id
                                   WHERE c.user_id = ?");
        $totalStmt->execute([$user_id]);
        $totalResult = $totalStmt->fetch();
        
        // Calculate tax (10%)
        $subtotal = $totalResult['subtotal'] ?? 0;
        $tax_rate = 0.10; // 10%
        $tax_amount = $subtotal * $tax_rate;
        $total = $subtotal + $tax_amount;

        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from cart',
            'subtotal' => number_format($subtotal, 3),
            'tax_amount' => number_format($tax_amount, 3),
            'total' => number_format($total, 3),
            'item_count' => (int)($totalResult['item_count'] ?? 0)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

// Handle cart count request
if (isset($_GET['count'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['userId'])) {
        echo json_encode(['status' => 'success', 'count' => 0]);
        exit;
    }

    $user_id = $_SESSION['userId'];
    
    try {
        $stmt = $conn->prepare("SELECT SUM(ci.quantity) as total_count 
                               FROM cart_items ci 
                               JOIN cart c ON ci.cart_id = c.id 
                               WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $count = $result['total_count'] ?? 0;
        
        echo json_encode(['status' => 'success', 'count' => (int)$count]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'count' => 0]);
    }
    exit;
}

// Regular page load - display cart
$user_id = $_SESSION['userId'] ?? null;

if (!$user_id) {
    $_SESSION['redirect_url'] = 'cart.php';
    header("Location: login_form.php");
    exit;
}

// Get cart items
$stmt = $conn->prepare("SELECT ci.id as cart_item_id, ci.quantity, p.id, p.name, p.price, p.image, p.description,
                        (p.price * ci.quantity) as subtotal
                        FROM cart_items ci
                        JOIN cart c ON ci.cart_id = c.id
                        JOIN products p ON ci.product_id = p.id
                        WHERE c.user_id = ?
                        ORDER BY ci.added_at DESC");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['subtotal'];
}

// Calculate tax (10%)
$tax_rate = 0.10; // 10%
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $tax_amount;

// Get user info for header
$stmt = $conn->prepare("SELECT u.Username, c.Fname, c.Lname FROM user u LEFT JOIN customer c ON u.id = c.UID WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - GroceryNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/navbar.css">
    
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
    
    <div class="cart-container">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="cart-header" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="cart-title">
                            <i class="fas fa-shopping-cart me-3"></i>
                            Shopping Cart
                        </h1>
                        <p class="cart-subtitle">
                            Welcome back, <?php echo htmlspecialchars($user_info['Fname'] ?? $user_info['Username']); ?>! 
                            Review and manage your cart items.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="cart-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= count($cartItems) ?></span>
                                <span class="stat-label">Cart Items</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h4 class="mb-3 text-muted">Your cart is empty</h4>
                <p class="text-muted mb-4">Add some delicious products to your cart to continue shopping</p>
                <a href="products.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <!-- Compact Cart Items -->
                    <div id="cart-items">
                        <?php foreach ($cartItems as $index => $item): ?>
                            <div class="cart-item d-flex align-items-center" 
                                 data-item-id="<?= $item['cart_item_id'] ?>"
                                 data-aos="fade-up" 
                                 data-aos-delay="<?= $index * 100 ?>"
                                 data-aos-duration="600">
                                <!-- Product Image -->
                                <div class="flex-shrink-0 me-3">
                                    <div class="product-img-container">
                                        <img src="Images/<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>" 
                                             class="product-img">
                                    </div>
                                </div>
                                
                                <!-- Product Details -->
                                <div class="flex-grow-1 overflow-hidden pe-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="mb-0"><?= htmlspecialchars($item['name']) ?></h5>
                                        <div class="subtotal-text ms-2">
                                            BHD <span class="subtotal-value"><?= number_format($item['subtotal'], 3) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div class="product-meta">
                                            BHD <?= number_format($item['price'], 3) ?>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="quantity-controls me-2">
                                                <button class="quantity-btn decrease-qty" data-item-id="<?= $item['cart_item_id'] ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="quantity-input" 
                                                       value="<?= $item['quantity'] ?>" min="1" max="99"
                                                       data-item-id="<?= $item['cart_item_id'] ?>" readonly>
                                                <button class="quantity-btn increase-qty" data-item-id="<?= $item['cart_item_id'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <button class="remove-btn remove-item" 
                                                    data-item-id="<?= $item['cart_item_id'] ?>"
                                                    data-product-name="<?= htmlspecialchars($item['name']) ?>"
                                                    title="Remove item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Cart Summary -->
                    <div class="card cart-summary" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal:</span>
                                <span id="subtotal">BHD <?= number_format($subtotal, 3) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping:</span>
                                <span class="text-success"><i class="fas fa-check me-1"></i>Free</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tax (10%):</span>
                                <span id="tax-amount">BHD <?= number_format($tax_amount, 3) ?></span>
                            </div>
                            <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                            <div class="d-flex justify-content-between mb-4">
                                <strong style="font-size: 1.2rem;">Total:</strong>
                                <strong style="font-size: 1.2rem;" id="total">BHD <?= number_format($total, 3) ?></strong>
                            </div>
                            <a href="checkout.php" class="btn checkout-btn w-100" <?= empty($cartItems) ? 'style="pointer-events: none; opacity: 0.6;"' : '' ?>>
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                            <div class="text-center mt-3">
                                <small><i class="fas fa-shield-alt me-1"></i>Secure payment guaranteed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/cart.js"></script>
    
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