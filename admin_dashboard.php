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
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --sidebar-bg: #1f2937;
            --sidebar-hover: #374151;
            --gradient-primary: linear-gradient(135deg, #10b981, #059669);
            --gradient-secondary: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --shadow-soft: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-large: 0 8px 30px rgba(0, 0, 0, 0.2);
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Sidebar Animations */
        .sidebar {
            background: var(--sidebar-bg);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-large);
            backdrop-filter: blur(10px);
        }
        
        .sidebar .nav-link {
            color: #d1d5db;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .sidebar .nav-link:hover::before {
            left: 100%;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--sidebar-hover);
            color: var(--primary-color);
            transform: translateX(5px);
            box-shadow: var(--shadow-soft);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            animation: slideInRight 0.6s ease-out;
        }
        
        /* Stats Cards with Animations */
        .stats-card {
            background: var(--gradient-primary);
            border: none;
            border-radius: 20px;
            color: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .stats-card:hover::before {
            transform: translateX(100%);
        }
        
        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-large);
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            animation: countUp 1s ease-out;
        }
        
        .stats-card i {
            transition: all 0.3s ease;
        }
        
        .stats-card:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        
        .stats-icon {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover .stats-icon {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Product Cards with Enhanced Animations */
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
            margin-bottom: 1rem;
            border: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            border-color: var(--primary-color);
        }
        
        .product-image-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
        }
        
        .product-card:hover .product-image-small {
            transform: scale(1.1);
            box-shadow: var(--shadow-medium);
        }
        
        /* Enhanced Buttons */
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
            transform: translateY(-2px);
        }
        
        /* Modal Enhancements */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-large);
            animation: modalSlideIn 0.4s ease-out;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 20px 20px 0 0;
        }
        
        /* Table Enhancements */
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        
        .table th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: none;
            font-weight: 600;
            color: #374151;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.1));
            transform: scale(1.01);
        }
        
        /* Badge Animations */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .badge:hover {
            transform: scale(1.1);
        }
        
        /* Header Section */
        .header-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(229, 231, 235, 0.5);
            backdrop-filter: blur(10px);
            animation: slideInDown 0.6s ease-out;
        }
        
        .header-section h2 {
            color: #1f2937;
            font-size: 1.875rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Alert Animations */
        .alert {
            border-radius: 15px;
            border: none;
            animation: slideInRight 0.5s ease-out;
            box-shadow: var(--shadow-soft);
        }
        
        /* Keyframe Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes countUp {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-card h3 {
                font-size: 2rem;
            }
        }
    </style>
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
                                        <button class="btn btn-sm btn-outline-success" 
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
    <script>
        // Show notification toast
        function showNotification(message, type = 'success') {
            const toast = document.getElementById('notificationToast');
            const messageEl = document.getElementById('toastMessage');
            
            // Set message
            messageEl.textContent = message;
            
            // Set color based on type
            toast.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning');
            switch(type) {
                case 'success':
                    toast.classList.add('bg-success');
                    break;
                case 'error':
                    toast.classList.add('bg-danger');
                    break;
                case 'info':
                    toast.classList.add('bg-info');
                    break;
                case 'warning':
                    toast.classList.add('bg-warning');
                    break;
            }
            
            // Show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
        }

        // Check for message in URL and show notification
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('message') && urlParams.has('type')) {
            showNotification(
                decodeURIComponent(urlParams.get('message')),
                urlParams.get('type')
            );
        }

        // --- Animation helpers (from new code) ---
        // Ripple effect for tab switching
        function addRippleEffect(e, el) {
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = e.clientX - el.offsetLeft + 'px';
            ripple.style.top = e.clientY - el.offsetTop + 'px';
            ripple.style.width = ripple.style.height = '20px';
            el.style.position = 'relative';
            el.style.overflow = 'hidden';
            el.appendChild(ripple);
            setTimeout(() => { ripple.remove(); }, 1000);
        }
        // Card/counter/product fade-in (from new code)
        document.addEventListener('DOMContentLoaded', function() {
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            const counters = document.querySelectorAll('.counter');
            const animateCounter = (counter) => {
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    counter.textContent = Math.floor(current);
                }, 16);
            };
            const observerOptions = { threshold: 0.5, rootMargin: '0px 0px -100px 0px' };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            counters.forEach(counter => { observer.observe(counter); });
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        // Add ripple effect CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to { transform: scale(4); opacity: 0; }
            }
            .tab-content-section { transition: all 0.3s ease; }
            .modal-content { transition: all 0.3s ease; }
        `;
        document.head.appendChild(style);

        // --- Old reliable logic for admin actions ---
        // Tab switching (with ripple, but old logic for show/hide)
        document.querySelectorAll('[data-tab]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                addRippleEffect(e, this);
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.tab-content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetTab = this.getAttribute('data-tab');
                const targetContent = document.getElementById(targetTab + '-content');
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
            });
        });
        // Edit product functionality (old logic)
        function editProduct(productData) {
            document.getElementById('edit_product_id').value = productData.id;
            document.getElementById('edit_name').value = productData.name || '';
            document.getElementById('edit_description').value = productData.description || '';
            document.getElementById('edit_price').value = productData.price || '';
            document.getElementById('edit_category_id').value = productData.category_id || '';
            document.getElementById('edit_inStock').checked = productData.inStock == 1;
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }
        // Delete product functionality (old logic)
        function deleteProduct(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${id}">
                    <input type="hidden" name="current_tab" value="products">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        // Delete category functionality (old logic)
        function deleteCategory(id, name) {
            if (confirm(`Are you sure you want to delete category "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${id}">
                    <input type="hidden" name="current_tab" value="categories">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        // Update order status functionality with modal confirmation
        function updateOrderStatus(orderId, status) {
            const statusNames = {
                'processing': 'Processing',
                'shipped': 'Shipped',
                'delivered': 'Delivered',
                'cancelled': 'Cancelled'
            };

            const statusIcons = {
                'processing': 'fas fa-cog',
                'shipped': 'fas fa-shipping-fast',
                'delivered': 'fas fa-check-circle',
                'cancelled': 'fas fa-times-circle'
            };

            const statusColors = {
                'processing': 'info',
                'shipped': 'primary',
                'delivered': 'success',
                'cancelled': 'danger'
            };

            // Create confirmation modal
            const confirmModal = document.createElement('div');
            confirmModal.className = 'modal fade';
            confirmModal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="${statusIcons[status]} me-2 text-${statusColors[status]}"></i>
                                Update Order Status
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="${statusIcons[status]} fa-3x text-${statusColors[status]}"></i>
                                </div>
                                <h4>Update Order Status?</h4>
                                <p class="mb-0">Are you sure you want to update order</p>
                                <p class="fw-bold">#${String(orderId).padStart(6, '0')}</p>
                                <p>to <span class="badge bg-${statusColors[status]}">${statusNames[status]}</span>?</p>
                            </div>
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-${statusColors[status]}" onclick="confirmStatusUpdate(${orderId}, '${status}')">
                                <i class="${statusIcons[status]} me-2"></i>Update Status
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(confirmModal);
            const modal = new bootstrap.Modal(confirmModal);
            modal.show();

            // Clean up modal when it's closed
            confirmModal.addEventListener('hidden.bs.modal', function () {
                confirmModal.remove();
            });
        }

        function confirmStatusUpdate(orderId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" value="${orderId}">
                <input type="hidden" name="status" value="${status}">
                <input type="hidden" name="current_tab" value="orders">
            `;
            document.body.appendChild(form);
            
            // Create success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
            alert.style.zIndex = '2000';
            alert.style.minWidth = '300px';
            alert.style.opacity = '0';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Order ${status} successfully
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '1';
            }, 10);
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => { alert.remove(); }, 500);
            }, 2500);
            
            form.submit();
        }
        // View order details functionality
        function viewOrderDetails(orderId) {
            // Create a modal to show order details
            const orderModal = document.createElement('div');
            orderModal.className = 'modal fade';
            orderModal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-receipt me-2"></i>
                                Order Details #${String(orderId).padStart(6, '0')}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading order details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(orderModal);
            const modal = new bootstrap.Modal(orderModal);
            modal.show();
            
            // Fetch order details
            const formData = new FormData();
            formData.append('action', 'get_order_details');
            formData.append('order_id', orderId);

            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalBody = orderModal.querySelector('.modal-body');
                        const orderDate = new Date(data.order.created_at).toLocaleString();
                        
                        modalBody.innerHTML = `
                            <div class="order-details">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Customer:</strong> ${data.order.Fname} ${data.order.Lname}</p>
                                        <p><strong>Contact:</strong> ${data.order.Mobile}</p>
                                        <p><strong>Address:</strong> ${data.order.Building}, Block ${data.order.Block}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Order Date:</strong> ${orderDate}</p>
                                        <p><strong>Payment Method:</strong> 
                                            <span class="badge bg-info">${data.order.payment_method}</span>
                                        </p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-${getStatusColor(data.order.status)}">${data.order.status}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="table-responsive mb-4">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.items.map(item => `
                                                <tr>
                                                    <td>${item.name}</td>
                                                    <td>BHD ${Number(item.price).toFixed(3)}</td>
                                                    <td>${item.quantity}</td>
                                                    <td>BHD ${(item.price * item.quantity).toFixed(3)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 ms-auto">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Subtotal:</strong></td>
                                                <td class="text-end">BHD ${Number(data.order.subtotal).toFixed(3)}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tax:</strong></td>
                                                <td class="text-end">BHD ${Number(data.order.tax_amount).toFixed(3)}</td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td><strong>Total:</strong></td>
                                                <td class="text-end"><strong>BHD ${Number(data.order.total).toFixed(3)}</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        const modalBody = orderModal.querySelector('.modal-body');
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${data.message || 'Failed to load order details'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const modalBody = orderModal.querySelector('.modal-body');
                    modalBody.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            An error occurred while loading order details.
                        </div>
                    `;
                });

            // Clean up modal when it's closed
            orderModal.addEventListener('hidden.bs.modal', function () {
                orderModal.remove();
            });
        }

        function getStatusColor(status) {
            const statusColors = {
                'pending': 'warning',
                'processing': 'info',
                'shipped': 'primary',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            return statusColors[status] || 'secondary';
        }
        
        // View message functionality
        function viewMessage(messageId) {
            // Create a modal to show message details
            const messageModal = document.createElement('div');
            messageModal.className = 'modal fade';
            messageModal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Message Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="loading text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading message...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(messageModal);
            const modal = new bootstrap.Modal(messageModal);
            modal.show();
            
            // Fetch message details via AJAX
            fetch(`admin_dashboard.php?action=get_message&id=${messageId}`)
                .then(response => response.json())
                .then(data => {
                    const modalBody = messageModal.querySelector('.modal-body');
                    if (data.error) {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${data.error}
                            </div>
                        `;
                    } else {
                        const messageDate = new Date(data.created_at).toLocaleString();
                        modalBody.innerHTML = `
                            <div class="message-details">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>From:</strong> ${data.name}</p>
                                        <p><strong>Email:</strong> ${data.email}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date:</strong> ${messageDate}</p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-success">Read</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <p><strong>Subject:</strong></p>
                                    <p class="text-primary fw-bold">${data.subject}</p>
                                </div>
                                <div class="mb-3">
                                    <p><strong>Message:</strong></p>
                                    <div class="message-content p-3 bg-light rounded">
                                        <p style="white-space: pre-wrap;">${data.message}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    const modalBody = messageModal.querySelector('.modal-body');
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error loading message: ${error.message}
                        </div>
                    `;
                });
            
            // Remove modal after it's hidden
            messageModal.addEventListener('hidden.bs.modal', () => {
                messageModal.remove();
            });
        }
        
        // Mark message as read functionality
        function markMessageAsRead(messageId) {
            const confirmModal = document.createElement('div');
            confirmModal.className = 'modal fade';
            confirmModal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                Mark Message as Read
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-envelope-open fa-3x text-success"></i>
                                </div>
                                <h4>Mark Message as Read?</h4>
                                <p>This message will be marked as read in your inbox.</p>
                            </div>
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-success" onclick="confirmMarkAsRead(${messageId})">
                                <i class="fas fa-check me-2"></i>Mark as Read
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(confirmModal);
            const modal = new bootstrap.Modal(confirmModal);
            modal.show();

            confirmModal.addEventListener('hidden.bs.modal', function () {
                confirmModal.remove();
            });
        }

        function confirmMarkAsRead(messageId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="mark_message_read">
                <input type="hidden" name="message_id" value="${messageId}">
                <input type="hidden" name="current_tab" value="messages">
            `;
            document.body.appendChild(form);
            
            // Create success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
            alert.style.zIndex = '2000';
            alert.style.minWidth = '300px';
            alert.style.opacity = '0';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Message marked as read successfully
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '1';
            }, 10);
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => { alert.remove(); }, 500);
            }, 2500);
            
            form.submit();
        }
        
        // Delete message functionality
        function deleteMessage(messageId) {
            const confirmModal = document.createElement('div');
            confirmModal.className = 'modal fade';
            confirmModal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-trash me-2 text-danger"></i>
                                Delete Message
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                                </div>
                                <h4>Delete Message?</h4>
                                <p>Are you sure you want to delete this message?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteMessage(${messageId})">
                                <i class="fas fa-trash me-2"></i>Delete Message
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(confirmModal);
            const modal = new bootstrap.Modal(confirmModal);
            modal.show();

            confirmModal.addEventListener('hidden.bs.modal', function () {
                confirmModal.remove();
            });
        }

        function confirmDeleteMessage(messageId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="message_id" value="${messageId}">
                <input type="hidden" name="current_tab" value="messages">
            `;
            document.body.appendChild(form);
            
            // Create success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
            alert.style.zIndex = '2000';
            alert.style.minWidth = '300px';
            alert.style.opacity = '0';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Message deleted successfully
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '1';
            }, 10);
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => { alert.remove(); }, 500);
            }, 2500);
            
            form.submit();
        }
        // Image preview functionality (old logic)
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        // Initialize on page load (old logic + new animations)
        document.addEventListener('DOMContentLoaded', function() {
            // Tab from URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam && tabParam !== 'dashboard') {
                document.querySelectorAll('.tab-content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetContent = document.getElementById(tabParam + '-content');
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                const activeLink = document.querySelector(`[data-tab="${tabParam}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            } else {
                document.getElementById('dashboard-content').style.display = 'block';
            }
            // Add edit product event listeners
            document.querySelectorAll('.edit-product-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productData = JSON.parse(this.getAttribute('data-product'));
                    editProduct(productData);
                });
            });
            // Add image preview listeners
            const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
            imageInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const previewId = this.getAttribute('data-preview');
                    if (previewId) {
                        previewImage(this, previewId);
                    }
                });
            });
            // Auto-hide alerts after 5 seconds (old logic, but with fade)
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // --- Add animation for Add/Update Product ---
        function showAnimatedAlert(message, type = 'success') {
            // Remove any existing alert
            const oldAlert = document.getElementById('animated-alert');
            if (oldAlert) oldAlert.remove();
            // Create alert
            const alert = document.createElement('div');
            alert.id = 'animated-alert';
            alert.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 fade show`;
            alert.style.zIndex = 2000;
            alert.style.minWidth = '300px';
            alert.style.opacity = '0';
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(alert);
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '1';
            }, 10);
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => { alert.remove(); }, 500);
            }, 2500);
        }

        // Intercept Add/Update Product form submit for animation
        document.addEventListener('DOMContentLoaded', function() {
            // Add Product
            const addProductForm = document.querySelector('#addProductModal form');
            if (addProductForm) {
                addProductForm.addEventListener('submit', function(e) {
                    const submitBtn = addProductForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                    // Fade out modal after short delay
                    setTimeout(() => {
                        const modalEl = document.getElementById('addProductModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        showAnimatedAlert('Product added successfully!', 'success');
                    }, 600);
                    // Let the form submit as normal (PHP will redirect)
                });
            }
            // Update Product
            const editProductForm = document.querySelector('#editProductModal form');
            if (editProductForm) {
                editProductForm.addEventListener('submit', function(e) {
                    const submitBtn = editProductForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                    // Fade out modal after short delay
                    setTimeout(() => {
                        const modalEl = document.getElementById('editProductModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        showAnimatedAlert('Product updated successfully!', 'success');
                    }, 600);
                    // Let the form submit as normal (PHP will redirect)
                });
            }
        });
    </script>
</body>
</html>