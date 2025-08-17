// Filter functionality
function filterProducts() {
    const search = document.getElementById('searchInput').value;
    const category = document.querySelector('.category-item.active').dataset.category;
    const sort = document.getElementById('sortSelect').value;
    
    // Show loading state
    document.getElementById('productsGrid').innerHTML = `
        <div class="col-12">
            <div class="loading">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading products...</p>
            </div>
        </div>
    `;
    
    // Update URL without page reload
    const params = new URLSearchParams({
        search: search,
        category: category,
        sort: sort
    });
    window.history.replaceState(null, null, `?${params.toString()}`);
    
    // Fetch filtered products
    fetch(`products.php?${params.toString()}&ajax=1`)
        .then(response => response.json())
        .then(products => {
            renderProducts(products);
            // Only refresh AOS if it's enabled
            if (typeof AOS !== 'undefined' && AOS.refresh) {
                AOS.refresh();
            }
            updateProductCount(products.length);
            updateCategoryTitle(category, search);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('productsGrid').innerHTML = `
                <div class="col-12">
                    <div class="no-products">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5>Error loading products</h5>
                        <p>Please try again later</p>
                    </div>
                </div>
            `;
        });
}

// Updated render products function with FIXED favorite button positioning
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    
    if (products.length === 0) {
        grid.innerHTML = `
            <div class="col-12">
                <div class="no-products">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>No products found</h5>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = products.map((product, index) => {
        const qty = productQuantities[product.id] || 1;
        return `
            <div class="col-12 col-sm-6 col-lg-3 col-xl-2" data-aos="fade-up" data-aos-delay="${Math.min(index * 20, 200)}">
                <div class="product-card" data-product-id="${product.id}" style="cursor: pointer;">
                    <div class="product-image-container">
                        <img src="Images/${product.image}" alt="${product.name}" class="product-image" loading="lazy">
                        <!-- FIXED: Favorite button with consistent positioning -->
                        <button class="favorite-btn" data-product-id="${product.id}" type="button">
                            <i class="far fa-heart"></i>
                        </button>
                        <div class="badge-container">
                            ${getDiscountBadge(product.original_price, product.price)}
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-category">${product.category}</div>
                        <div class="product-title">${product.name}</div>
                        <div class="product-price">
                            <span class="ah-currency">BHD</span>
                            <span class="price">${parseFloat(product.price).toFixed(3)}</span>
                            ${product.original_price && product.original_price > product.price ? 
                                `<span class="original-price">BHD ${parseFloat(product.original_price).toFixed(3)}</span>` : ''}
                        </div>
                        <div class="product-bottom-actions">
                            <div class="quantity-controls">
                                <button class="quantity-btn decrease-qty" data-item-id="${product.id}" type="button" ${qty === 1 ? 'disabled' : ''}>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="quantity-input" data-item-id="${product.id}" value="${qty}" min="1" max="99" readonly>
                                <button class="quantity-btn increase-qty" data-item-id="${product.id}" type="button" ${qty >= 99 ? 'disabled' : ''}>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button class="add-to-cart-btn" data-product-id="${product.id}" type="button">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Re-initialize product quantities for new products
    products.forEach(product => {
        if (!productQuantities[product.id]) {
            productQuantities[product.id] = 1;
        }
    });
    
    console.log('Products rendered, attaching event listeners...');
    // Attach event listeners immediately after rendering
    attachEventListeners();
    
    // FIXED: Ensure favorite buttons are positioned correctly after rendering
    fixFavoriteButtonPositioning();
}

// NEW: Function to fix favorite button positioning
function fixFavoriteButtonPositioning() {
    // Remove any inline styles that might interfere with positioning
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        // Remove conflicting inline styles but keep essential ones
        btn.style.position = 'absolute';
        btn.style.top = '10px';
        btn.style.right = '10px';
        btn.style.zIndex = '10';
        
        console.log('Fixed favorite button positioning for product:', btn.dataset.productId);
    });
}

// Helper functions
function getDiscountBadge(original, current) {
    if (original && original > current) {
        const discount = Math.round(((original - current) / original) * 100);
        return `<div class="discount-badge">${discount}% OFF</div>`;
    }
    return '';
}

function updateProductCount(count) {
    const countElement = document.querySelector('.products-header .me-3');
    if (countElement) {
        countElement.textContent = `${count} Products found`;
    }
}

