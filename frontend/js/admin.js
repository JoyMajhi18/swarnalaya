document.addEventListener('DOMContentLoaded', () => {
    Auth.requireAuth('admin');
    loadDashboard();
});

async function loadDashboard() {
    try {
        const res = await API.request('/admin/dashboard');
        if (res.status === 'success') {
            const data = res.data;
            
            // Populate stats
            document.getElementById('stats-container').innerHTML = `
                <div class="stat-card animate-fade"><h3>Gross Revenue</h3><div class="value">$${(data.total_revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</div></div>
                <div class="stat-card animate-fade" style="animation-delay:0.1s"><h3>Total Orders</h3><div class="value">${data.total_orders}</div></div>
                <div class="stat-card animate-fade" style="animation-delay:0.2s"><h3>Catalog Size</h3><div class="value">${data.total_products}</div></div>
                <div class="stat-card animate-fade" style="animation-delay:0.3s"><h3>Registered Customers</h3><div class="value">${data.total_users}</div></div>
            `;
            
            // Populate recent orders
            const ordersHtml = data.recent_orders.map(o => `
                <tr>
                    <td>#${o.id}</td>
                    <td style="color:var(--text-muted)">${new Date(o.order_date).toLocaleDateString()}</td>
                    <td><span class="badge">${o.payment_status || 'Pending'}</span></td>
                    <td style="font-weight: bold; color: var(--primary)">$${o.total_amount.toFixed(2)}</td>
                </tr>
            `).join('');
            
            document.getElementById('recent-orders-list').innerHTML = ordersHtml || `<tr><td colspan="4" style="color:var(--text-muted)">No recent transactions recorded.</td></tr>`;
            
            // Load catalog items into the 4-column grid
            loadCatalog();
        }
    } catch (err) {
        UI.showToast('Failed to sync dashboard intelligence loop.', 'error');
    }
}

async function loadCatalog() {
    const catalogContainer = document.getElementById('admin-catalog');
    try {
        const res = await API.request('/products');
        if (res.status === 'success') {
            const products = res.data;
            if (products.length === 0) {
                catalogContainer.innerHTML = '<p style="color:var(--text-muted); grid-column: 1/-1;">No products found in live catalog.</p>';
                return;
            }

            catalogContainer.innerHTML = products.map(p => `
                <div class="admin-product-card animate-fade">
                    <img src="${p.image_url || 'https://via.placeholder.com/300'}" alt="${p.name}">
                    <h4>${p.name}</h4>
                    <p style="color:var(--text-muted); font-size: 0.8rem; margin-bottom: 0.5rem;">${p.category || 'Uncategorized'}</p>
                    <div class="price">$${p.price.toFixed(2)}</div>
                    <div class="admin-actions">
                        <button class="btn-edit" onclick="UI.showToast('Edit feature coming soon')">Edit</button>
                        <button class="btn-delete" onclick="deleteProduct(${p.id})">Delete</button>
                    </div>
                </div>
            `).join('');
        }
    } catch (err) {
        catalogContainer.innerHTML = '<p style="color:var(--danger); grid-column: 1/-1;">Critical failure during catalog retrieval.</p>';
    }
}

async function deleteProduct(id) {
    if (!confirm('Are you absolutely sure you want to purge this item from the live storefront? This action is irreversible.')) return;
    
    try {
        const res = await API.request(`/admin/products/${id}`, { method: 'DELETE' });
        if (res.status === 'success') {
            UI.showToast('Product purged successfully.', 'success');
            loadDashboard(); // Refresh stats and catalog
        }
    } catch (err) {
        // Error toast handled by API helper
    }
}

async function addProduct(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-add-product');
    btn.textContent = 'Encrypting & Publishing...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('name', document.getElementById('p-name').value);
    formData.append('category', document.getElementById('p-category').value);
    formData.append('price', document.getElementById('p-price').value);
    formData.append('description', document.getElementById('p-desc').value);
    
    const fileInput = document.getElementById('p-image');
    if (fileInput.files.length > 0) {
        formData.append('image', fileInput.files[0]);
    }
    
    try {
        const res = await API.request('/admin/products', {
            method: 'POST',
            body: formData // API limits setting Content-Type magically for FormData payload boundary
        });
        
        if (res.status === 'success') {
            UI.showToast('Product successfully published to live catalog!', 'success');
            document.getElementById('add-product-form').reset();
            loadDashboard(); // Resync stats to show +1 active product
        }
    } catch (err) {
        // UI toast thrown implicitly
    } finally {
        btn.textContent = 'Publish to Storefront';
        btn.disabled = false;
    }
}
