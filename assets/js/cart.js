/* Global cart helpers — loaded on every page via footer.php */
(function () {
    'use strict';

    /* ── Toast notification ───────────────────────────────── */
    function showCartToast(message, type) {
        var existing = document.getElementById('cart-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:99999',
            'background:' + (type === 'error' ? '#e74c3c' : '#1d2d44'),
            'color:#fff', 'padding:12px 20px', 'border-radius:8px',
            'font-size:14px', 'font-weight:500', 'box-shadow:0 4px 16px rgba(0,0,0,.2)',
            'display:flex', 'align-items:center', 'gap:10px',
            'transform:translateY(20px)', 'opacity:0',
            'transition:all .3s ease', 'max-width:320px'
        ].join(';');

        var icon = type === 'error' ? '✕' : '✓';
        toast.innerHTML = '<span style="font-size:16px">' + icon + '</span><span>' + message + '</span>';
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        setTimeout(function () {
            toast.style.transform = 'translateY(20px)';
            toast.style.opacity = '0';
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
        }, 3000);
    }

    /* ── Update cart badge count ──────────────────────────── */
    function updateCartCount(count) {
        var badges = document.querySelectorAll('.cart-count');
        badges.forEach(function (b) { b.textContent = count; });
    }

    /* ── Fetch current count on page load ────────────────── */
    function fetchCartCount() {
        var base = (window.SITE_BASE || '');
        fetch(base + 'ajax/cart-count.php')
            .then(function (r) { return r.json(); })
            .then(function (data) { updateCartCount(data.count || 0); })
            .catch(function () {});
    }

    /* ── Add to cart (quick add from listing pages) ───────── */
    window.addToCartDirect = function (productId, qty, btn) {
        qty = qty || 1;
        var base = (window.SITE_BASE || '');
        var form = new FormData();
        form.append('product_id', productId);
        form.append('quantity', qty);

        if (btn) {
            btn.disabled = true;
            btn.dataset.origText = btn.innerHTML;
            btn.innerHTML = '...';
        }

        fetch(base + 'ajax/add-to-cart.php', { method: 'POST', body: form })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showCartToast(res.message, 'success');
                    updateCartCount(res.cart_count);
                } else {
                    showCartToast(res.message || 'Error adding to cart', 'error');
                }
            })
            .catch(function () {
                showCartToast('Something went wrong. Please try again.', 'error');
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.origText || 'Add To Cart';
                }
            });
    };

    /* ── Update wishlist badge count ─────────────────────── */
    function updateWishlistCount(count) {
        var badges = document.querySelectorAll('.wishlist-count');
        badges.forEach(function (b) { b.textContent = count; });
    }

    /* ── Fetch wishlist IDs for current visitor ──────────── */
    function fetchWishlistIds(callback) {
        var base = (window.SITE_BASE || '');
        fetch(base + 'ajax/wishlist-ids.php')
            .then(function (r) { return r.json(); })
            .then(function (data) { if (callback) callback(data.ids || []); })
            .catch(function () { if (callback) callback([]); });
    }

    /* ── Toggle wishlist (add / remove) ──────────────────── */
    window.toggleWishlist = function (productId, el) {
        var base = (window.SITE_BASE || '');
        var form = new FormData();
        form.append('product_id', productId);

        fetch(base + 'ajax/wishlist-toggle.php', { method: 'POST', body: form })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    showCartToast(res.message, 'success');
                    updateWishlistCount(res.count);
                    // Toggle heart fill state
                    if (el) {
                        var icon = el.querySelector ? el.querySelector('i') : (el.tagName === 'I' ? el : null);
                        if (!icon && el.closest) {
                            var parent = el.closest('.wishlist-btn, [onclick]');
                            if (parent) icon = parent.querySelector('i');
                        }
                        if (icon) {
                            if (res.added) {
                                icon.classList.remove('bi-heart');
                                icon.classList.add('bi-heart-fill');
                                icon.style.color = '#e74c3c';
                            } else {
                                icon.classList.remove('bi-heart-fill');
                                icon.classList.add('bi-heart');
                                icon.style.color = '';
                            }
                        }
                    }
                } else {
                    showCartToast(res.message || 'Error updating wishlist', 'error');
                }
            })
            .catch(function () {
                showCartToast('Something went wrong. Please try again.', 'error');
            });
    };

    /* ── Fetch current wishlist count on page load ───────── */
    function fetchWishlistCount() {
        var base = (window.SITE_BASE || '');
        fetch(base + 'ajax/wishlist-count.php')
            .then(function (r) { return r.json(); })
            .then(function (data) { updateWishlistCount(data.count || 0); })
            .catch(function () {});
    }

    /* ── Expose helpers globally ──────────────────────────── */
    window.showCartToast      = showCartToast;
    window.updateCartCount    = updateCartCount;
    window.updateWishlistCount = updateWishlistCount;
    window.fetchWishlistIds   = fetchWishlistIds;

    /* ── Init on DOM ready ────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            fetchCartCount();
            fetchWishlistCount();
        });
    } else {
        fetchCartCount();
        fetchWishlistCount();
    }
})();
