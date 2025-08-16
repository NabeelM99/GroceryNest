// Show notification toast
function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const messageEl = document.getElementById('toastMessage');
    
    // Set message
    messageEl.textContent = message;
    
    // Set color based on type
    toast.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning');
    switch(type) {
        case 'success':
            toast.classList.add('bg-success');
            break;
        case 'error':
            toast.classList.add('bg-danger');
            break;
        case 'info':
            toast.classList.add('bg-info');
            break;
        case 'warning':
            toast.classList.add('bg-warning');
            break;
    }
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    bsToast.show();
}

// Check for message in URL and show notification
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('message') && urlParams.has('type')) {
    showNotification(
        decodeURIComponent(urlParams.get('message')),
        urlParams.get('type')
    );
}

// --- Animation helpers (from new code) ---
// Ripple effect for tab switching
function addRippleEffect(e, el) {
    const ripple = document.createElement('span');
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'rgba(255, 255, 255, 0.3)';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'ripple 0.6s linear';
    ripple.style.left = e.clientX - el.offsetLeft + 'px';
    ripple.style.top = e.clientY - el.offsetTop + 'px';
    ripple.style.width = ripple.style.height = '20px';
    el.style.position = 'relative';
    el.style.overflow = 'hidden';
    el.appendChild(ripple);
    setTimeout(() => { ripple.remove(); }, 1000);
}
// Card/counter/product fade-in (from new code)
document.addEventListener('DOMContentLoaded', function() {
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
    const counters = document.querySelectorAll('.counter');
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    };
    const observerOptions = { threshold: 0.5, rootMargin: '0px 0px -100px 0px' };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    counters.forEach(counter => { observer.observe(counter); });
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
// Add ripple effect CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to { transform: scale(4); opacity: 0; }
    }
    .tab-content-section { transition: all 0.3s ease; }
    .modal-content { transition: all 0.3s ease; }
`;
document.head.appendChild(style);

// --- Old reliable logic for admin actions ---
// Tab switching (with ripple, but old logic for show/hide)
document.querySelectorAll('[data-tab]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        addRippleEffect(e, this);
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content-section').forEach(section => {
            section.style.display = 'none';
        });
        const targetTab = this.getAttribute('data-tab');
        const targetContent = document.getElementById(targetTab + '-content');
        if (targetContent) {
            targetContent.style.display = 'block';
        }
    });
});
// Edit product functionality (old logic)
function editProduct(productData) {
    document.getElementById('edit_product_id').value = productData.id;
    document.getElementById('edit_name').value = productData.name || '';
    document.getElementById('edit_description').value = productData.description || '';
    document.getElementById('edit_price').value = productData.price || '';
    document.getElementById('edit_category_id').value = productData.category_id || '';
    document.getElementById('edit_inStock').checked = productData.inStock == 1;
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    modal.show();
}
// Delete product functionality (old logic)
function deleteProduct(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="product_id" value="${id}">
            <input type="hidden" name="current_tab" value="products">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
// Delete category functionality (old logic)
function deleteCategory(id, name) {
    if (confirm(`Are you sure you want to delete category "${name}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="${id}">
            <input type="hidden" name="current_tab" value="categories">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
// Update order status functionality with modal confirmation
function updateOrderStatus(orderId, status) {
    const statusNames = {
        'processing': 'Processing',
        'shipped': 'Shipped',
        'delivered': 'Delivered',
        'cancelled': 'Cancelled'
    };

    const statusIcons = {
        'processing': 'fas fa-cog',
        'shipped': 'fas fa-shipping-fast',
        'delivered': 'fas fa-check-circle',
        'cancelled': 'fas fa-times-circle'
    };

    const statusColors = {
        'processing': 'warning',
        'shipped': 'info',
        'delivered': 'success',
        'cancelled': 'danger'
    };

    // Create confirmation modal
    const confirmModal = document.createElement('div');
    confirmModal.className = 'modal fade';
    confirmModal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="${statusIcons[status]} me-2 text-${statusColors[status]}"></i>
                        Update Order Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="${statusIcons[status]} fa-3x text-${statusColors[status]}"></i>
                        </div>
                        <h4>Update Order Status?</h4>
                        <p class="mb-0">Are you sure you want to update order</p>
                        <p class="fw-bold">#${String(orderId).padStart(6, '0')}</p>
                        <p>to <span class="badge bg-${statusColors[status]}">${statusNames[status]}</span>?</p>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-${statusColors[status]}" onclick="confirmStatusUpdate(${orderId}, '${status}')">
                        <i class="${statusIcons[status]} me-2"></i>Update Status
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(confirmModal);
    const modal = new bootstrap.Modal(confirmModal);
    modal.show();

    // Clean up modal when it's closed
    confirmModal.addEventListener('hidden.bs.modal', function () {
        confirmModal.remove();
    });
}

