// Restaurant Page Functionality

class RestaurantPage {
    constructor() {
        this.restaurant = null;
        this.selectedCategory = 0;
        this.cart = [];
        this.showCart = false;
        this.selectedItem = null;
        this.showItemModal = false;
        this.showReviewModal = false;
        this.rating = 5;
        this.reviewText = '';
        this.slug = '';
        this.init();
    }

    init() {
        this.getSlugFromURL();
        this.setupEventListeners();
        this.loadRestaurant();
        this.setupLucideIcons();
    }

    getSlugFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        this.slug = urlParams.get('slug');
        
        if (!this.slug) {
            this.showError('المطعم غير موجود');
            return;
        }
    }

    setupEventListeners() {
        // Cart toggle
        window.toggleCart = () => this.toggleCart();
        window.closeItemModal = () => this.closeItemModal();
        window.closeReviewModal = () => this.closeReviewModal();
        window.openReviewModal = () => this.openReviewModal();
        window.addToCartFromModal = () => this.addToCartFromModal();
        window.submitReview = () => this.submitReview();
        window.checkout = () => this.checkout();
        window.clearCart = () => this.clearCart();
    }

    async loadRestaurant() {
        try {
            if (window.apiClient) {
                const response = await window.apiClient.getRestaurant(this.slug);
                if (response && (response.success || response.ok)) {
                    const restaurantData = response.data?.restaurant || response.data;
                    if (restaurantData) {
                        this.restaurant = this.normalizeRestaurantData(restaurantData);
                        this.renderRestaurant();
                        await this.loadMenu();
                    } else {
                        this.loadMockRestaurant();
                    }
                } else {
                    this.loadMockRestaurant();
                }
            } else {
                this.loadMockRestaurant();
            }
        } catch (error) {
            console.log('Using mock data for restaurant');
            this.loadMockRestaurant();
        }
    }

    loadMockRestaurant() {
        const mockRestaurantData = {
            'al-thawq-al-shami': {
                id: 1,
                name: 'مطعم الذوق الشامي',
                image: 'https://images.unsplash.com/photo-1504674900967-a8bd7f9d1b0e?w=800&h=400&fit=crop',
                rating: 4.8,
                reviews: 245,
                city: 'دمشق',
                address: 'شارع النيل، دمشق',
                phone: '+963 11 123 4567',
                hours: '11:00 - 23:00',
                description: 'مطعم متخصص في الطعام الشامي الأصيل مع أفضل المكونات المحلية',
                categories: [
                    {
                        id: 1,
                        name: 'المقبلات',
                        items: [
                            { id: 1, name: 'حمص بالطحينة', price: 35000, image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=300&fit=crop', description: 'حمص طازج مع طحينة عالية الجودة' },
                            { id: 2, name: 'بابا غنوج', price: 35000, image: 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?w=300&h=300&fit=crop', description: 'باذنجان مشوي مع طحينة وليمون' },
                        ]
                    },
                    {
                        id: 2,
                        name: 'الأطباق الرئيسية',
                        items: [
                            { id: 3, name: 'كباب لحم', price: 85000, image: 'https://images.unsplash.com/photo-1555939594-58d7cb561404?w=300&h=300&fit=crop', description: 'كباب لحم مشوي على الفحم' },
                            { id: 4, name: 'شاورما دجاج', price: 65000, image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&h=300&fit=crop', description: 'شاورما دجاج طازجة مع الخضار' },
                        ]
                    },
                ]
            }
        };

        const mockRestaurant = mockRestaurantData[this.slug];
        if (!mockRestaurant) {
            this.showError('المطعم غير موجود');
            return;
        }

        this.restaurant = {
            ...this.normalizeRestaurantData(mockRestaurant),
            categories: mockRestaurant.categories
        };

        this.renderRestaurant();
        this.renderMenu();
    }

    async loadMenu() {
        try {
            if (window.apiClient) {
                const response = await window.apiClient.getRestaurantMenu(this.slug);
                if (response && (response.success || response.ok) && response.data) {
                    const categories = Array.isArray(response.data)
                        ? response.data
                        : (Array.isArray(response.data?.data) ? response.data.data : []);

                    if (categories.length) {
                        this.restaurant.categories = categories;
                        this.renderMenu();
                    }
                }
            }
        } catch (error) {
            console.log('Using mock menu data');
            this.renderMenu();
        }
    }

    renderRestaurant() {
        if (!this.restaurant) return;

        // Update restaurant info
        const imageElement = document.getElementById('restaurant-image');
        const ratingElement = document.getElementById('restaurant-rating');

        document.getElementById('restaurant-name').textContent = this.restaurant.name;
        if (imageElement) {
            imageElement.src = this.restaurant.image;
            imageElement.alt = this.restaurant.name;
        }
        if (ratingElement) {
            ratingElement.textContent = `${this.restaurant.rating} (${this.restaurant.reviews} تقييم)`;
        }
        document.getElementById('restaurant-city').textContent = this.restaurant.city;
        document.getElementById('restaurant-description').textContent = this.restaurant.description;
        document.getElementById('restaurant-phone').textContent = this.restaurant.phone;
        document.getElementById('restaurant-address').textContent = this.restaurant.address;
        document.getElementById('restaurant-hours').textContent = this.restaurant.hours;

        // Update page title
        document.title = `${this.restaurant.name} - E-Menu`;
    }

    renderMenu() {
        if (!this.restaurant || !this.restaurant.categories) return;

        this.renderCategoryTabs();
        this.renderMenuItems();
    }

    renderCategoryTabs() {
        const categoryTabs = document.getElementById('category-tabs');
        if (!categoryTabs) return;

        categoryTabs.innerHTML = this.restaurant.categories.map((category, index) => `
            <button onclick="restaurantPage.selectCategory(${index})" 
                    class="px-4 py-2 rounded-lg whitespace-nowrap transition-all animate-fade-in-up ${
                        this.selectedCategory === index
                            ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white'
                            : 'bg-slate-200 hover:bg-slate-300'
                    }"
                    style="animation-delay: ${index * 0.1}s">
                ${category.name}
            </button>
        `).join('');
    }

    renderMenuItems() {
        const menuItems = document.getElementById('menu-items');
        if (!menuItems || !this.restaurant.categories[this.selectedCategory]) return;

        const currentCategory = this.restaurant.categories[this.selectedCategory];
        
        menuItems.innerHTML = currentCategory.items.map((item, index) => `
            <div class="overflow-hidden transition-all hover:shadow-lg cursor-pointer animate-fade-in-up hover-lift bg-white hover:border-orange-500 border border-slate-200 rounded-lg"
                 style="animation-delay: ${index * 0.1}s"
                 onclick="restaurantPage.openItemModal(${item.id})">
                <div class="flex gap-4 p-4">
                    <img src="${item.image}" 
                         alt="${item.name}" 
                         class="w-24 h-24 object-cover rounded-lg">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold mb-1">${item.name}</h3>
                        <p class="text-sm mb-3 text-slate-600">${item.description}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-orange-500">
                                ${item.price.toLocaleString()} ل.س
                            </span>
                            <button onclick="event.stopPropagation(); restaurantPage.addToCart(${item.id})" 
                                    class="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 transition-all text-white py-2 px-4 rounded-lg font-semibold">
                                <i data-lucide="plus" class="w-4 h-4 inline ml-2"></i>
                                أضف
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        this.setupLucideIcons();
    }

    selectCategory(index) {
        this.selectedCategory = index;
        this.renderMenuItems();
    }

    addToCart(itemId) {
        const currentCategory = this.restaurant.categories[this.selectedCategory];
        const item = currentCategory.items.find(i => i.id === itemId);
        
        if (!item) return;

        const existingItem = this.cart.find(i => i.id === item.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({ ...item, quantity: 1 });
        }

        this.updateCartDisplay();
    }

    removeFromCart(itemId) {
        this.cart = this.cart.filter(i => i.id !== itemId);
        this.updateCartDisplay();
    }

    updateQuantity(itemId, quantity) {
        if (quantity <= 0) {
            this.removeFromCart(itemId);
        } else {
            const item = this.cart.find(i => i.id === itemId);
            if (item) {
                item.quantity = quantity;
            }
        }
        this.updateCartDisplay();
    }

    updateCartDisplay() {
        const cartCount = document.getElementById('cart-count');
        const cartSidebar = document.getElementById('cart-sidebar');
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');

        // Update cart count
        if (cartCount) {
            if (this.cart.length > 0) {
                cartCount.textContent = this.cart.length;
                cartCount.classList.remove('hidden');
            } else {
                cartCount.classList.add('hidden');
            }
        }

        // Update cart sidebar
        if (cartSidebar) {
            if (this.showCart) {
                cartSidebar.classList.remove('hidden');
            }
        }

        // Update cart items
        if (cartItems) {
            if (this.cart.length > 0) {
                cartItems.innerHTML = this.cart.map((item, idx) => `
                    <div class="flex items-center justify-between p-3 rounded-lg animate-fade-in-up bg-slate-100"
                         style="animation-delay: ${idx * 0.05}s">
                        <div class="flex-1">
                            <p class="font-semibold text-sm">${item.name}</p>
                            <p class="text-orange-500 text-sm">
                                ${(item.price * item.quantity).toLocaleString()} ل.س
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="restaurantPage.updateQuantity(${item.id}, ${item.quantity - 1})" 
                                    class="p-1 rounded transition-colors hover-lift hover:bg-slate-200">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>
                            <span class="w-6 text-center font-semibold">${item.quantity}</span>
                            <button onclick="restaurantPage.updateQuantity(${item.id}, ${item.quantity + 1})" 
                                    class="p-1 rounded transition-colors hover-lift hover:bg-slate-200">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                cartItems.innerHTML = '<p class="text-center py-8 text-slate-600">السلة فارغة</p>';
            }
        }

        // Update total
        if (cartTotal) {
            const total = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = `${total.toLocaleString()} ل.س`;
        }

        this.setupLucideIcons();
    }

    toggleCart() {
        this.showCart = !this.showCart;
        const cartSidebar = document.getElementById('cart-sidebar');
        if (cartSidebar) {
            if (this.showCart) {
                cartSidebar.classList.remove('hidden');
            } else {
                cartSidebar.classList.add('hidden');
            }
        }
        this.updateCartDisplay();
    }

    openItemModal(itemId) {
        const currentCategory = this.restaurant.categories[this.selectedCategory];
        this.selectedItem = currentCategory.items.find(i => i.id === itemId);
        
        if (!this.selectedItem) return;

        // Update modal content
        document.getElementById('modal-item-image').src = this.selectedItem.image;
        document.getElementById('modal-item-image').alt = this.selectedItem.name;
        document.getElementById('modal-item-name').textContent = this.selectedItem.name;
        document.getElementById('modal-item-description').textContent = this.selectedItem.description;
        document.getElementById('modal-item-price').textContent = `${this.selectedItem.price.toLocaleString()} ل.س`;

        // Show modal
        const modal = document.getElementById('item-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    closeItemModal() {
        const modal = document.getElementById('item-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
        this.selectedItem = null;
    }

    addToCartFromModal() {
        if (this.selectedItem) {
            this.addToCart(this.selectedItem.id);
            this.closeItemModal();
        }
    }

    openReviewModal() {
        this.generateRatingStars();
        const modal = document.getElementById('review-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    closeReviewModal() {
        const modal = document.getElementById('review-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
        this.rating = 5;
        this.reviewText = '';
        document.getElementById('review-text').value = '';
    }

    generateRatingStars() {
        const ratingStars = document.getElementById('rating-stars');
        if (!ratingStars) return;

        ratingStars.innerHTML = [1, 2, 3, 4, 5].map((star) => `
            <button onclick="restaurantPage.setRating(${star})" 
                    class="transition-transform hover:scale-110">
                <i data-lucide="star" 
                   class="w-8 h-8 ${star <= this.rating ? 'fill-yellow-400 text-yellow-400' : 'text-slate-300'}"></i>
            </button>
        `).join('');

        this.setupLucideIcons();
    }

    setRating(rating) {
        this.rating = rating;
        this.generateRatingStars();
    }

    async submitReview() {
        this.reviewText = document.getElementById('review-text').value;

        try {
            if (window.apiClient) {
                const response = await window.apiClient.addRestaurantReview(this.slug, {
                    rating: this.rating,
                    comment: this.reviewText
                });

                if (!response || !(response.success || response.ok)) {
                    throw new Error(response?.message || 'فشل في إرسال التقييم');
                }
            }

            this.closeReviewModal();
            this.showNotification('تم إرسال التقييم بنجاح', 'success');
        } catch (error) {
            this.showNotification(error.message || 'فشل في إرسال التقييم', 'error');
        }
    }

    normalizeRestaurantData(raw) {
        if (!raw) {
            return null;
        }

        const fallbackImage = raw.cover_url
            || raw.cover_image
            || raw.logo_url
            || raw.logo
            || raw.image
            || 'https://via.placeholder.com/800x400?text=E-Menu';

        const averageRating = (typeof raw.average_rating !== 'undefined' && raw.average_rating !== null)
            ? Number(raw.average_rating)
            : null;
        const normalizedRating = Number.isFinite(averageRating)
            ? averageRating.toFixed(1)
            : (typeof raw.rating !== 'undefined' ? raw.rating : '0');

        const reviewsCount = raw.reviews_count ?? raw.reviews ?? 0;
        const normalizedReviews = Number.isFinite(Number(reviewsCount)) ? Number(reviewsCount) : 0;

        const formatHours = this.formatOperatingHours(raw.opening_time, raw.closing_time, raw.hours);

        return {
            ...raw,
            image: fallbackImage,
            rating: normalizedRating,
            reviews: normalizedReviews,
            city: raw.city || raw.city_ar || 'غير محدد',
            description: raw.description || raw.description_ar || 'لا توجد تفاصيل متاحة حالياً.',
            phone: raw.phone || 'غير متوفر',
            address: raw.address || raw.address_ar || 'غير متوفر',
            hours: formatHours
        };
    }

    formatOperatingHours(opening, closing, fallback) {
        if (fallback && fallback.trim() !== '') {
            return fallback;
        }

        if (opening && closing) {
            return `${opening} - ${closing}`;
        }

        return 'ساعات العمل غير محددة';
    }

    checkout() {
        if (this.cart.length === 0) {
            this.showNotification('السلة فارغة', 'error');
            return;
        }
        
        // Here you would typically redirect to checkout page or show checkout modal
        this.showNotification('سيتم توجيهك لصفحة الدفع قريباً', 'info');
    }

    clearCart() {
        this.cart = [];
        this.updateCartDisplay();
        this.showNotification('تم مسح السلة', 'success');
    }

    showError(message) {
        document.body.innerHTML = `
            <div class="min-h-screen flex items-center justify-center bg-slate-100">
                <div class="text-center">
                    <i data-lucide="chef-hat" class="w-16 h-16 mx-auto mb-4 text-slate-400"></i>
                    <p class="text-xl font-semibold">${message}</p>
                </div>
            </div>
        `;
        this.setupLucideIcons();
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
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }

    setupLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Initialize restaurant page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.restaurantPage = new RestaurantPage();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RestaurantPage;
}
