class RestaurantDashboard {
    constructor() {
        this.auth = window.dashboardAuth;
        this.session = null;
        this.client = null;
        this.analyticsAvailable = true;
    }

    async init() {
        try {
            this.session = await this.auth.requireRole('restaurant');
            this.client = await this.auth.waitForApiClient();
        } catch (error) {
            console.error('Unable to initialize restaurant dashboard:', error);
            return;
        }

        this.auth.bindLogoutButtons();
        this.cacheElements();
        this.bindEvents();
        this.updateHeader();
        await this.loadData();
    }

    cacheElements() {
        this.ownerName = document.getElementById('owner-name');
        this.ownerEmail = document.getElementById('owner-email');
        this.restaurantName = document.getElementById('restaurant-name');
        this.restaurantSubtitle = document.getElementById('restaurant-subtitle');
        this.profileLink = document.getElementById('profile-link');
        this.lastUpdated = document.getElementById('last-updated');
        this.analyticsAlert = document.getElementById('analytics-alert');
    }

    bindEvents() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadData());
        }
    }

    updateHeader() {
        if (this.ownerName && this.session?.user) {
            this.ownerName.textContent = this.session.user.name || this.session.user.email;
        }
        if (this.ownerEmail && this.session?.user) {
            this.ownerEmail.textContent = this.session.user.email;
        }
    }

    async loadData() {
        if (this.analyticsAlert) {
            this.analyticsAlert.classList.add('d-none');
            this.analyticsAlert.textContent = '';
        }

        try {
            const [restaurantResponse, analyticsResponse] = await Promise.all([
                this.client.getRestaurantDashboard(),
                this.client.getRestaurantAnalytics().catch((error) => {
                    this.analyticsAvailable = false;
                    return { error };
                })
            ]);

            const restaurantPayload = restaurantResponse.data || restaurantResponse;
            const restaurant = restaurantPayload.data || restaurantPayload;
            this.renderRestaurantInfo(restaurant);

            if (analyticsResponse && !analyticsResponse.error) {
                const analyticsPayload = analyticsResponse.data || analyticsResponse;
                const analytics = analyticsPayload.data || analyticsPayload;
                this.analyticsAvailable = true;
                this.renderAnalytics(analytics);
            } else if (analyticsResponse?.error) {
                this.handleAnalyticsError(analyticsResponse.error);
            }

            this.touchUpdatedTimestamp();
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            if (this.client) {
                this.client.showNotification('تعذر تحميل بيانات لوحة التحكم، يرجى المحاولة لاحقاً', 'error');
            }
        }
    }

    handleAnalyticsError(error) {
        this.analyticsAvailable = false;
        if (!this.analyticsAlert) return;
        const message = error?.body?.message || error?.message || 'خدمة التحليلات غير متاحة لخطة اشتراكك الحالية.';
        this.analyticsAlert.textContent = message;
        this.analyticsAlert.classList.remove('d-none');
    }

    renderRestaurantInfo(restaurant) {
        if (this.restaurantName) {
            this.restaurantName.textContent = restaurant.name_ar || restaurant.name || 'لوحة التحكم';
        }

        if (this.restaurantSubtitle) {
            const city = restaurant.city_ar || restaurant.city || '';
            const cuisine = restaurant.cuisine_type_ar || restaurant.cuisine_type || '';
            const parts = [city, cuisine].filter(Boolean);
            this.restaurantSubtitle.textContent = parts.length ? parts.join(' • ') : 'متابعة أداء المطعم';
        }

        if (this.profileLink && restaurant.slug) {
            this.profileLink.href = 'profile.html';
        }

        const infoContainer = document.getElementById('restaurant-info');
        if (infoContainer) {
            const address = restaurant.address_ar || restaurant.address || 'لم يتم تحديد العنوان';
            const phone = restaurant.phone || 'لم يتم تحديد الهاتف';
            const workingDays = Array.isArray(restaurant.working_days) && restaurant.working_days.length
                ? restaurant.working_days.join('، ')
                : 'غير محدد';

            infoContainer.innerHTML = `
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="text-muted">رقم الهاتف</div>
                        <div class="fw-semibold">${phone}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">البريد الإلكتروني</div>
                        <div class="fw-semibold">${restaurant.email || 'غير متوفر'}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted">العنوان</div>
                        <div class="fw-semibold">${address}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">أيام العمل</div>
                        <div class="fw-semibold">${workingDays}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">أوقات الدوام</div>
                        <div class="fw-semibold">${(restaurant.opening_time || '00:00')} - ${(restaurant.closing_time || '00:00')}</div>
                    </div>
                </div>
            `;
        }

        const subscriptionContainer = document.getElementById('subscription-info');
        const subscription = restaurant.subscription || this.session?.subscription || null;
        if (subscriptionContainer) {
            if (!subscription) {
                subscriptionContainer.innerHTML = '<div class="alert alert-warning mb-0">لم يتم ربط المطعم بخطة اشتراك بعد.</div>';
            } else {
                const features = subscription.features || {};
                const featureList = Object.entries(features).map(([key, enabled]) => {
                    const labels = {
                        color_customization: 'تخصيص الألوان',
                        analytics: 'التحليلات',
                        reviews: 'إدارة التقييمات',
                        online_ordering: 'الطلبات عبر الإنترنت',
                        custom_domain: 'نطاق مخصص'
                    };
                    return `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${labels[key] || key}</span>
                        <span class="badge ${enabled ? 'bg-success' : 'bg-secondary'}">${enabled ? 'متاح' : 'غير متاح'}</span>
                    </li>`;
                }).join('');

                subscriptionContainer.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold">${subscription.plan_name_ar || subscription.plan_name || 'خطة الاشتراك'}</div>
                            <small class="text-muted">الحالة: ${this.describeSubscriptionStatus(subscription.status || restaurant.subscription_status)}</small>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        ${featureList || '<li class="list-group-item text-muted">لا توجد ميزات إضافية.</li>'}
                    </ul>
                `;
            }
        }
    }

    describeSubscriptionStatus(status) {
        switch (status) {
            case 'active':
                return 'نشطة';
            case 'suspended':
                return 'موقوفة مؤقتاً';
            case 'expired':
                return 'منتهية';
            default:
                return status || 'غير معروف';
        }
    }

    renderAnalytics(analytics) {
        const totals = analytics.totals || {};
        const growth = analytics.growth_rates || {};

        this.updateStat('stat-visitors', totals.total_visitors);
        this.updateStat('stat-page-views', totals.total_page_views);
        this.updateStat('stat-menu-views', totals.total_menu_views);
        this.updateStat('stat-item-views', totals.total_item_views);

        this.updateGrowth('growth-visitors', growth.visitors);
        this.updateGrowth('growth-page-views', growth.page_views);
        this.updateGrowth('growth-menu-views', growth.menu_views);
        this.updateGrowth('growth-item-views', growth.item_views);

        this.renderPopularItems(analytics.popular_items || []);
        this.renderRecentReviews(analytics.recent_reviews || [], totals.total_reviews);
        this.renderDailyStats(analytics.daily_stats || []);
    }

    updateStat(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = this.auth.formatNumber(value || 0);
        }
    }

    updateGrowth(elementId, value) {
        const element = document.getElementById(elementId);
        if (!element) return;

        if (value === null || value === undefined) {
            element.textContent = '';
            return;
        }

        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            element.textContent = '';
            return;
        }

        const sign = numeric > 0 ? '+' : '';
        element.textContent = `${sign}${numeric}%`;
        element.classList.toggle('text-success', numeric > 0);
        element.classList.toggle('text-danger', numeric < 0);
    }

    renderPopularItems(items) {
        const container = document.getElementById('popular-items');
        const counter = document.getElementById('popular-items-count');
        if (!container) return;

        if (!items.length) {
            container.innerHTML = '<div class="list-group-item text-center text-muted">لا توجد بيانات بعد.</div>';
            if (counter) counter.textContent = '';
            return;
        }

        container.innerHTML = items.map((item) => `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${item.name_ar || item.name}</div>
                    <small class="text-muted">${this.auth.formatNumber(item.views_count || 0)} مشاهدة • ${this.auth.formatNumber(item.orders_count || 0)} طلب</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">${Number(item.price || 0).toFixed(2)}</div>
                    <small class="text-warning">${Number(item.average_rating || 0).toFixed(1)} ★</small>
                </div>
            </div>
        `).join('');

        if (counter) {
            counter.textContent = `${this.auth.formatNumber(items.length)} صنف`; 
        }
    }

    renderRecentReviews(reviews, totalReviews = null) {
        const container = document.getElementById('recent-reviews');
        const counter = document.getElementById('reviews-count');
        if (!container) return;

        if (!reviews.length) {
            container.innerHTML = '<div class="list-group-item text-center text-muted">لا توجد تقييمات بعد.</div>';
            if (counter) counter.textContent = '';
            return;
        }

        container.innerHTML = reviews.map((review) => {
            const reviewDate = this.formatDate(review.created_at);
            const itemLabel = review.item_name_ar || review.item_name || 'قائمة المطعم';
            const customer = review.customer_name || review.user_name || 'زائر';
            return `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">${customer}</div>
                    <span class="badge bg-warning text-dark">${Number(review.rating || 0).toFixed(1)} ★</span>
                </div>
                <p class="mb-2">${review.comment || 'بدون تعليق'}</p>
                <small class="text-muted">${itemLabel}${reviewDate ? ` • ${reviewDate}` : ''}</small>
            </div>
        `;
        }).join('');

        if (counter) {
            if (totalReviews !== null && totalReviews !== undefined) {
                counter.textContent = `${this.auth.formatNumber(reviews.length)} من ${this.auth.formatNumber(totalReviews)} تقييم`;
            } else {
                counter.textContent = `${this.auth.formatNumber(reviews.length)} تقييم`;
            }
        }
    }

    renderDailyStats(stats) {
        const tbody = document.getElementById('daily-stats-body');
        if (!tbody) return;

        if (!stats.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">لا توجد بيانات متاحة.</td></tr>';
            return;
        }

        const rows = stats.slice(0, 30).map((stat) => `
            <tr>
                <td>${this.formatDate(stat.date)}</td>
                <td>${this.auth.formatNumber(stat.visitors_count || 0)}</td>
                <td>${this.auth.formatNumber(stat.page_views || 0)}</td>
                <td>${this.auth.formatNumber(stat.menu_views || 0)}</td>
                <td>${this.auth.formatNumber(stat.orders_count || 0)}</td>
            </tr>
        `).join('');

        tbody.innerHTML = rows;
    }

    touchUpdatedTimestamp() {
        if (this.lastUpdated) {
            const now = new Date();
            this.lastUpdated.textContent = `آخر تحديث: ${now.toLocaleString('ar-EG')}`;
        }
    }

    formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.valueOf())) {
            return '';
        }
        return date.toLocaleDateString('ar-EG');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new RestaurantDashboard();
    dashboard.init();
});
