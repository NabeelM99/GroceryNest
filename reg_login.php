<?php
session_start();
require_once 'project_connection.php';

function redirectWithError($url, $errorCode) {
    header("Location: $url?error=$errorCode");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN
    if (isset($_POST['login_user'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Server-side validation
        if (empty($username) || empty($password)) {
            redirectWithError('login_form.php', 1); // Missing fields
        }

        $stmt = $conn->prepare("SELECT id, Username, Password, Type FROM user WHERE Username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['activeUser'] = $user['Username'];
            $_SESSION['userId'] = $user['id'];
            $_SESSION['userType'] = $user['Type'];
            header("Location: index.php");
            exit;
        } else {
            redirectWithError('login_form.php', 2); // Invalid credentials
        }
    }


    
    // REGISTRATION
    if (isset($_POST['register_user'])) {
        // Collect and sanitize
        $name = trim($_POST['name'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $mail = trim($_POST['mail'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $cnfm_password = $_POST['cnfm_password'] ?? '';

        // Patterns
        $namePattern = '/^([a-z]+\s)*[a-z]+$/i';
        $mailPattern = '/^[a-zA-Z0-9._-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z.]{2,5}$/';
        $unamePattern = '/^[a-z0-9]\w{4,19}$/i';
        $pwdPattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/';

        // First check for empty required fields
        if (empty($name) || empty($lname) || empty($mail) || 
            empty($username) || empty($password) || empty($cnfm_password)) {
            redirectWithError('registration_form.php', 1); // Missing required fields
        }

        // Country-specific mobile
        /*if ($country_code == '+973') {
            $mobilePattern = '/^(32|33|34|35|36|37|38|39)[0-9]{6}$/';
            $country = "Bahrain";
        } elseif ($country_code == '+966') {
            $mobilePattern = '/^(54|56|57|58|59)[0-9]{6,8}$/';
            $country = "Saudi Arabia";
        } elseif ($country_code == '+971') {
            $mobilePattern = '/^(50|52|54|55|56|58)[0-9]{6,8}$/';
            $country = "United Arab Emirates";
        } else {
            $mobilePattern = '/^[0-9]{8,12}$/';
            $country = "Other";
        }*/

        // Validation
        $valid = true;
        $errors = [];

        if (!preg_match($namePattern, $name)) {
            $valid = false;
            $errors[] = "Invalid first name";
        }
        if (!preg_match($namePattern, $lname)) {
            $valid = false;
            $errors[] = "Invalid last name";
        }
        if (!preg_match($mailPattern, $mail)) {
            $valid = false;
            $errors[] = "Invalid email format";
        }
        if (!preg_match($unamePattern, $username)) {
            $valid = false;
            $errors[] = "Invalid username format";
        }
        if (!preg_match($pwdPattern, $password)) {
            $valid = false;
            $errors[] = "Password must be at least 6 characters with 1 uppercase, 1 lowercase, and 1 digit";
        }
        if ($password !== $cnfm_password) {
            $valid = false;
            $errors[] = "Passwords do not match";
        }
        //if (!preg_match($mobilePattern, $mobile)) $valid = false;
        //if (!preg_match($addrPattern, $address)) $valid = false;

        if (!$valid) {
            // Log the specific validation errors for debugging
            error_log("Validation errors: " . implode(", ", $errors));
            redirectWithError('registration_form.php', 2); // Validation failed
        }

        // Check if username or email exists
        $checkStmt = $conn->prepare("SELECT id FROM user WHERE Username = ? OR Email = ?");
        $checkStmt->execute([$username, $mail]);
        if ($checkStmt->fetch()) {
            redirectWithError('registration_form.php', 3); // User or email exists
        }


        // Insert user
         try {
            $conn->beginTransaction();

            $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
            $profilePic = 'default.jpg';

            // Insert into user table
            $userInsert = $conn->prepare("INSERT INTO user (Username, Email, Password, Type) VALUES (?, ?, ?, 'Customer')");
            $userInsert->execute([$username, $mail, $hashedPwd]);
            $userId = $conn->lastInsertId();

            // Insert into customer table
            $customerInsert = $conn->prepare("INSERT INTO customer (Fname, Lname, UID, Profile_pic) VALUES (?, ?, ?, ?)");
            $customerInsert->execute([$name, $lname, $userId, $profilePic]);

            $conn->commit();

            $_SESSION['activeUser'] = $username;
            $_SESSION['userId'] = $userId;
            $_SESSION['userType'] = 'Customer';
            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Registration error: " . $e->getMessage());
            redirectWithError('registration_form.php', 4); // Database error
        }
    }
}
echo "Invalid request.";
?>