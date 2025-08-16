<?php
session_start();
require_once 'project_connection.php';

// Check if user is admin
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Admin') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_message') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    header("Location: login_form.php");
    exit;
}

// Handle get_message request
if (isset($_GET['action']) && $_GET['action'] === 'get_message' && isset($_GET['id'])) {
    try {
        $message_id = intval($_GET['id']);
        
        $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($message) {
            // Mark as read when viewed
            $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $update_stmt->execute([$message_id]);
            
            echo json_encode($message);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Message not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Messages table not available yet. Please create the messages table first.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

// Handle messages from redirects
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $messageType = urldecode($_GET['type']);
}

// Get current tab from URL or default to dashboard
$current_tab = $_GET['tab'] ?? 'dashboard';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_product':
                    error_log("Processing add_product action");
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $price = floatval($_POST['price']);
                    $category_id = intval($_POST['category_id']);
                    error_log("Add product data: name=$name, price=$price, category_id=$category_id");
                    
                    // Handle file upload
                    $image = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                        $target_dir = "Images/";
                        $image = time() . '_' . basename($_FILES["image"]["name"]);
                        $target_file = $target_dir . $image;
                        
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            // Image uploaded successfully
                        } else {
                            throw new Exception("Failed to upload image");
                        }
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category_id, inStock) VALUES (?, ?, ?, ?, ?, ?)");
                    $inStock = 1; // Default to in stock
                    $stmt->execute([$name, $description, $price, $image, $category_id, $inStock]);
                    
                    $message = "Product added successfully!";
                    $messageType = "success";
                    // Redirect to prevent form resubmission
                    $tab = $_POST['current_tab'] ?? 'products';
                    header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                    exit;
                    break;
                    
                case 'update_product':
                    error_log("Processing update_product action");
                    $id = intval($_POST['product_id']);
                    $name = trim($_POST['name']);
                    $description = trim($_POST['description']);
                    $price = floatval($_POST['price']);
                    $category_id = intval($_POST['category_id']);
                    error_log("Update product data: id=$id, name=$name, price=$price, category_id=$category_id, inStock=" . (isset($_POST['inStock']) ? '1' : '0'));
                    
                    // Validate that product exists
                    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        throw new Exception("Product not found");
                    }
                    
                    // Handle file upload for update
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                        $target_dir = "Images/";
                        $image = time() . '_' . basename($_FILES["image"]["name"]);
                        $target_file = $target_dir . $image;
                        
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, image=?, category_id=?, inStock=? WHERE id=?");
                            $inStock = isset($_POST['inStock']) ? 1 : 0;
                            $stmt->execute([$name, $description, $price, $image, $category_id, $inStock, $id]);
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category_id=?, inStock=? WHERE id=?");
                        $inStock = isset($_POST['inStock']) ? 1 : 0;
                        $stmt->execute([$name, $description, $price, $category_id, $inStock, $id]);
                    }
                    
                    $message = "Product updated successfully!";
                    $messageType = "success";
                    // Redirect to prevent form resubmission
                    $tab = $_POST['current_tab'] ?? 'products';
                    header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                    exit;
                    break;
                    
                case 'delete_product':
                    $id = intval($_POST['product_id']);
                    
                    // Validate that product exists
                    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if (!$check_stmt->fetch()) {
                        throw new Exception("Product not found");
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    $message = "Product deleted successfully!";
                    $messageType = "success";
                    // Redirect to prevent form resubmission
                    $tab = $_POST['current_tab'] ?? 'products';
                    header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                    exit;
                    break;
                    
                case 'add_category':
                    $name = trim($_POST['category_name']);
                    
                    // Check if category already exists
                    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                    $check_stmt->execute([$name]);
                    if ($check_stmt->fetch()) {
                        throw new Exception("Category already exists");
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$name]);
                    
                    $message = "Category added successfully!";
                    $messageType = "success";
                    // Redirect to prevent form resubmission
                    $tab = $_POST['current_tab'] ?? 'categories';
                    header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                    exit;
                    break;
                    
                case 'update_order_status':
                    $order_id = intval($_POST['order_id']);
                    $new_status = $_POST['status'];
                    
                    // Validate order exists
                    $check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
                    $check_stmt->execute([$order_id]);
                    if (!$check_stmt->fetch()) {
                        throw new Exception("Order not found");
                    }
                    
                    // Validate status
                    $valid_statuses = ['processing', 'shipped', 'delivered', 'cancelled'];
                    if (!in_array($new_status, $valid_statuses)) {
                        throw new Exception("Invalid status");
                    }
                    
                    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $order_id]);
                    
                    $message = "Order status updated successfully!";
                    $messageType = "success";
                    // Redirect to prevent form resubmission
                    $tab = $_POST['current_tab'] ?? 'orders';
                    header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                    exit;
                    break;
                    
                case 'mark_message_read':
                    try {
                        $message_id = intval($_POST['message_id']);
                        
                        // Validate message exists
                        $check_stmt = $conn->prepare("SELECT id FROM messages WHERE id = ?");
                        $check_stmt->execute([$message_id]);
                        if (!$check_stmt->fetch()) {
                            throw new Exception("Message not found");
                        }
                        
                        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
                        $stmt->execute([$message_id]);
                        
                        $message = "Message marked as read!";
                        $messageType = "success";
                        // Redirect to prevent form resubmission
                        $tab = $_POST['current_tab'] ?? 'messages';
                        header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                        exit;
                    } catch (PDOException $e) {
                        throw new Exception("Messages table not available yet. Please create the messages table first.");
                    }
                    break;
                    
                case 'delete_message':
                    try {
                        $message_id = intval($_POST['message_id']);
                        
                        // Validate message exists
                        $check_stmt = $conn->prepare("SELECT id FROM messages WHERE id = ?");
                        $check_stmt->execute([$message_id]);
                        if (!$check_stmt->fetch()) {
                            throw new Exception("Message not found");
                        }
                        
                        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
                        $stmt->execute([$message_id]);
                        
                        $message = "Message deleted successfully!";
                        $messageType = "success";
                        // Redirect to prevent form resubmission
                        $tab = $_POST['current_tab'] ?? 'messages';
                        header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . urlencode($messageType) . "&tab=" . urlencode($tab));
                        exit;
                    } catch (PDOException $e) {
                        throw new Exception("Messages table not available yet. Please create the messages table first.");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch data for display
$products_stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch orders with customer details
$orders_stmt = $conn->prepare("
    SELECT o.*, u.Username, c.Fname, c.Lname, c.Mobile 
    FROM orders o 
    JOIN user u ON o.user_id = u.id 
    LEFT JOIN customer c ON u.id = c.UID 
    ORDER BY o.created_at DESC
");
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$out_of_stock = $conn->query("SELECT COUNT(*) FROM products WHERE inStock = 0")->fetchColumn();
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Check if messages table exists and handle messages
$unread_messages = 0;
$messages = [];
try {
    $unread_messages = $conn->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
    
    // Fetch messages
    $messages_stmt = $conn->prepare("SELECT * FROM messages ORDER BY created_at DESC");
    $messages_stmt->execute();
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Messages table doesn't exist yet, continue without messages functionality
    $unread_messages = 0;
    $messages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GroceryNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-shopping-cart me-2"></i>
                GroceryNest Admin
            </h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" data-tab="dashboard">
                    <i class="fas fa-chart-bar me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#products" data-tab="products">
                    <i class="fas fa-box me-2"></i>
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#categories" data-tab="categories">
                    <i class="fas fa-tags me-2"></i>
                    Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#orders" data-tab="orders">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#messages" data-tab="messages">
                    <i class="fas fa-envelope me-2"></i>
                    Messages
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $unread_messages ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-section mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="fw-bold mb-1">Admin Dashboard</h2>
                    <p class="text-muted mb-0">Manage your grocery store efficiently</p>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">Welcome back, Administrator</span>
                    <button class="btn btn-success d-flex align-items-center">
                        <i class="fas fa-user me-2"></i>
                        Administrator
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Tab -->
        <div id="dashboard-content" class="tab-content-section">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="counter" data-target="<?= $total_products ?>">0</h3>
                                <p class="mb-0">Total Products</p>
                                <small class="opacity-75">Active inventory</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="counter" data-target="<?= $total_categories ?>">0</h3>
                                <p class="mb-0">Categories</p>
                                <small class="opacity-75">Product categories</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-tags fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="counter" data-target="<?= $out_of_stock ?>">0</h3>
                                <p class="mb-0">Out of Stock</p>
                                <small class="opacity-75">Needs attention</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="counter" data-target="<?= $total_orders ?>">0</h3>
                                <p class="mb-0">Total Orders</p>
                                <small class="opacity-75">All time</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-shopping-bag fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Stats Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stats-card" style="background: var(--gradient-secondary);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="counter" data-target="<?= $pending_orders ?>">0</h3>
                                <p class="mb-0">Pending Orders</p>
                                <small class="opacity-75">Awaiting processing</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?= number_format($total_products > 0 ? ($total_products - $out_of_stock) / $total_products * 100 : 0, 1) ?>%</h3>
                                <p class="mb-0">Stock Rate</p>
                                <small class="opacity-75">Products in stock</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="product-card">
                <h5 class="mb-3">Recent Products</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($products, 0, 5) as $product): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="Images/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-image-small me-3">
                                        <span><?= htmlspecialchars($product['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td>BHD <?= number_format($product['price'], 3) ?></td>
                                <td>
                                    <span class="badge <?= $product['inStock'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $product['inStock'] ? 'In Stock' : 'Out of Stock' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Products Tab -->
        <div id="products-content" class="tab-content-section" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Products Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Add Product
                </button>
            </div>

            <div class="product-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($product['image']): ?>
                                        <img src="Images/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-image-small me-3"
                                             onerror="this.src='Images/default-product.jpg'">
                                        <?php else: ?>
                                        <div class="product-image-small me-3 bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 50)) ?><?= strlen($product['description'] ?? '') > 50 ? '...' : '' ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'No Category') ?></td>
                                <td>BHD <?= number_format($product['price'], 3) ?></td>
                                <td>
                                    <span class="badge <?= $product['inStock'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $product['inStock'] ? 'In Stock' : 'Out of Stock' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1 edit-product-btn" 
                                            data-product='<?= json_encode([
                                                'id' => $product['id'],
                                                'name' => $product['name'],
                                                'description' => $product['description'] ?? '',
                                                'price' => $product['price'],
                                                'category_id' => $product['category_id'],
                                                'inStock' => $product['inStock']
                                            ]) ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div id="categories-content" class="tab-content-section" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Categories Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>

            <div class="product-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Products Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <?php
                            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                            $count_stmt->execute([$category['id']]);
                            $product_count = $count_stmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?= $category['id'] ?></td>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td><span class="badge bg-primary"><?= $product_count ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orders Tab -->
        <div id="orders-content" class="tab-content-section" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Orders Management</h4>
            </div>

            <div class="product-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold">
                                            <?= htmlspecialchars($order['Fname'] ?? 'N/A') ?> <?= htmlspecialchars($order['Lname'] ?? '') ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($order['Username']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><?= htmlspecialchars($order['Mobile'] ?? 'N/A') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order['shipping_address'] ?? 'No address') ?></small>
                                    </div>
                                </td>
                                <td>
                                    <strong>BHD <?= number_format($order['total'], 3) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Subtotal: BHD <?= number_format($order['subtotal'], 3) ?><br>
                                        Tax: BHD <?= number_format($order['tax_amount'], 3) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-warning',
                                        'processing' => 'bg-info',
                                        'shipped' => 'bg-primary',
                                        'delivered' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $statusColor = $statusColors[$order['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusColor ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                        <br>
                                        <small class="text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewOrderDetails(<?= $order['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, 'processing')"
                                                <?= $order['status'] !== 'pending' ? 'disabled' : '' ?>>
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, 'shipped')"
                                                <?= !in_array($order['status'], ['pending', 'processing']) ? 'disabled' : '' ?>>
                                            <i class="fas fa-shipping-fast"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, 'delivered')"
                                                <?= !in_array($order['status'], ['pending', 'processing', 'shipped']) ? 'disabled' : '' ?>>
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, 'cancelled')"
                                                <?= $order['status'] === 'cancelled' ? 'disabled' : '' ?>>
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Messages Tab -->
        <div id="messages-content" class="tab-content-section" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Messages</h4>
            </div>

            <div class="product-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>From</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= $message['id'] ?></td>
                                <td><?= htmlspecialchars($message['subject']) ?></td>
                                <td><?= htmlspecialchars($message['name']) ?></td>
                                <td><?= date('M d, Y', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $message['is_read'] ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $message['is_read'] ? 'Read' : 'Unread' ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewMessage(<?= $message['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="markMessageAsRead(<?= $message['id'] ?>)">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteMessage(<?= $message['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_product">
                        <input type="hidden" name="current_tab" value="products">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (BHD)</label>
                                <input type="number" step="0.001" class="form-control" name="price" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="inStock" id="add_inStock" checked>
                                    <label class="form-check-label" for="add_inStock">
                                        In Stock
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <input type="hidden" name="current_tab" value="products">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (BHD)</label>
                                <input type="number" step="0.001" class="form-control" name="price" id="edit_price" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Image (optional)</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="inStock" id="edit_inStock" checked>
                                    <label class="form-check-label" for="edit_inStock">
                                        In Stock
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        <input type="hidden" name="current_tab" value="categories">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Notification Toast -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="notificationToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="toastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin_dashboard.js"></script>

</body>
</html>