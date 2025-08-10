<?php
session_start();
require_once 'project_connection.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    echo "<p>✅ Database connection successful</p>";
    
    // Test if tables exist
    $tables = ['user', 'customer', 'categories', 'products', 'cart', 'cart_items', 'wishlist', 'orders', 'order_items'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' does NOT exist</p>";
        }
    }
    
    // Test sample data
    echo "<h3>Sample Data Check:</h3>";
    
    // Check users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user");
    $stmt->execute();
    $userCount = $stmt->fetch()['count'];
    echo "<p>Users: $userCount</p>";
    
    // Check products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $productCount = $stmt->fetch()['count'];
    echo "<p>Products: $productCount</p>";
    
    // Check if customer has cart
    $stmt = $conn->prepare("SELECT c.id FROM cart c JOIN user u ON c.user_id = u.id WHERE u.Username = 'customer'");
    $stmt->execute();
    $cart = $stmt->fetch();
    if ($cart) {
        echo "<p>✅ Customer has cart (ID: {$cart['id']})</p>";
    } else {
        echo "<p>❌ Customer does NOT have cart</p>";
    }
    
    // Test cart functionality
    echo "<h3>Cart Functionality Test:</h3>";
    
    // Simulate adding product to cart
    $user_id = 2; // customer user
    $product_id = 1; // first product
    
    // Get or create cart
    $cartStmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cart = $cartStmt->fetch();
    
    if (!$cart) {
        echo "<p>❌ No cart found for user $user_id</p>";
        // Create cart
        $createCartStmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $createCartStmt->execute([$user_id]);
        echo "<p>✅ Created cart for user $user_id</p>";
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $cart['id'];
        echo "<p>✅ Found existing cart (ID: $cart_id)</p>";
    }
    
    // Test adding item to cart
    try {
        $insertStmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)");
        $insertStmt->execute([$cart_id, $product_id]);
        echo "<p>✅ Successfully added product $product_id to cart</p>";
        
        // Clean up test data
        $deleteStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $deleteStmt->execute([$cart_id, $product_id]);
        echo "<p>✅ Cleaned up test data</p>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error adding to cart: " . $e->getMessage() . "</p>";
    }
    
    // Test wishlist functionality
    echo "<h3>Wishlist Functionality Test:</h3>";
    
    try {
        $insertStmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $insertStmt->execute([$user_id, $product_id]);
        echo "<p>✅ Successfully added product $product_id to wishlist</p>";
        
        // Clean up test data
        $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $deleteStmt->execute([$user_id, $product_id]);
        echo "<p>✅ Cleaned up test data</p>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error adding to wishlist: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>✅ All tests completed!</h3>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?> 