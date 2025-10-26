class AdminDashboard {
    constructor() {
        this.auth = window.dashboardAuth;
        this.session = null;
        this.client = null;
        this.currentPage = 1;
        this.limit = 10;
        this.totalPages = 1;
        this.totalItems = 0;
        this.plans = [];
        this.currentRestaurantId = null;
        this.planModal = null;
        this.planSelect = null;
        this.planModalRestaurant = null;
    }

    async init() {
        try {
            this.session = await this.auth.requireRole('admin');
            this.client = await this.auth.waitForApiClient();
        } catch (error) {
            console.error('Unable to initialize admin dashboard:', error);
            return;
        }

        this.auth.bindLogoutButtons();
        this.cacheElements();
        this.bindEvents();
        this.updateHeader();
        await this.loadInitialData();
    }

    cacheElements() {
        this.planModalElement = document.getElementById('planModal');
        if (this.planModalElement && window.bootstrap) {
            this.planModal = new bootstrap.Modal(this.planModalElement);
        }
        this.planSelect = document.getElementById('plan-select');
        this.planModalRestaurant = document.getElementById('planModalRestaurant');
        this.tableBody = document.getElementById('restaurants-tbody');
        this.summaryElement = document.getElementById('restaurants-summary');
        this.planFilter = document.getElementById('filter-plan');
    }

    bindEvents() {
        const filtersForm = document.getElementById('restaurant-filters');
        if (filtersForm) {
            filtersForm.addEventListener('submit', (event) => {
                event.preventDefault();
                this.currentPage = 1;
                this.loadRestaurants();
            });
        }

        const refreshRestaurantsBtn = document.getElementById('refresh-restaurants');
        if (refreshRestaurantsBtn) {
            refreshRestaurantsBtn.addEventListener('click', () => this.loadRestaurants());
        }

        const refreshDashboardBtn = document.getElementById('refresh-dashboard');
        if (refreshDashboardBtn) {
            refreshDashboardBtn.addEventListener('click', () => this.loadSystemAnalytics());
        }

        const prevPage = document.getElementById('prev-page');
        if (prevPage) {
            prevPage.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage -= 1;
                    this.loadRestaurants();
                }
            });
        }

        const nextPage = document.getElementById('next-page');
        if (nextPage) {
            nextPage.addEventListener('click', () => {
                if (this.currentPage < this.totalPages) {
                    this.currentPage += 1;
                    this.loadRestaurants();
                }
            });
        }

        if (this.tableBody) {
            this.tableBody.addEventListener('click', (event) => this.handleTableAction(event));
        }

        const assignPlanSubmit = document.getElementById('assign-plan-submit');
        if (assignPlanSubmit) {
            assignPlanSubmit.addEventListener('click', () => this.assignSelectedPlan());
        }
    }

    async loadInitialData() {
        await Promise.all([
            this.loadPlans(),
            this.loadSystemAnalytics(),
            this.loadRestaurants()
        ]);
    }

    updateHeader() {
        const nameElement = document.getElementById('admin-name');
        if (nameElement && this.session?.user) {
            nameElement.textContent = this.session.user.name || this.session.user.email;
        }

        const welcome = document.getElementById('welcome-text');
        if (welcome && this.session?.user) {
            const name = this.session.user.name || this.session.user.email;
            welcome.textContent = `مرحباً ${name}، يمكنك متابعة أداء المنصة وإدارة المطاعم من هنا.`;
        }
    }

    async loadPlans() {
        try {
            const response = await this.client.getSubscriptionPlans();
            const payload = response.data || response;
            const plans = payload.data || payload;
            this.plans = Array.isArray(plans) ? plans : [];
            this.populatePlanSelects();
        } catch (error) {
            console.error('Failed to load subscription plans:', error);
            this.plans = [];
        }
    }

    populatePlanSelects() {
        const renderOption = (plan) => {
            const label = plan.name_ar || plan.name || `الخطة ${plan.id}`;
            return `<option value="${plan.id}">${label}</option>`;
        };

        if (this.planSelect) {
            if (!this.plans.length) {
                this.planSelect.innerHTML = '<option value="">لا توجد خطط متاحة</option>';
            } else {
                this.planSelect.innerHTML = this.plans.map(renderOption).join('');
            }
        }

        if (this.planFilter) {
            const currentValue = this.planFilter.value;
            const defaultOption = '<option value="">كل الخطط</option>';
            this.planFilter.innerHTML = defaultOption + this.plans.map(renderOption).join('');
            this.planFilter.value = currentValue;
        }
    }

    async loadSystemAnalytics() {
        try {
            const response = await this.client.getSystemAnalytics();
            const payload = response.data || response;
            const data = payload.data || payload;
            const totals = data.system_totals || {};
            const restaurantStats = data.restaurant_stats || {};

            this.updateStatCards(totals, restaurantStats);
            this.renderPlanDistribution(data.plan_distribution || []);
            this.renderTopRestaurants(data.top_restaurants || []);
        } catch (error) {
            console.error('Failed to load analytics:', error);
            if (this.client) {
                this.client.showNotification('تعذر تحميل الإحصائيات، يرجى المحاولة لاحقاً', 'error');
            }
        }
    }

    updateStatCards(totals, restaurantStats) {
        const totalRestaurants = restaurantStats.total_restaurants || 0;
        const activeRestaurants = restaurantStats.active_restaurants || 0;
        const approvedRestaurants = restaurantStats.approved_restaurants || 0;
        const totalVisitors = totals.total_visitors || 0;
        const totalMenuViews = totals.total_menu_views || 0;

        const totalElement = document.getElementById('stat-total-restaurants');
        if (totalElement) {
            totalElement.textContent = this.auth.formatNumber(totalRestaurants);
        }

        const approvedElement = document.getElementById('stat-approved-restaurants');
        if (approvedElement) {
            approvedElement.textContent = `${this.auth.formatNumber(approvedRestaurants)} مطعم معتمد`;
        }

        const activeElement = document.getElementById('stat-active-restaurants');
        if (activeElement) {
            activeElement.textContent = this.auth.formatNumber(activeRestaurants);
        }

        const visitorsElement = document.getElementById('stat-total-visitors');
        if (visitorsElement) {
            visitorsElement.textContent = this.auth.formatNumber(totalVisitors);
        }

        const menuViewsElement = document.getElementById('stat-total-menu-views');
        if (menuViewsElement) {
            menuViewsElement.textContent = this.auth.formatNumber(totalMenuViews);
        }
    }

    renderPlanDistribution(list) {
        const container = document.getElementById('plan-distribution');
        if (!container) return;

        if (!Array.isArray(list) || !list.length) {
            container.innerHTML = '<li class="list-group-item text-center text-muted">لا توجد بيانات بعد.</li>';
            return;
        }

        container.innerHTML = list.map((item) => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${item.name_ar || item.name}</span>
                <span class="badge bg-primary rounded-pill">${this.auth.formatNumber(item.restaurant_count)}</span>
            </li>
        `).join('');
    }

    renderTopRestaurants(list) {
        const container = document.getElementById('top-restaurants');
        if (!container) return;

        if (!Array.isArray(list) || !list.length) {
            container.innerHTML = '<div class="list-group-item text-center text-muted">لا توجد بيانات بعد.</div>';
            return;
        }

        container.innerHTML = list.map((restaurant) => `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">${restaurant.name_ar || restaurant.name}</div>
                        <small class="text-muted">${this.auth.formatNumber(restaurant.total_visitors || 0)} زيارة</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-warning">${Number(restaurant.average_rating || 0).toFixed(1)} ★</div>
                        <small class="text-muted">${this.auth.formatNumber(restaurant.reviews_count || 0)} تقييم</small>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async loadRestaurants() {
        if (!this.tableBody) return;

        this.tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">جاري تحميل البيانات...</td></tr>';

        try {
            const filters = this.getCurrentFilters();
            const response = await this.client.getAdminRestaurants({
                ...filters,
                page: this.currentPage,
                limit: this.limit
            });
            const payload = response.data || response;
            const data = payload.data || payload;
            const restaurants = Array.isArray(data.data) ? data.data : [];

            this.totalItems = data.total || restaurants.length;
            this.totalPages = data.total_pages || 1;
            this.renderRestaurants(restaurants);
            this.updateSummary(restaurants.length);
        } catch (error) {
            console.error('Failed to load restaurants:', error);
            this.tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">تعذر تحميل المطاعم</td></tr>';
        }
    }

    getCurrentFilters() {
        const filters = {};
        const status = document.getElementById('filter-status');
        const search = document.getElementById('filter-search');

        if (status && status.value) {
            filters.status = status.value;
        }
        if (search && search.value) {
            filters.search = search.value.trim();
        }
        if (this.planFilter && this.planFilter.value) {
            filters.plan_id = this.planFilter.value;
        }

        return filters;
    }

    renderRestaurants(restaurants) {
        if (!restaurants.length) {
            this.tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">لا توجد مطاعم مطابقة للمعايير الحالية.</td></tr>';
            return;
        }

        this.tableBody.innerHTML = restaurants.map((restaurant) => {
            const statusBadges = this.buildStatusBadges(restaurant);
            return `
                <tr data-restaurant-id="${restaurant.id}" data-restaurant-name="${restaurant.name}">
                    <td>
                        <div class="fw-semibold">${restaurant.name_ar || restaurant.name}</div>
                        <small class="text-muted">${restaurant.subdomain || restaurant.slug}</small>
                    </td>
                    <td>
                        <div>${restaurant.owner_name || '-'}</div>
                        <small class="text-muted">${restaurant.owner_email || ''}</small>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info">${restaurant.plan_name_ar || restaurant.plan_name || 'غير محدد'}</span>
                    </td>
                    <td>${statusBadges}</td>
                    <td>
                        <small class="text-muted d-block">القوائم: ${this.auth.formatNumber(restaurant.stats?.categories_count || 0)}</small>
                        <small class="text-muted d-block">الأصناف: ${this.auth.formatNumber(restaurant.stats?.items_count || 0)}</small>
                        <small class="text-muted">التقييم: ${(restaurant.stats?.average_rating || 0).toFixed(1)} ★</small>
                    </td>
                    <td class="text-center">
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-success" data-action="activate">تفعيل</button>
                            <button class="btn btn-sm btn-outline-warning" data-action="deactivate">إيقاف</button>
                            <button class="btn btn-sm btn-outline-primary" data-action="assign-plan">تعيين خطة</button>
                            <button class="btn btn-sm btn-outline-danger" data-action="delete">حذف</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    buildStatusBadges(restaurant) {
        const badges = [];
        if (restaurant.is_approved) {
            badges.push('<span class="badge bg-success">معتمد</span>');
        } else {
            badges.push('<span class="badge bg-secondary">بانتظار الموافقة</span>');
        }

        if (restaurant.is_active) {
            badges.push('<span class="badge bg-primary">نشط</span>');
        } else {
            badges.push('<span class="badge bg-warning text-dark">موقوف</span>');
        }

        if (restaurant.subscription_status === 'expired') {
            badges.push('<span class="badge bg-danger">منتهي</span>');
        }

        return badges.join(' ');
    }

    updateSummary(visibleCount) {
        if (!this.summaryElement) return;

        if (!this.totalItems) {
            this.summaryElement.textContent = 'لا توجد نتائج.';
            return;
        }

        const start = ((this.currentPage - 1) * this.limit) + 1;
        const end = start + visibleCount - 1;
        this.summaryElement.textContent = `عرض ${this.auth.formatNumber(start)}-${this.auth.formatNumber(end)} من ${this.auth.formatNumber(this.totalItems)} مطاعم`;
    }

    handleTableAction(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const action = button.getAttribute('data-action');
        const row = button.closest('tr[data-restaurant-id]');
        if (!row) return;

        const restaurantId = row.getAttribute('data-restaurant-id');
        const restaurantName = row.getAttribute('data-restaurant-name');

        switch (action) {
            case 'activate':
                this.changeRestaurantStatus(restaurantId, restaurantName, 'activate');
                break;
            case 'deactivate':
                this.changeRestaurantStatus(restaurantId, restaurantName, 'deactivate');
                break;
            case 'assign-plan':
                this.openPlanModal(restaurantId, restaurantName);
                break;
            case 'delete':
                this.deleteRestaurant(restaurantId, restaurantName);
                break;
            default:
        }
    }

    async changeRestaurantStatus(id, name, action) {
        const confirmMessage = action === 'activate'
            ? `هل تريد تفعيل المطعم «${name}»؟`
            : `هل تريد إيقاف المطعم «${name}»؟`;

        if (!window.confirm(confirmMessage)) {
            return;
        }

        try {
            if (action === 'activate') {
                await this.client.activateRestaurant(id);
            } else {
                await this.client.deactivateRestaurant(id);
            }
            this.client.showNotification('تم تحديث حالة المطعم بنجاح', 'success');
            this.loadRestaurants();
        } catch (error) {
            console.error('Failed to change status:', error);
            this.client.showNotification(error.message || 'تعذر تحديث الحالة', 'error');
        }
    }

    openPlanModal(id, name) {
        if (!this.planModal || !this.planSelect) {
            this.client.showNotification('تعذر فتح نافذة تعيين الخطة', 'error');
            return;
        }

        this.currentRestaurantId = id;
        if (this.planModalRestaurant) {
            this.planModalRestaurant.textContent = `المطعم: ${name}`;
        }
        this.planModal.show();
    }

    async assignSelectedPlan() {
        if (!this.currentRestaurantId || !this.planSelect || !this.planSelect.value) {
            this.client.showNotification('يرجى اختيار خطة صالحة', 'error');
            return;
        }

        try {
            await this.client.assignPlan(this.currentRestaurantId, this.planSelect.value);
            this.client.showNotification('تم تعيين الخطة بنجاح', 'success');
            this.planModal?.hide();
            this.loadRestaurants();
        } catch (error) {
            console.error('Failed to assign plan:', error);
            this.client.showNotification(error.message || 'تعذر تعيين الخطة', 'error');
        }
    }

    async deleteRestaurant(id, name) {
        if (!window.confirm(`هل أنت متأكد من حذف المطعم «${name}»؟ لا يمكن التراجع عن هذه الخطوة.`)) {
            return;
        }

        try {
            await this.client.deleteRestaurant(id);
            this.client.showNotification('تم حذف المطعم بنجاح', 'success');
            this.loadRestaurants();
        } catch (error) {
            console.error('Failed to delete restaurant:', error);
            this.client.showNotification(error.message || 'تعذر حذف المطعم', 'error');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new AdminDashboard();
    dashboard.init();
});
