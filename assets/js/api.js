// API Client - Ajax requests to PHP endpoints

class APIClient {
    constructor() {
        const fallbackBase = () => {
            if (typeof window.getApiBaseUrl === 'function') {
                return window.getApiBaseUrl();
            }
            return (typeof window.API_BASE_URL === 'string' && window.API_BASE_URL.length > 0)
                ? window.API_BASE_URL
                : '/api';
        };

        const deriveBaseFromScript = () => {
            const extractBasePath = (src) => {
                if (!src) return null;

                try {
                    const baseReference = document.baseURI || window.location.href;
                    const url = new URL(src, baseReference);
                    let path = url.pathname.replace(/\\/g, '/');
                    path = path.replace(/\/+/g, '/');
                    path = path.replace(/\/?assets\/js\/[^/]+$/, '');
                    path = path.replace(/\/$/, '');
                    return path;
                } catch (error) {
                    return null;
                }
            };

            if (Object.prototype.hasOwnProperty.call(window, '__APP_SCRIPT_BASE__')) {
                return window.__APP_SCRIPT_BASE__;
            }

            let derived = extractBasePath(document.currentScript && document.currentScript.src);

            if (derived === null) {
                const scripts = document.getElementsByTagName('script');
                for (const script of scripts) {
                    const src = script.src || script.getAttribute('src');
                    if (!src) continue;
                    if (src.includes('assets/js/api.js') || src.includes('assets/js/app.js')) {
                        derived = extractBasePath(src);
                        if (derived !== null) {
                            break;
                        }
                    }
                }
            }

            window.__APP_SCRIPT_BASE__ = derived;
            return derived;
        };

        const resolvedBase = (() => {
            if (typeof window.buildApiUrl === 'function') {
                return window.buildApiUrl('');
            }

            const derived = deriveBaseFromScript();
            if (derived !== null && typeof derived === 'string') {
                const normalized = derived.replace(/\/$/, '');
                return normalized ? `${normalized}/api` : '/api';
            }

            return fallbackBase();
        })();

        this.baseURL = resolvedBase || fallbackBase();
        this.csrfToken = null;
        this.readyPromise = this.refreshCsrfToken();
        this.readyPromise.catch(() => {});
    }

