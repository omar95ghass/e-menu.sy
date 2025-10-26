// E-Menu Application - Main JavaScript File

// Determine the correct base paths for the application and API endpoints
// so the front-end works whether it's served from the web root or a
// subdirectory (e.g. http://localhost/e-menu/ when using XAMPP).
(function initializeBasePaths() {
    if (window.getApiBaseUrl && window.getAppBasePath && window.buildApiUrl) {
        // Base helpers already defined (avoid redefining when scripts are loaded multiple times)
        return;
    }

    const isAbsoluteUrl = (value) => /^([a-z][a-z\d+\-.]*:)?\/\//i.test(value);

    const normalizeRelativePath = (value, { allowRoot = false } = {}) => {
        if (!value) return '';
        let normalized = String(value).trim();
        if (!normalized) return '';

        normalized = normalized.replace(/\\/g, '/');
        normalized = normalized.replace(/\/+/g, '/');
        normalized = normalized.replace(/\/$/, '');

        if (!normalized) return allowRoot ? '/' : '';

        if (!normalized.startsWith('/')) {
            normalized = `/${normalized}`;
        }

        if (!allowRoot && normalized === '/') {
            return '';
        }

        return normalized;
    };

    const deriveBaseFromScript = () => {
        if (Object.prototype.hasOwnProperty.call(window, '__APP_SCRIPT_BASE__')) {
            return window.__APP_SCRIPT_BASE__;
        }

        const extractBasePath = (src) => {
            if (!src) return null;

            try {
                const url = new URL(src, window.location.origin);
                let path = url.pathname.replace(/\\/g, '/');
                path = path.replace(/\/+/g, '/');
                path = path.replace(/\/?assets\/js\/[^/]+$/, '');
                path = path.replace(/\/$/, '');
                return path;
            } catch (error) {
                return null;
            }
        };

        const { currentScript } = document;
        let derived = extractBasePath(currentScript && currentScript.src);

        if (derived === null) {
            const scripts = document.getElementsByTagName('script');
            for (const script of scripts) {
                const src = script.getAttribute('src');
                if (!src) continue;
                if (src.includes('assets/js/app.js') || src.includes('assets/js/api.js')) {
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

    window.getAppBasePath = function getAppBasePath() {
        if (typeof window.APP_BASE_PATH === 'string') {
            return window.APP_BASE_PATH;
        }

        const metaTag = document.querySelector('meta[name="app-base-path"]');
        if (metaTag && metaTag.content) {
            window.APP_BASE_PATH = normalizeRelativePath(metaTag.content, { allowRoot: false });
            return window.APP_BASE_PATH;
        }

        const derived = deriveBaseFromScript();
        if (derived !== null && typeof derived === 'string') {
            window.APP_BASE_PATH = derived;
            return window.APP_BASE_PATH;
        }

        const { pathname } = window.location;

        if (!pathname || pathname === '/' || pathname === '') {
            window.APP_BASE_PATH = '';
            return window.APP_BASE_PATH;
        }

        const segments = pathname.split('/').filter(Boolean);
        const isDirectory = pathname.endsWith('/');

        if (!isDirectory) {
            segments.pop();
        }

        window.APP_BASE_PATH = segments.length ? `/${segments.join('/')}` : '';
        return window.APP_BASE_PATH;
    };

    window.getApiBaseUrl = function getApiBaseUrl() {
        if (typeof window.API_BASE_URL === 'string' && window.API_BASE_URL.length > 0) {
            return window.API_BASE_URL;
        }

        const metaTag = document.querySelector('meta[name="api-base-url"]');
        if (metaTag && metaTag.content) {
            const content = metaTag.content.trim();
            if (content) {
                window.API_BASE_URL = isAbsoluteUrl(content)
                    ? content.replace(/\/$/, '')
                    : normalizeRelativePath(content, { allowRoot: true }).replace(/\/$/, '');
                return window.API_BASE_URL || '/api';
            }
        }

        const derived = deriveBaseFromScript();
        if (derived !== null && typeof derived === 'string') {
            const normalized = derived.replace(/\/$/, '');
            window.API_BASE_URL = normalized ? `${normalized}/api` : '/api';
            return window.API_BASE_URL;
        }

        const appBasePath = window.getAppBasePath();
        const baseUrl = `${appBasePath}/api`.replace(/\/+/g, '/').replace(/\/$/, '');
        window.API_BASE_URL = baseUrl || '/api';
        return window.API_BASE_URL;
    };

    window.buildApiUrl = function buildApiUrl(path = '') {
        const baseUrl = window.getApiBaseUrl();
        const normalizedBase = baseUrl.replace(/\/$/, '');
        const normalizedPath = typeof path === 'string' ? path.replace(/^\/+/, '') : '';

        if (!normalizedPath) {
            return normalizedBase;
        }

        return `${normalizedBase}/${normalizedPath}`;
    };

    // Initialise and memoize the resolved paths
    window.APP_BASE_PATH = window.getAppBasePath();
    window.API_BASE_URL = window.getApiBaseUrl();
})();

class EMenuApp {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.currentLanguage = localStorage.getItem('language') || 'ar';
        this.isScrolled = false;
        this.init();
    }

    init() {
        this.setupTheme();
        this.setupLanguage();
        this.setupScrollListener();
        this.setupLucideIcons();
        this.loadFeaturedRestaurants();
        this.setupEventListeners();
    }

    setupTheme() {
        const root = document.documentElement;
        const themeIcon = document.getElementById('theme-icon');
        
        if (this.currentTheme === 'dark') {
            root.classList.add('dark');
            if (themeIcon) {
                themeIcon.setAttribute('data-lucide', 'sun');
            }
        } else {
            root.classList.remove('dark');
            if (themeIcon) {
                themeIcon.setAttribute('data-lucide', 'moon');
            }
        }
        
        // Update body classes
        document.body.className = this.getBodyClasses();
    }

    setupLanguage() {
        const root = document.documentElement;
        const dir = this.currentLanguage === 'ar' ? 'rtl' : 'ltr';
        root.dir = dir;
        root.lang = this.currentLanguage;
    }

    setupScrollListener() {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY > 50;
            if (scrolled !== this.isScrolled) {
                this.isScrolled = scrolled;
                this.updateHeader();
            }
        });
    }

    updateHeader() {
        const header = document.getElementById('header');
        if (!header) return;

        if (this.isScrolled) {
            header.classList.add('shadow-lg');
            if (this.currentTheme === 'dark') {
                header.classList.add('bg-slate-900/95');
            } else {
                header.classList.add('bg-white/95');
            }
        } else {
            header.classList.remove('shadow-lg');
            if (this.currentTheme === 'dark') {
                header.classList.remove('bg-slate-900/95');
            } else {
                header.classList.remove('bg-white/95');
            }
        }
    }

    setupLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    async loadFeaturedRestaurants() {
        try {
            const response = await fetch(window.buildApiUrl('restaurants'));
            const data = await response.json();

            const restaurants = Array.isArray(data.data)
                ? data.data
                : (Array.isArray(data.data?.data) ? data.data.data : []);

            if ((data.success || data.ok) && restaurants.length) {
                this.renderFeaturedRestaurants(restaurants.slice(0, 3));
            } else {
                this.renderMockRestaurants();
            }
        } catch (error) {
            console.log('Using mock data for featured restaurants');
            this.renderMockRestaurants();
        }
    }

    renderMockRestaurants() {
        const mockRestaurants = [
            {
                id: 1,
                name: 'مطعم الذوق الشامي',
                slug: 'al-thawq-al-shami',
                image: 'https://images.unsplash.com/photo-1504674900967-a8bd7f9d1b0e?w=400&h=300&fit=crop',
                rating: 4.8,
                reviews: 245,
                city: 'دمشق',
                cuisine: 'شامي',
            },
            {
                id: 2,
                name: 'Burger Palace',
                slug: 'burger-palace',
                image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop',
                rating: 4.5,
                reviews: 189,
                city: 'دمشق',
                cuisine: 'أمريكي',
            },
            {
                id: 3,
                name: 'مطعم البحر الأحمر',
                slug: 'al-bahr-al-ahmar',
                image: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=300&fit=crop',
                rating: 4.7,
                reviews: 312,
                city: 'اللاذقية',
                cuisine: 'بحري',
            },
        ];

        this.renderFeaturedRestaurants(mockRestaurants);
    }

    renderFeaturedRestaurants(restaurants) {
        const container = document.getElementById('featured-restaurants');
        if (!container) return;

        container.innerHTML = restaurants.map((restaurant, index) => `
            <a href="restaurant.html?slug=${restaurant.slug}" class="group">
                <div class="overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer animate-fade-in-up hover-lift bg-white hover:border-orange-500 border border-slate-200 rounded-lg"
                     style="animation-delay: ${index * 0.1}s">
                    <!-- Image -->
                    <div class="relative h-48 overflow-hidden bk-text">
                        <img src="${restaurant.image}" 
                             alt="${restaurant.name}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 text-right">
                        <h3 class="text-lg font-bold mb-2 group-hover:text-orange-500 transition-colors orange-text">
                            ${restaurant.name}
                        </h3>

                        <!-- Rating -->
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 fill-yellow-400 text-yellow-400"></i>
                                <span class="font-semibold">${restaurant.rating}</span>
                            </div>
                            <span class="text-sm text-slate-500">
                                (${restaurant.reviews} تقييم)
                            </span>
                        </div>

                        <!-- Location & Cuisine -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm bk-text">
                                <i data-lucide="map-pin" class="w-4 h-4 text-orange-500"></i>
                                <span>${restaurant.city}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm bk-text">
                                <i data-lucide="chef-hat" class="w-4 h-4 text-orange-500"></i>
                                <span>${restaurant.cuisine}</span>
                            </div>
                        </div>

                        <!-- View Menu Button -->
                        <button class="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 text-white py-2 px-4 rounded-lg font-semibold transition-all">
                            عرض القائمة
                        </button>
                    </div>
                </div>
            </a>
        `).join('');

        // Re-initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    setupEventListeners() {
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('[onclick="toggleMobileMenu()"]');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', this.toggleMobileMenu.bind(this));
        }

        // Theme toggle
        const themeBtn = document.querySelector('[onclick="toggleTheme()"]');
        if (themeBtn) {
            themeBtn.addEventListener('click', this.toggleTheme.bind(this));
        }

        // Language toggle
        const langBtn = document.querySelector('[onclick="toggleLanguage()"]');
        if (langBtn) {
            langBtn.addEventListener('click', this.toggleLanguage.bind(this));
        }
    }

    toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu) {
            mobileMenu.classList.toggle('hidden');
        }
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.currentTheme);
        this.setupTheme();
    }

    toggleLanguage() {
        this.currentLanguage = this.currentLanguage === 'ar' ? 'en' : 'ar';
        localStorage.setItem('language', this.currentLanguage);
        this.setupLanguage();
        // Reload page to update content
        window.location.reload();
    }

    getBodyClasses() {
        const baseClasses = 'transition-colors duration-300 scroll-smooth';
        if (this.currentTheme === 'dark') {
            return `${baseClasses} bg-slate-950 text-white`;
        } else {
            return `${baseClasses} bg-gradient-to-br from-slate-50 to-slate-100 text-slate-900`;
        }
    }
}

// Global functions for onclick handlers
function toggleTheme() {
    if (window.eMenuApp) {
        window.eMenuApp.toggleTheme();
    }
}

function toggleLanguage() {
    if (window.eMenuApp) {
        window.eMenuApp.toggleLanguage();
    }
}

function toggleMobileMenu() {
    if (window.eMenuApp) {
        window.eMenuApp.toggleMobileMenu();
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.eMenuApp = new EMenuApp();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EMenuApp;
}
