class RestaurantProfile {
    constructor() {
        this.auth = window.dashboardAuth;
        this.session = null;
        this.client = null;
        this.restaurant = null;
    }

    async init() {
        try {
            this.session = await this.auth.requireRole('restaurant');
            this.client = await this.auth.waitForApiClient();
        } catch (error) {
            console.error('Unable to initialize profile page:', error);
            return;
        }

        this.auth.bindLogoutButtons();
        this.cacheElements();
        this.bindEvents();
        await this.loadRestaurant();
    }

    cacheElements() {
        this.form = document.getElementById('profile-form');
        this.alert = document.getElementById('form-alert');
        this.logoInput = document.getElementById('logo-input');
        this.coverInput = document.getElementById('cover-input');
        this.logoPreview = document.getElementById('logo-preview');
        this.coverPreview = document.getElementById('cover-preview');
        this.saveButton = document.getElementById('save-button');
        this.profileTitle = document.getElementById('profile-title');
        this.workingDayCheckboxes = Array.from(document.querySelectorAll('#working-days-group .form-check-input'));
        this.inputs = {
            name_ar: document.getElementById('name'),
            name: document.getElementById('name-en'),
            description_ar: document.getElementById('description'),
            phone: document.getElementById('phone'),
            email: document.getElementById('email'),
            city_ar: document.getElementById('city'),
            address_ar: document.getElementById('address'),
            opening_time: document.getElementById('opening-time'),
            closing_time: document.getElementById('closing-time'),
            cuisine_type_ar: document.getElementById('cuisine'),
            website: document.getElementById('website'),
            social_facebook: document.getElementById('social-facebook'),
            social_instagram: document.getElementById('social-instagram'),
            social_twitter: document.getElementById('social-twitter'),
            social_youtube: document.getElementById('social-youtube')
        };
    }

    bindEvents() {
        if (this.form) {
            this.form.addEventListener('submit', (event) => this.handleSubmit(event));
        }

        if (this.logoInput) {
            this.logoInput.addEventListener('change', (event) => this.handleFileUpload(event, 'logo'));
        }

        if (this.coverInput) {
            this.coverInput.addEventListener('change', (event) => this.handleFileUpload(event, 'cover'));
        }
    }

    async loadRestaurant() {
        try {
            console.log('RestaurantProfile.loadRestaurant: fetching restaurant profile');
            const response = await this.client.getRestaurantDashboard();
            console.log('RestaurantProfile.loadRestaurant: response', response);
            const payload = response.data || response;
            this.restaurant = payload.data || payload;
            this.populateForm(this.restaurant);
        } catch (error) {
            console.error('Failed to load restaurant profile:', error);
            this.showAlert('تعذر تحميل بيانات المطعم، يرجى تحديث الصفحة.', 'danger');
        }
    }

    populateForm(restaurant) {
        if (this.profileTitle) {
            this.profileTitle.textContent = `تحديث ملف ${restaurant.name_ar || restaurant.name || ''}`;
        }

        Object.entries(this.inputs).forEach(([key, input]) => {
            if (!input) return;
            input.value = restaurant[key] || '';
        });

        if (Array.isArray(restaurant.working_days)) {
            this.workingDayCheckboxes.forEach((checkbox) => {
                checkbox.checked = restaurant.working_days.includes(checkbox.value);
            });
        }

        if (this.logoPreview && restaurant.logo_url) {
            this.logoPreview.src = restaurant.logo_url;
        }

        if (this.coverPreview && restaurant.cover_url) {
            this.coverPreview.src = restaurant.cover_url;
        }
    }

    collectFormData() {
        const data = {};
        Object.entries(this.inputs).forEach(([key, input]) => {
            if (!input) return;
            const value = input.value.trim();
            if (value) {
                data[key] = value;
            } else {
                data[key] = '';
            }
        });

        const selectedDays = this.workingDayCheckboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value);

        data.working_days = selectedDays;
        return data;
    }

    async handleSubmit(event) {
        event.preventDefault();
        if (!this.form || !this.saveButton) return;

        const data = this.collectFormData();
        console.log('RestaurantProfile.handleSubmit: submitting data', data);
        this.setLoading(true);

        try {
            const response = await this.client.updateRestaurantProfile(data);
            console.log('RestaurantProfile.handleSubmit: response', response);
            const payload = response.data || response;
            if (payload.success === false) {
                throw new Error(payload.message || 'تعذر تحديث البيانات');
            }
            const updated = payload.data || payload;
            this.restaurant = updated;
            this.populateForm(updated);
            this.showAlert('تم تحديث بيانات المطعم بنجاح.', 'success');
            this.client.showNotification('تم حفظ بيانات المطعم بنجاح', 'success');
        } catch (error) {
            console.error('Failed to update profile:', error);
            const message = error.message || 'حدث خطأ أثناء تحديث البيانات.';
            this.showAlert(message, 'danger');
            this.client.showNotification(message, 'error');
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(isLoading) {
        if (!this.saveButton) return;
        this.saveButton.disabled = isLoading;
        this.saveButton.textContent = isLoading ? 'جاري الحفظ...' : 'حفظ التغييرات';
    }

    async handleFileUpload(event, type) {
        const file = event.target.files?.[0];
        if (!file) return;

        try {
            if (type === 'logo') {
                console.log('RestaurantProfile.handleFileUpload: uploading logo', file);
                const response = await this.client.uploadRestaurantLogo(file);
                console.log('RestaurantProfile.handleFileUpload: logo response', response);
                const payload = response.data || response;
                const data = payload.data || payload;
                if (data.logo_url && this.logoPreview) {
                    this.logoPreview.src = data.logo_url;
                }
                this.client.showNotification(payload.message || 'تم تحديث الشعار', 'success');
            } else if (type === 'cover') {
                console.log('RestaurantProfile.handleFileUpload: uploading cover', file);
                const response = await this.client.uploadRestaurantCover(file);
                console.log('RestaurantProfile.handleFileUpload: cover response', response);
                const payload = response.data || response;
                const data = payload.data || payload;
                if (data.cover_url && this.coverPreview) {
                    this.coverPreview.src = data.cover_url;
                }
                this.client.showNotification(payload.message || 'تم تحديث صورة الغلاف', 'success');
            }
        } catch (error) {
            console.error('Failed to upload image:', error);
            this.client.showNotification(error.message || 'تعذر رفع الصورة', 'error');
        } finally {
            event.target.value = '';
        }
    }

    showAlert(message, type = 'info') {
        if (!this.alert) return;
        this.alert.textContent = message;
        this.alert.className = `alert alert-${type}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const profile = new RestaurantProfile();
    profile.init();
});