function updateCategoryTitle(category, search) {
    const titleElement = document.querySelector('.products-header h4');
    if (titleElement) {
        titleElement.innerHTML = `
            ${category === 'all' ? 'All Products' : category}
            ${search ? `<span class="text-muted fs-6">for "${search}"</span>` : ''}
        `;
    }
}

// Quantity management
let productQuantities = {};

function updateQuantityDisplay(itemId, quantity) {
    // Update the input value
    const input = document.querySelector(`input[data-item-id="${itemId}"]`);
    if (input) {
        input.value = quantity;
        console.log('Updated input value for', itemId, 'to', quantity);
    }
    
    // Update button states
    const decreaseBtn = document.querySelector(`.decrease-qty[data-item-id="${itemId}"]`);
    const increaseBtn = document.querySelector(`.increase-qty[data-item-id="${itemId}"]`);
    
    if (decreaseBtn) {
        decreaseBtn.disabled = quantity <= 1;
        decreaseBtn.style.opacity = quantity <= 1 ? '0.5' : '1';
        decreaseBtn.style.cursor = quantity <= 1 ? 'not-allowed' : 'pointer';
    }
    
    if (increaseBtn) {
        increaseBtn.disabled = quantity >= 99;
        increaseBtn.style.opacity = quantity >= 99 ? '0.5' : '1';
        increaseBtn.style.cursor = quantity >= 99 ? 'not-allowed' : 'pointer';
    }
}

function updateQuantity(itemId, newQuantity) {
    // Ensure quantity is within bounds
    const quantity = Math.max(1, Math.min(99, newQuantity));
    
    // Update in memory
    productQuantities[itemId] = quantity;
    console.log('Updated quantity in memory for', itemId, 'to', quantity);
    
    // Update display
    updateQuantityDisplay(itemId, quantity);
    
    return quantity;
}

function changeQuantity(itemId, change) {
    const currentQty = productQuantities[itemId] || 1;
    const newQuantity = currentQty + change;
    console.log('Changing quantity for', itemId, 'from', currentQty, 'by', change, 'to', newQuantity);
    return updateQuantity(itemId, newQuantity);
}

// Cart functionality
function addToCart(productId, button) {
    const quantity = productQuantities[productId] || 1;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch(`cart.php?add=${productId}&quantity=${quantity}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added' || data.status === 'updated') {
                button.innerHTML = '<i class="fas fa-check"></i>';
                showToast('success', `${quantity} item(s) added to cart`);
                
                // Reset quantity to 1 after adding to cart
                updateQuantity(productId, 1);
                updateCartCount();
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 1500);
            } else {
                button.innerHTML = originalText;
                button.disabled = false;
                showToast('error', data.message || 'Failed to add to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            showToast('error', 'Failed to add to cart. Please try again.');
        });
}

// Wishlist functionality
function toggleFavorite(productId, button) {
    const icon = button.querySelector('i');
    
    fetch(`wishlist.php?toggle=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('active');
                showToast('success', 'Added to favorites');
            } else if (data.status === 'removed') {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('active');
                showToast('info', 'Removed from favorites');
            } else {
                showToast('error', data.message || 'Failed to update favorites');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Failed to update favorites. Please try again.');
        });
}

