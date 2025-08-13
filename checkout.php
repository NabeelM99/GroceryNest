<?php
session_start();
require_once 'project_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login_form.php');
    exit();
}

$user_id = $_SESSION['userId'];

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    header('Content-Type: application/json');
    
    try {
        $conn->beginTransaction();
        
        // Get cart items
        $stmt = $conn->prepare("
            SELECT ci.id, ci.quantity, p.id as product_id, p.name, p.price, p.inStock
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN products p ON ci.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cartItems = $stmt->fetchAll();
        
        if (empty($cartItems)) {
            throw new Exception('Cart is empty');
        }
        
        // Check stock availability
        foreach ($cartItems as $item) {
            if (!$item['inStock']) {
                throw new Exception("Product '{$item['name']}' is out of stock");
            }
        }
        
        // Calculate total
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        // Calculate tax (10%)
        $tax_rate = 0.10; // 10%
        $tax_amount = $subtotal * $tax_rate;
        $total = $subtotal + $tax_amount;
        
        // Get form data
        $shipping_address = $_POST['shipping_address'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        
        if (empty($shipping_address) || empty($payment_method)) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Create order
        try {
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, subtotal, tax_amount, total, shipping_address, payment_method, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$user_id, $subtotal, $tax_amount, $total, $shipping_address, $payment_method]);
        } catch (PDOException $e) {
            // If columns don't exist, try the old format
            if (strpos($e->getMessage(), 'subtotal') !== false) {
                $stmt = $conn->prepare("
                    INSERT INTO orders (user_id, total, shipping_address, payment_method, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$user_id, $total, $shipping_address, $payment_method]);
            } else {
                throw $e;
            }
        }
        $order_id = $conn->lastInsertId();
        
        // Add order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cartItems as $item) {
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM cart WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $order_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Get cart items for checkout
$stmt = $conn->prepare("
    SELECT ci.id, ci.quantity, p.id as product_id, p.name, p.price, p.image, p.description,
           (p.price * ci.quantity) as subtotal
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    JOIN products p ON ci.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['subtotal'];
}

// Calculate tax (10%)
$tax_rate = 0.10; // 10%
$tax_amount = $subtotal * $tax_rate;
$total_with_tax = $subtotal + $tax_amount;

// Get user info - FIXED QUERY TO INCLUDE EMAIL
$stmt = $conn->prepare("
    SELECT u.Username, u.Email, c.Fname, c.Lname, c.Mobile, c.Building, c.Block
    FROM user u 
    LEFT JOIN customer c ON u.id = c.UID 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch();

// Get user's profile information for mobile number
$profileStmt = $conn->prepare("
    SELECT Mobile 
    FROM customer 
    WHERE UID = ?
");
$profileStmt->execute([$user_id]);
$profileInfo = $profileStmt->fetch();

// Default shipping address
$defaultAddress = '';
if ($userInfo['Building'] && $userInfo['Block']) {
    $defaultAddress = "Building: {$userInfo['Building']}, Block: {$userInfo['Block']}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - GroceryNest</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/checkout.css">
    
    <style>
        /* Additional styling for readonly email field */
        .form-control[readonly] {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #6c757d;
        }
        
        .readonly-indicator {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="checkout-container">
        <div class="container">
            <!-- Header Section -->
            <div class="checkout-header" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="checkout-title">
                            <i class="fas fa-credit-card me-3"></i>
                            Checkout
                        </h1>
                        <p class="checkout-subtitle">
                            Complete your order and we'll deliver your groceries to your doorstep
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="checkout-steps">
                            <span class="step active">
                                <i class="fas fa-shopping-cart"></i>
                                Cart
                            </span>
                            <span class="step-divider">→</span>
                            <span class="step active">
                                <i class="fas fa-credit-card"></i>
                                Checkout
                            </span>
                            <span class="step-divider">→</span>
                            <span class="step">
                                <i class="fas fa-check"></i>
                                Complete
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="checkoutForm" class="checkout-form">
                <div class="row">
                    <!-- Checkout Form -->
                    <div class="col-lg-8">
                        <div class="checkout-sections" data-aos="fade-up" data-aos-delay="200">
                            
                            <!-- Shipping Information -->
                            <div class="checkout-section">
                                <div class="section-header">
                                    <h3><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h3>
                                </div>
                                <div class="section-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="firstName" name="firstName" 
                                                   value="<?= htmlspecialchars($userInfo['Fname'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="lastName" name="lastName" 
                                                   value="<?= htmlspecialchars($userInfo['Lname'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= htmlspecialchars($profileInfo['Mobile'] ?? $userInfo['Mobile'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($userInfo['Email'] ?? '') ?>" readonly required>
                                            <div class="readonly-indicator">
                                                <i class="fas fa-lock me-1"></i>Using your account email
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="shipping_address" class="form-label">Shipping Address</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                                  rows="3" placeholder="Enter your complete shipping address" required><?= htmlspecialchars($defaultAddress) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="checkout-section">
                                <div class="section-header">
                                    <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                                </div>
                                <div class="section-body">
                                    <div class="payment-methods">
                                        <div class="payment-option">
                                            <input type="radio" class="form-check-input" id="cash_on_delivery" 
                                                   name="payment_method" value="cash_on_delivery" checked>
                                            <label class="form-check-label" for="cash_on_delivery">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                Cash on Delivery
                                            </label>
                                        </div>
                                        <div class="payment-option">
                                            <input type="radio" class="form-check-input" id="credit_card" 
                                                   name="payment_method" value="credit_card">
                                            <label class="form-check-label" for="credit_card">
                                                <i class="fas fa-credit-card me-2"></i>
                                                Credit Card
                                            </label>
                                        </div>
                                        <div class="payment-option">
                                            <input type="radio" class="form-check-input" id="debit_card" 
                                                   name="payment_method" value="debit_card">
                                            <label class="form-check-label" for="debit_card">
                                                <i class="fas fa-credit-card me-2"></i>
                                                Debit Card
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="order-summary" data-aos="fade-up" data-aos-delay="400">
                            <div class="summary-header">
                                <h4><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                            </div>
                            
                            <div class="summary-items">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="summary-item">
                                        <div class="item-info">
                                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="item-details">
                                                <span class="item-quantity">Qty: <?= $item['quantity'] ?></span>
                                                <span class="item-price">BHD <?= number_format($item['price'], 3) ?></span>
                                            </div>
                                        </div>
                                        <div class="item-subtotal">
                                            BHD <?= number_format($item['subtotal'], 3) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="summary-totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span>BHD <?= number_format($subtotal, 3) ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Shipping:</span>
                                    <span class="text-success">Free</span>
                                </div>
                                <div class="total-row">
                                    <span>Tax (10%):</span>
                                    <span>BHD <?= number_format($tax_amount, 3) ?></span>
                                </div>
                                <div class="total-row total-final">
                                    <strong>Total:</strong>
                                    <strong>BHD <?= number_format($total_with_tax, 3) ?></strong>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn place-order-btn" id="placeOrderBtn">
                                <i class="fas fa-lock me-2"></i>
                                Place Order
                            </button>
                            
                            <div class="security-notice">
                                <small>
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Your payment information is secure and encrypted
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Processing Your Order</h5>
                    <p class="text-muted">Please wait while we process your order...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="success-icon mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Order Placed Successfully!</h4>
                    <p class="text-muted">Your order has been placed and is being processed.</p>
                    <div class="order-details mt-3">
                        <p><strong>Order ID:</strong> <span id="orderId"></span></p>
                        <p><strong>Total Amount:</strong> <span id="orderTotal"></span></p>
                    </div>
                    <div class="mt-4">
                        <a href="orders.php" class="btn btn-primary me-2">
                            <i class="fas fa-list me-1"></i>View Orders
                        </a>
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag me-1"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS -->
    <script src="js/checkout.js"></script>
    
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