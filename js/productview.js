// Product View JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Quantity controls
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');
    const quantityInput = document.getElementById('productQuantity');

    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseBtn.addEventListener('click', function() {
            let currentValue = parseInt(quantityInput.value);
            let maxValue = parseInt(quantityInput.getAttribute('max')) || 10;
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            }
        });

        // Validate quantity input
        quantityInput.addEventListener('input', function() {
            let value = parseInt(this.value);
            let min = parseInt(this.getAttribute('min')) || 1;
            let max = parseInt(this.getAttribute('max')) || 10;
            
            if (isNaN(value) || value < min) {
                this.value = min;
            } else if (value > max) {
                this.value = max;
            }
        });
    }

    // Add to cart functionality with login check
    const addToCartBtn = document.querySelector('.add-to-cart-main');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
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
                    // Not logged in → show same message as add-to-cart
                    if (data.status === 'error' && /please login/i.test(data.message || '')) {
                        showNotification('Please login to add items to cart', 'error');
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
        `;
        document.head.appendChild(style);
    }
});