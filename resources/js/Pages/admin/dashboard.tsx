import { Head } from '@inertiajs/react';
import IconMenuDashboard from '@/admin/components/Icon/Menu/IconMenuDashboard';
import { 
    Newspaper, MessageSquare, Video, Play, Radio, Layers, 
    Share2, Users, Settings, TrendingUp, Eye, UserPlus 
} from 'lucide-react';
import '@/../css/admin.css';

export default function Dashboard() {
    const stats = [
        { title: 'إجمالي الأخبار', value: '1,250', icon: Newspaper, color: 'text-blue-600', bg: 'bg-blue-100', trend: '+12%' },
        { title: 'التعليقات الجديدة', value: '340', icon: MessageSquare, color: 'text-green-600', bg: 'bg-green-100', trend: '+5%' },
        { title: 'مشاهدات الريلز', value: '45.6K', icon: Play, color: 'text-purple-600', bg: 'bg-purple-100', trend: '+28%' },
        { title: 'المشتركون النشطون', value: '2,100', icon: Users, color: 'text-orange-600', bg: 'bg-orange-100', trend: '+10%' },
    ];

    const recentActivities = [
        { id: 1, type: 'news', title: 'تم نشر خبر جديد: "تطورات الذكاء الاصطناعي"', time: 'منذ 5 دقائق', user: 'أحمد علي' },
        { id: 2, type: 'comment', title: 'تعليق جديد على خبر "أسعار الذهب اليوم"', time: 'منذ 12 دقيقة', user: 'سارة خالد' },
        { id: 3, type: 'video', title: 'تم رفع فيديو جديد في قسم الرياضة', time: 'منذ 25 دقيقة', user: 'ياسين محمد' },
        { id: 4, type: 'ads', title: 'بدء حملة إعلانية جديدة: "تخفيضات الربيع"', time: 'منذ 40 دقيقة', user: 'مدير الإعلانات' },
    ];

    return (
        <>
            <Head title="لوحة التحكم الكاملة | TailAdmin" />
            
            <div className="animate-in fade-in slide-in-from-bottom-4 duration-700">
                {/* Header Welcome */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-[#1c2434] dark:text-white">مرحباً بك، محمد أحمد 👋</h1>
                        <p className="text-gray-500 mt-1">إليك نظرة شاملة على أداء موقعك اليوم.</p>
                    </div>
                    <div className="flex gap-3">
                        <button className="flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1c2434] border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 transition-all">
                            تصدير التقارير
                        </button>
                        <button className="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:opacity-90 transition-all">
                            تحديث البيانات
                        </button>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
                    {stats.map((stat, idx) => (
                        <div key={idx} className="panel bg-white dark:bg-[#1c2434] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 group hover:scale-[1.02] transition-all">
                            <div className="flex items-center justify-between mb-4">
                                <div className={`p-3 rounded-xl ${stat.bg} ${stat.color}`}>
                                    <stat.icon className="h-6 w-6" />
                                </div>
                                <span className="text-xs font-bold text-green-500 bg-green-50 dark:bg-green-500/10 px-2 py-1 rounded-lg">
                                    {stat.trend}
                                </span>
                            </div>
                            <div>
                                <h4 className="text-2xl font-black text-[#1c2434] dark:text-white">{stat.value}</h4>
                                <p className="text-sm font-bold text-gray-400 mt-1">{stat.title}</p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
                    {/* Charts Section */}
                    <div className="lg:col-span-8 space-y-6">
                        <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5">
                            <div className="flex items-center justify-between mb-6">
                                <h3 className="text-lg font-bold flex items-center gap-2 text-[#1c2434] dark:text-white">
                                    <TrendingUp className="h-5 w-5 text-primary" />
                                    إحصائيات الزيارات (Weekly)
                                </h3>
                                <div className="flex gap-2">
                                    <span className="flex items-center gap-1 text-xs font-bold text-gray-400"><span className="h-2 w-2 rounded-full bg-primary"></span> الزوار</span>
                                    <span className="flex items-center gap-1 text-xs font-bold text-gray-400"><span className="h-2 w-2 rounded-full bg-blue-400"></span> المشاهدات</span>
                                </div>
                            </div>
                            <div className="h-[300px] w-full bg-gray-50 dark:bg-white/5 rounded-xl border-2 border-dashed border-gray-100 dark:border-white/10 flex items-center justify-center relative overflow-hidden">
                                {/* SVG Mockup Chart */}
                                <svg className="absolute inset-0 w-full h-full p-4" preserveAspectRatio="none">
                                    <path d="M0 250 Q 100 150, 200 200 T 400 100 T 600 180 T 800 50" fill="none" stroke="currentColor" strokeWidth="4" className="text-primary opacity-20" />
                                    <path d="M0 250 Q 100 150, 200 200 T 400 100 T 600 180 T 800 50" fill="none" stroke="currentColor" strokeWidth="4" className="text-primary" style={{strokeDasharray: '1000', strokeDashoffset: '1000', animation: 'dash 3s linear forwards'}} />
                                </svg>
                                <span className="text-gray-400 font-bold z-10">تحليلات الأداء المتقدمة</span>
                            </div>
                        </div>

                        {/* Quick Actions Grid */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {[
                                { title: 'إضافة خبر', icon: Newspaper, color: 'bg-blue-500' },
                                { title: 'بث مباشر', icon: Radio, color: 'bg-red-500' },
                                { title: 'رفع فيديو', icon: Video, color: 'bg-purple-500' },
                                { title: 'إرسال واتساب', icon: Share2, color: 'bg-green-500' },
                            ].map((action, idx) => (
                                <button key={idx} className="panel flex flex-col items-center justify-center p-4 bg-white dark:bg-[#1c2434] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 hover:border-primary/50 transition-all group">
                                    <div className={`p-3 rounded-xl ${action.color} text-white mb-3 group-hover:scale-110 transition-transform`}>
                                        <action.icon className="h-5 w-5" />
                                    </div>
                                    <span className="text-xs font-bold text-[#1c2434] dark:text-white">{action.title}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Right Sidebar Section */}
                    <div className="lg:col-span-4 space-y-6">
                        {/* Recent Activity */}
                        <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5">
                            <h3 className="text-lg font-bold mb-6 text-[#1c2434] dark:text-white">آخر النشاطات</h3>
                            <div className="space-y-6">
                                {recentActivities.map((activity) => (
                                    <div key={activity.id} className="relative flex gap-4 pl-4 border-l-2 border-gray-50 dark:border-white/5 last:border-0 pb-6 last:pb-0">
                                        <div className="absolute -left-[9px] top-0 h-4 w-4 rounded-full bg-primary border-4 border-white dark:border-[#1c2434]"></div>
                                        <div className="flex-1">
                                            <p className="text-sm font-bold text-[#1c2434] dark:text-white leading-tight">{activity.title}</p>
                                            <div className="flex items-center justify-between mt-2">
                                                <span className="text-[10px] font-bold text-gray-400">{activity.user}</span>
                                                <span className="text-[10px] font-medium text-gray-400">{activity.time}</span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <button className="w-full mt-6 py-2 text-xs font-bold text-primary hover:underline">عرض جميع النشاطات</button>
                        </div>

                        {/* Top Categories */}
                        <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-white/5">
                            <h3 className="text-lg font-bold mb-4 text-[#1c2434] dark:text-white">الأقسام الأكثر تفاعلاً</h3>
                            <div className="space-y-4">
                                {[
                                    { name: 'سياسة', count: '450', color: 'bg-primary' },
                                    { name: 'رياضة', count: '320', color: 'bg-blue-400' },
                                    { name: 'فن', count: '280', color: 'bg-purple-400' },
                                    { name: 'تكنولوجيا', count: '150', color: 'bg-green-400' },
                                ].map((cat, idx) => (
                                    <div key={idx} className="space-y-1">
                                        <div className="flex justify-between text-xs font-bold">
                                            <span className="dark:text-white">{cat.name}</span>
                                            <span className="text-gray-400">{cat.count} تفاعل</span>
                                        </div>
                                        <div className="h-1.5 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div className={`h-full ${cat.color}`} style={{ width: `${(parseInt(cat.count) / 500) * 100}%` }}></div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style dangerouslySetInnerHTML={{ __html: `
                @keyframes dash {
                    to {
                        stroke-dashoffset: 0;
                    }
                }
            ` }} />
        </>
    );
}
