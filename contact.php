<?php
session_start();
require_once 'project_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login_form.php");
    exit;
}

// Fetch current user data to get email and name
$stmt = $conn->prepare("SELECT u.Username, u.Email, u.Type, c.Fname, c.Lname 
                        FROM user u 
                        LEFT JOIN customer c ON u.id = c.UID 
                        WHERE u.id = ?");
$stmt->execute([$_SESSION['userId']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login_form.php");
    exit;
}

// Pre-fill name field - combine first and last name if available, otherwise use username
$userFullName = '';
if (!empty($user['Fname']) && !empty($user['Lname'])) {
    $userFullName = $user['Fname'] . ' ' . $user['Lname'];
} else {
    $userFullName = $user['Username'];
}

$userEmail = $user['Email'];

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
            
            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Your message has been sent successfully!'
            ];
            
            header("Location: contact.php");
            exit;
        } catch (PDOException $e) {
            throw new Exception("Contact system not available yet. Please try again later.");
        }
        
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        header("Location: contact.php");
        exit;
    }
}


// Get any flash messages and clear them
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Get any form errors and old input
$formErrors = $_SESSION['form_errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - GroceryNest</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/navbar.css">
    
    <style>
        :root {
            --primary: #10b981;
            --primary-light: #d1fae5;
            --dark: #1f2937;
            --light: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #c4ea95 0%, #295f1b 100%);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding-top: 76px;
        }

        .container.py-4 {
            margin: 90px 0 0 0;
            border-radius: 20px;
            padding-top: 0 !important;
            padding-bottom: 2rem !important;
        }
        
        .contact-header {
            background: var(--gradient-primary);
            border-radius: 12px; 
            padding: 1.2rem 1.5rem;
            margin-bottom: 0.5rem; 
            color: white;
            box-shadow: var(--shadow-large);
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .row.g-4 {
            margin-top: 0.5rem !important;
        }

        .alert {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .contact-title {
            font-size: 1.8rem; 
            font-weight: 700;
            margin-bottom: 0.5rem; 
            display: flex;
            align-items: center;
            line-height: 1.2;
        }

        .contact-title i {
            margin-right: 1rem; 
            font-size: 1.8rem; 
        }

        .contact-subtitle {
            font-size: 1rem; 
            opacity: 0.9;
            margin-bottom: 0;
            line-height: 1.5;
        }
        
        .contact-icon {
            width: 42px;
            height: 42px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--primary);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .contact-item:hover .contact-icon {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        
        .form-control, .form-select {
            border: 1px solid rgba(0,0,0,0.1);
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(196, 234, 149, 0.25);
            border-color: #c4ea95;
        }
        
        /* Style for readonly email field */
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
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .alert {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="contact-header" data-aos="fade-up">
            <div class="d-flex align-items-center">
                <div class="contact-title">
                    <i class="fas fa-headset"></i>
                    Contact Support
                </div>
            </div>
            <div class="contact-subtitle">
                We're here to help! Send us a message and we'll get back to you as soon as possible.
            </div>
        </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?= $_SESSION['alert']['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
        <i class="fas fa-<?= $_SESSION['alert']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($_SESSION['alert']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['alert']); endif; ?>

        <div class="row g-4">
            <!-- Contact Form Section -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Send us a Message</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="contact.php" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($userFullName) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($userEmail) ?>" readonly required>
                                </div>

                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="" disabled selected>Select a subject</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Order Support">Order Support</option>
                                        <option value="Delivery Issue">Delivery Issue</option>
                                        <option value="Product Question">Product Question</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4 d-flex flex-column">
                <!-- Quick Contact -->
                <div class="card mb-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Quick Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="contact-icon me-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Phone</h6>
                                <p class="mb-0 text-muted">+973-33234554</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            <div class="contact-icon me-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Email</h6>
                                <p class="mb-0 text-muted">support@freshnest.com</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <div class="contact-icon me-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Address</h6>
                                <p class="mb-0 text-muted">Road No.: 2045<br>Block No.: 123<br>Manama Souq, Bahrain</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store Hours -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Store Hours</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Monday - Friday</span>
                                <span>7:00 AM - 10:00 PM</span>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Saturday</span>
                                <span>8:00 AM - 11:00 PM</span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Sunday</span>
                                <span>9:00 AM - 9:00 PM</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Form validation
        const forms = document.querySelectorAll('form[novalidate]');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>