import { useEffect, useState } from 'react';
import axios from 'axios';
import { useRoute } from 'wouter';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useTheme } from '@/contexts/ThemeContext';
import { Star, MapPin, Phone, Clock, ChefHat, ShoppingCart, X, Plus, Minus, MessageSquare } from 'lucide-react';

// Mock restaurant data
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
  },
};

interface CartItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
}

export default function RestaurantProfile() {
  const [match, params] = useRoute('/restaurant/:slug');
  const { theme } = useTheme();
  const [selectedCategory, setSelectedCategory] = useState(0);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [showCart, setShowCart] = useState(false);
  const [selectedItem, setSelectedItem] = useState<any>(null);
  const [showItemModal, setShowItemModal] = useState(false);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [rating, setRating] = useState(5);
  const [reviewText, setReviewText] = useState('');

  const slug = params?.slug as string;
  const restaurant = mockRestaurantData[slug as keyof typeof mockRestaurantData];

  useEffect(() => {
    if (!slug) return;
    // preload CSRF cookie for public review submission later
    axios.get('/api/auth/csrf-token', { withCredentials: true }).catch(() => {});
  }, [slug]);

  if (!restaurant) {
    return (
      <div className={`min-h-screen flex items-center justify-center ${
        theme === 'dark' ? 'bg-slate-950' : 'bg-slate-100'
      }`}>
        <div className="text-center">
          <ChefHat className="w-16 h-16 mx-auto mb-4 text-slate-400" />
          <p className="text-xl font-semibold">المطعم غير موجود</p>
        </div>
      </div>
    );
  }

  const addToCart = (item: any) => {
    const existingItem = cart.find(i => i.id === item.id);
    if (existingItem) {
      setCart(cart.map(i => 
        i.id === item.id ? { ...i, quantity: i.quantity + 1 } : i
      ));
    } else {
      setCart([...cart, { ...item, quantity: 1 }]);
    }
  };

  const removeFromCart = (itemId: number) => {
    setCart(cart.filter(i => i.id !== itemId));
  };

  const updateQuantity = (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(itemId);
    } else {
      setCart(cart.map(i => 
        i.id === itemId ? { ...i, quantity } : i
      ));
    }
  };

  const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  return (
    <div className={`min-h-screen transition-colors duration-300 scroll-smooth ${
      theme === 'dark' 
        ? 'bg-slate-950 text-white' 
        : 'bg-slate-50 text-slate-900'
    }`}>
      {/* Header with Restaurant Image */}
      <div className="relative h-64 md:h-96 overflow-hidden">
        <img
          src={restaurant.image}
          alt={restaurant.name}
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
        
        {/* Restaurant Info Overlay */}
        <div className="absolute bottom-0 left-0 right-0 p-6 text-white animate-fade-in-up">
          <h1 className="text-4xl font-bold mb-2">{restaurant.name}</h1>
          <div className="flex flex-wrap gap-4">
            <div className="flex items-center gap-2">
              <Star className="w-5 h-5 fill-yellow-400 text-yellow-400" />
              <span className="font-semibold">{restaurant.rating} ({restaurant.reviews} تقييم)</span>
            </div>
            <div className="flex items-center gap-2">
              <MapPin className="w-5 h-5" />
              <span>{restaurant.city}</span>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="absolute top-4 right-4 flex gap-2">
          <button
            onClick={() => setShowReviewModal(true)}
            className="bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-full shadow-lg transition-all relative hover-glow"
          >
            <MessageSquare className="w-6 h-6" />
          </button>
          <button
            onClick={() => setShowCart(!showCart)}
            className="bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-full shadow-lg transition-all relative hover-glow"
          >
            <ShoppingCart className="w-6 h-6" />
            {cart.length > 0 && (
              <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center animate-bounce-in">
                {cart.length}
              </span>
            )}
          </button>
        </div>
      </div>

      {/* Restaurant Details */}
      <div className={`border-b transition-colors ${
        theme === 'dark' ? 'border-slate-800' : 'border-slate-200'
      }`}>
        <div className="container mx-auto px-4 py-6">
          <p className={`mb-4 ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
            {restaurant.description}
          </p>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex items-center gap-2 animate-fade-in-up">
              <Phone className="w-5 h-5 text-orange-500" />
              <span>{restaurant.phone}</span>
            </div>
            <div className="flex items-center gap-2 animate-fade-in-up" style={{ animationDelay: '0.1s' }}>
              <MapPin className="w-5 h-5 text-orange-500" />
              <span>{restaurant.address}</span>
            </div>
            <div className="flex items-center gap-2 animate-fade-in-up" style={{ animationDelay: '0.2s' }}>
              <Clock className="w-5 h-5 text-orange-500" />
              <span>{restaurant.hours}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Menu */}
          <div className="lg:col-span-2">
            {/* Category Tabs */}
            <div className="flex gap-2 mb-6 overflow-x-auto pb-2">
              {restaurant.categories.map((category, index) => (
                <button
                  key={category.id}
                  onClick={() => setSelectedCategory(index)}
                  className={`px-4 py-2 rounded-lg whitespace-nowrap transition-all animate-fade-in-up ${
                    selectedCategory === index
                      ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white'
                      : theme === 'dark'
                      ? 'bg-slate-800 hover:bg-slate-700'
                      : 'bg-slate-200 hover:bg-slate-300'
                  }`}
                  style={{ animationDelay: `${index * 0.1}s` }}
                >
                  {category.name}
                </button>
              ))}
            </div>

            {/* Menu Items */}
            <div className="space-y-4">
              {restaurant.categories[selectedCategory]?.items.map((item: any, index: number) => (
                <Card
                  key={item.id}
                  className={`overflow-hidden transition-all hover:shadow-lg cursor-pointer animate-fade-in-up hover-lift ${
                    theme === 'dark'
                      ? 'bg-slate-800 border-slate-700 hover:border-orange-500'
                      : 'bg-white hover:border-orange-500'
                  }`}
                  style={{
                    animationDelay: `${index * 0.1}s`,
                  }}
                  onClick={() => {
                    setSelectedItem(item);
                    setShowItemModal(true);
                  }}
                >
                  <div className="flex gap-4 p-4">
                    <img
                      src={item.image}
                      alt={item.name}
                      className="w-24 h-24 object-cover rounded-lg"
                    />
                    <div className="flex-1">
                      <h3 className="text-lg font-bold mb-1">{item.name}</h3>
                      <p className={`text-sm mb-3 ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                        {item.description}
                      </p>
                      <div className="flex items-center justify-between">
                        <span className="text-xl font-bold text-orange-500">
                          {item.price.toLocaleString()} ل.س
                        </span>
                        <Button
                          onClick={(e) => {
                            e.stopPropagation();
                            addToCart(item);
                          }}
                          className="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 transition-all"
                        >
                          <Plus className="w-4 h-4 ml-2" />
                          أضف
                        </Button>
                      </div>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </div>

          {/* Cart Sidebar */}
          {showCart && (
            <div className={`rounded-lg p-6 h-fit sticky top-20 transition-colors animate-slide-in-right ${
              theme === 'dark'
                ? 'bg-slate-800 border border-slate-700'
                : 'bg-white border border-slate-200'
            }`}>
              <h2 className="text-2xl font-bold mb-4">سلة الطلب</h2>
              
              {cart.length > 0 ? (
                <>
                  <div className="space-y-3 mb-4 max-h-64 overflow-y-auto">
                    {cart.map((item, idx) => (
                      <div
                        key={item.id}
                        className={`flex items-center justify-between p-3 rounded-lg animate-fade-in-up ${
                          theme === 'dark' ? 'bg-slate-700' : 'bg-slate-100'
                        }`}
                        style={{ animationDelay: `${idx * 0.05}s` }}
                      >
                        <div className="flex-1">
                          <p className="font-semibold text-sm">{item.name}</p>
                          <p className="text-orange-500 text-sm">
                            {(item.price * item.quantity).toLocaleString()} ل.س
                          </p>
                        </div>
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                            className={`p-1 rounded transition-colors hover-lift ${
                              theme === 'dark'
                                ? 'hover:bg-slate-600'
                                : 'hover:bg-slate-200'
                            }`}
                          >
                            <Minus className="w-4 h-4" />
                          </button>
                          <span className="w-6 text-center font-semibold">{item.quantity}</span>
                          <button
                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                            className={`p-1 rounded transition-colors hover-lift ${
                              theme === 'dark'
                                ? 'hover:bg-slate-600'
                                : 'hover:bg-slate-200'
                            }`}
                          >
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>

                  <div className={`border-t pt-4 mb-4 ${
                    theme === 'dark' ? 'border-slate-700' : 'border-slate-200'
                  }`}>
                    <div className="flex justify-between mb-2">
                      <span>المجموع:</span>
                      <span className="font-bold text-lg text-orange-500">
                        {totalPrice.toLocaleString()} ل.س
                      </span>
                    </div>
                  </div>

                  <Button className="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 mb-2 transition-all">
                    إتمام الطلب
                  </Button>
                  <Button
                    variant="outline"
                    className="w-full"
                    onClick={() => setCart([])}
                  >
                    مسح السلة
                  </Button>
                </>
              ) : (
                <p className={`text-center py-8 ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                  السلة فارغة
                </p>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Item Modal */}
      {showItemModal && selectedItem && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 animate-fade-in">
          <Card className={`max-w-md w-full transition-colors animate-scale-up ${
            theme === 'dark'
              ? 'bg-slate-800 border-slate-700'
              : 'bg-white'
          }`}>
            <div className="relative">
              <button
                onClick={() => setShowItemModal(false)}
                className="absolute top-2 right-2 p-2 bg-black/50 text-white rounded-lg hover:bg-black/70 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
              <img
                src={selectedItem.image}
                alt={selectedItem.name}
                className="w-full h-48 object-cover"
              />
            </div>
            <div className="p-6">
              <h3 className="text-2xl font-bold mb-2">{selectedItem.name}</h3>
              <p className={`mb-4 ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                {selectedItem.description}
              </p>
              <div className="flex items-center justify-between">
                <span className="text-2xl font-bold text-orange-500">
                  {selectedItem.price.toLocaleString()} ل.س
                </span>
                <Button
                  onClick={() => {
                    addToCart(selectedItem);
                    setShowItemModal(false);
                  }}
                  className="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 transition-all"
                >
                  <Plus className="w-4 h-4 ml-2" />
                  أضف للسلة
                </Button>
              </div>
            </div>
          </Card>
        </div>
      )}

      {/* Review Modal */}
      {showReviewModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 animate-fade-in">
          <Card className={`max-w-md w-full transition-colors animate-scale-up ${
            theme === 'dark'
              ? 'bg-slate-800 border-slate-700'
              : 'bg-white'
          }`}>
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h3 className="text-2xl font-bold">أضف تقييمك</h3>
                <button
                  onClick={() => setShowReviewModal(false)}
                  className="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Rating Stars */}
              <div className="mb-6">
                <label className="block text-sm font-semibold mb-3">التقييم</label>
                <div className="flex gap-2 justify-end">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      key={star}
                      onClick={() => setRating(star)}
                      className="transition-transform hover:scale-110"
                    >
                      <Star
                        className={`w-8 h-8 ${
                          star <= rating
                            ? 'fill-yellow-400 text-yellow-400'
                            : theme === 'dark'
                            ? 'text-slate-600'
                            : 'text-slate-300'
                        }`}
                      />
                    </button>
                  ))}
                </div>
              </div>

              {/* Review Text */}
              <div className="mb-6">
                <label className="block text-sm font-semibold mb-2">تعليقك</label>
                <textarea
                  value={reviewText}
                  onChange={(e) => setReviewText(e.target.value)}
                  placeholder="شارك تجربتك مع المطعم..."
                  className={`w-full px-4 py-3 rounded-lg border transition-all h-24 resize-none ${
                    theme === 'dark'
                      ? 'bg-slate-700 border-slate-600 text-white placeholder-slate-400 focus:border-blue-500'
                      : 'bg-slate-100 border-slate-300 focus:border-blue-500'
                  }`}
                />
              </div>

              {/* Buttons */}
              <div className="flex gap-3">
                <Button
                  onClick={() => {
                    setShowReviewModal(false);
                    setRating(5);
                    setReviewText('');
                  }}
                  variant="outline"
                  className="flex-1"
                >
                  إلغاء
                </Button>
                <Button
                  onClick={async () => {
                    try {
                      await axios.post(`/api/restaurants/${slug}/review`, { rating, comment: reviewText });
                    } catch {}
                    setShowReviewModal(false);
                    setRating(5);
                    setReviewText('');
                  }}
                  className="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:opacity-90 text-white"
                >
                  إرسال التقييم
                </Button>
              </div>
            </div>
          </Card>
        </div>
      )}
    </div>
  );
}

