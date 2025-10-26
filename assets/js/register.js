// Register Page Functionality

class RegisterPage {
    constructor() {
        this.formData = {
            restaurantName: '',
            ownerName: '',
            email: '',
            phone: '',
            city: '',
            password: '',
            confirmPassword: ''
        };
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
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        // Input fields
        const inputs = [
            'restaurant-name', 'owner-name', 'email', 'phone', 'city', 'password', 'confirm-password'
        ];

        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', (e) => {
                    this.updateFormData(inputId, e.target.value);
                    this.clearError();
                });
            }
        });

        // Password toggle
        window.togglePassword = () => this.togglePassword();
    }

    updateFormData(inputId, value) {
        const fieldMap = {
            'restaurant-name': 'restaurantName',
            'owner-name': 'ownerName',
            'email': 'email',
            'phone': 'phone',
            'city': 'city',
            'password': 'password',
            'confirm-password': 'confirmPassword'
        };

        const field = fieldMap[inputId];
        if (field) {
            this.formData[field] = value;
        }
    }

    async handleSubmit() {
        if (this.loading) return;

        this.loading = true;
        this.clearError();

        // Validate inputs
        const validation = this.validateForm();
        if (!validation.valid) {
            this.showError(validation.message);
            this.loading = false;
            return;
        }

        // Update UI
        this.updateSubmitButton();

        try {
            if (window.apiClient) {
                const payload = {
                    restaurant_name: this.formData.restaurantName,
                    owner_name: this.formData.ownerName,
                    email: this.formData.email,
                    phone: this.formData.phone,
                    city: this.formData.city,
                    password: this.formData.password
                };

                const response = await window.apiClient.register(payload);

                if (response && (response.success || response.ok)) {
                    this.showNotification('تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول', 'success');

                    const appBasePath = typeof window.getAppBasePath === 'function'
                        ? window.getAppBasePath()
                        : (typeof window.APP_BASE_PATH === 'string' ? window.APP_BASE_PATH : '');
                    const normalizedBase = appBasePath ? appBasePath.replace(/\/$/, '') : '';
                    const loginUrl = normalizedBase ? `${normalizedBase}/login.html` : '/login.html';

                    // Redirect to login page after a short delay
                    setTimeout(() => {
                        window.location.href = loginUrl;
                    }, 2000);
                } else {
                    this.showError(response?.message || 'فشل في إنشاء الحساب');
                }
            } else {
                // Mock registration for demo
                this.mockRegister();
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showError(error.message || 'فشل في إنشاء الحساب');
        } finally {
            this.loading = false;
            this.updateSubmitButton();
        }
    }

    validateForm() {
        // Check required fields
        const requiredFields = ['restaurantName', 'ownerName', 'email', 'phone', 'city', 'password', 'confirmPassword'];
        
        for (const field of requiredFields) {
            if (!this.formData[field] || this.formData[field].trim() === '') {
                return {
                    valid: false,
                    message: 'يرجى ملء جميع الحقول المطلوبة'
                };
            }
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(this.formData.email)) {
            return {
                valid: false,
                message: 'يرجى إدخال بريد إلكتروني صحيح'
            };
        }

        // Validate password length
        if (this.formData.password.length < 6) {
            return {
                valid: false,
                message: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'
            };
        }

        // Validate password confirmation
        if (this.formData.password !== this.formData.confirmPassword) {
            return {
                valid: false,
                message: 'كلمة المرور وتأكيدها غير متطابقتين'
            };
        }

        // Validate phone format (basic Syrian phone number)
        const phoneRegex = /^(\+963|0)?[1-9][0-9]{7,8}$/;
        if (!phoneRegex.test(this.formData.phone.replace(/\s/g, ''))) {
            return {
                valid: false,
                message: 'يرجى إدخال رقم هاتف صحيح'
            };
        }

        // Check terms acceptance
        const termsCheckbox = document.getElementById('terms');
        if (!termsCheckbox || !termsCheckbox.checked) {
            return {
                valid: false,
                message: 'يرجى الموافقة على الشروط والأحكام'
            };
        }

        return { valid: true };
    }

    mockRegister() {
        // Simulate API delay
        setTimeout(() => {
            // Simulate successful registration
            this.showNotification('تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول', 'success');

            const appBasePath = typeof window.getAppBasePath === 'function'
                ? window.getAppBasePath()
                : (typeof window.APP_BASE_PATH === 'string' ? window.APP_BASE_PATH : '');
            const normalizedBase = appBasePath ? appBasePath.replace(/\/$/, '') : '';
            const loginUrl = normalizedBase ? `${normalizedBase}/login.html` : '/login.html';

            // Redirect to login page after a short delay
            setTimeout(() => {
                window.location.href = loginUrl;
            }, 2000);

            this.loading = false;
            this.updateSubmitButton();
        }, 1500);
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
                submitText.textContent = 'جاري إنشاء الحساب...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = false;
                submitText.textContent = 'إنشاء الحساب';
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

// Initialize register page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.registerPage = new RegisterPage();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RegisterPage;
}
