document.addEventListener('DOMContentLoaded', () => {
    initNav();
    runHealthCheck();
    loadProducts();
});

async function runHealthCheck() {
    try {
        const res = await API.request('/health');
        if (res.status === 'success') {
            console.log('API health:', res.services, res.server);
        }
    } catch (err) {
        console.warn('Health check failed:', err.message);
        UI.showToast('Backend health check failed. Please restart the API router.', 'error');
    }
}

function initNav() {
    const navArea = document.getElementById('nav-area');
    if (Auth.isLoggedIn()) {
        const role = Auth.getRole();
        if (role === 'admin') {
            navArea.innerHTML = `
                <a href="admin.html">Dashboard</a>
                <a href="#" onclick="Auth.logout()">Logout</a>
            `;
        } else {
            navArea.innerHTML = `
                <a href="cart.html">My Cart</a>
                <a href="orders.html">Purchases</a>
                <a href="#" onclick="Auth.logout()">Logout</a>
            `;
        }
    } else {
        navArea.innerHTML = `<a href="login.html" class="btn" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;">Sign In</a>`;
    }
}

async function loadProducts() {
    const catalog = document.getElementById('catalog');
    try {
        const res = await API.request('/products');
        if (res.status === 'success') {
            if (res.data.length === 0) {
                catalog.innerHTML = `<h3 style="color:var(--text-muted); grid-column: 1/-1; text-align: center; padding: 4rem;">No products available in the catalog yet.</h3>`;
                return;
            }
            
            catalog.innerHTML = res.data.map((p, i) => `
                <div class="product-card animate-fade" style="animation-delay: ${0.1 * i}s">
                    <img src="${p.image && p.image !== 'null' ? (p.image.startsWith('http') ? p.image : '..' + p.image) : 'https://images.unsplash.com/photo-1596944924616-7b38e7cfac36?auto=format&fit=crop&q=80&w=600'}" class="product-image" alt="${p.name}">
                    <div class="product-info">
                        <div class="product-category">${p.category || 'Jewellery'}</div>
                        <h3 class="product-title">${p.name}</h3>
                        <div class="product-price">$${p.price.toFixed(2)}</div>
                        <button class="btn-add" onclick="addToCart(${p.id})">Add to Cart</button>
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        catalog.innerHTML = `<h3 style="color:var(--danger); grid-column: 1/-1; text-align: center; padding: 4rem;">Failed to load catalog server. Please ensure the backend is running.</h3>`;
    }
}

async function addToCart(productId) {
    if (!Auth.isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }

    try {
        await API.request('/cart', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });
        UI.showToast('Added to cart securely!', 'success');
    } catch (err) {
        // Handled by API class
    }
}
