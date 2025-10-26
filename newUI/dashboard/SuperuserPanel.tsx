import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useTheme } from '@/contexts/ThemeContext';
import { LogOut, BarChart3, Store, Settings, Users, Package, Plus, Edit, Trash2, Power } from 'lucide-react';
import { Link } from 'wouter';

export default function SuperuserPanel() {
  const { theme } = useTheme();
  const [activeTab, setActiveTab] = useState('dashboard');

  const tabs = [
    { id: 'dashboard', label: 'لوحة المعلومات', icon: BarChart3 },
    { id: 'restaurants', label: 'إدارة المطاعم', icon: Store },
    { id: 'plans', label: 'إدارة الخطط', icon: Package },
    { id: 'users', label: 'إدارة المستخدمين', icon: Users },
    { id: 'settings', label: 'الإعدادات', icon: Settings },
  ];

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard theme={theme} />;
      case 'restaurants':
        return <RestaurantsManagement theme={theme} />;
      case 'plans':
        return <PlansManagement theme={theme} />;
      case 'users':
        return <UsersManagement theme={theme} />;
      case 'settings':
        return <SystemSettings theme={theme} />;
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
            <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
              <Settings className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="font-bold text-lg">لوحة تحكم المشرف</h1>
              <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                نظام إدارة المنصة
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
                        ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
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

// Dashboard Component
function Dashboard({ theme }: { theme: string }) {
  const stats = [
    { label: 'إجمالي المطاعم', value: '245', color: 'from-blue-500 to-blue-600' },
    { label: 'المطاعم النشطة', value: '198', color: 'from-green-500 to-green-600' },
    { label: 'المستخدمين', value: '1,234', color: 'from-orange-500 to-orange-600' },
    { label: 'الإيرادات الشهرية', value: '$12,450', color: 'from-purple-500 to-purple-600' },
  ];

  return (
    <div className="space-y-6 animate-fade-in-up">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {stats.map((stat, index) => (
          <Card
            key={index}
            className={`p-6 border-l-4 transition-all hover:shadow-lg ${
              theme === 'dark'
                ? 'bg-slate-800 border-slate-700'
                : 'bg-white'
            }`}
          >
            <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
              {stat.label}
            </p>
            <p className={`text-3xl font-bold mt-2 bg-gradient-to-r ${stat.color} bg-clip-text text-transparent`}>
              {stat.value}
            </p>
          </Card>
        ))}
      </div>

      {/* Recent Activity */}
      <Card className={`p-6 ${
        theme === 'dark'
          ? 'bg-slate-800 border-slate-700'
          : 'bg-white'
      }`}>
        <h3 className="text-xl font-bold mb-4">آخر النشاطات</h3>
        <div className="space-y-3">
          {[1, 2, 3].map((i) => (
            <div
              key={i}
              className={`p-3 rounded-lg ${
                theme === 'dark'
                  ? 'bg-slate-700'
                  : 'bg-slate-100'
              }`}
            >
              <p className="font-semibold">تم تفعيل مطعم جديد</p>
              <p className={`text-sm ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
                قبل ساعة واحدة
              </p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}

// Restaurants Management Component
function RestaurantsManagement({ theme }: { theme: string }) {
  const [restaurants] = useState([
    { id: 1, name: 'مطعم الذوق الشامي', city: 'دمشق', status: 'active' },
    { id: 2, name: 'Burger Palace', city: 'دمشق', status: 'active' },
    { id: 3, name: 'مطعم البحر الأحمر', city: 'اللاذقية', status: 'pending' },
  ]);

  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">إدارة المطاعم</h2>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className={`border-b ${theme === 'dark' ? 'border-slate-700' : 'border-slate-200'}`}>
              <th className="text-right px-4 py-3 font-semibold">اسم المطعم</th>
              <th className="text-right px-4 py-3 font-semibold">المدينة</th>
              <th className="text-right px-4 py-3 font-semibold">الحالة</th>
              <th className="text-right px-4 py-3 font-semibold">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            {restaurants.map((restaurant) => (
              <tr
                key={restaurant.id}
                className={`border-b transition-colors ${
                  theme === 'dark'
                    ? 'border-slate-700 hover:bg-slate-700'
                    : 'border-slate-200 hover:bg-slate-100'
                }`}
              >
                <td className="px-4 py-3">{restaurant.name}</td>
                <td className="px-4 py-3">{restaurant.city}</td>
                <td className="px-4 py-3">
                  <span className={`px-3 py-1 rounded-full text-sm ${
                    restaurant.status === 'active'
                      ? 'bg-green-500/20 text-green-500'
                      : 'bg-yellow-500/20 text-yellow-500'
                  }`}>
                    {restaurant.status === 'active' ? 'نشط' : 'قيد المراجعة'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm">
                      <Power className="w-4 h-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Edit className="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

// Plans Management Component
function PlansManagement({ theme }: { theme: string }) {
  const [plans] = useState([
    { id: 1, name: 'الخطة الأساسية', price: '$9.99', items: 20 },
    { id: 2, name: 'الخطة المتقدمة', price: '$29.99', items: 100 },
    { id: 3, name: 'الخطة الاحترافية', price: '$99.99', items: 'غير محدود' },
  ]);

  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">إدارة الخطط</h2>
        <Button className="bg-gradient-to-r from-purple-500 to-pink-500 hover:opacity-90">
          <Plus className="w-4 h-4 ml-2" />
          إضافة خطة
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {plans.map((plan) => (
          <div
            key={plan.id}
            className={`p-6 rounded-lg border transition-all ${
              theme === 'dark'
                ? 'bg-slate-700 border-slate-600 hover:border-purple-500'
                : 'bg-slate-100 border-slate-300 hover:border-purple-500'
            }`}
          >
            <h3 className="font-bold text-lg mb-2">{plan.name}</h3>
            <p className="text-2xl font-bold text-purple-500 mb-4">{plan.price}</p>
            <p className={`text-sm mb-4 ${theme === 'dark' ? 'text-slate-400' : 'text-slate-600'}`}>
              {plan.items} أصناف
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" className="flex-1">
                <Edit className="w-4 h-4" />
              </Button>
              <Button variant="outline" size="sm" className="flex-1">
                <Trash2 className="w-4 h-4 text-red-500" />
              </Button>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

// Users Management Component
function UsersManagement({ theme }: { theme: string }) {
  const [users] = useState([
    { id: 1, name: 'أحمد محمد', email: 'ahmed@example.com', role: 'restaurant' },
    { id: 2, name: 'فاطمة علي', email: 'fatima@example.com', role: 'restaurant' },
    { id: 3, name: 'محمود حسن', email: 'mahmoud@example.com', role: 'superuser' },
  ]);

  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <h2 className="text-2xl font-bold mb-6">إدارة المستخدمين</h2>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className={`border-b ${theme === 'dark' ? 'border-slate-700' : 'border-slate-200'}`}>
              <th className="text-right px-4 py-3 font-semibold">الاسم</th>
              <th className="text-right px-4 py-3 font-semibold">البريد الإلكتروني</th>
              <th className="text-right px-4 py-3 font-semibold">الدور</th>
              <th className="text-right px-4 py-3 font-semibold">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            {users.map((user) => (
              <tr
                key={user.id}
                className={`border-b transition-colors ${
                  theme === 'dark'
                    ? 'border-slate-700 hover:bg-slate-700'
                    : 'border-slate-200 hover:bg-slate-100'
                }`}
              >
                <td className="px-4 py-3">{user.name}</td>
                <td className="px-4 py-3">{user.email}</td>
                <td className="px-4 py-3">
                  <span className={`px-3 py-1 rounded-full text-sm ${
                    user.role === 'superuser'
                      ? 'bg-purple-500/20 text-purple-500'
                      : 'bg-blue-500/20 text-blue-500'
                  }`}>
                    {user.role === 'superuser' ? 'مشرف' : 'مطعم'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <Button variant="outline" size="sm">
                    <Edit className="w-4 h-4" />
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

// System Settings Component
function SystemSettings({ theme }: { theme: string }) {
  return (
    <Card className={`p-6 animate-fade-in-up ${
      theme === 'dark'
        ? 'bg-slate-800 border-slate-700'
        : 'bg-white'
    }`}>
      <h2 className="text-2xl font-bold mb-6">إعدادات النظام</h2>

      <div className="space-y-6">
        {/* General Settings */}
        <div>
          <h3 className="font-bold mb-4">الإعدادات العامة</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-semibold mb-2">اسم الموقع</label>
              <input
                type="text"
                defaultValue="E-Menu"
                className={`w-full px-4 py-2 rounded-lg border transition-all ${
                  theme === 'dark'
                    ? 'bg-slate-700 border-slate-600 text-white focus:border-purple-500'
                    : 'bg-slate-100 border-slate-300 focus:border-purple-500'
                }`}
              />
            </div>
            <div>
              <label className="block text-sm font-semibold mb-2">البريد الإلكتروني للدعم</label>
              <input
                type="email"
                defaultValue="support@emenu.com"
                className={`w-full px-4 py-2 rounded-lg border transition-all ${
                  theme === 'dark'
                    ? 'bg-slate-700 border-slate-600 text-white focus:border-purple-500'
                    : 'bg-slate-100 border-slate-300 focus:border-purple-500'
                }`}
              />
            </div>
          </div>
        </div>

        {/* Color Settings */}
        <div>
          <h3 className="font-bold mb-4">الألوان</h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-semibold mb-2">اللون الأساسي</label>
              <input
                type="color"
                defaultValue="#f97316"
                className="w-full h-10 rounded-lg cursor-pointer"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold mb-2">لون ثانوي</label>
              <input
                type="color"
                defaultValue="#ef4444"
                className="w-full h-10 rounded-lg cursor-pointer"
              />
            </div>
          </div>
        </div>

        <Button className="bg-gradient-to-r from-purple-500 to-pink-500 hover:opacity-90 w-full md:w-auto">
          حفظ الإعدادات
        </Button>
      </div>
    </Card>
  );
}

