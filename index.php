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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GroceryNest - Online Grocery Shopping</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/index_feat_cat.css">
    <link rel="stylesheet" href="css/products.css">
</head>


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














    <!-- Special Offers Section -->
    <section id="offers" class="section bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Special Offers</h2>
                <p class="section-subtitle">Explore our featured categories</p>
            </div>
            
            <div class="row g-4">
                <!-- Fruits Category -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <a href="products.php?category=fruits" class="text-decoration-none">
                        <div class="offer-card h-100 p-4 rounded-4 bg-white shadow-sm text-center">
                            <div class="offer-badge bg-success text-white mb-3">SPECIAL OFFER</div>
                            <img src="https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?auto=format&fit=crop&w=300&q=80" 
                                 alt="Fresh Fruits" 
                                 class="img-fluid rounded-3 mb-3 offer-image">
                            <h4 class="h5 mb-3 text-dark">Fresh Fruits</h4>
                            <span class="btn btn-outline-success">Shop Now</span>
                        </div>
                    </a>
                </div>
                
                <!-- Vegetables Category -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <a href="products.php?category=vegetables" class="text-decoration-none">
                        <div class="offer-card h-100 p-4 rounded-4 bg-white shadow-sm text-center">
                            <div class="offer-badge bg-success text-white mb-3">SPECIAL OFFER</div>
                            <img src="https://images.unsplash.com/photo-1516594798947-e65505dbb29d?auto=format&fit=crop&w=300&q=80" 
                                 alt="Fresh Vegetables" 
                                 class="img-fluid rounded-3 mb-3 offer-image">
                            <h4 class="h5 mb-3 text-dark">Fresh Vegetables</h4>
                            <span class="btn btn-outline-success">Shop Now</span>
                        </div>
                    </a>
                </div>
                
                <!-- Fresh Meat Category -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <a href="products.php?category=meat" class="text-decoration-none">
                        <div class="offer-card h-100 p-4 rounded-4 bg-white shadow-sm text-center">
                            <div class="offer-badge bg-success text-white mb-3">SPECIAL OFFER</div>
                            <img src="https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=300&q=80" 
                                 alt="Fresh Meat" 
                                 class="img-fluid rounded-3 mb-3 offer-image">
                            <h4 class="h5 mb-3 text-dark">Fresh Meat</h4>
                            <span class="btn btn-outline-success">Shop Now</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>







    
    <!-- Testimonials Section -->
    <section class="section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">What Our Customers Say</h2>
                <p class="section-subtitle">Trusted by thousands of happy customers</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card p-4 rounded-4 shadow-sm h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Customer" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Sarah A.</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"The quality of fruits and vegetables is outstanding! I've been a customer for over a year now and I'm always impressed with the freshness and delivery speed."</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card p-4 rounded-4 shadow-sm h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Customer" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Ahmed K.</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"The special offers are amazing! I saved 25% on my last order. The delivery was prompt and everything was packed perfectly. Highly recommended!"</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mx-auto" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-card p-4 rounded-4 shadow-sm h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Customer" class="rounded-circle me-3" width="60" height="60">
                            <div>
                                <h5 class="mb-0">Fatima M.</h5>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">"The customer service is exceptional. I had an issue with a delivery and they resolved it immediately. The quality of products is consistently great!"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>









    <!-- Footer -->
    <footer id="about" class="footer bg-dark text-white">
        <div class="container py-5">
            <div class="row gx-0">
                <div class="col-lg-5 mb-4 mb-lg-0 pe-5">
                    <div class="about-content">
                        <h2 class="section-title text-white mb-4">About GroceryNest</h2>
                        <p class="lead mb-4">Your trusted online grocery store delivering fresh and high-quality products directly to your doorstep.</p>
                        <p class="mb-4">At GroceryNest, we believe in providing the freshest produce, highest quality meats, and pantry staples at competitive prices. Our mission is to make grocery shopping convenient, fast, and enjoyable.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="d-flex align-items-center me-4">
                                <div class="me-3">
                                    <i class="fas fa-truck fa-2x text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Fast Delivery</h5>
                                    <small class="text-muted">30 min delivery</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-leaf fa-2x text-success"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Fresh Products</h5>
                                    <small class="text-muted">Farm to table</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-auto px-5">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="footer-link">Home</a></li>
                        <li class="mb-2"><a href="products.php" class="footer-link">Products</a></li>
                        <li class="mb-2"><a href="#features" class="footer-link">Features</a></li>
                        <li class="mb-2"><a href="about.php" class="footer-link">About Us</a></li>
                        <li class="mb-2"><a href="#contact" class="footer-link">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="col-auto px-5">
                    <h5 class="fw-bold mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="products.php?category=Fruits" class="footer-link">Fruits</a></li>
                        <li class="mb-2"><a href="products.php?category=Vegetables" class="footer-link">Vegetables</a></li>
                        <li class="mb-2"><a href="products.php?category=Dairy" class="footer-link">Dairy</a></li>
                        <li class="mb-2"><a href="products.php?category=Meat" class="footer-link">Meat</a></li>
                        <li class="mb-2"><a href="products.php?category=Beverages" class="footer-link">Beverages</a></li>
                        <li class="mb-2"><a href="products.php?category=Snacks" class="footer-link">Snacks</a></li>
                        <a href="products.php" class="mb-2">View all</a>
                    </ul>
                </div>
                
                <div class="col-auto ps-5">
                    <h5 class="fw-bold mb-3">Help & Support</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo isset($_SESSION['activeUser']) ? 'contact.php' : 'login_form.php'; ?>" class="footer-link">Contact Us</a></li>
                        <li class="mb-2"><a href="<?php echo isset($_SESSION['activeUser']) ? 'contact.php#email' : 'login_form.php'; ?>" class="footer-link">Email Support</a></li>
                        <li class="mb-2"><a href="<?php echo isset($_SESSION['activeUser']) ? 'contact.php#returns' : 'login_form.php'; ?>" class="footer-link">Returns & Refunds</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom py-3 border-top border-secondary text-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2025 GroceryNest. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-links">
                            <a href="#" class="text-white me-3">Privacy Policy</a>
                            <a href="#" class="text-white me-3">Terms of Service</a>
                            <a href="#" class="text-white">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Smooth Scrolling for Anchor Links -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if URL has a hash
        if (window.location.hash) {
            const targetId = window.location.hash;
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // Small delay to ensure the page has fully loaded
                setTimeout(() => {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80, // Adjust for fixed navbar
                        behavior: 'smooth'
                    });
                }, 100);
            }
        }

        // Add smooth scrolling for all anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const targetId = href;
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80, // Adjust for fixed navbar
                            behavior: 'smooth'
                        });
                        
                        // Update URL without adding to history
                        history.pushState(null, null, targetId);
                    }
                }
            });
        });
    });
    </script>
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