class Auth {
    static async login(email, password, isAdmin = false) {
        const endpoint = isAdmin ? '/auth/admin_login' : '/auth/login';
        const payload = isAdmin ? { username: email, password } : { email, password };
        
        const data = await API.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (data.status === 'success') {
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('user_role', data.role);
            return data;
        }
        throw new Error(data.message);
    }

    static async register(name, email, password, phone, address) {
        return await API.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify({ name, email, password, phone, address })
        });
    }

    static async logout() {
        try {
            await API.request('/auth/logout', { method: 'POST' });
        } catch (err) {
            // best-effort logout even if server fails
        }
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_role');
        window.location.href = 'index.html';
    }

    static isLoggedIn() {
        return !!localStorage.getItem('auth_token');
    }

    static getRole() {
        return localStorage.getItem('user_role');
    }

    static redirectIfAuthenticated() {
        if (this.isLoggedIn()) {
            window.location.href = 'index.html';
        }
    }
    
    static requireAuth(role = null) {
        if (!this.isLoggedIn()) {
            window.location.href = 'login.html';
            return false;
        }
        if (role && this.getRole() !== role) {
            window.location.href = 'index.html';
            return false;
        }
        return true;
    }
}