function confirmStatusUpdate(orderId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_order_status">
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="status" value="${status}">
        <input type="hidden" name="current_tab" value="orders">
    `;
    document.body.appendChild(form);
    
    // Create success alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
    alert.style.zIndex = '2000';
    alert.style.minWidth = '300px';
    alert.style.opacity = '0';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Order ${status} successfully
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '1';
    }, 10);
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => { alert.remove(); }, 500);
    }, 2500);
    
    form.submit();
}
// View order details functionality
function viewOrderDetails(orderId) {
    // Create a modal to show order details
    const orderModal = document.createElement('div');
    orderModal.className = 'modal fade';
    orderModal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        Order Details #${String(orderId).padStart(6, '0')}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(orderModal);
    const modal = new bootstrap.Modal(orderModal);
    modal.show();
    
    // Fetch order details
    const formData = new FormData();
    formData.append('action', 'get_order_details');
    formData.append('order_id', orderId);

    fetch('orders.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalBody = orderModal.querySelector('.modal-body');
                const orderDate = new Date(data.order.created_at).toLocaleString();
                
                modalBody.innerHTML = `
                    <div class="order-details">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Customer:</strong> ${data.order.Fname} ${data.order.Lname}</p>
                                <p><strong>Contact:</strong> ${data.order.Mobile}</p>
                                <p><strong>Address:</strong> ${data.order.Building}, Block ${data.order.Block}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Order Date:</strong> ${orderDate}</p>
                                <p><strong>Payment Method:</strong> 
                                    <span class="badge bg-info">${data.order.payment_method}</span>
                                </p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-${getStatusColor(data.order.status)}">${data.order.status}</span>
                                </p>
                            </div>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.items.map(item => `
                                        <tr>
                                            <td>${item.name}</td>
                                            <td>BHD ${Number(item.price).toFixed(3)}</td>
                                            <td>${item.quantity}</td>
                                            <td>BHD ${(item.price * item.quantity).toFixed(3)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-md-6 ms-auto">
                                <table class="table table-bordered">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-end">BHD ${Number(data.order.subtotal).toFixed(3)}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax:</strong></td>
                                        <td class="text-end">BHD ${Number(data.order.tax_amount).toFixed(3)}</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-end"><strong>BHD ${Number(data.order.total).toFixed(3)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const modalBody = orderModal.querySelector('.modal-body');
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Failed to load order details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const modalBody = orderModal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    An error occurred while loading order details.
                </div>
            `;
        });

    // Clean up modal when it's closed
    orderModal.addEventListener('hidden.bs.modal', function () {
        orderModal.remove();
    });
}

function getStatusColor(status) {
    const statusColors = {
        'pending': 'warning',
        'processing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return statusColors[status] || 'secondary';
}

// View message functionality
function viewMessage(messageId) {
    // Create a modal to show message details
    const messageModal = document.createElement('div');
    messageModal.className = 'modal fade';
    messageModal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="loading text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading message...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(messageModal);
    const modal = new bootstrap.Modal(messageModal);
    modal.show();
    
    // Fetch message details via AJAX
    fetch(`admin_dashboard.php?action=get_message&id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            const modalBody = messageModal.querySelector('.modal-body');
            if (data.error) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${data.error}
                    </div>
                `;
            } else {
                const messageDate = new Date(data.created_at).toLocaleString();
                modalBody.innerHTML = `
                    <div class="message-details">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>From:</strong> ${data.name}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date:</strong> ${messageDate}</p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-success">Read</span>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p><strong>Subject:</strong></p>
                            <p class="text-primary fw-bold">${data.subject}</p>
                        </div>
                        <div class="mb-3">
                            <p><strong>Message:</strong></p>
                            <div class="message-content p-3 bg-light rounded">
                                <p style="white-space: pre-wrap;">${data.message}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            const modalBody = messageModal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading message: ${error.message}
                </div>
            `;
        });
    
    // Remove modal after it's hidden
    messageModal.addEventListener('hidden.bs.modal', () => {
        messageModal.remove();
    });
}

