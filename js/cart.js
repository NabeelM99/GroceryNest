// Quantity increase
document.addEventListener('click', function(e) {
    if (e.target.closest('.increase-qty')) {
        const btn = e.target.closest('.increase-qty');
        const itemId = btn.dataset.itemId;
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        const newQuantity = parseInt(input.value) + 1;
        
        if (newQuantity <= 99) {
            updateQuantity(itemId, newQuantity);
        }
    }
});

// Quantity decrease
document.addEventListener('click', function(e) {
    if (e.target.closest('.decrease-qty')) {
        const btn = e.target.closest('.decrease-qty');
        const itemId = btn.dataset.itemId;
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        const newQuantity = parseInt(input.value) - 1;
        
        if (newQuantity >= 1) {
            updateQuantity(itemId, newQuantity);
        }
    }
});

// Remove item
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.preventDefault();
        const btn = e.target.closest('.remove-item');
        const itemId = btn.dataset.itemId;
        const productName = btn.getAttribute('data-product-name') || 'this item';
        
        // Create confirmation modal
        const confirmModal = document.createElement('div');
        confirmModal.className = 'modal fade';
        confirmModal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-trash-alt me-2 text-danger"></i>
                            Remove Item
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                        </div>
                        <h5>Remove ${productName}?</h5>
                        <p class="text-muted">Are you sure you want to remove this item from your cart?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-danger confirm-remove" data-item-id="${itemId}">
                            <i class="fas fa-trash-alt me-2"></i>Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add to DOM and show
        document.body.appendChild(confirmModal);
        const modal = new bootstrap.Modal(confirmModal);
        modal.show();
        
        // Handle confirm button click
        confirmModal.querySelector('.confirm-remove').addEventListener('click', function() {
            modal.hide();
            removeItem(itemId);
        });
        
        // Clean up on modal close
        confirmModal.addEventListener('hidden.bs.modal', function () {
            confirmModal.remove();
        });
    }
});



function updateQuantity(itemId, quantity) {
    const cartItem = document.querySelector(`[data-item-id="${itemId}"]`);
    const input = cartItem.querySelector('.quantity-input');
    const originalValue = input.value;
    
    // Add loading state
    cartItem.classList.add('loading');
    
    const formData = new FormData();
    formData.append('update_quantity', '1');
    formData.append('cart_item_id', itemId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        cartItem.classList.remove('loading');
        
        if (data.status === 'success') {
            input.value = quantity;
            cartItem.querySelector('.subtotal-value').textContent = data.subtotal;
            document.getElementById('subtotal').textContent = '$' + data.total;
            document.getElementById('total').textContent = '$' + data.total;
            
            showToast('success', 'Cart updated successfully!');
        } else {
            input.value = originalValue;
            showToast('error', data.message || 'Failed to update quantity');
        }
    })
    .catch(error => {
        cartItem.classList.remove('loading');
        input.value = originalValue;
        console.error('Error:', error);
        showToast('error', 'Failed to update quantity');
    });
}

function removeItem(itemId) {
    const cartItem = document.querySelector(`[data-item-id="${itemId}"]`);
    
    // Add loading state
    cartItem.classList.add('loading');
    
    const formData = new FormData();
    formData.append('remove_item', '1');
    formData.append('cart_item_id', itemId);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Animate removal
            cartItem.style.transition = 'all 0.3s ease';
            cartItem.style.opacity = '0';
            cartItem.style.transform = 'translateX(100px)';
            
            setTimeout(() => {
                cartItem.remove();
                
                // Update totals
                document.getElementById('subtotal').textContent = '$' + data.total;
                document.getElementById('total').textContent = '$' + data.total;
                document.getElementById('item-count').textContent = data.item_count + ' items';
                
                // Check if cart is empty
                if (data.item_count === 0) {
                    location.reload(); // Refresh to show empty cart message
                }
            }, 300);
            
            showToast('success', data.message);
        } else {
            cartItem.classList.remove('loading');
            showToast('error', data.message || 'Failed to remove item');
        }
    })
    .catch(error => {
        cartItem.classList.remove('loading');
        console.error('Error:', error);
        showToast('error', 'Failed to remove item');
    });
}



// Toast notification function
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 1055;
        animation: slideInRight 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 8px;
        max-width: 300px;
    `;
    toast.innerHTML = `
        <i class="${type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Initialize AOS
AOS.init({
    once: false,
    duration: 300,
    offset: 50,
    delay: 0,
    easing: 'ease-out'
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);


let lastHoverTime = 0;

document.addEventListener('mouseover', function(e) {
    const now = Date.now();
    if (now - lastHoverTime < 100) return; // Throttle hover events
    
    lastHoverTime = now;
    const cartItem = e.target.closest('.cart-item');
    
    if (cartItem) {
        cartItem.classList.add('hover-active');
        setTimeout(() => cartItem.classList.remove('hover-active'), 200);
    }
});
