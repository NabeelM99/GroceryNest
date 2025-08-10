<?php
// test_login.php - Use this to debug your login issues
include 'project_connection.php';

// Test with your actual credentials
$test_email = "your_email@example.com"; // Replace with your actual email
$test_password = "Waleed12"; // Replace with your actual password

echo "<h2>Login Debug Test</h2>";

try {
    // Check what's in the database
    $stmt = $conn->prepare("SELECT id, Username, Email, Password, Type FROM user WHERE Email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p><strong>User found in database:</strong></p>";
        echo "<p>ID: " . $user['id'] . "</p>";
        echo "<p>Username: " . $user['Username'] . "</p>";
        echo "<p>Email: " . $user['Email'] . "</p>";
        echo "<p>Type: " . $user['Type'] . "</p>";
        echo "<p>Hashed Password: " . substr($user['Password'], 0, 20) . "...</p>";
        
        // Test password verification
        if (password_verify($test_password, $user['Password'])) {
            echo "<p style='color: green;'><strong>✓ Password verification SUCCESSFUL!</strong></p>";
            echo "<p>Your login should work. The issue might be elsewhere.</p>";
        } else {
            echo "<p style='color: red;'><strong>✗ Password verification FAILED!</strong></p>";
            echo "<p>The password you're trying doesn't match the hashed password in the database.</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>No user found with that email.</strong></p>";
        echo "<p>Make sure you're using the correct email address.</p>";
    }
    
    // Show all users in the database
    echo "<h3>All users in database:</h3>";
    $allUsers = $conn->query("SELECT id, Username, Email, Type FROM user");
    while ($row = $allUsers->fetch()) {
        echo "<p>ID: {$row['id']}, Username: {$row['Username']}, Email: {$row['Email']}, Type: {$row['Type']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>