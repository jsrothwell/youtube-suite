/**
 * YouTube Suite - Social Sharing JavaScript
 */

// Open share window
function ytsShareWindow(element) {
    const url = element.getAttribute('href');
    const network = element.getAttribute('data-network');
    
    if (network === 'email') {
        return true; // Let email links work normally
    }
    
    // Open popup window
    const width = 600;
    const height = 480;
    const left = (window.innerWidth - width) / 2;
    const top = (window.innerHeight - height) / 2;
    
    window.open(
        url,
        'share-dialog',
        `width=${width},height=${height},left=${left},top=${top},toolbar=0,status=0,resizable=1`
    );
    
    // Track the share
    ytsTrackShare(network, element);
    
    // Add shared animation
    element.classList.add('yts-shared');
    setTimeout(() => element.classList.remove('yts-shared'), 500);
    
    return false;
}

// Copy link to clipboard
function ytsShareCopyLink(element) {
    const url = window.location.href;
    
    // Modern clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            ytsShowCopySuccess();
            ytsTrackShare('copy', element);
        }).catch(err => {
            ytsLegacyCopyLink(url);
        });
    } else {
        ytsLegacyCopyLink(url);
    }
    
    return false;
}

// Legacy copy method for older browsers
function ytsLegacyCopyLink(url) {
    const tempInput = document.createElement('input');
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        ytsShowCopySuccess();
    } catch (err) {
        alert('Failed to copy link');
    }
    
    document.body.removeChild(tempInput);
}

// Show copy success message
function ytsShowCopySuccess() {
    const message = document.createElement('div');
    message.className = 'yts-copy-success';
    message.textContent = '✓ Link copied to clipboard!';
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    }, 2000);
}

// Track share via AJAX
function ytsTrackShare(network, element) {
    if (!window.ytsData) return;
    
    const postId = ytsData.postId || 0;
    
    fetch(ytsData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'yts_track_share',
            nonce: ytsData.nonce,
            network: network,
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.count) {
            // Update the share count display
            const countElement = element.querySelector('.yts-share-count');
            if (countElement) {
                countElement.textContent = ytsFormatCount(data.data.count);
            } else if (data.data.count > 0) {
                // Add count if it didn't exist before
                const span = document.createElement('span');
                span.className = 'yts-share-count';
                span.textContent = ytsFormatCount(data.data.count);
                element.appendChild(span);
            }
        }
    })
    .catch(error => console.error('Share tracking error:', error));
}

// Format count (1k, 1.5k, etc.)
function ytsFormatCount(count) {
    if (count < 1000) {
        return count;
    } else if (count < 1000000) {
        return Math.round(count / 100) / 10 + 'k';
    } else {
        return Math.round(count / 100000) / 10 + 'M';
    }
}

// Hide/show floating bar on scroll
document.addEventListener('DOMContentLoaded', function() {
    const floatingBar = document.querySelector('.yts-floating-share-bar');
    
    if (!floatingBar) return;
    
    let lastScroll = 0;
    let ticking = false;
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                const currentScroll = window.pageYOffset;
                
                // Show bar only after scrolling down 100px
                if (currentScroll > 100) {
                    floatingBar.style.opacity = '1';
                    floatingBar.style.pointerEvents = 'auto';
                } else {
                    floatingBar.style.opacity = '0';
                    floatingBar.style.pointerEvents = 'none';
                }
                
                lastScroll = currentScroll;
                ticking = false;
            });
            
            ticking = true;
        }
    });
});

// Mobile share button handling
document.addEventListener('DOMContentLoaded', function() {
    // Add mobile-specific share handling if Web Share API is available
    if (navigator.share) {
        const shareButtons = document.querySelectorAll('.yts-share-button');
        
        shareButtons.forEach(button => {
            if (window.innerWidth <= 768) {
                button.addEventListener('click', function(e) {
                    const network = this.getAttribute('data-network');
                    
                    // Use native share for certain networks on mobile
                    if (network === 'copy' || network === 'email') {
                        return; // Let default handlers work
                    }
                    
                    e.preventDefault();
                    
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    }).then(() => {
                        ytsTrackShare(network, this);
                    }).catch(err => {
                        // Fallback to popup if native share fails
                        ytsShareWindow(this);
                    });
                    
                    return false;
                });
            }
        });
    }
});

// Click to Tweet tracking
document.addEventListener('DOMContentLoaded', function() {
    const cttButtons = document.querySelectorAll('.yts-ctt-button');
    
    cttButtons.forEach(button => {
        button.addEventListener('click', function() {
            ytsTrackShare('twitter', this);
        });
    });
});
