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
        const btn = e.target.closest('.remove-item');
        const itemId = btn.dataset.itemId;
        
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            removeItem(itemId);
        }
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
