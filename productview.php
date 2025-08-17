<?php
// filepath: c:\xampp\htdocs\grocery_website\productview.php
session_start();
require_once 'project_connection.php';

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

try {
    // Fetch product details with category
    $stmt = $conn->prepare("
        SELECT products.*, categories.name AS category_name 
        FROM products 
        LEFT JOIN categories ON products.category_id = categories.id 
        WHERE products.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
    
    // Fetch related products from same category
    $related_stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND id != ? 
        LIMIT 4
    ");
    $related_stmt->execute([$product['category_id'], $product_id]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to format price
function formatPrice($price) {
    return number_format($price, 3);
}

// Function to calculate discount percentage
function getDiscountPercentage($original, $current) {
    if ($original > $current) {
        return round((($original - $current) / $original) * 100);
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - GroceryNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="stylesheet" href="css/productview.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container py-4 mt-5 pt-3">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item"><a href="products.php?category=<?= urlencode($product['category_name']) ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Image Section -->
            <div class="col-lg-6">
                <div class="product-image-section">
                    <div class="main-image-container">
                        <img src="Images/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="main-product-image" 
                             id="mainProductImage">
                        
                        <!-- Discount Badge -->
                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                            <div class="discount-badge-large">
                                <?= getDiscountPercentage($product['original_price'], $product['price']) ?>% OFF
                            </div>
                        <?php endif; ?>
                        
                        <!-- Stock Status -->
                        <div class="stock-badge <?= $product['inStock'] ? 'in-stock' : 'out-of-stock' ?>">
                            <?= $product['inStock'] ? 'In Stock' : 'Out of Stock' ?>
                        </div>
                    </div>
                    
                    <!-- Thumbnail Images (if multiple images were available) -->
                    <div class="thumbnail-container mt-3">
                        <div class="thumbnail-item active">
                            <img src="Images/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="thumbnail-image">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details Section -->
            <div class="col-lg-6">
                <div class="product-details-section">
                    <!-- Brand/Category -->
                    <div class="product-brand mb-2">
                        <span class="text-muted">View all products from</span> 
                        <a href="products.php?category=<?= urlencode($product['category_name']) ?>" class="brand-link">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </a>
                    </div>

                    <!-- Product Title -->
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

                

                    <!-- Price Section -->
                    <div class="price-section mb-4">
                        <div class="current-price">BHD <?= formatPrice($product['price']) ?></div>
                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                            <div class="original-price">BHD <?= formatPrice($product['original_price']) ?></div>
                            <div class="savings">You save BHD <?= formatPrice($product['original_price'] - $product['price']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Quantity and Add to Cart -->
                    <div class="purchase-section mb-4">
                        <div class="quantity-controls">
                            <button class="quantity-btn decrease-qty" data-item-id="<?= $product['id'] ?>" type="button" disabled>
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" data-item-id="<?= $product['id'] ?>" value="1" min="1" max="99" readonly>
                            <button class="quantity-btn increase-qty" data-item-id="<?= $product['id'] ?>" type="button">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        

                        <div class="action-buttons">
                            <button class="btn btn-primary add-to-cart-main" 
                                    data-product-id="<?= $product['id'] ?>" 
                                    <?= !$product['inStock'] ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart me-2"></i>
                                <?= $product['inStock'] ? 'Add to Cart' : 'Out of Stock' ?>
                            </button>
                            
                            <button class="btn btn-outline-danger favorite-btn-main" 
                                    data-product-id="<?= $product['id'] ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            
                            <button class="btn btn-outline-secondary share-btn">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Product Information Tabs -->
                    <div class="product-info-tabs">
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" 
                                        data-bs-target="#description" type="button" role="tab">
                                    Description
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" 
                                        data-bs-target="#specifications" type="button" role="tab">
                                    Specifications
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="productTabsContent">
                            <div class="tab-pane fade show active" id="description" role="tabpanel">
                                <div class="tab-content-inner">
                                    <h6>Product Summary</h6>
                                    <div class="product-description">
                                        <?= nl2br(htmlspecialchars($product['product_summary'] ?? $product['description'])) ?>
                                    </div>
                                    
                                    <!-- Features List -->
                                    <?php if (!empty($product['features'])): ?>
                                    <div class="features-list mt-3">
                                        <?php 
                                        $features = explode("\n", $product['features']);
                                        foreach ($features as $feature): 
                                            $feature = trim($feature);
                                            if (!empty($feature)):
                                        ?>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?= htmlspecialchars($feature) ?>
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="specifications" role="tabpanel">
                                <div class="tab-content-inner">
                                    <div class="specifications-table">
                                        <?php if (!empty($product['specifications'])): ?>
                                            <?php 
                                            $specs = explode("\n", $product['specifications']);
                                            foreach ($specs as $spec): 
                                                $spec = trim($spec);
                                                if (!empty($spec)):
                                                    $parts = explode(':', $spec, 2);
                                                    $label = trim($parts[0]);
                                                    $value = isset($parts[1]) ? trim($parts[1]) : '';
                                            ?>
                                            <div class="spec-row">
                                                <span class="spec-label"><?= htmlspecialchars($label) ?>:</span>
                                                <span class="spec-value"><?= htmlspecialchars($value) ?></span>
                                            </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        <?php else: ?>
                                        <div class="spec-row">
                                            <span class="spec-label">Category:</span>
                                            <span class="spec-value"><?= htmlspecialchars($product['category_name']) ?></span>
                                        </div>
                                        <div class="spec-row">
                                            <span class="spec-label">SKU:</span>
                                            <span class="spec-value"><?= str_pad($product['id'], 8, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        <div class="spec-row">
                                            <span class="spec-label">Stock Status:</span>
                                            <span class="spec-value"><?= $product['inStock'] ? 'In Stock' : 'Out of Stock' ?></span>
                                        </div>
                                        <div class="spec-row">
                                            <span class="spec-label">Added Date:</span>
                                            <span class="spec-value"><?= date('F j, Y', strtotime($product['created_at'])) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        <?php if (!empty($related_products)): ?>
        <div class="related-products-section mt-5">
            <h3 class="section-title mb-4">Related Products</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card related-product-card" onclick="window.location.href='productview.php?id=<?= $related['id'] ?>'">
                        <div class="product-image-container">
                            <img src="Images/<?= htmlspecialchars($related['image']) ?>" 
                                 alt="<?= htmlspecialchars($related['name']) ?>" 
                                 class="product-image">
                            
                            <?php if ($related['original_price'] && $related['original_price'] > $related['price']): ?>
                                <div class="badge-container">
                                    <span class="discount-badge">
                                        <?= getDiscountPercentage($related['original_price'], $related['price']) ?>% OFF
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h6 class="product-name"><?= htmlspecialchars($related['name']) ?></h6>
                            <div class="product-price">
                                <span class="current-price">BHD <?= formatPrice($related['price']) ?></span>
                                <?php if ($related['original_price'] && $related['original_price'] > $related['price']): ?>
                                    <span class="original-price">BHD <?= formatPrice($related['original_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom JS -->
    <script src="js/productview.js"></script>
</body>
</html>
