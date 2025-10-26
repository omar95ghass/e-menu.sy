// Login Page Functionality

class LoginPage {
    constructor() {
        this.email = '';
        this.password = '';
        this.showPassword = false;
        this.loading = false;
        this.error = '';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupLucideIcons();
    }

    setupEventListeners() {
        // Form submission
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        // Input fields
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        if (emailInput) {
            emailInput.addEventListener('input', (e) => {
                this.email = e.target.value;
                this.clearError();
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => {
                this.password = e.target.value;
                this.clearError();
            });
        }

        // Password toggle
        window.togglePassword = () => this.togglePassword();
    }

    async handleSubmit() {
        if (this.loading) return;

        this.loading = true;
        this.clearError();

        // Validate inputs
        if (!this.email || !this.password) {
            this.showError('يرجى ملء جميع الحقول');
            this.loading = false;
            return;
        }

        // Update UI
        this.updateSubmitButton();

        try {
            if (window.apiClient) {
                const response = await window.apiClient.login({
                    email: this.email,
                    password: this.password
                });

                if (response && (response.success || response.ok)) {
                    const payload = response.data || response;
                    const userData = payload.user || payload.data?.user || null;
                    const restaurantData = payload.restaurant || payload.data?.restaurant || null;

                    if (userData) {
                        const sessionSnapshot = {
                            ...userData,
                            restaurant: restaurantData || undefined
                        };
                        localStorage.setItem('user', JSON.stringify(sessionSnapshot));
                    }

                    const dashboardUrl = this.getDashboardUrl(userData?.role);
                    window.location.href = dashboardUrl;
                } else {
                    this.showError(response?.message || 'فشل تسجيل الدخول');
                }
            } else {
                // Mock login for demo
                this.mockLogin();
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showError(error.message || 'فشل تسجيل الدخول');
        } finally {
            this.loading = false;
            this.updateSubmitButton();
        }
    }

    mockLogin() {
        // Simulate API delay
        setTimeout(() => {
            if (this.email && this.password === 'password') {
                const role = this.email.toLowerCase().includes('admin') ? 'admin' : 'restaurant';
                const mockUser = {
                    id: 1,
                    email: this.email,
                    name: 'مطعم تجريبي',
                    role
                };

                localStorage.setItem('user', JSON.stringify(mockUser));
                this.showNotification('تم تسجيل الدخول بنجاح', 'success');

                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = this.getDashboardUrl(mockUser.role);
                }, 1000);
            } else {
                this.showError('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }
            this.loading = false;
            this.updateSubmitButton();
        }, 1000);
    }

    getDashboardUrl(role) {
        const appBasePath = typeof window.getAppBasePath === 'function'
            ? window.getAppBasePath()
            : (typeof window.APP_BASE_PATH === 'string' ? window.APP_BASE_PATH : '');
        const normalizedBase = appBasePath ? appBasePath.replace(/\/$/, '') : '';

        const routes = {
            admin: '/dashboard/admin/index.html',
            restaurant: '/dashboard/restaurant/index.html'
        };

        const fallback = '/index.html';
        const path = routes[role] || fallback;
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;

        return normalizedBase ? `${normalizedBase}${normalizedPath}` : normalizedPath;
    }

    togglePassword() {
        this.showPassword = !this.showPassword;
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('password-icon');

        if (passwordInput) {
            passwordInput.type = this.showPassword ? 'text' : 'password';
        }

        if (passwordIcon) {
            passwordIcon.setAttribute('data-lucide', this.showPassword ? 'eye-off' : 'eye');
            this.setupLucideIcons();
        }
    }

    updateSubmitButton() {
        const submitBtn = document.getElementById('submit-btn');
        const submitText = document.getElementById('submit-text');

        if (submitBtn && submitText) {
            if (this.loading) {
                submitBtn.disabled = true;
                submitText.textContent = 'جاري التحميل...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = false;
                submitText.textContent = 'تسجيل الدخول';
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }
    }

    showError(message) {
        this.error = message;
        const errorMessage = document.getElementById('error-message');
        
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.remove('hidden');
            errorMessage.classList.add('animate-shake');
            
            // Remove shake animation after it completes
            setTimeout(() => {
                errorMessage.classList.remove('animate-shake');
            }, 500);
        }
    }

    clearError() {
        this.error = '';
        const errorMessage = document.getElementById('error-message');
        
        if (errorMessage) {
            errorMessage.classList.add('hidden');
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'success' ? 'bg-green-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('animate-fade-in');
        }, 100);

        setTimeout(() => {
            notification.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    setupLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Initialize login page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.loginPage = new LoginPage();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoginPage;
}
