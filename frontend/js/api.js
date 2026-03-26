const API_BASE_URL = 'http://localhost:8000/api';

class API {
    static getAuthHeaders() {
        const token = localStorage.getItem('auth_token');
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }

    static async request(endpoint, options = {}) {
        const headers = { ...this.getAuthHeaders(), ...(options.headers || {}) };
        
        // Don't set Content-Type if uploading FormData (browser handles boundaries)
        if (!(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers
            });

            const data = await response.json().catch(() => ({}));
            
            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (err) {
            UI.showToast(err.message, 'error');
            throw err;
        }
    }
}

class UI {
    static showToast(message, type = 'success') {
        let toast = document.getElementById('global-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'global-toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.className = `toast show ${type}`;
        
        setTimeout(() => {
            toast.className = 'toast';
        }, 3500);
    }
}
