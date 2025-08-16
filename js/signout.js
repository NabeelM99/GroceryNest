function showSignoutModal() {
    const overlay = document.getElementById('signoutOverlay');
    const modal = document.getElementById('signoutModal');
    document.body.classList.add('modal-active');
    
    // Show overlay
    overlay.style.display = 'flex';
    
    // Trigger animations after a small delay
    setTimeout(() => {
        overlay.classList.add('show');
        setTimeout(() => {
            modal.classList.add('show');
        }, 100);
    }, 10);
    
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
}

function hideSignoutModal() {
    const overlay = document.getElementById('signoutOverlay');
    const modal = document.getElementById('signoutModal');
    
    // Reverse animations
    modal.classList.remove('show');
    setTimeout(() => {
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.classList.remove('modal-active');
            document.body.style.overflow = '';
        }, 300);
    }, 200);
}

async function confirmSignout() {
    const signoutBtn = document.getElementById('signoutBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const signoutIcon = document.getElementById('signoutIcon');
    const signoutText = document.getElementById('signoutText');
    
    // Show loading state
    signoutBtn.disabled = true;
    loadingSpinner.style.display = 'inline-block';
    signoutIcon.style.display = 'none';
    signoutText.textContent = 'Signing out...';
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=signout'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success state
            signoutText.textContent = 'Signed out!';
            loadingSpinner.style.display = 'none';
            signoutIcon.style.display = 'inline';
            signoutIcon.className = 'fas fa-check me-2';
            signoutBtn.classList.remove('btn-signout-confirm');
            signoutBtn.classList.add('btn-success');
            
            // Redirect after short delay
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1500);
        } else {
            throw new Error('Signout failed');
        }
    } catch (error) {
        // Show error state
        signoutText.textContent = 'Error occurred';
        loadingSpinner.style.display = 'none';
        signoutIcon.style.display = 'inline';
        signoutIcon.className = 'fas fa-exclamation-triangle me-2';
        signoutBtn.classList.remove('btn-signout-confirm');
        signoutBtn.classList.add('btn-warning');
        
        // Reset after delay
        setTimeout(() => {
            signoutBtn.disabled = false;
            signoutIcon.className = 'fas fa-sign-out-alt me-2';
            signoutText.textContent = 'Yes, Sign Out';
            signoutBtn.classList.remove('btn-warning');
            signoutBtn.classList.add('btn-signout-confirm');
        }, 2000);
    }
}

// Close modal when clicking outside
document.getElementById('signoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideSignoutModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('signoutOverlay').classList.contains('show')) {
        hideSignoutModal();
    }
});