// Mark message as read functionality
function markMessageAsRead(messageId) {
    const confirmModal = document.createElement('div');
    confirmModal.className = 'modal fade';
    confirmModal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2 text-success"></i>
                        Mark Message as Read
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-envelope-open fa-3x text-success"></i>
                        </div>
                        <h4>Mark Message as Read?</h4>
                        <p>This message will be marked as read in your inbox.</p>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmMarkAsRead(${messageId})">
                        <i class="fas fa-check me-2"></i>Mark as Read
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(confirmModal);
    const modal = new bootstrap.Modal(confirmModal);
    modal.show();

    confirmModal.addEventListener('hidden.bs.modal', function () {
        confirmModal.remove();
    });
}

function confirmMarkAsRead(messageId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="mark_message_read">
        <input type="hidden" name="message_id" value="${messageId}">
        <input type="hidden" name="current_tab" value="messages">
    `;
    document.body.appendChild(form);
    
    // Create success alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
    alert.style.zIndex = '2000';
    alert.style.minWidth = '300px';
    alert.style.opacity = '0';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Message marked as read successfully
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '1';
    }, 10);
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => { alert.remove(); }, 500);
    }, 2500);
    
    form.submit();
}

// Delete message functionality
function deleteMessage(messageId) {
    const confirmModal = document.createElement('div');
    confirmModal.className = 'modal fade';
    confirmModal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2 text-danger"></i>
                        Delete Message
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                        </div>
                        <h4>Delete Message?</h4>
                        <p>Are you sure you want to delete this message?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteMessage(${messageId})">
                        <i class="fas fa-trash me-2"></i>Delete Message
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(confirmModal);
    const modal = new bootstrap.Modal(confirmModal);
    modal.show();

    confirmModal.addEventListener('hidden.bs.modal', function () {
        confirmModal.remove();
    });
}

function confirmDeleteMessage(messageId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete_message">
        <input type="hidden" name="message_id" value="${messageId}">
        <input type="hidden" name="current_tab" value="messages">
    `;
    document.body.appendChild(form);
    
    // Create success alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 fade show';
    alert.style.zIndex = '2000';
    alert.style.minWidth = '300px';
    alert.style.opacity = '0';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Message deleted successfully
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '1';
    }, 10);
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => { alert.remove(); }, 500);
    }, 2500);
    
    form.submit();
}
// Image preview functionality (old logic)
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
// Initialize on page load (old logic + new animations)
document.addEventListener('DOMContentLoaded', function() {
    // Tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && tabParam !== 'dashboard') {
        document.querySelectorAll('.tab-content-section').forEach(section => {
            section.style.display = 'none';
        });
        const targetContent = document.getElementById(tabParam + '-content');
        if (targetContent) {
            targetContent.style.display = 'block';
        }
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        const activeLink = document.querySelector(`[data-tab="${tabParam}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    } else {
        document.getElementById('dashboard-content').style.display = 'block';
    }
    // Add edit product event listeners
    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productData = JSON.parse(this.getAttribute('data-product'));
            editProduct(productData);
        });
    });
    // Add image preview listeners
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            if (previewId) {
                previewImage(this, previewId);
            }
        });
    });
    // Auto-hide alerts after 5 seconds (old logic, but with fade)
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// --- Add animation for Add/Update Product ---
function showAnimatedAlert(message, type = 'success') {
    // Remove any existing alert
    const oldAlert = document.getElementById('animated-alert');
    if (oldAlert) oldAlert.remove();
    // Create alert
    const alert = document.createElement('div');
    alert.id = 'animated-alert';
    alert.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 fade show`;
    alert.style.zIndex = 2000;
    alert.style.minWidth = '300px';
    alert.style.opacity = '0';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(alert);
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '1';
    }, 10);
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => { alert.remove(); }, 500);
    }, 2500);
}

// Intercept Add/Update Product form submit for animation
document.addEventListener('DOMContentLoaded', function() {
    // Add Product
    const addProductForm = document.querySelector('#addProductModal form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            const submitBtn = addProductForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            // Fade out modal after short delay
            setTimeout(() => {
                const modalEl = document.getElementById('addProductModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                showAnimatedAlert('Product added successfully!', 'success');
            }, 600);
            // Let the form submit as normal (PHP will redirect)
        });
    }
    // Update Product
    const editProductForm = document.querySelector('#editProductModal form');
    if (editProductForm) {
        editProductForm.addEventListener('submit', function(e) {
            const submitBtn = editProductForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            // Fade out modal after short delay
            setTimeout(() => {
                const modalEl = document.getElementById('editProductModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                showAnimatedAlert('Product updated successfully!', 'success');
            }, 600);
            // Let the form submit as normal (PHP will redirect)
        });
    }
});