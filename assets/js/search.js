// Search Page Functionality

class SearchPage {
    constructor() {
        this.searchQuery = '';
        this.filters = {
            city: '',
            category: '',
            rating: ''
        };
        this.restaurants = [];
        this.filteredRestaurants = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadRestaurants();
        this.setupLucideIcons();
    }

    setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value;
                this.performSearch();
            });

            // Search on Enter key
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch();
                }
            });
        }

        // Filter selects
        const cityFilter = document.getElementById('city-filter');
        const categoryFilter = document.getElementById('category-filter');
        const ratingFilter = document.getElementById('rating-filter');

        if (cityFilter) {
            cityFilter.addEventListener('change', (e) => {
                this.filters.city = e.target.value;
                this.performSearch();
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.filters.category = e.target.value;
                this.performSearch();
            });
        }

        if (ratingFilter) {
            ratingFilter.addEventListener('change', (e) => {
                this.filters.rating = e.target.value;
                this.performSearch();
            });
        }
    }

    async loadRestaurants() {
        try {
            this.showLoading(true);
            
            if (window.apiClient) {
                const response = await window.apiClient.getRestaurants();
                if (response.ok && response.data) {
                    this.restaurants = response.data;
                } else {
                    this.loadMockRestaurants();
                }
            } else {
                this.loadMockRestaurants();
            }
            
            this.performSearch();
        } catch (error) {
            console.log('Using mock data for restaurants');
            this.loadMockRestaurants();
            this.performSearch();
        } finally {
            this.showLoading(false);
        }
    }

    loadMockRestaurants() {
        this.restaurants = [
            {
                id: 1,
                name: 'مطعم الذوق الشامي',
                slug: 'al-thawq-al-shami',
                image: 'https://images.unsplash.com/photo-1504674900967-a8bd7f9d1b0e?w=400&h=300&fit=crop',
                rating: 4.8,
                reviews: 245,
                city: 'دمشق',
                category: 'طعام شامي',
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
                category: 'برجر',
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
                category: 'مأكولات بحرية',
                cuisine: 'بحري',
            },
            {
                id: 4,
                name: 'Pizza Napoli',
                slug: 'pizza-napoli',
                image: 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=400&h=300&fit=crop',
                rating: 4.6,
                reviews: 156,
                city: 'حمص',
                category: 'بيتزا',
                cuisine: 'إيطالي',
            },
            {
                id: 5,
                name: 'مطعم المشاوي الذهبية',
                slug: 'al-mashawi-al-thahabiya',
                image: 'https://images.unsplash.com/photo-1555939594-58d7cb561404?w=400&h=300&fit=crop',
                rating: 4.9,
                reviews: 278,
                city: 'حلب',
                category: 'مشاوي',
                cuisine: 'عربي',
            },
            {
                id: 6,
                name: 'Café Damascus',
                slug: 'cafe-damascus',
                image: 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=400&h=300&fit=crop',
                rating: 4.4,
                reviews: 134,
                city: 'دمشق',
                category: 'مقاهي',
                cuisine: 'عربي',
            }
        ];
    }

    performSearch() {
        this.filteredRestaurants = this.restaurants.filter(restaurant => {
            const matchesSearch = !this.searchQuery || 
                restaurant.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                restaurant.cuisine.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                restaurant.category.toLowerCase().includes(this.searchQuery.toLowerCase());
            
            const matchesCity = !this.filters.city || restaurant.city === this.filters.city;
            const matchesCategory = !this.filters.category || restaurant.category === this.filters.category;
            const matchesRating = !this.filters.rating || restaurant.rating >= parseFloat(this.filters.rating);
            
            return matchesSearch && matchesCity && matchesCategory && matchesRating;
        });

        this.renderResults();
        this.updateResultsInfo();
    }

    renderResults() {
        const resultsGrid = document.getElementById('results-grid');
        const noResults = document.getElementById('no-results');
        
        if (!resultsGrid) return;

        if (this.filteredRestaurants.length === 0) {
            resultsGrid.innerHTML = '';
            if (noResults) {
                noResults.classList.remove('hidden');
            }
            return;
        }

        if (noResults) {
            noResults.classList.add('hidden');
        }

        resultsGrid.innerHTML = this.filteredRestaurants.map((restaurant, index) => `
            <a href="restaurant.html?slug=${restaurant.slug}" class="group">
                <div class="overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 cursor-pointer animate-fade-in-up hover-lift bg-white hover:border-orange-500 border border-slate-200 rounded-lg"
                     style="animation-delay: ${index * 0.1}s">
                    <!-- Image -->
                    <div class="relative h-48 overflow-hidden">
                        <img src="${restaurant.image}" 
                             alt="${restaurant.name}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <h3 class="text-lg font-bold mb-2 group-hover:text-orange-500 transition-colors">
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

                        <!-- Location & Category -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="map-pin" class="w-4 h-4 text-orange-500"></i>
                                <span>${restaurant.city}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="chef-hat" class="w-4 h-4 text-orange-500"></i>
                                <span>${restaurant.cuisine}</span>
                            </div>
                        </div>

                        <!-- View Menu Button -->
                        <button class="w-full mt-4 bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 text-white py-2 px-4 rounded-lg font-semibold transition-all">
                            عرض القائمة
                        </button>
                    </div>
                </div>
            </a>
        `).join('');

        // Re-initialize Lucide icons
        this.setupLucideIcons();
    }

    updateResultsInfo() {
        const resultsCount = document.getElementById('results-count');
        const searchQueryDisplay = document.getElementById('search-query-display');
        const searchQueryText = document.getElementById('search-query-text');

        if (resultsCount) {
            resultsCount.textContent = this.filteredRestaurants.length;
        }

        if (this.searchQuery) {
            if (searchQueryDisplay) {
                searchQueryDisplay.style.display = 'block';
            }
            if (searchQueryText) {
                searchQueryText.textContent = this.searchQuery;
            }
        } else {
            if (searchQueryDisplay) {
                searchQueryDisplay.style.display = 'none';
            }
        }
    }

    showLoading(show) {
        const loadingSpinner = document.getElementById('loading-spinner');
        if (loadingSpinner) {
            if (show) {
                loadingSpinner.classList.remove('hidden');
            } else {
                loadingSpinner.classList.add('hidden');
            }
        }
    }

    setupLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Global function for search button
function performSearch() {
    if (window.searchPage) {
        window.searchPage.performSearch();
    }
}

// Initialize search page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.searchPage = new SearchPage();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchPage;
}
