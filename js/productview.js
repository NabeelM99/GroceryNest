// Product View JavaScript
document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });

    // Quantity controls - Updated to match your HTML structure
    document.addEventListener('click', function(e) {
        // Handle decrease quantity
        if (e.target.closest('.decrease-qty')) {
            const btn = e.target.closest('.decrease-qty');
            const itemId = btn.dataset.itemId;
            const input = document.querySelector(`input[data-item-id="${itemId}"]`);
            
            if (input) {
                const currentValue = parseInt(input.value);
                const minValue = parseInt(input.getAttribute('min')) || 1;
                
                if (currentValue > minValue) {
                    input.value = currentValue - 1;
                    updateQuantityButtons(itemId, currentValue - 1);
                }
            }
        }
        
        // Handle increase quantity
        if (e.target.closest('.increase-qty')) {
            const btn = e.target.closest('.increase-qty');
            const itemId = btn.dataset.itemId;
            const input = document.querySelector(`input[data-item-id="${itemId}"]`);
            
            if (input) {
                const currentValue = parseInt(input.value);
                const maxValue = parseInt(input.getAttribute('max')) || 99;
                
                if (currentValue < maxValue) {
                    input.value = currentValue + 1;
                    updateQuantityButtons(itemId, currentValue + 1);
                }
            }
        }
    });

    // Function to update quantity button states
    function updateQuantityButtons(itemId, quantity) {
        const decreaseBtn = document.querySelector(`.decrease-qty[data-item-id="${itemId}"]`);
        const increaseBtn = document.querySelector(`.increase-qty[data-item-id="${itemId}"]`);
        const input = document.querySelector(`input[data-item-id="${itemId}"]`);
        
        if (decreaseBtn && input) {
            const minValue = parseInt(input.getAttribute('min')) || 1;
            decreaseBtn.disabled = quantity <= minValue;
        }
        
        if (increaseBtn && input) {
            const maxValue = parseInt(input.getAttribute('max')) || 99;
            increaseBtn.disabled = quantity >= maxValue;
        }
    }

    // Validate quantity input on manual input
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const value = parseInt(input.value);
            const min = parseInt(input.getAttribute('min')) || 1;
            const max = parseInt(input.getAttribute('max')) || 99;
            const itemId = input.dataset.itemId;
            
            if (isNaN(value) || value < min) {
                input.value = min;
            } else if (value > max) {
                input.value = max;
            }
            
            updateQuantityButtons(itemId, parseInt(input.value));
        }
    });

    // Initialize quantity buttons state
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        const itemId = input.dataset.itemId;
        const currentValue = parseInt(input.value);
        updateQuantityButtons(itemId, currentValue);
    });

    // Add to cart functionality with login check
    const addToCartBtn = document.querySelector('.add-to-cart-main');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantityInput = document.querySelector(`.quantity-input[data-item-id="${productId}"]`);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            // Add visual feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            this.disabled = true;
            
            // Make AJAX request to add to cart
            fetch(`cart.php?add=${productId}&quantity=${quantity}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'error' && data.message === 'Please login to add items to cart') {
                        // User not logged in - show same notification as products page
                        this.innerHTML = originalText;
                        this.disabled = false;
                        showNotification('Please login to add items to cart', 'error');
                        
                        // No redirect, just the notification message
                    } else if (data.status === 'added' || data.status === 'updated') {
                        // Successfully added to cart
                        this.innerHTML = '<i class="fas fa-check me-2"></i>Added to Cart!';
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-success');
                        
                        // Show success message
                        showNotification('Product added to cart successfully!', 'success');
                        
                        // Update cart count in navbar if function exists
                        if (typeof updateCartCount === 'function') {
                            updateCartCount();
                        }
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-primary');
                            this.disabled = false;
                        }, 2000);
                    } else {
                        // Other error
                        this.innerHTML = originalText;
                        this.disabled = false;
                        showNotification(data.message || 'Failed to add to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showNotification('Failed to add to cart. Please try again.', 'error');
                });
        });
    }

    // Update cart count in navbar (if cart count element exists)
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

    // Favorite button functionality with login check + backend toggle
    const favoriteBtn = document.querySelector('.favorite-btn-main');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const button = this;
            const icon = button.querySelector('i');
            const productId = button.getAttribute('data-product-id');

            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            fetch(`wishlist.php?toggle=${productId}`)
                .then(response => response.json())
                .then(data => {
                    // Not logged in → show wishlist login message
                    if (data.status === 'error' && /please login/i.test(data.message || '')) {
                        showNotification('Please login to manage your wishlist', 'error');
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                        return;
                    }

                    if (data.status === 'added') {
                        // Update icon and button style
                        if (icon) {
                            icon.className = data.icon || 'fas fa-heart';
                        }
                        button.classList.remove('btn-outline-danger');
                        button.classList.add('btn-danger');
                        showNotification(data.message || 'Added to favorites!', 'success');
                    } else if (data.status === 'removed') {
                        if (icon) {
                            icon.className = data.icon || 'far fa-heart';
                        }
                        button.classList.remove('btn-danger');
                        button.classList.add('btn-outline-danger');
                        showNotification(data.message || 'Removed from favorites!', 'info');
                    } else {
                        showNotification(data.message || 'Could not update wishlist', 'error');
                    }

                    button.innerHTML = originalHTML;
                    button.disabled = false;
                })
                .catch(() => {
                    showNotification('Failed to update wishlist. Please try again.', 'error');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                });
        });
    }

    // Share button functionality
    const shareBtn = document.querySelector('.share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            if (navigator.share) {
                // Use native share API if available
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).then(() => {
                    showNotification('Shared successfully!', 'success');
                }).catch((error) => {
                    console.log('Error sharing:', error);
                    fallbackShare();
                });
            } else {
                fallbackShare();
            }
        });
    }

    // Fallback share function
    function fallbackShare() {
        // Copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Product link copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Unable to copy link. Please copy manually.', 'error');
        });
    }

    // Thumbnail image switching (if multiple images were available)
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImage = document.getElementById('mainProductImage');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Update main image
            const newImageSrc = this.querySelector('img').src;
            if (mainImage) {
                mainImage.src = newImageSrc;
            }
        });
    });

    // Smooth scrolling for related products
    const relatedProducts = document.querySelectorAll('.related-product-card');
    relatedProducts.forEach(card => {
        card.addEventListener('click', function() {
            // Add loading state
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
            
            // Navigate after short delay for visual feedback
            setTimeout(() => {
                window.location.href = this.getAttribute('onclick').match(/window\.location\.href='([^']+)'/)[1];
            }, 200);
        });
    });

    // Notification system - Updated to match products page style
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.custom-toast');
        existingNotifications.forEach(notification => notification.remove());

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

    // Image zoom functionality (optional enhancement)
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            // Create modal for image zoom
            const modal = document.createElement('div');
            modal.className = 'image-zoom-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                cursor: pointer;
            `;
            
            const zoomedImage = document.createElement('img');
            zoomedImage.src = this.src;
            zoomedImage.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            `;
            
            modal.appendChild(zoomedImage);
            document.body.appendChild(modal);
            
            // Close modal on click
            modal.addEventListener('click', function() {
                this.remove();
            });
            
            // Close modal on escape key
            document.addEventListener('keydown', function escapeHandler(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', escapeHandler);
                }
            });
        });
    }

    // Sticky navigation enhancement
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (window.scrollY > 100) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        }
    });

    // Price animation on load
    const priceElement = document.querySelector('.current-price');
    if (priceElement) {
        priceElement.style.opacity = '0';
        priceElement.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            priceElement.style.transition = 'all 0.6s ease';
            priceElement.style.opacity = '1';
            priceElement.style.transform = 'translateY(0)';
        }, 300);
    }

    // Initial cart count update
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }

    // Add CSS animations for toast notifications
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
            
            .quantity-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);
    }
});