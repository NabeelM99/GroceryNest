// Orders Page JavaScript
class OrdersManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
    }

    bindEvents() {
        // Bind global functions to window for onclick handlers
        window.viewOrderDetails = this.viewOrderDetails.bind(this);
        window.cancelOrder = this.cancelOrder.bind(this);
        window.reorderItems = this.reorderItems.bind(this);
    }

    initializeTooltips() {
        // Initialize Bootstrap tooltips if any exist
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    async viewOrderDetails(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        const modalContent = document.getElementById('orderDetailsContent');
        
        // Show loading state
        modalContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading order details...</p>
            </div>
        `;
        
        modal.show();

        try {
            const formData = new FormData();
            formData.append('action', 'get_order_details');
            formData.append('order_id', orderId);

            const response = await fetch('orders.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                modalContent.innerHTML = this.renderOrderDetails(data.order, data.items);
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Failed to load order details'}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    An error occurred while loading order details. Please try again.
                </div>
            `;
        }
    }

    renderOrderDetails(order, items) {
        const orderDate = new Date(order.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        let itemsHtml = '';
        let subtotal = 0;

        items.forEach(item => {
            const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
            subtotal += itemTotal;
            
            itemsHtml += `
                <div class="order-item">
                    <img src="Images/${item.image || 'default-product.jpg'}" 
                         alt="${this.escapeHtml(item.name)}" 
                         class="item-image"
                         onerror="this.src='Images/default-product.jpg'">
                    <div class="item-details">
                        <div class="item-name">${this.escapeHtml(item.name)}</div>
                        <div class="item-price">BHD ${parseFloat(item.price).toFixed(3)} each</div>
                    </div>
                    <div class="item-quantity">
                        Qty: ${item.quantity}
                    </div>
                    <div class="item-total">
                        <strong>BHD ${itemTotal.toFixed(3)}</strong>
                    </div>
                </div>
            `;
        });

        return `
            <div class="order-details-header">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-2">
                            <i class="fas fa-receipt me-2"></i>
                            Order #${String(order.id).padStart(6, '0')}
                        </h6>
                        <p class="mb-1">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Date:</strong> ${orderDate}
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Status:</strong> 
                            <span class="status-badge status-${order.status}">
                                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-2">
                            <i class="fas fa-user me-2"></i>
                            Customer Information
                        </h6>
                        <p class="mb-1">
                            <strong>Name:</strong> ${this.escapeHtml(order.Fname || '')} ${this.escapeHtml(order.Lname || '')}
                        </p>
                        ${order.Mobile ? `<p class="mb-1"><strong>Mobile:</strong> ${this.escapeHtml(order.Mobile)}</p>` : ''}
                        ${order.Building || order.Block ? `
                            <p class="mb-0">
                                <strong>Address:</strong> 
                                ${this.escapeHtml(order.Building || '')} ${this.escapeHtml(order.Block || '')}
                            </p>
                        ` : ''}
                    </div>
                </div>
            </div>

            <h6 class="mb-3">
                <i class="fas fa-shopping-bag me-2"></i>
                Order Items (${items.length})
            </h6>
            
            <div class="order-items-list">
                ${itemsHtml}
            </div>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>BHD ${subtotal.toFixed(3)}</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>BHD 0.000</span>
                </div>
                <div class="summary-row">
                    <span><strong>Total:</strong></span>
                    <span><strong>BHD ${parseFloat(order.total).toFixed(3)}</strong></span>
                </div>
            </div>
        `;
    }

    async cancelOrder(orderId) {
        // Show custom confirmation modal
        const confirmed = await this.showCancelConfirmationModal();
        
        if (!confirmed) {
            return;
        }

        // Find the button that was clicked
        const button = document.querySelector(`button[onclick*="cancelOrder(${orderId})"]`);
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<span class="loading-spinner"></span> Cancelling...';
        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'cancel_order');
            formData.append('order_id', orderId);

            const response = await fetch('orders.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Order cancelled successfully', 'success');
                
                // Update the order card status
                const orderCard = button.closest('.order-card');
                const statusBadge = orderCard.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-cancelled';
                statusBadge.textContent = 'Cancelled';
                
                // Remove cancel button and add reorder button
                const actionsContainer = button.closest('.order-actions');
                button.remove();
                
                const reorderBtn = document.createElement('button');
                reorderBtn.className = 'btn btn-outline-success btn-sm';
                reorderBtn.onclick = () => this.reorderItems(orderId);
                reorderBtn.innerHTML = '<i class="fas fa-redo me-1"></i> Reorder';
                actionsContainer.appendChild(reorderBtn);
                
            } else {
                this.showToast(data.message || 'Failed to cancel order', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            this.showToast('An error occurred while cancelling the order', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    async reorderItems(orderId) {
        // Find the button that was clicked
        const button = document.querySelector(`button[onclick*="reorderItems(${orderId})"]`);
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<span class="loading-spinner"></span> Adding to Cart...';
        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('order_id', orderId);

            const response = await fetch('orders.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message, 'success');
                
                // Show cart confirmation modal
                this.showCartConfirmationModal();
            } else {
                this.showToast(data.message || 'Failed to reorder items', 'error');
            }
        } catch (error) {
            console.error('Error reordering items:', error);
            this.showToast('An error occurred while reordering items', 'error');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    showToast(message, type = 'info') {
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

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }

    showCartConfirmationModal() {
        // Create modal overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'cart-confirmation-overlay';
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-out;
        `;
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'cart-confirmation-modal';
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease-out;
            position: relative;
        `;
        
        modalContent.innerHTML = `
            <button class="close-btn" onclick="this.closest('.cart-confirmation-overlay').remove()" style="
                position: absolute;
                top: 15px;
                right: 20px;
                background: none;
                border: none;
                font-size: 24px;
                color: #6b7280;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
            " onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#374151';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#6b7280';">
                <i class="fas fa-times"></i>
            </button>
            <div class="success-icon mb-4">
                <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981;"></i>
            </div>
            <h4 class="mb-3">Items Added to Cart!</h4>
            <p class="text-muted mb-4">Your items have been successfully added to your cart.</p>
            <div class="d-flex gap-3 justify-content-center">
                <button class="btn btn-outline-secondary" onclick="this.closest('.cart-confirmation-overlay').remove()">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Continue Shopping
                </button>
                <button class="btn btn-primary" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart me-2"></i>
                    View Cart
                </button>
            </div>
        `;
        
        modalOverlay.appendChild(modalContent);
        document.body.appendChild(modalOverlay);
        
        // Add CSS animations if not already present
        if (!document.querySelector('#cart-modal-animations')) {
            const style = document.createElement('style');
            style.id = 'cart-modal-animations';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideInUp {
                    from {
                        transform: translateY(50px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
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
        
        // Close modal when clicking outside
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.remove();
            }
        });
        
        // Modal will stay open until user chooses an action or clicks the close button
    }

    showCancelConfirmationModal() {
        return new Promise((resolve) => {
            // Create modal overlay
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'cancel-confirmation-overlay';
            modalOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease-out;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'cancel-confirmation-modal';
            modalContent.style.cssText = `
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                animation: slideInUp 0.3s ease-out;
                position: relative;
            `;
            
            const handleClose = (result) => {
                modalOverlay.remove();
                resolve(result);
            };
            
            modalContent.innerHTML = `
                <button class="close-btn" style="
                    position: absolute;
                    top: 15px;
                    right: 20px;
                    background: none;
                    border: none;
                    font-size: 24px;
                    color: #6b7280;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                " onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#374151';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#6b7280';">
                    <i class="fas fa-times"></i>
                </button>
                <div class="warning-icon mb-4">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b;"></i>
                </div>
                <h4 class="mb-3">Cancel Order</h4>
                <p class="text-muted mb-4">Are you sure you want to cancel this order? This action cannot be undone.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button class="btn btn-outline-secondary" id="keepOrderBtn">
                        <i class="fas fa-times me-2"></i>
                        No, Keep Order
                    </button>
                    <button class="btn btn-danger" id="confirmCancelBtn">
                        <i class="fas fa-check me-2"></i>
                        Yes, Cancel Order
                    </button>
                </div>
            `;
            
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);
            
            // Add event listeners
            modalContent.querySelector('.close-btn').addEventListener('click', () => handleClose(false));
            modalContent.querySelector('#keepOrderBtn').addEventListener('click', () => handleClose(false));
            modalContent.querySelector('#confirmCancelBtn').addEventListener('click', () => handleClose(true));
            
            // Close modal when clicking outside
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    handleClose(false);
                }
            });
        });
    }

    // Utility function to format currency
    formatCurrency(amount) {
        return `BHD ${parseFloat(amount).toFixed(3)}`;
    }

    // Utility function to format date
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Initialize the orders manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new OrdersManager();
    
    // Add smooth scrolling for any anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add loading states to pagination links
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function() {
            if (!this.closest('.page-item').classList.contains('active')) {
                this.innerHTML = '<span class="loading-spinner"></span>';
            }
        });
    });
});

// Add some utility functions to the global scope
window.OrdersUtils = {
    formatCurrency: function(amount) {
        return `BHD ${parseFloat(amount).toFixed(3)}`;
    },
    
    formatDate: function(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    getStatusColor: function(status) {
        const colors = {
            'pending': '#f59e0b',
            'processing': '#3b82f6',
            'shipped': '#8b5cf6',
            'delivered': '#10b981',
            'cancelled': '#ef4444'
        };
        return colors[status] || '#6b7280';
    }
};