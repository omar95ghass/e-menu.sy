class DashboardAuth {
    constructor() {
        this.session = null;
        this.locale = document.documentElement.lang || 'ar';
    }

    async waitForApiClient() {
        if (window.apiClient) {
            return window.apiClient;
        }

        if (window.apiClientReady && typeof window.apiClientReady.then === 'function') {
            return window.apiClientReady;
        }

        return new Promise((resolve) => {
            document.addEventListener('apiClientReady', (event) => resolve(event.detail), { once: true });
        });
    }

    getAppBasePath() {
        if (typeof window.getAppBasePath === 'function') {
            return window.getAppBasePath();
        }
        if (typeof window.APP_BASE_PATH === 'string') {
            return window.APP_BASE_PATH;
        }
        return '';
    }

    resolvePath(path) {
        const normalizedPath = typeof path === 'string' && path.length > 0
            ? (path.startsWith('/') ? path : `/${path}`)
            : '';
        const base = this.getAppBasePath();
        const normalizedBase = base ? base.replace(/\/$/, '') : '';
        return normalizedBase ? `${normalizedBase}${normalizedPath}` : (normalizedPath || '/');
    }

    getDashboardUrl(role) {
        const routes = {
            admin: '/dashboard/admin/index.html',
            restaurant: '/dashboard/restaurant/index.html'
        };
        const fallback = '/index.html';
        return this.resolvePath(routes[role] || fallback);
    }

    getLoginUrl() {
        return this.resolvePath('/login.html');
    }

    getProfileUrl() {
        return this.resolvePath('/dashboard/restaurant/profile.html');
    }

    redirectTo(url) {
        window.location.replace(url);
    }

    redirectToLogin() {
        this.redirectTo(this.getLoginUrl());
    }

    async fetchSession() {
        const client = await this.waitForApiClient();

        try {
            const response = await client.getCurrentUser();
            const payload = response.data || response;
            const user = payload.user || payload.data?.user || null;

            if (!user) {
                this.session = null;
                throw Object.assign(new Error('SESSION_MISSING'), { status: 401 });
            }

            this.session = {
                user,
                restaurant: payload.restaurant || payload.data?.restaurant || null,
                subscription: payload.subscription || payload.data?.subscription || null
            };

            return this.session;
        } catch (error) {
            if (error && typeof error === 'object' && 'status' in error && error.status === 401) {
                this.session = null;
            }
            throw error;
        }
    }

    async requireRole(role) {
        try {
            const session = await this.fetchSession();
            const { user } = session;

            if (!user) {
                this.redirectToLogin();
                throw new Error('AUTH_REQUIRED');
            }

            if (role && user.role !== role) {
                const destination = this.getDashboardUrl(user.role);
                this.redirectTo(destination);
                throw new Error('ROLE_MISMATCH');
            }

            return session;
        } catch (error) {
            if (error && typeof error === 'object' && error.status === 401) {
                this.redirectToLogin();
            }
            throw error;
        }
    }

    bindLogoutButtons(selector = '[data-action="logout"]') {
        this.waitForApiClient().then((client) => {
            const buttons = document.querySelectorAll(selector);
            buttons.forEach((button) => {
                button.addEventListener('click', async (event) => {
                    event.preventDefault();
                    try {
                        await client.logout();
                    } catch (error) {
                        console.error('Failed to logout:', error);
                        this.redirectToLogin();
                    }
                });
            });
        });
    }

    formatNumber(value, options = {}) {
        const number = Number(value) || 0;
        return number.toLocaleString(this.locale === 'ar' ? 'ar-EG' : 'en-US', options);
    }

    formatCurrency(value, currency = 'USD') {
        const number = Number(value) || 0;
        return number.toLocaleString(this.locale === 'ar' ? 'ar-EG' : 'en-US', {
            style: 'currency',
            currency,
            maximumFractionDigits: 2
        });
    }
}

window.dashboardAuth = new DashboardAuth();
