<?php
session_start();

// Redirect admin users to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    header("Location: admin_dashboard.php");
    exit;
}

// filepath: c:\xampp\htdocs\grocery_website\products.php
require_once 'project_connection.php';

// Get filters from GET params (for AJAX and normal load)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'name';

// Build SQL query with proper sanitization
$where = [];
$params = [];
if ($search) {
    $where[] = "(products.name LIKE ? OR products.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category && $category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
switch ($sortBy) {
    case 'price-low':
        $orderBy = 'ORDER BY price ASC';
        break;
    case 'price-high':
        $orderBy = 'ORDER BY price DESC';
        break;
    case 'name':
    default:
        $orderBy = 'ORDER BY name ASC';
        break;
}

try {
    // Fetch products
     $sql = "SELECT products.*, categories.name AS category
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id";

if ($where) {
    // Update category filter condition to use categories.name
    foreach ($where as &$cond) {
        if ($cond === "category = ?") {
            $cond = "categories.name = ?";
        }
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
} else {
    $whereSql = '';
}

$sql .= " $whereSql $orderBy";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*$stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);*/
    
    // Get categories for sidebar
    $categoryStmt = $conn->prepare("SELECT name FROM categories ORDER BY name");
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// For AJAX: return JSON if requested
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
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
    <title>Our Products - GroceryNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/products.css">
    

</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container py-4 mt-5 pt-3">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar p-3 mb-4">
                    <h6 class="fw-bold mb-3">Categories</h6>
                    <div class="category-list">
                        <div class="category-item <?= $category === 'all' ? 'active' : '' ?>" data-category="all">
                            <span>All Products</span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <?php foreach ($categories as $cat): ?>
                        <div class="category-item <?= $category === $cat ? 'active' : '' ?>" data-category="<?= htmlspecialchars($cat) ?>">
                            <span><?= htmlspecialchars($cat) ?></span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sidebar p-3 mb-4">
                    <h6 class="fw-bold mb-3">Search</h6>
                    <div class="search-box">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="products-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0 fw-bold">
                                <?= $category === 'all' ? 'All Products' : htmlspecialchars($category) ?>
                                <?php if ($search): ?>
                                    <span class="text-muted fs-6">for "<?= htmlspecialchars($search) ?>"</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <span class="me-3 text-muted"><?= count($products) ?> Products found</span>
                                <div class="d-flex align-items-center">
                                    <span class="me-2 text-muted">Sort by:</span>
                                    <select class="form-select form-select-sm" id="sortSelect" style="width: 150px;">
                                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                                        <option value="price-low" <?= $sortBy === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                                        <option value="price-high" <?= $sortBy === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <!-- Updated Products Grid with proper responsive classes -->
                <div class="row g-3 g-md-4" id="productsGrid">
                    <?php if (empty($products)): ?>
                        <div class="col-12">
                            <div class="no-products">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <h5>No products found</h5>
                                <p>Try adjusting your search or filter criteria</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <!-- Updated responsive grid classes: col-12 (mobile), col-sm-6 (tablet), col-lg-3 (desktop) -->
                        <div class="col-6 col-sm-6 col-lg-3 col-xl-2" data-aos="fade-up" data-aos-delay="<?= (array_search($product, $products) % 5) * 100 ?>">
                            <div class="product-card" data-product-id="<?= $product['id'] ?>" style="cursor: pointer;">
                                <div class="product-image-container">
                                    <img src="Images/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image" loading="lazy">
                                    
                                    <!-- Favorite button -->
                                    <button class="favorite-btn" data-product-id="<?= $product['id'] ?>" type="button" style="position:absolute;top:10px;right:10px;z-index:9999;">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    
                                    <div class="badge-container">
                                        <?php 
                                        $discountPercent = getDiscountPercentage($product['original_price'], $product['price']);
                                        if ($discountPercent > 0): 
                                        ?>
                                            <div class="discount-badge"><?= $discountPercent ?>% OFF</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                    <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                                    
                                    <!-- Price section -->
                                    <div class="product-price">
                                        <span class="ah-currency">BHD</span>
                                        <span class="price"><?= formatPrice($product['price']) ?></span>
                                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                            <span class="original-price">BHD <?= formatPrice($product['original_price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Bottom actions -->
                                    <div class="product-bottom-actions">
                                        <!-- Quantity controls -->
                                        <div class="quantity-controls">
                                            <button class="quantity-btn decrease-qty" data-item-id="<?= $product['id'] ?>" type="button" disabled>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="quantity-input" data-item-id="<?= $product['id'] ?>" value="1" min="1" max="99" readonly>
                                            <button class="quantity-btn increase-qty" data-item-id="<?= $product['id'] ?>" type="button">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Add to cart button -->
                                        <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>" type="button">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/products.js"></script>
</body>
</html>