// Toast notifications
function showToast(type, message) {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
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
    toast.className = 'custom-toast';
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

// Update cart count in navbar
function updateCartCount() {
    fetch('cart.php?count=1')
        .then(response => response.json())
        .then(data => {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement && data.status === 'success') {
                cartCountElement.textContent = data.count;
                if (data.count > 0) {
                    cartCountElement.style.display = 'inline';
                } else {
                    cartCountElement.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Individual event listeners (fallback approach)
function attachEventListeners() {
    console.log('Attaching individual event listeners...');
    
    // Use event delegation instead of individual listeners for better performance
    // Remove the cloning approach as it's expensive
    
    // Event delegation is handled in the main document event listener below
    // This prevents memory leaks and improves performance
    
    console.log('Event delegation ready for product interactions');
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // Initialize all product quantities to 1
    document.querySelectorAll('.quantity-input').forEach(input => {
        const itemId = input.dataset.itemId;
        if (itemId) {
            productQuantities[itemId] = parseInt(input.value) || 1;
            updateQuantityDisplay(itemId, productQuantities[itemId]);
        }
    });
    
    // FIXED: Fix favorite button positioning on initial load
    fixFavoriteButtonPositioning();
    
    // Category selection
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            filterProducts();
        });
    });
    
    // Search input
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterProducts, 500);
    });
    
    // Pressing Enter in search
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout);
            filterProducts();
        }
    });
    
    // Sort select
    document.getElementById('sortSelect').addEventListener('change', filterProducts);
    
    // Initialize AOS with performance optimizations
    const isMobile = window.innerWidth < 768;
    AOS.init({
        once: true, // Only animate once to improve performance
        duration: 800,
        offset: 100,
        delay: 0,
        easing: 'ease-out',
        disable: isMobile ? true : false // Disable on mobile for better performance
    });

    // Initial event listeners for existing content
    attachEventListeners();
    
    // Add CSS animations for toast
    if (!document.querySelector('#toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
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
            
            /* Ensure buttons are clickable */
            .quantity-btn, .add-to-cart-btn, .favorite-btn {
                pointer-events: auto !important;
                cursor: pointer !important;
                position: relative;
                z-index: 1;
            }
            
            .quantity-btn:disabled {
                cursor: not-allowed !important;
            }
            
            /* FIXED: Additional favorite button positioning */
            .favorite-btn {
                position: absolute !important;
                top: 10px !important;
                right: 10px !important;
                z-index: 10 !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initial cart count update
    updateCartCount();
});

// Event delegation as backup (both approaches for maximum compatibility)
document.addEventListener('click', function(e) {
    console.log('Document click detected on:', e.target);
    
    // Handle increase quantity
    if (e.target.closest('.increase-qty')) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Event delegation: Increase button clicked');
        
        const btn = e.target.closest('.increase-qty');
        const itemId = btn.dataset.itemId;
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        const currentQty = parseInt(input.value) || 1;
        const newQuantity = currentQty + 1;
        
        if (newQuantity <= 99) {
            updateQuantity(itemId, newQuantity);
            console.log('Event delegation: Increased quantity for product', itemId, 'to', newQuantity);
        }
        return;
    }
    
    // Handle decrease quantity
    if (e.target.closest('.decrease-qty')) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Event delegation: Decrease button clicked');
        
        const btn = e.target.closest('.decrease-qty');
        const itemId = btn.dataset.itemId;
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        const currentQty = parseInt(input.value) || 1;
        const newQuantity = currentQty - 1;
        
        if (newQuantity >= 1) {
            updateQuantity(itemId, newQuantity);
            console.log('Event delegation: Decreased quantity for product', itemId, 'to', newQuantity);
        }
        return;
    }
    
    // Handle add to cart
    if (e.target.closest('.add-to-cart-btn')) {
        e.preventDefault();
        e.stopPropagation();
        const button = e.target.closest('.add-to-cart-btn');
        const productId = button.dataset.productId;
        console.log('Event delegation: Add to cart clicked for product', productId);
        addToCart(productId, button);
        return;
    }
    
    // Handle favorite toggle
    if (e.target.closest('.favorite-btn')) {
        e.preventDefault();
        e.stopPropagation();
        const button = e.target.closest('.favorite-btn');
        const productId = button.dataset.productId;
        console.log('Event delegation: Favorite clicked for product', productId);
        toggleFavorite(productId, button);
        return;
    }

    // Handle product card clicks (but not button clicks)
    if (e.target.closest('.product-card') && !e.target.closest('button') && !e.target.closest('input')) {
        e.preventDefault();
        const card = e.target.closest('.product-card');
        const productId = card.dataset.productId;
        if (productId) {
            navigateToProduct(productId);
        }
        return;
    }
});

// Handle direct input changes (if user types in the input)
document.addEventListener('input', function(e) {
    if (e.target.matches('.quantity-input')) {
        const input = e.target;
        const itemId = input.dataset.itemId;
        let newQuantity = parseInt(input.value);
        
        // Validate and correct the input
        if (isNaN(newQuantity) || newQuantity < 1) {
            newQuantity = 1;
        } else if (newQuantity > 99) {
            newQuantity = 99;
        }
        
        updateQuantity(itemId, newQuantity);
        console.log('Direct input change for product', itemId, 'to', newQuantity);
    }
});

// Navigation function for product view
function navigateToProduct(productId) {
    window.location.href = `productview.php?id=${productId}`;
}