<?php
session_start();
require_once 'project_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: index.php?contact=error&message=" . urlencode("Please login to send a message") . "#contact");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            throw new Exception("All fields are required");
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Insert message into database
        try {
            $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), 0)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            // Redirect back with success message
            header("Location: index.php?contact=success#contact");
            exit;
        } catch (PDOException $e) {
            throw new Exception("Contact system not available yet. Please contact the administrator.");
        }
        
    } catch (Exception $e) {
        // Redirect back with error message
        header("Location: index.php?contact=error&message=" . urlencode($e->getMessage()) . "#contact");
        exit;
    }
} else {
    // If not POST request, redirect to home
    header("Location: index.php");
    exit;
}
?> 