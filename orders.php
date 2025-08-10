<?php
session_start();

require_once 'project_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login_form.php');
    exit();
}

$userId = $_SESSION['userId'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_id']))) {
    header('Content-Type: application/json');
    
    // Handle admin GET request for order details
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_id'])) {
        if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        $order_id = intval($_GET['order_id']);

        try {
            // Get order details with customer information
            $stmt = $conn->prepare("
                SELECT o.*, u.Username, c.Fname, c.Lname, c.Mobile, c.Building, c.Block 
                FROM orders o 
                JOIN user u ON o.user_id = u.id 
                LEFT JOIN customer c ON u.id = c.UID 
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.*, p.name, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error occurred'
            ]);
            exit;
        }
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_order_details':
            $order_id = intval($_POST['order_id']);
            
            // Different queries for admin and regular users
            if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
                // Admin can view any order
                $stmt = $conn->prepare("
                    SELECT o.*, u.Username, c.Fname, c.Lname, c.Mobile, c.Building, c.Block 
                    FROM orders o 
                    JOIN user u ON o.user_id = u.id 
                    LEFT JOIN customer c ON u.id = c.UID 
                    WHERE o.id = ?
                ");
                $stmt->execute([$order_id]);
            } else {
                // Regular users can only view their own orders
                $stmt = $conn->prepare("
                    SELECT o.*, u.Username, c.Fname, c.Lname, c.Mobile, c.Building, c.Block 
                    FROM orders o 
                    JOIN user u ON o.user_id = u.id 
                    LEFT JOIN customer c ON u.id = c.UID 
                    WHERE o.id = ? AND o.user_id = ?
                ");
                $stmt->execute([$order_id, $userId]);
            }
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit();
            }
            
            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.*, p.name, p.image, p.price 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            exit();
            
        case 'cancel_order':
            $order_id = intval($_POST['order_id']);
            
            // Debug logging
            error_log("Cancel order request received for order_id: $order_id, user_id: $userId");
            
            // Check if order can be cancelled (only pending orders)
            $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("Order not found: order_id=$order_id, user_id=$userId");
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit();
            }
            
            error_log("Order status: " . $order['status']);
            
            if ($order['status'] !== 'pending') {
                error_log("Order cannot be cancelled - status is not pending: " . $order['status']);
                echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
                exit();
            }
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            $success = $stmt->execute([$order_id, $userId]);
            
            error_log("Order cancellation result: " . ($success ? 'success' : 'failed'));
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Order cancelled successfully' : 'Failed to cancel order'
            ]);
            exit();
            
        case 'reorder':
            $order_id = intval($_POST['order_id']);
            
            try {
                $conn->beginTransaction();
                
                // Get or create cart for user
                $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                $cart = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cart) {
                    $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                    $cart_id = $conn->lastInsertId();
                } else {
                    $cart_id = $cart['id'];
                }
                
                // Get order items
                $stmt = $conn->prepare("
                    SELECT oi.product_id, oi.quantity 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.id = ? AND o.user_id = ?
                ");
                $stmt->execute([$order_id, $userId]);
                $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $added_items = 0;
                foreach ($order_items as $item) {
                    // Check if product is still in stock
                    $stmt = $conn->prepare("SELECT inStock FROM products WHERE id = ?");
                    $stmt->execute([$item['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product && $product['inStock']) {
                        // Check if item already exists in cart
                        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
                        $stmt->execute([$cart_id, $item['product_id']]);
                        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing_item) {
                            // Update quantity
                            $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
                            $stmt->execute([$item['quantity'], $existing_item['id']]);
                        } else {
                            // Add new item
                            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                            $stmt->execute([$cart_id, $item['product_id'], $item['quantity']]);
                        }
                        $added_items++;
                    }
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "$added_items items added to cart successfully"
                ]);
                
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to reorder items']);
            }
            exit();
    }
}

// Get orders for the user with pagination
$page = intval($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Get orders
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$userId, $per_page, $offset]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user info
$stmt = $conn->prepare("SELECT u.Username, c.Fname, c.Lname FROM user u LEFT JOIN customer c ON u.id = c.UID WHERE u.id = ?");
$stmt->execute([$userId]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - GroceryNest</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/orders.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="orders-container">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="orders-header" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="orders-title">
                            <i class="fas fa-shopping-bag me-3"></i>
                            My Orders
                        </h1>
                        <p class="orders-subtitle">
                            Welcome back, <?php echo htmlspecialchars($user_info['Fname'] ?? $user_info['Username']); ?>! 
                            Track and manage your orders here.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="orders-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $total_orders; ?></span>
                                <span class="stat-label">Total Orders</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="orders-list" data-aos="fade-up" data-aos-delay="200">
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <div class="empty-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" data-aos="fade-up" data-aos-delay="100">
                            <div class="order-header">
                                <div class="order-info">
                                    <div class="order-id">
                                        <strong>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </div>
                                    <div class="order-date">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="order-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Items:</span>
                                                <span class="detail-value"><?php echo $order['item_count']; ?> item(s)</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Total:</span>
                                                <span class="detail-value total-amount">BHD <?php echo number_format($order['total'], 3); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="order-actions">
                                            <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>
                                                View Details
                                            </button>
                                            
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($order['status'], ['delivered', 'cancelled'])): ?>
                                                <button class="btn btn-outline-success btn-sm" onclick="reorderItems(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-redo me-1"></i>
                                                    Reorder
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container" data-aos="fade-up">
                            <nav aria-label="Orders pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">
                        <i class="fas fa-receipt me-2"></i>
                        Order Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
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
    <script src="js/orders.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Add CSS animations for toast notifications
        if (!document.querySelector('#toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>