document.addEventListener('DOMContentLoaded', () => {
    Auth.requireAuth('admin');
    loadDashboard();
});

function showSection(name) {
    document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    
    document.getElementById(`section-${name}`).style.display = 'block';
    event.target.classList.add('active');

    if (name === 'orders') {
        loadOrders();
    } else {
        loadDashboard();
    }
}

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
                    <td>#${o.order_id}</td>
                    <td style="color:var(--text-muted)">${new Date(o.order_date).toLocaleDateString()}</td>
                    <td><span class="badge">${o.payment_status || 'Pending'}</span></td>
                    <td style="font-weight: bold; color: var(--primary)">$${o.total_amount.toFixed(2)}</td>
                </tr>
            `).join('');
            
            document.getElementById('recent-orders-list').innerHTML = ordersHtml || `<tr><td colspan="4" style="color:var(--text-muted); text-align:center">No recent transactions.</td></tr>`;
            
            loadCatalog();
        }
    } catch (err) {
        UI.showToast(`Dashboard Sync Failed: ${err.message}`, 'error');
    }
}

async function loadOrders() {
    const list = document.getElementById('full-orders-list');
    list.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted)">Synchronizing database...</td></tr>';
    
    try {
        const res = await API.request('/admin/orders');
        if (res.status === 'success') {
            const orders = res.data;
            if (orders.length === 0) {
                list.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--text-muted)">No orders found in database.</td></tr>';
                return;
            }

            list.innerHTML = orders.map(o => `
                <tr>
                    <td>#${o.order_id}</td>
                    <td>
                        <div style="font-weight:500">${o.customer_name}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted)">ID: ${o.user_id}</div>
                    </td>
                    <td>${o.product_name}</td>
                    <td>${o.quantity}</td>
                    <td style="color:var(--primary); font-weight:bold">$${parseFloat(o.total_amount).toFixed(2)}</td>
                    <td style="font-size:0.85rem">${new Date(o.date).toLocaleDateString()}</td>
                    <td>
                        <select class="status-select" onchange="updatePaymentStatus(${o.order_id}, this.value)">
                            <option value="Pending" ${o.payment_status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Paid" ${o.payment_status === 'Paid' ? 'selected' : ''}>Paid</option>
                            <option value="Failed" ${o.payment_status === 'Failed' ? 'selected' : ''}>Failed</option>
                        </select>
                    </td>
                    <td>
                        <select class="status-select" onchange="updateOrderStatus(${o.order_id}, this.value)">
                            <option value="Processing" ${o.order_status === 'Processing' ? 'selected' : ''}>Processing</option>
                            <option value="Shipped" ${o.order_status === 'Shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="Delivered" ${o.order_status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="Cancelled" ${o.order_status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                </tr>
            `).join('');
        }
    } catch (err) {
        list.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--danger)">Failed to load orders.</td></tr>';
    }
}

async function updateOrderStatus(id, status) {
    try {
        await API.request(`/admin/orders/${id}/order_status`, {
            method: 'PUT',
            body: JSON.stringify({ order_status: status })
        });
        UI.showToast(`Order #${id} status updated to ${status}`, 'success');
    } catch (err) {
        loadOrders(); // Revert on failure
    }
}

async function updatePaymentStatus(id, status) {
    try {
        await API.request(`/admin/orders/${id}/payment_status`, {
            method: 'PUT',
            body: JSON.stringify({ payment_status: status })
        });
        UI.showToast(`Order #${id} payment updated to ${status}`, 'success');
    } catch (err) {
        loadOrders(); // Revert on failure
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
            loadDashboard();
        }
    } catch (err) { }
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
            body: formData
        });
        
        if (res.status === 'success') {
            UI.showToast('Product successfully published to live catalog!', 'success');
            document.getElementById('add-product-form').reset();
            loadDashboard();
        }
    } catch (err) { } finally {
        btn.textContent = 'Publish to Storefront';
        btn.disabled = false;
    }
}
