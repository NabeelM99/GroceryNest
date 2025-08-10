<?php
session_start();
require_once 'project_connection.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['userId'])) {
    header("Location: login_form.php");
    exit;
}

$user_id = $_SESSION['userId'];
$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $building = trim($_POST['building'] ?? '');
    $block = trim($_POST['block'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($firstName)) {
        $errors[] = "First name is required";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    }
    
    // Handle profile picture upload
    $profilePicName = null;
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileInfo = pathinfo($_FILES['profilePic']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed";
        }
        
        // Validate file size (5MB max)
        if ($_FILES['profilePic']['size'] > 5 * 1024 * 1024) {
            $errors[] = "File size must be less than 5MB";
        }
        
        if (empty($errors)) {
            // Generate unique filename
            $profilePicName = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $profilePicName;
            
            if (!move_uploaded_file($_FILES['profilePic']['tmp_name'], $targetPath)) {
                $errors[] = "Failed to upload profile picture";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Check if customer record exists
            $stmt = $conn->prepare("SELECT id, Profile_pic FROM customer WHERE UID = ?");
            $stmt->execute([$user_id]);
            $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingCustomer) {
                // Update existing customer record
                $updateQuery = "UPDATE customer SET Fname = ?, Lname = ?, Mobile = ?, Building = ?, Block = ?";
                $params = [$firstName, $lastName, $mobile, $building, $block];
                
                if ($profilePicName) {
                    $updateQuery .= ", Profile_pic = ?";
                    $params[] = $profilePicName;
                    
                    // Delete old profile picture if it exists and is not default
                    if ($existingCustomer['Profile_pic'] && 
                        $existingCustomer['Profile_pic'] !== 'default.jpg' && 
                        file_exists('uploads/profiles/' . $existingCustomer['Profile_pic'])) {
                        unlink('uploads/profiles/' . $existingCustomer['Profile_pic']);
                    }
                }
                
                $updateQuery .= " WHERE UID = ?";
                $params[] = $user_id;
                
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute($params);
            } else {
                // Insert new customer record
                $stmt = $conn->prepare("INSERT INTO customer (Fname, Lname, Mobile, Building, Block, UID, Profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $mobile, $building, $block, $user_id, $profilePicName ?? 'default.jpg']);
            }
            
            $conn->commit();
            $message = "Profile updated successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = "danger";
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT u.Username, u.Email, u.Type, c.Fname, c.Lname, c.Mobile, c.Building, c.Block, c.Profile_pic 
                        FROM user u 
                        LEFT JOIN customer c ON u.id = c.UID 
                        WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login_form.php");
    exit;
}

$firstName = $user['Fname'] ?? '';
$lastName = $user['Lname'] ?? '';
$mobile = $user['Mobile'] ?? '';
$building = $user['Building'] ?? '';
$block = $user['Block'] ?? '';
$profilePic = $user['Profile_pic'] ?? 'default.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - GroceryNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ensure consistent box-sizing */
        * {
            box-sizing: border-box;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-pic-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
        }
        
        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .profile-pic-placeholder {
            width: 100px;
            height: 100px;
            background: linear-gradient(to right, #c4ea95, #d9fdd3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .pic-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        
        .pic-upload-btn:hover {
            background: #c4ea95;
        }
        
        .form-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            /* Prevent any movement or animation */
            transform: none !important;
            transition: none !important;
        }
        
        /* Remove any Bootstrap card hover effects */
        .form-card:hover {
            transform: none !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08) !important;
        }
        
        .form-header {
            background: linear-gradient(to right, #c4ea95, #d9fdd3);
            color:rgb(81, 145, 81);
            padding: 1.5rem;
            text-align: center;
        }

        .mb-0{
            font-weight: bold;
        }
        
        .form-body {
            padding: 1.5rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 10px 14px;
            /* Only transition specific properties to prevent movement */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
            outline: none;
        }
        
        .form-control:hover {
            border-color: #10b981;
            /* No box-shadow changes on hover to prevent visual movement */
        }
        
        .btn-save {
            background: linear-gradient(to right, #c4ea95, #d9fdd3);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            color: #10b981;
            transition: background-color 0.3s ease;
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .btn-cancel {
            background: #6b7280;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #4b5563;
        }
        
        #profilePicInput {
            display: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .compact-section {
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Prevent any card animations */
        .card {
            transform: none !important;
            transition: none !important;
        }
        
        @media (max-width: 768px) {
            .form-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container py-5 mt-5 pt-5">
    <div class="profile-container">
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card form-card">
            <div class="form-header">
                <h3 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit Profile
                </h3>
                <p class="mb-0 opacity-75">Update your personal information</p>
            </div>
            
            <div class="form-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left Column - Profile Picture & Personal Info -->
                        <div class="col-lg-8">
                            <div class="row">
                                <!-- Profile Picture Section -->
                                <div class="col-md-3">
                                    <div class="compact-section">
                                        <div class="profile-pic-container">
                                            <?php if ($profilePic && $profilePic !== 'default.jpg' && file_exists("uploads/profiles/$profilePic")): ?>
                                                <img src="uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                                                     alt="Profile" class="profile-pic-preview" id="profilePreview">
                                            <?php else: ?>
                                                <div class="profile-pic-placeholder" id="profilePreview">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <button type="button" class="pic-upload-btn" onclick="document.getElementById('profilePicInput').click()">
                                                <i class="fas fa-camera"></i>
                                            </button>
                                        </div>
                                        <input type="file" id="profilePicInput" name="profilePic" accept="image/*">
                                        <p class="text-muted small text-center mb-0">Click camera to change</p>
                                    </div>
                                </div>

                                <!-- Personal Information -->
                                <div class="col-md-9">
                                    <div class="compact-section">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="firstName" class="form-label">
                                                    <i class="fas fa-user me-1"></i>First Name *
                                                </label>
                                                <input type="text" class="form-control" id="firstName" name="firstName" 
                                                       value="<?= htmlspecialchars($firstName) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lastName" class="form-label">
                                                    <i class="fas fa-user me-1"></i>Last Name *
                                                </label>
                                                <input type="text" class="form-control" id="lastName" name="lastName" 
                                                       value="<?= htmlspecialchars($lastName) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="mobile" class="form-label">
                                                    <i class="fas fa-phone me-1"></i>Mobile Number
                                                </label>
                                                <input type="tel" class="form-control" id="mobile" name="mobile" 
                                                       value="<?= htmlspecialchars($mobile) ?>" placeholder="+973-1234-5678">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="building" class="form-label">
                                                    <i class="fas fa-building me-1"></i>Building
                                                </label>
                                                <input type="text" class="form-control" id="building" name="building" 
                                                       value="<?= htmlspecialchars($building) ?>" placeholder="Building A">
                                            </div>
                                            <div class="col-12">
                                                <label for="block" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Block/Area
                                                </label>
                                                <input type="text" class="form-control" id="block" name="block" 
                                                       value="<?= htmlspecialchars($block) ?>" placeholder="Block 1, Manama">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Account Info & Actions -->
                        <div class="col-lg-4">
                            <!-- Account Information (Read-only) -->
                            <div class="compact-section">
                                <h6 class="mb-2">
                                    <i class="fas fa-info-circle me-2"></i>Account Information
                                </h6>
                                <div class="mb-2">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['Username']) ?>" readonly>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['Email']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Account Type</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['Type']) ?>" readonly>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>
                                    Username and email cannot be changed. Contact support if needed.
                                </small>
                            </div>

                            <!-- Form Actions -->
                            <div class="compact-section mt-4">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-save">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <a href="profile.php" class="btn btn-secondary btn-cancel">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
// Profile picture preview
document.getElementById('profilePicInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profilePreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview" class="profile-pic-preview">`;
        };
        reader.readAsDataURL(file);
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    
    if (!firstName || !lastName) {
        e.preventDefault();
        alert('First name and last name are required!');
        return false;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('.btn-save');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    submitBtn.disabled = true;
    
    // Re-enable after a delay to prevent double submission
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>
</body>
</html>