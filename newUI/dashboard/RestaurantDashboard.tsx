import { useEffect, useState } from 'react';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useTheme } from '@/contexts/ThemeContext';
import { ChefHat, LogOut, Settings, Menu, Image, BarChart3, CreditCard, Plus, Edit, Trash2, Upload } from 'lucide-react';
import { Link } from 'wouter';

export default function RestaurantDashboard() {
  const { theme } = useTheme();
  const [activeTab, setActiveTab] = useState('profile');
  const [stats, setStats] = useState<{items:number;reviews:number;visits:number}>({items:0,reviews:0,visits:0});
  useEffect(() => {
    axios.get('/api/restaurant/dashboard', { withCredentials: true })
      .then(r => { if (r.data?.ok) setStats(r.data.data); })
      .catch(() => {});
  }, []);

  const tabs = [
    { id: 'profile', label: 'إعدادات الملف', icon: Settings },
    { id: 'menu', label: 'إدارة المنيو', icon: Menu },
    { id: 'images', label: 'الصور', icon: Image },
    { id: 'statistics', label: 'الإحصائيات', icon: BarChart3 },
    { id: 'subscription', label: 'الاشتراك', icon: CreditCard },
  ];

  const renderContent = () => {
    switch (activeTab) {
      case 'profile':
        return <ProfileSettings theme={theme} />;
      case 'menu':
        return <MenuManager theme={theme} />;
      case 'images':
        return <ImagesManager theme={theme} />;
      case 'statistics':
        return <Statistics theme={theme} stats={stats} />;
      case 'subscription':
        return <Subscription theme={theme} />;
      default:
        return null;
    }
  };

  return (
    <div className={`min-h-screen transition-colors duration-300 ${
      theme === 'dark'
        ? 'bg-slate-950 text-white'
        : 'bg-slate-50 text-slate-900'
    }`}>
      {/* Header */}
      <header className={`sticky top-0 z-50 backdrop-blur-md border-b transition-colors ${
        theme === 'dark'
          ? 'bg-slate-900/80 border-slate-800'
          : 'bg-white/80 border-slate-200'
      }`}>
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
              <ChefHat className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="font-bold text-lg">لوحة تحكم المطعم</h1>
              <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                مطعم الذوق الشامي
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <Link href="/">
              <a className={`p-2 rounded-lg transition-colors ${
                theme === 'dark'
                  ? 'hover:bg-slate-800'
                  : 'hover:bg-slate-200'
              }`}>
                <LogOut className="w-5 h-5" />
              </a>
            </Link>
          </div>
        </div>
      </header>

      {/* Sidebar & Content */}
      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Sidebar */}
          <div className="lg:col-span-1">
            <div className={`rounded-lg p-4 space-y-2 sticky top-24 ${
              theme === 'dark'
                ? 'bg-slate-800 border border-slate-700'
                : 'bg-white border border-slate-200'
            }`}>
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-right ${
                      activeTab === tab.id
                        ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white'
                        : theme === 'dark'
                        ? 'hover:bg-slate-700'
                        : 'hover:bg-slate-100'
                    }`}
                  >
                    <Icon className="w-5 h-5" />
                    <span className="font-semibold">{tab.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Content */}
          <div className="lg:col-span-3">
            {renderContent()}
          </div>
        </div>
      </div>
    </div>
  );
}

// Profile Settings Component
function ProfileSettings({ theme }: { theme: string }) {
  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <h2 className="text-2xl font-bold mb-6">إعدادات الملف الشخصي</h2>

      <div className="space-y-6">
        {/* Logo */}
        <div>
          <label className="block text-sm font-semibold mb-2">شعار المطعم</label>
          <div className={`border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors ${
            theme === 'dark'
              ? 'border-slate-700 hover:border-orange-500'
              : 'border-slate-300 hover:border-orange-500'
          }`}>
            <Upload className="w-8 h-8 mx-auto mb-2 text-slate-400" />
            <p className="text-sm">اسحب الصورة هنا أو انقر للاختيار</p>
          </div>
        </div>

        {/* Restaurant Info */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-semibold mb-2">اسم المطعم</label>
            <input
              type="text"
              defaultValue="مطعم الذوق الشامي"
              className={`w-full px-4 py-2 rounded-lg border transition-all ${
                theme === 'dark'
                  ? 'bg-slate-700 border-slate-600 text-white focus:border-orange-500'
                  : 'bg-slate-100 border-slate-300 focus:border-orange-500'
              }`}
            />
          </div>
          <div>
            <label className="block text-sm font-semibold mb-2">المدينة</label>
            <input
              type="text"
              defaultValue="دمشق"
              className={`w-full px-4 py-2 rounded-lg border transition-all ${
                theme === 'dark'
                  ? 'bg-slate-700 border-slate-600 text-white focus:border-orange-500'
                  : 'bg-slate-100 border-slate-300 focus:border-orange-500'
              }`}
            />
          </div>
        </div>

        {/* Description */}
        <div>
          <label className="block text-sm font-semibold mb-2">الوصف</label>
          <textarea
            defaultValue="مطعم متخصص في الطعام الشامي الأصيل"
            className={`w-full px-4 py-2 rounded-lg border transition-all h-24 ${
              theme === 'dark'
                ? 'bg-slate-700 border-slate-600 text-white focus:border-orange-500'
                : 'bg-slate-100 border-slate-300 focus:border-orange-500'
            }`}
          />
        </div>

        {/* Contact Info */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-semibold mb-2">رقم الهاتف</label>
            <input
              type="tel"
              defaultValue="+963 11 123 4567"
              className={`w-full px-4 py-2 rounded-lg border transition-all ${
                theme === 'dark'
                  ? 'bg-slate-700 border-slate-600 text-white focus:border-orange-500'
                  : 'bg-slate-100 border-slate-300 focus:border-orange-500'
              }`}
            />
          </div>
          <div>
            <label className="block text-sm font-semibold mb-2">البريد الإلكتروني</label>
            <input
              type="email"
              defaultValue="info@restaurant.com"
              className={`w-full px-4 py-2 rounded-lg border transition-all ${
                theme === 'dark'
                  ? 'bg-slate-700 border-slate-600 text-white focus:border-orange-500'
                  : 'bg-slate-100 border-slate-300 focus:border-orange-500'
              }`}
            />
          </div>
        </div>

        <Button className="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 w-full md:w-auto">
          حفظ التغييرات
        </Button>
      </div>
    </Card>
  );
}

// Menu Manager Component
function MenuManager({ theme }: { theme: string }) {
  const [categories] = useState([
    { id: 1, name: 'المقبلات', items: 2 },
    { id: 2, name: 'الأطباق الرئيسية', items: 5 },
  ]);

  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">إدارة المنيو</h2>
        <Button className="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90">
          <Plus className="w-4 h-4 ml-2" />
          إضافة فئة
        </Button>
      </div>

      <div className="space-y-4">
        {categories.map((category) => (
          <div
            key={category.id}
            className={`p-4 rounded-lg border flex items-center justify-between transition-all ${
              theme === 'dark'
                ? 'bg-slate-700 border-slate-600 hover:border-orange-500'
                : 'bg-slate-100 border-slate-300 hover:border-orange-500'
            }`}
          >
            <div>
              <h3 className="font-semibold">{category.name}</h3>
              <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                {category.items} أصناف
              </p>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" size="sm">
                <Edit className="w-4 h-4" />
              </Button>
              <Button variant="outline" size="sm">
                <Trash2 className="w-4 h-4 text-red-500" />
              </Button>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

// Images Manager Component
function ImagesManager({ theme }: { theme: string }) {
  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);

  const onFile = (f: File) => {
    setFile(f);
    setPreview(URL.createObjectURL(f));
  };

  const upload = async () => {
    if (!file) return;
    setUploading(true);
    try {
      const form = new FormData();
      form.append('image', file);
      const res = await axios.post('/api/restaurant/upload-image', form, {
        withCredentials: true,
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data?.ok) {
        setPreview(res.data.thumb || res.data.path);
      }
    } finally {
      setUploading(false);
    }
  };

  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">إدارة الصور</h2>
      </div>

      <div className={`border-2 border-dashed rounded-lg p-12 text-center transition-colors ${
        theme === 'dark'
          ? 'border-slate-700 hover:border-orange-500'
          : 'border-slate-300 hover:border-orange-500'
      }`}>
        <input
          type="file"
          accept="image/*"
          onChange={(e) => e.target.files && onFile(e.target.files[0])}
          className="hidden"
          id="upload-input"
        />
        <label htmlFor="upload-input" className="cursor-pointer block">
          <Upload className="w-12 h-12 mx-auto mb-4 text-slate-400" />
          <p className="text-lg font-semibold mb-2">اسحب الصور هنا أو انقر للاختيار</p>
          <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>PNG, JPG, WEBP</p>
        </label>
        {preview && (
          <div className="mt-6">
            <img src={preview} alt="preview" className="w-40 h-40 object-cover rounded-lg mx-auto" />
          </div>
        )}
        <Button onClick={upload} disabled={!file || uploading} className="mt-6 bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90">
          {uploading ? 'جار الرفع...' : 'رفع الصورة'}
        </Button>
      </div>
    </Card>
  );
}

// Statistics Component
function Statistics({ theme, stats }: { theme: string; stats: {items:number;reviews:number;visits:number} }) {
  return (
    <div className="space-y-6 animate-fade-in-up">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card className={`p-6 ${theme === 'dark' ? 'bg-slate-800 border-slate-700' : 'bg-white'}`}>
          <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>عدد الأصناف</p>
          <p className="text-3xl font-bold text-orange-500 mt-2">{stats.items}</p>
        </Card>
        <Card className={`p-6 ${theme === 'dark' ? 'bg-slate-800 border-slate-700' : 'bg-white'}`}>
          <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>عدد التقييمات</p>
          <p className="text-3xl font-bold text-orange-500 mt-2">{stats.reviews}</p>
        </Card>
        <Card className={`p-6 ${theme === 'dark' ? 'bg-slate-800 border-slate-700' : 'bg-white'}`}>
          <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>عدد الزيارات</p>
          <p className="text-3xl font-bold text-orange-500 mt-2">{stats.visits}</p>
        </Card>
      </div>
    </div>
  );
}

// Subscription Component
function Subscription({ theme }: { theme: string }) {
  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <h2 className="text-2xl font-bold mb-6">خطة الاشتراك</h2>

      <div className="bg-gradient-to-r from-orange-500 to-red-500 rounded-lg p-6 text-white mb-6">
        <h3 className="text-2xl font-bold mb-2">الخطة المتقدمة</h3>
        <p className="mb-4">تاريخ الانتهاء: 2025-12-31</p>
        <div className="space-y-2 text-sm">
          <p>✓ حد أقصى 50 صنف</p>
          <p>✓ 100 صورة</p>
          <p>✓ إحصائيات متقدمة</p>
          <p>✓ دعم العملاء 24/7</p>
        </div>
      </div>

      <Button className="bg-gradient-to-r from-orange-500 to-red-500 hover:opacity-90 w-full">
        ترقية الخطة
      </Button>
    </Card>
  );
}

