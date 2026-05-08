import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Zap, Shield, AlertTriangle, BarChart3, Save, Globe, 
    RefreshCw, CheckCircle2, XCircle, Clock, Server, Activity, Check
} from 'lucide-react';
import '@/../css/admin.css';

interface CDNSettingsProps {
    settings: {
        cdn_enabled: boolean;
        cdn_provider: string;
        cdn_token: string;
        cdn_zone_id: string;
        cdn_plan: string;
        cdn_auto_purge: boolean;
        cdn_fallback_provider: string;
        cdn_fallback_token: string;
        cdn_fallback_zone_id: string;
        cdn_fallback_plan: string;
        cdn_alert_email: string;
        cdn_alert_slack_webhook: string;
    };
    stats: any;
    circuitStatus: any;
}

export default function CDNSettings({ settings, stats, circuitStatus }: CDNSettingsProps) {
    const [activeTab, setActiveTab] = useState('config');
    const form = useForm(settings);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/settings/cdn', {
            preserveScroll: true,
        });
    };

    const tabs = [
        { id: 'config', title: 'إعدادات الاتصال', icon: Server },
        { id: 'fallback', title: 'المزود الاحتياطي', icon: Shield },
        { id: 'monitoring', title: 'المراقبة والإحصائيات', icon: Activity },
        { id: 'alerts', title: 'التنبيهات', icon: AlertTriangle },
    ];

    return (
        <div className="font-tajawal animate-in fade-in duration-500 pb-12">
            <Head title="إعدادات CDN" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-[#1c2434] dark:text-white">إعدادات شبكة توصيل المحتوى (CDN)</h2>
                        <p className="text-gray-500 text-sm mt-1">إدارة تسريع الموقع وتطهير التخزين المؤقت (Cache Purge)</p>
                    </div>
                    <div className="flex items-center gap-3">
                        {settings.cdn_enabled ? (
                            <div className="flex items-center gap-2 px-3 py-1 bg-green-500/10 text-green-600 border border-green-500/20 text-xs font-bold uppercase tracking-wider">
                                <CheckCircle2 className="w-3 h-3" />
                                مفعل
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 px-3 py-1 bg-red-500/10 text-red-600 border border-red-500/20 text-xs font-bold uppercase tracking-wider">
                                <XCircle className="w-3 h-3" />
                                معطل
                            </div>
                        )}
                        {circuitStatus?.state === 'open' && (
                            <div className="flex items-center gap-2 px-3 py-1 bg-amber-500/10 text-amber-600 border border-amber-500/20 text-xs font-bold uppercase tracking-wider animate-pulse">
                                <AlertTriangle className="w-3 h-3" />
                                قاطع التيار مفتوح (Circuit Open)
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex flex-col md:flex-row gap-6">
                    {/* Sidebar Tabs */}
                    <div className="w-full md:w-72 space-y-2">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`w-full flex items-center gap-3 px-4 py-3 text-sm font-medium transition-all rounded-none border-2 ${
                                    activeTab === tab.id
                                        ? 'bg-[#1c2434] text-white border-[#1c2434] dark:bg-primary dark:border-primary'
                                        : 'bg-white text-gray-600 border-gray-200 hover:border-[#1c2434] dark:bg-[#1c2434] dark:text-gray-400 dark:border-white/10 dark:hover:border-white/30'
                                }`}
                            >
                                <tab.icon className={`w-5 h-5 ${activeTab === tab.id ? 'text-white' : 'text-gray-400'}`} />
                                {tab.title}
                            </button>
                        ))}
                    </div>

                    {/* Main Content */}
                    <div className="flex-1">
                        <form onSubmit={submit} className="space-y-6">
                            {activeTab === 'config' && (
                                <div className="space-y-6">
                                    <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                        <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                            <h3 className="text-lg font-bold flex items-center gap-2">
                                                <Globe className="w-5 h-5 text-primary" />
                                                المزود الأساسي (Primary)
                                            </h3>
                                            <div className="flex items-center gap-2 relative">
                                                <span className="text-sm font-bold">تفعيل CDN</span>
                                                <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.cdn_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                    <div className="w-4 h-4 bg-white transition-all"></div>
                                                </div>
                                                <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.cdn_enabled} onChange={e => form.setData('cdn_enabled', e.target.checked)} />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">المزود</label>
                                                <select value={form.cdn_provider} onChange={e => form.setData('cdn_provider', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none">
                                                    <option value="cloudflare">Cloudflare</option>
                                                </select>
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">الخطة (Plan)</label>
                                                <select value={form.cdn_plan} onChange={e => form.setData('cdn_plan', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none">
                                                    <option value="free">Free</option>
                                                    <option value="pro">Pro</option>
                                                    <option value="business">Business</option>
                                                    <option value="enterprise">Enterprise</option>
                                                </select>
                                            </div>
                                            <div className="space-y-1 md:col-span-2">
                                                <label className="text-sm font-bold">API Token</label>
                                                <input type="password" value={form.cdn_token} onChange={e => form.setData('cdn_token', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="space-y-1 md:col-span-2">
                                                <label className="text-sm font-bold">Zone ID</label>
                                                <input type="text" value={form.cdn_zone_id} onChange={e => form.setData('cdn_zone_id', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="md:col-span-2 pt-2">
                                                <div className="flex items-center gap-3 relative">
                                                    <div className={`w-6 h-6 border-2 flex items-center justify-center transition-all ${form.cdn_auto_purge ? 'bg-primary border-primary' : 'border-gray-300 dark:border-white/20'}`}>
                                                        {form.cdn_auto_purge && <Check className="w-4 h-4 text-white" />}
                                                    </div>
                                                    <input 
                                                        type="checkbox" 
                                                        className="absolute inset-0 opacity-0 cursor-pointer" 
                                                        checked={form.cdn_auto_purge}
                                                        onChange={e => form.setData('cdn_auto_purge', e.target.checked)}
                                                    />
                                                    <span className="text-sm font-bold">تطهير تلقائي (Auto Purge) عند تحديث المحتوى</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'monitoring' && (
                                <div className="space-y-6">
                                    {/* Stats Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        {[
                                            { label: 'إجمالي Purge (24h)', value: stats?.purges || 0, icon: RefreshCw, color: 'text-blue-500' },
                                            { label: 'نسبة النجاح', value: (stats?.success_rate || 0) + '%', icon: CheckCircle2, color: 'text-green-500' },
                                            { label: 'فشل (Failures)', value: stats?.failures || 0, icon: AlertTriangle, color: 'text-red-500' },
                                            { label: 'متوسط الاستجابة', value: (stats?.avg_latency_ms || 0) + 'ms', icon: Clock, color: 'text-amber-500' },
                                        ].map((stat, idx) => (
                                            <div key={idx} className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-200 dark:border-white/10 p-5 rounded-none flex items-center gap-4">
                                                <div className={`p-3 bg-gray-50 dark:bg-black/20 ${stat.color}`}>
                                                    <stat.icon className="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <div className="text-xs font-bold text-gray-500 uppercase">{stat.label}</div>
                                                    <div className="text-xl font-bold dark:text-white">{stat.value}</div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Latency Percentiles */}
                                    <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                        <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                            <Activity className="w-5 h-5 text-primary" />
                                            توزيع زمن الاستجابة (Latency Percentiles)
                                        </h3>
                                        <div className="space-y-6">
                                            {[
                                                { label: 'P50 (Median)', value: stats?.p50_ms || 0, color: 'bg-blue-500' },
                                                { label: 'P95 (Slowest 5%)', value: stats?.p95_ms || 0, color: 'bg-amber-500' },
                                                { label: 'P99 (Extreme Edge)', value: stats?.p99_ms || 0, color: 'bg-red-500' },
                                            ].map((p, idx) => (
                                                <div key={idx} className="space-y-2">
                                                    <div className="flex justify-between text-sm font-bold">
                                                        <span>{p.label}</span>
                                                        <span>{p.value}ms</span>
                                                    </div>
                                                    <div className="h-3 bg-gray-100 dark:bg-black/30 rounded-none overflow-hidden">
                                                        <div 
                                                            className={`h-full ${p.color} transition-all duration-1000`} 
                                                            style={{ width: `${Math.min(100, (p.value / 2000) * 100)}%` }}
                                                        ></div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'alerts' && (
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <AlertTriangle className="w-5 h-5 text-primary" />
                                        إعدادات التنبيهات
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6">
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">البريد الإلكتروني للتنبيهات</label>
                                            <input type="email" value={form.cdn_alert_email} onChange={e => form.setData('cdn_alert_email', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" placeholder="admin@site.com" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Slack Webhook URL</label>
                                            <input type="url" value={form.cdn_alert_slack_webhook} onChange={e => form.setData('cdn_alert_slack_webhook', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" placeholder="https://hooks.slack.com/services/..." />
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end pt-4">
                                <button
                                    disabled={form.processing}
                                    className="flex items-center gap-2 px-10 py-4 bg-[#1c2434] dark:bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50 rounded-none shadow-lg shadow-primary/20"
                                >
                                    <Save className="w-5 h-5" />
                                    حفظ إعدادات CDN
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
