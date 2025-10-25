// E-Menu Application - Main JavaScript File

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
            const response = await fetch('api/restaurants');
            const data = await response.json();
            
            if (data.ok && data.data) {
                this.renderFeaturedRestaurants(data.data.slice(0, 3));
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
