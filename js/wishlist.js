
// Remove from wishlist functionality
document.querySelectorAll('.remove-wishlist').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const card = this.closest('.product-card');
        const productContainer = this.closest('.col-md-2');
        
        if (!card) {
            console.error('Could not find product card element');
            showToast('error', 'Failed to remove item');
            return;
        }
        
        // Add loading state
        const originalHTML = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        this.disabled = true;
        
        fetch(`wishlist.php?toggle=${productId}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'removed') {
                // Smooth removal animation
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    if (productContainer) {
                        productContainer.remove();
                    } else {
                        card.remove();
                    }
                    
                    // Check if wishlist is now empty
                    const remainingCards = document.querySelectorAll('.product-card');
                    if (remainingCards.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 200);
                
                showToast('success', data.message);
            } else {
                showToast('error', data.message || 'Failed to remove item');
                this.innerHTML = originalHTML;
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Failed to remove item');
            this.innerHTML = originalHTML;
            this.disabled = false;
        });
    });
});

// Quantity control functionality
document.querySelectorAll('.quantity-decrease').forEach(btn => {
    btn.addEventListener('click', function() {
        updateQuantity(this.dataset.productId, 'decrease');
    });
});

document.querySelectorAll('.quantity-increase').forEach(btn => {
    btn.addEventListener('click', function() {
        updateQuantity(this.dataset.productId, 'increase');
    });
});

function updateQuantity(productId, action) {
    const quantityDisplay = document.querySelector(`.quantity-display[data-product-id="${productId}"]`);
    const decreaseBtn = document.querySelector(`.quantity-decrease[data-product-id="${productId}"]`);
    const increaseBtn = document.querySelector(`.quantity-increase[data-product-id="${productId}"]`);
    const itemTotal = document.querySelector(`.item-total[data-product-id="${productId}"]`);
    
    // Check if quantity controls exist
    if (!decreaseBtn || !increaseBtn) {
        showToast('error', 'Quantity controls not available');
        return;
    }
    
    // Add loading state
    decreaseBtn.disabled = true;
    increaseBtn.disabled = true;
    
    fetch(`wishlist.php?update_quantity=${productId}&action=${action}`, {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update quantity display
            quantityDisplay.textContent = data.new_quantity;
            
            // Update total price
            const priceSection = itemTotal.closest('.price-section');
            const unitPriceElement = priceSection.querySelector('.unit-price');
            if (unitPriceElement) {
                const unitPrice = parseFloat(unitPriceElement.textContent.replace(/[^\d.]/g, ''));
                const newTotal = unitPrice * data.new_quantity;
                itemTotal.textContent = `BHD ${newTotal.toFixed(3)}`;
            }
            
            // Update button states
            decreaseBtn.disabled = data.new_quantity <= 1;
            increaseBtn.disabled = data.new_quantity >= 99;
            
            showToast('success', 'Quantity updated');
        } else {
            showToast('error', data.message || 'Failed to update quantity');
            // Re-enable buttons on error
            decreaseBtn.disabled = false;
            increaseBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Failed to update quantity');
        decreaseBtn.disabled = false;
        increaseBtn.disabled = false;
    });
}

// Initialize AOS
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
});

// Add to cart functionality
document.querySelectorAll('.add-to-cart-from-wishlist').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const quantity = document.querySelector(`.quantity-display[data-product-id="${productId}"]`).textContent;
        const originalHtml = this.innerHTML;
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        this.disabled = true;
        
        fetch(`cart.php?add=${productId}&quantity=${quantity}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added' || data.status === 'updated') {
                showToast('success', `${quantity} item(s) added to cart`);
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }, 2000);
            } else {
                showToast('error', data.message || 'Failed to add to cart');
                this.innerHTML = originalHtml;
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Failed to add to cart');
            this.innerHTML = originalHtml;
            this.disabled = false;
        });
    });
});

// Add all to cart functionality
document.getElementById('addAllToCart')?.addEventListener('click', function() {
    const originalHtml = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding All...';
    this.disabled = true;
    
    const promises = [];
    document.querySelectorAll('.wishlist-card').forEach(card => {
        const productId = card.dataset.productId;
        const quantity = card.querySelector('.quantity-display').textContent;
        
        promises.push(
            fetch(`cart.php?add=${productId}&quantity=${quantity}`, {
                method: 'GET',
                credentials: 'include'
            }).then(response => response.json())
        );
    });
    
    Promise.all(promises)
        .then(results => {
            const successCount = results.filter(r => r.status === 'added' || r.status === 'updated').length;
            showToast('success', `${successCount} items added to cart!`);
            this.innerHTML = '<i class="fas fa-check me-2"></i>All Added';
            setTimeout(() => {
                this.innerHTML = originalHtml;
                this.disabled = false;
            }, 3000);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Some items failed to add to cart');
            this.innerHTML = originalHtml;
            this.disabled = false;
        });
});

// Toast notifications - same as products.js for consistency
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