    async refreshCsrfToken() {
        try {
            const response = await fetch(`${this.baseURL}/auth/csrf-token`, {
                method: 'GET',
                credentials: 'include'
            });
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Failed to fetch CSRF token (${response.status}): ${errorText}`);
            }

            const data = await response.json();
            if ((data.success || data.ok) && data.token) {
                this.csrfToken = data.token;
                return this.csrfToken;
            }

            throw new Error('CSRF token was not provided by the server');
        } catch (error) {
            this.csrfToken = null;
            throw error;
        }
    }

    async ensureReady() {
        if (!this.readyPromise) {
            this.readyPromise = this.refreshCsrfToken();
        }

        try {
            await this.readyPromise;
        } catch (error) {
            this.readyPromise = null;
            throw error;
        }

        return this.csrfToken;
    }

    async getCSRFToken() {
        await this.ensureReady();
        return this.csrfToken;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const method = (options.method || 'GET').toUpperCase();
        const requiresCsrf = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

        if (requiresCsrf) {
            try {
                await this.ensureReady();
            } catch (error) {
                throw new Error(`Unable to fetch CSRF token: ${error.message}`);
            }
        }

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken && { 'X-CSRF-Token': this.csrfToken })
            },
            credentials: 'include'
        };

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        if (finalOptions.body instanceof FormData) {
            delete finalOptions.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, finalOptions);
            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : await response.text();

            if (!response.ok) {
                const message = typeof data === 'object' && data !== null
                    ? data.message || data.error || `HTTP error! status: ${response.status}`
                    : `HTTP error! status: ${response.status}`;
                throw new Error(message);
            }

            if (requiresCsrf) {
                this.readyPromise = this.refreshCsrfToken().catch((error) => {
                    console.warn('Failed to refresh CSRF token after request:', error);
                    return null;
                });
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            if (requiresCsrf) {
                this.readyPromise = this.refreshCsrfToken().catch((refreshError) => {
                    console.warn('Failed to refresh CSRF token after error:', refreshError);
                    return null;
                });
            }
            throw error;
        }
    }

    // Restaurant API methods
    async getRestaurants(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `/restaurants?${queryString}` : '/restaurants';
        return await this.request(endpoint);
    }

    async getRestaurant(slug) {
        return await this.request(`/restaurants/${slug}`);
    }

    async getRestaurantMenu(slug) {
        return await this.request(`/restaurants/${slug}/menu`);
    }

    async addRestaurantReview(slug, reviewData) {
        return await this.request(`/restaurants/${slug}/review`, {
            method: 'POST',
            body: JSON.stringify(reviewData)
        });
    }

    // Auth API methods
    async register(registrationData) {
        return await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(registrationData)
        });
    }

    async login(loginData) {
        return await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify(loginData)
        });
    }

    async logout() {
        // Since we're using session-based auth, logout is handled server-side
        // We can clear local storage and redirect
        localStorage.removeItem('user');
        const appBasePath = typeof window.getAppBasePath === 'function'
            ? window.getAppBasePath()
            : (typeof window.APP_BASE_PATH === 'string' ? window.APP_BASE_PATH : '');
        const normalizedBase = appBasePath ? appBasePath.replace(/\/$/, '') : '';
        window.location.href = normalizedBase ? `${normalizedBase}/index.html` : '/index.html';
    }

    // Restaurant Dashboard API methods
    async getDashboardStats() {
        return await this.request('/restaurant/dashboard');
    }

    async addMenuItem(menuItemData) {
        return await this.request('/restaurant/menu', {
            method: 'POST',
            body: JSON.stringify(menuItemData)
        });
    }

    async updateMenuItem(id, menuItemData) {
        return await this.request(`/restaurant/menu/${id}`, {
            method: 'PUT',
            body: JSON.stringify(menuItemData)
        });
    }

    async deleteMenuItem(id) {
        return await this.request(`/restaurant/menu/${id}`, {
            method: 'DELETE'
        });
    }

    async uploadImage(file) {
        await this.ensureReady();
        const formData = new FormData();
        formData.append('image', file);

        const response = await fetch(`${this.baseURL}/restaurant/upload-image`, {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-CSRF-Token': this.csrfToken
            }
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result?.message || 'فشل في رفع الصورة');
        }

        this.readyPromise = this.refreshCsrfToken().catch(() => null);

        return result;
    }

    // Admin API methods
    async getAdminRestaurants() {
        return await this.request('/admin/restaurants');
    }

    async activateRestaurant(id) {
        return await this.request(`/admin/restaurants/${id}/activate`, {
            method: 'PUT'
        });
    }

    async assignPlan(restaurantId, planId) {
        return await this.request(`/admin/restaurants/${restaurantId}/assign-plan`, {
            method: 'PUT',
            body: JSON.stringify({ plan_id: planId })
        });
    }

    async createPlan(planData) {
        return await this.request('/admin/plans', {
            method: 'POST',
            body: JSON.stringify(planData)
        });
    }

    async updatePlan(id, planData) {
        return await this.request(`/admin/plans/${id}`, {
            method: 'PUT',
            body: JSON.stringify(planData)
        });
    }

    // Health check
    async healthCheck() {
        return await this.request('/health');
    }

    // Utility methods
    async handleError(error, context = '') {
        console.error(`API Error ${context}:`, error);
        
        // Show user-friendly error message
        this.showNotification(
            error.message || 'حدث خطأ غير متوقع',
            'error'
        );
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'success' ? 'bg-green-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.add('animate-fade-in');
        }, 100);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }

    // Search functionality
    async searchRestaurants(query, filters = {}) {
        const params = {
            search: query,
            ...filters
        };
        return await this.getRestaurants(params);
    }

    // Filter restaurants by city, category, etc.
    async filterRestaurants(filters) {
        return await this.getRestaurants(filters);
    }
}

// Initialize API client
let apiClient;

document.addEventListener('DOMContentLoaded', () => {
    apiClient = new APIClient();
    
    // Make it globally available
    window.apiClient = apiClient;
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIClient;
}
