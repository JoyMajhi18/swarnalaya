document.addEventListener('DOMContentLoaded', () => {
    Auth.requireAuth('user');
    loadCart();
});

async function loadCart() {
    const list = document.getElementById('cart-list');
    try {
        const res = await API.request('/cart');
        if (res.status === 'success') {
            document.getElementById('subtotal').textContent = `$${res.cart_subtotal.toFixed(2)}`;
            
            if (res.data.length === 0) {
                list.innerHTML = `<p style="color:var(--text-muted)">Your cart is currently empty. Head over to our catalog to discover our elegant pieces.</p>`;
                document.getElementById('checkout-btn').disabled = true;
                document.getElementById('checkout-btn').style.opacity = '0.5';
                return;
            }
            
            list.innerHTML = res.data.map(item => `
                <div class="cart-item">
                    <div>
                        <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem">${item.product_name}</h4>
                        <div style="color: var(--text-muted)">$${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="updateQty(${item.cart_item_id}, ${item.quantity - 1})">-</button>
                        <span style="min-width: 20px; text-align: center;">${item.quantity}</span>
                        <button class="qty-btn" onclick="updateQty(${item.cart_item_id}, ${item.quantity + 1})">+</button>
                    </div>
                    <div>
                        <div style="font-weight: bold; font-size: 1.2rem; color: var(--primary)">$${item.total_item_price.toFixed(2)}</div>
                        <button class="remove-btn" onclick="removeObj(${item.cart_item_id})">Remove</button>
                    </div>
                </div>
            `).join('');
            document.getElementById('checkout-btn').disabled = false;
            document.getElementById('checkout-btn').style.opacity = '1';
        }
    } catch (err) {
        list.innerHTML = `<p style="color:var(--danger)">Failed to load cart.</p>`;
    }
}

async function updateQty(cartItemId, newQty) {
    if (newQty < 1) return removeObj(cartItemId);
    try {
        await API.request(`/cart/${cartItemId}`, {
            method: 'PUT', body: JSON.stringify({ quantity: newQty })
        });
        loadCart();
    } catch (err) {}
}

async function removeObj(cartItemId) {
    try {
        await API.request(`/cart/${cartItemId}`, { method: 'DELETE' });
        loadCart();
        UI.showToast('Item removed', 'success');
    } catch (err) {}
}

async function handleCheckout(e) {
    e.preventDefault();
    const btn = document.getElementById('checkout-btn');
    btn.textContent = 'Processing...';
    btn.disabled = true;

    try {
        const res = await API.request('/orders/checkout', {
            method: 'POST',
            body: JSON.stringify({
                address: document.getElementById('address').value,
                payment_method: document.getElementById('payment-method').value
            })
        });
        if(res.status === 'success') {
            UI.showToast('Purchase Successful! Redirecting...', 'success');
            setTimeout(() => window.location.href = 'orders.html', 2000);
        }
    } catch(err) {
        btn.textContent = 'Complete Purchase';
        btn.disabled = false;
    }
}
