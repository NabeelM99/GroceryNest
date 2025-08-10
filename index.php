<?php
session_start();

// Redirect admin users to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    header("Location: admin_dashboard.php");
    exit;
}

require_once 'project_connection.php';

// Fetch a small curated set for Featured Products (customize selection as needed)
try {
    $featuredStmt = $conn->prepare(
        "SELECT p.id, p.name, p.price, p.original_price, p.image, p.inStock, c.name AS category
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.inStock = 1
         ORDER BY RAND()
         LIMIT 6"
    );
    $featuredStmt->execute();
    $featuredProducts = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredProducts = [];
}

function formatPrice($price) { return number_format($price, 3); }
function getDiscountPercentage($original, $current) {
    if ($original > $current) { return round((($original - $current) / $original) * 100); }
    return 0;
}
?>
<!DOCTYPE html>
<html lang="en">


<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="floating-element">
            <i class="fas fa-apple-alt fa-3x" style="color: rgba(255, 255, 255, 0.2);"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-carrot fa-2x" style="color: rgba(255, 255, 255, 0.2);"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-bread-slice fa-2x" style="color: rgba(255, 255, 255, 0.2);"></i>
        </div>
        
        <div class="hero-content">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 text-white" data-aos="fade-right">
                        <h1 class="hero-title">Fresh Groceries Delivered in Minutes</h1>
                        <p class="hero-subtitle">Shop from thousands of premium products and get them delivered to your door in 30 minutes or less.</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="products.php" class="btn btn-light btn-lg px-4 py-3 btn-glow">
                                <i class="fas fa-shopping-bag me-2"></i>Shop Now
                            </a>
                            <a href="#features" class="btn btn-outline-light btn-lg px-4 py-3">
                                <i class="fas fa-play-circle me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left">
                        <div class="image-carousel">
                            <div class="carousel-slide active">
                                <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80" alt="Fresh groceries">
                                <div class="carousel-overlay"></div>
                                <div class="carousel-badge">🔥 30% OFF</div>
                            </div>
                            <div class="carousel-slide">
                                <img src="https://images.unsplash.com/photo-1566385101042-1a0aa0c1268c?auto=format&fit=crop&w=800&q=80" alt="Fresh vegetables">
                                <div class="carousel-overlay"></div>
                                <div class="carousel-badge">🥕 Fresh Daily</div>
                            </div>
                            <div class="carousel-slide">
                                <img src="https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=800&q=80" alt="Fresh fruits">
                                <div class="carousel-overlay"></div>
                                <div class="carousel-badge">🍎 Organic</div>
                            </div>
                            <div class="carousel-slide">
                                <img src="https://plus.unsplash.com/premium_photo-1723874465750-870e02eca9d4?auto=format&fit=crop&w=800&q=80" alt="Dairy products">
                                <div class="carousel-overlay"></div>
                                <div class="carousel-badge">🥛 Farm Fresh</div>
                            </div>
                            <div class="carousel-slide">
                                <img src="https://images.unsplash.com/photo-1637059396175-47c6f3f77f55?auto=format&fit=crop&w=800&q=80" alt="Bakery items">
                                <div class="carousel-overlay"></div>
                                <div class="carousel-badge">🍞 Baked Today</div>
                            </div>
                            
                            <div class="carousel-indicators">
                                <div class="carousel-indicator active" data-slide="0"></div>
                                <div class="carousel-indicator" data-slide="1"></div>
                                <div class="carousel-indicator" data-slide="2"></div>
                                <div class="carousel-indicator" data-slide="3"></div>
                                <div class="carousel-indicator" data-slide="4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>










    <!-- Featured Categories -->
    <section class="section-lg bg-light">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up" data-aos-delay="100" data-aos-once="false">
                <h2 class="section-title">Featured Categories</h2>
                <p class="section-subtitle">Browse products by category</p>
                
                <!-- Carousel Controls -->
                <div class="carousel-controls">
                    <button class="carousel-btn" id="prevBtn" onclick="moveCarousel('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn" id="nextBtn" onclick="moveCarousel('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Horizontal Scrolling Container -->
            <div class="carousel-wrapper" style="overflow: hidden; position: relative;" data-aos="fade-up" data-aos-delay="100">
                <div class="carousel-container d-flex flex-nowrap" id="categoryCarousel" style="gap: 20px; transition: transform 0.5s ease-in-out;">
                    <!-- Original cards -->
                    <a href="products.php?category=produce" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=300&q=80" 
                                 alt="Fruits & Vegetables" 
                                 class="category-image" />
                            <h5 class="mb-0">Fruits & Vegetables</h5>
                        </div>
                    </a>
                    
                    <a href="products.php?category=dairy" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=300&q=80" 
                                 alt="Dairy, Bread & Eggs" 
                                 class="category-image" />
                            <h5 class="mb-0">Dairy, Bread & Eggs</h5>
                        </div>
                    </a>
                    
                    <a href="products.php?category=snacks" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=300&q=80" 
                                 alt="Snack & Munchies" 
                                 class="category-image" />
                            <h5 class="mb-0">Snack & Munchies</h5>
                        </div>
                    </a>
                    
                    <a href="products.php?category=bakery" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&w=300&q=80" 
                                 alt="Bakery & Biscuits" 
                                 class="category-image" />
                            <h5 class="mb-0">Bakery & Biscuits</h5>
                        </div>
                    </a>
                    
                    <a href="products.php?category=beverages" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=300&q=80" 
                                 alt="Tea, Coffee & Drinks" 
                                 class="category-image" />
                            <h5 class="mb-0">Tea, Coffee & Drinks</h5>
                        </div>
                    </a>
                    
                    <a href="products.php?category=staples" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=300&q=80" 
                                 alt="Atta, Rice & Dal" 
                                 class="category-image" />
                            <h5 class="mb-0">Atta, Rice & Dal</h5>
                        </div>
                    </a>

                    <a href="products.php?category=meat" class="category-card text-decoration-none" style="min-width: 200px; flex-shrink: 0;">
                        <div class="card-content text-center p-4 rounded-3 bg-white h-100">
                            <img src="https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=300&q=80" 
                                 alt="Chicken, Meat & Fish" 
                                 class="category-image" />
                            <h5 class="mb-0">Chicken, Meat & Fish</h5>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>










    <!-- Features Section -->
    <section id="features" class="section bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Why Choose FreshCart?</h2>
                <p class="section-subtitle">Experience the future of grocery shopping with our premium features</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">Lightning Fast Delivery</h3>
                        <p class="text-muted">Get your groceries delivered in 30 minutes or less with our express delivery service.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-primary">30 min</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">Quality Guaranteed</h3>
                        <p class="text-muted">Premium quality products with 100% freshness guarantee or your money back.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-success">100% Fresh</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">Best Prices</h3>
                        <p class="text-muted">Competitive prices with daily deals, seasonal discounts, and exclusive member offers.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-warning">Best Deals</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">24/7 Support</h3>
                        <p class="text-muted">Round-the-clock customer support to help you with any questions or concerns.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-info">Always Available</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">Eco-Friendly</h3>
                        <p class="text-muted">Sustainable packaging and eco-friendly delivery options for a greener future.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-success">Green Delivery</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card h-100 p-4 text-center rounded-4 shadow-sm">
                        <div class="feature-icon mb-4">
                            <i class="fa-solid fa-desktop"></i>
                        </div>
                        <h3 class="h5 mb-3 fw-bold">Easy Ordering</h3>
                        <p class="text-muted">Simple and intuitive website for seamless shopping experience anywhere, anytime.</p>
                        <div class="feature-stats mt-3">
                            <span class="badge bg-primary">Website First</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>













    <!-- Featured Products (dynamic) -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Featured Products</h2>
                <p class="section-subtitle">Handpicked fresh products just for you</p>
                <a href="products.php" class="btn btn-outline-success btn-sm mt-2">View all</a>
            </div>

            <div class="row g-3 g-md-4" id="featuredGrid">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-6 col-sm-6 col-lg-3 col-xl-2" data-aos="fade-up">
                    <div class="product-card" data-product-id="<?= $product['id'] ?>" style="cursor: pointer;">
                        <div class="product-image-container" onclick="window.location.href='productview.php?id=<?= $product['id'] ?>'">
                            <img src="Images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image" loading="lazy">

                            <button class="favorite-btn" data-product-id="<?= $product['id'] ?>" type="button" style="position:absolute;top:10px;right:10px;z-index:9999;">
                                <i class="far fa-heart"></i>
                            </button>

                            <?php $discountPercent = getDiscountPercentage($product['original_price'], $product['price']); ?>
                            <?php if ($discountPercent > 0): ?>
                                <div class="badge-container">
                                    <div class="discount-badge"><?= $discountPercent ?>% OFF</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($product['category'] ?? '') ?></div>
                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>

                            <div class="product-price">
                                <span class="ah-currency">BHD</span>
                                <span class="price"><?= formatPrice($product['price']) ?></span>
                                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                    <span class="original-price">BHD <?= formatPrice($product['original_price']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="product-bottom-actions">
                                <div class="quantity-controls">
                                    <button class="quantity-btn decrease-qty" data-item-id="<?= $product['id'] ?>" type="button" disabled>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="quantity-input" data-item-id="<?= $product['id'] ?>" value="1" min="1" max="99" readonly>
                                    <button class="quantity-btn increase-qty" data-item-id="<?= $product['id'] ?>" type="button">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>" type="button">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>










    <!-- Contact Section -->
    <section id="contact" class="section bg-white">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div class="section-badge mb-3">
                    <i class="fas fa-envelope me-2"></i>Get in Touch
                </div>
                <h2 class="section-title">Contact Us</h2>
                <p class="section-subtitle">We'd love to hear from you! Send us your queries or feedback.</p>
            </div>
            
            <!-- Contact Form Messages -->
            <?php if (isset($_GET['contact'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        <?php if ($_GET['contact'] === 'success'): ?>
                            showContactToast('Thank you! Your message has been sent successfully. We\'ll get back to you soon!', 'success');
                        <?php elseif ($_GET['contact'] === 'error'): ?>
                            showContactToast('<?= htmlspecialchars($_GET['message'] ?? 'Something went wrong. Please try again.') ?>', 'error');
                        <?php endif; ?>
                    });
                </script>
            <?php endif; ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up">
                    <div class="contact-card p-4 rounded-4 shadow-sm">
                        <?php if (!isset($_SESSION['userId'])): ?>
                            <div class="text-center py-5">
                                <div class="login-required-icon mb-4">
                                    <i class="fas fa-lock fa-3x text-muted"></i>
                                </div>
                                <h4 class="mb-3">Login Required</h4>
                                <p class="text-muted mb-4">Please login to send us a message. This helps us provide better support and track your inquiries.</p>
                                <div class="d-flex gap-3 justify-content-center">
                                    <a href="login_form.php" class="btn btn-primary btn-lg px-4">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Login
                                    </a>
                                    <a href="registration_form.php" class="btn btn-outline-primary btn-lg px-4">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Register
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form action="contact_form.php" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Your Name</label>
                                    <input type="text" name="name" class="form-control form-control-lg" placeholder="Enter your name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Your Email</label>
                                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Enter your email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Subject</label>
                                <select name="subject" class="form-select form-select-lg" required>
                                    <option value="">Select a subject</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Customer Support">Customer Support</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Partnership">Partnership</option>
                                    <option value="Complaint">Complaint</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Your Message</label>
                                <textarea name="message" class="form-control form-control-lg" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 btn-glow">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="row mt-5 g-4">
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-info-card text-center p-4 rounded-4 bg-light">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Our Location</h5>
                        <p class="text-muted mb-0">Manama, Bahrain<br>Central Business District</p>
                    </div>
                </div>
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-info-card text-center p-4 rounded-4 bg-light">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-phone fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Phone Number</h5>
                        <p class="text-muted mb-0">+973 1234 5678<br>+973 9876 5432</p>
                    </div>
                </div>
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-info-card text-center p-4 rounded-4 bg-light">
                        <div class="contact-icon mb-3">
                            <i class="fas fa-envelope fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Email Address</h5>
                        <p class="text-muted mb-0">support@grocerynest.com<br>info@grocerynest.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-white">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand mb-3">
                        <h3 class="fw-bold text-primary">GroceryNest</h3>
                    </div>
                    <p class="mb-3">Premium groceries delivered fresh to your door. Experience the future of grocery shopping with our fast, reliable, and quality service.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="footer-link">Home</a></li>
                        <li class="mb-2"><a href="products.php" class="footer-link">Products</a></li>
                        <li class="mb-2"><a href="#features" class="footer-link">Features</a></li>
                        <li class="mb-2"><a href="#contact" class="footer-link">Contact</a></li>
                        <li class="mb-2"><a href="about.php" class="footer-link">About Us</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="products.php?category=produce" class="footer-link">Fruits & Vegetables</a></li>
                        <li class="mb-2"><a href="products.php?category=dairy" class="footer-link">Dairy & Bread</a></li>
                        <li class="mb-2"><a href="products.php?category=meat" class="footer-link">Meat & Fish</a></li>
                        <li class="mb-2"><a href="products.php?category=beverages" class="footer-link">Beverages</a></li>
                        <li class="mb-2"><a href="products.php?category=snacks" class="footer-link">Snacks</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">Contact Information</h5>
                    <div class="contact-item mb-3">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        <span>Manama, Bahrain - Central Business District</span>
                    </div>
                    <div class="contact-item mb-3">
                        <i class="fas fa-phone me-2 text-primary"></i>
                        <span>+973 1234 5678</span>
                    </div>
                    <div class="contact-item mb-3">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        <span>support@grocerynest.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        <span>24/7 Customer Support</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom py-3 border-top border-secondary">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">&copy; 2025 GroceryNest. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-links">
                            <a href="#" class="text-muted me-3">Privacy Policy</a>
                            <a href="#" class="text-muted me-3">Terms of Service</a>
                            <a href="#" class="text-muted">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/index.js"></script>
    <script src="js/products.js"></script>

    <!-- Contact Toast Notifications -->
    <script>
        function showContactToast(message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.contact-toast');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.contact-toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'contact-toast-container';
                toastContainer.style.cssText = `
                    position: fixed;
                    top: 100px;
                    right: 20px;
                    z-index: 1055;
                    max-width: 350px;
                `;
                document.body.appendChild(toastContainer);
            }
            
            const toastIcons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };
            
            const toastColors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6',
                warning: '#f59e0b'
            };
            
            const toast = document.createElement('div');
            toast.className = 'contact-toast';
            toast.style.cssText = `
                background: ${toastColors[type] || toastColors.info};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 300px;
                margin-bottom: 10px;
                animation: slideInRight 0.3s ease-out;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            toast.innerHTML = `
                <i class="${toastIcons[type] || toastIcons.info}"></i>
                <span>${message}</span>
            `;
            
            toastContainer.appendChild(toast);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 4000);
        }
        
        // Add CSS animations for contact toast notifications
        if (!document.querySelector('#contact-toast-animations')) {
            const style = document.createElement('style');
            style.id = 'contact-toast-animations';
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