import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Settings, Mail, Share2, BarChart3, Save, Globe, Clock, Phone, 
    AtSign, Copyright, Info, Image as ImageIcon, Trash2, Shield, Check
} from 'lucide-react';
import '@/../css/admin.css';

interface GeneralSettingsProps {
    settings: {
        site_name: string;
        site_email: string;
        site_phone: string;
        site_phone_country_code: string;
        site_phone_country_flag: string;
        site_url: string;
        timezone: string;
        copyright_text: string;
        footer_attribution: string;
        cookies_text: string;
        enable_watermark: boolean;
        enable_comments: boolean;
        active_theme: string;
    };
    media: {
        logo_light_url: string | null;
        logo_dark_url: string | null;
        favicon_url: string | null;
        watermark_image_url: string | null;
    };
    smtp: any;
    social: any;
    tracking: any;
    timezones: Array<{ id: string; label: string }>;
}

export default function GeneralSettings({ settings, media, smtp, social, tracking, timezones }: GeneralSettingsProps) {
    const [activeTab, setActiveTab] = useState('general');

    const generalForm = useForm(settings);
    const smtpForm = useForm(smtp);
    const socialForm = useForm(social);
    const trackingForm = useForm(tracking);

    const submitGeneral = (e: React.FormEvent) => {
        e.preventDefault();
        generalForm.post('/admin/settings/general', { preserveScroll: true });
    };

    const submitSmtp = (e: React.FormEvent) => {
        e.preventDefault();
        smtpForm.post('/admin/settings/smtp', { preserveScroll: true });
    };

    const submitSocial = (e: React.FormEvent) => {
        e.preventDefault();
        socialForm.post('/admin/settings/social', { preserveScroll: true });
    };

    const submitTracking = (e: React.FormEvent) => {
        e.preventDefault();
        trackingForm.post('/admin/settings/tracking', { preserveScroll: true });
    };

    const handleMediaUpload = (collection: string, file: File) => {
        const formData = new FormData();
        formData.append('collection', collection);
        formData.append('file', file);
        router.post('/admin/settings/media', formData, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const clearMedia = (collection: string) => {
        if (confirm('Are you sure you want to remove this image?')) {
            router.delete('/admin/settings/media', {
                data: { collection },
                preserveScroll: true,
            });
        }
    };

    const tabs = [
        { id: 'general', title: 'الإعدادات العامة', icon: Settings },
        { id: 'smtp', title: 'إعدادات SMTP', icon: Mail },
        { id: 'social', title: 'روابط التواصل', icon: Share2 },
        { id: 'tracking', title: 'أكواد التتبع', icon: BarChart3 },
    ];

    return (
        <div className="font-tajawal animate-in fade-in duration-500 pb-12">
            <Head title="إعدادات الموقع" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-[#1c2434] dark:text-white">إعدادات الموقع</h2>
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
                    <div className="flex-1 space-y-6">
                        {activeTab === 'general' && (
                            <div className="space-y-6">
                                {/* Branding & Identity */}
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <ImageIcon className="w-5 h-5 text-primary" />
                                        الهوية والشعار
                                    </h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        {[
                                            { label: 'الشعار الفاتح', collection: 'logo_light', url: media.logo_light_url },
                                            { label: 'الشعار الداكن', collection: 'logo_dark', url: media.logo_dark_url },
                                            { label: 'أيقونة الموقع', collection: 'favicon', url: media.favicon_url },
                                            { label: 'العلامة المائية', collection: 'watermark_image', url: media.watermark_image_url },
                                        ].map((item) => (
                                            <div key={item.collection} className="space-y-3">
                                                <label className="text-sm font-bold text-gray-700 dark:text-gray-300 block">{item.label}</label>
                                                <div className="relative group border-2 border-dashed border-gray-300 dark:border-white/10 p-4 flex flex-col items-center justify-center min-h-[160px] bg-gray-50 dark:bg-black/20">
                                                    {item.url ? (
                                                        <>
                                                            <img src={item.url} alt={item.label} className="max-h-24 object-contain mb-4" />
                                                            <div className="flex gap-2">
                                                                <button 
                                                                    onClick={() => clearMedia(item.collection)}
                                                                    className="p-2 bg-red-500 text-white hover:bg-red-600 transition-colors"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                                <label className="p-2 bg-primary text-white hover:opacity-90 cursor-pointer transition-opacity">
                                                                    <Save className="w-4 h-4" />
                                                                    <input type="file" className="hidden" onChange={(e) => {
                                                                        const file = e.target.files?.[0];
                                                                        if (file) handleMediaUpload(item.collection, file);
                                                                    }} />
                                                                </label>
                                                            </div>
                                                        </>
                                                    ) : (
                                                        <label className="flex flex-col items-center cursor-pointer text-gray-400 hover:text-primary transition-colors">
                                                            <ImageIcon className="w-12 h-12 mb-2 opacity-20" />
                                                            <span className="text-xs">رفع {item.label}</span>
                                                            <input type="file" className="hidden" onChange={(e) => {
                                                                const file = e.target.files?.[0];
                                                                if (file) handleMediaUpload(item.collection, file);
                                                            }} />
                                                        </label>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Site Information */}
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <Info className="w-5 h-5 text-primary" />
                                        معلومات الموقع الأساسية
                                    </h3>
                                    <form onSubmit={submitGeneral} className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label className="text-sm font-bold block">اسم الموقع</label>
                                            <div className="relative">
                                                <Globe className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                                <input
                                                    type="text"
                                                    value={generalForm.site_name}
                                                    onChange={e => generalForm.setData('site_name', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-bold block">رابط الموقع (URL)</label>
                                            <div className="relative">
                                                <AtSign className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                                <input
                                                    type="url"
                                                    value={generalForm.site_url}
                                                    onChange={e => generalForm.setData('site_url', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-bold block">البريد الإلكتروني الرسمي</label>
                                            <div className="relative">
                                                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                                <input
                                                    type="email"
                                                    value={generalForm.site_email}
                                                    onChange={e => generalForm.setData('site_email', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-bold block">رقم الهاتف</label>
                                            <div className="relative">
                                                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                                <input
                                                    type="text"
                                                    value={generalForm.site_phone}
                                                    onChange={e => generalForm.setData('site_phone', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-bold block">التوقيت (Timezone)</label>
                                            <div className="relative">
                                                <Clock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                                <select
                                                    value={generalForm.timezone}
                                                    onChange={e => generalForm.setData('timezone', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all appearance-none"
                                                >
                                                    <option value="">اختر التوقيت...</option>
                                                    {timezones.map((tz) => (
                                                        <option key={tz.id} value={tz.id}>
                                                            {tz.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        </div>

                                        <div className="space-y-2 md:col-span-2">
                                            <label className="text-sm font-bold block">نص حقوق الملكية</label>
                                            <div className="relative">
                                                <Copyright className="absolute left-3 top-4 w-4 h-4 text-gray-400" />
                                                <textarea
                                                    rows={3}
                                                    value={generalForm.copyright_text}
                                                    onChange={e => generalForm.setData('copyright_text', e.target.value)}
                                                    className="w-full pl-10 pr-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all resize-none"
                                                ></textarea>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-6 md:col-span-2 pt-4">
                                            <div className="flex items-center gap-3 relative">
                                                <div className={`w-6 h-6 border-2 flex items-center justify-center transition-all ${generalForm.enable_watermark ? 'bg-primary border-primary' : 'border-gray-300 dark:border-white/20'}`}>
                                                    {generalForm.enable_watermark && <Check className="w-4 h-4 text-white" />}
                                                </div>
                                                <input 
                                                    type="checkbox" 
                                                    className="absolute inset-0 opacity-0 cursor-pointer" 
                                                    checked={generalForm.enable_watermark}
                                                    onChange={e => generalForm.setData('enable_watermark', e.target.checked)}
                                                />
                                                <span className="text-sm font-bold">تفعيل العلامة المائية</span>
                                            </div>

                                            <div className="flex items-center gap-3 relative">
                                                <div className={`w-6 h-6 border-2 flex items-center justify-center transition-all ${generalForm.enable_comments ? 'bg-primary border-primary' : 'border-gray-300 dark:border-white/20'}`}>
                                                    {generalForm.enable_comments && <Check className="w-4 h-4 text-white" />}
                                                </div>
                                                <input 
                                                    type="checkbox" 
                                                    className="absolute inset-0 opacity-0 cursor-pointer" 
                                                    checked={generalForm.enable_comments}
                                                    onChange={e => generalForm.setData('enable_comments', e.target.checked)}
                                                />
                                                <span className="text-sm font-bold">تفعيل التعليقات</span>
                                            </div>
                                        </div>

                                        <div className="md:col-span-2 flex justify-end">
                                            <button
                                                disabled={generalForm.processing}
                                                className="flex items-center gap-2 px-8 py-3 bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50"
                                            >
                                                <Save className="w-5 h-5" />
                                                حفظ الإعدادات العامة
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        )}

                        {activeTab === 'smtp' && (
                            <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                    <Mail className="w-5 h-5 text-primary" />
                                    إعدادات خادم البريد (SMTP)
                                </h3>
                                <form onSubmit={submitSmtp} className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">نوع الخدمة (Mailer)</label>
                                        <select
                                            value={smtpForm.mailer}
                                            onChange={e => smtpForm.setData('mailer', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        >
                                            <option value="smtp">SMTP</option>
                                            <option value="log">Log (للتحقق)</option>
                                        </select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">مضيف SMTP (Host)</label>
                                        <input
                                            type="text"
                                            value={smtpForm.host}
                                            onChange={e => smtpForm.setData('host', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">المنفذ (Port)</label>
                                        <input
                                            type="number"
                                            value={smtpForm.port}
                                            onChange={e => smtpForm.setData('port', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">التشفير (Encryption)</label>
                                        <select
                                            value={smtpForm.encryption}
                                            onChange={e => smtpForm.setData('encryption', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        >
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                            <option value="">None</option>
                                        </select>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">اسم المستخدم</label>
                                        <input
                                            type="text"
                                            value={smtpForm.username}
                                            onChange={e => smtpForm.setData('username', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">كلمة المرور</label>
                                        <input
                                            type="password"
                                            value={smtpForm.password}
                                            onChange={e => smtpForm.setData('password', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">البريد المرسل من</label>
                                        <input
                                            type="email"
                                            value={smtpForm.from_email}
                                            onChange={e => smtpForm.setData('from_email', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">اسم المرسل</label>
                                        <input
                                            type="text"
                                            value={smtpForm.from_name}
                                            onChange={e => smtpForm.setData('from_name', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                        />
                                    </div>

                                    <div className="md:col-span-2 flex justify-end">
                                        <button
                                            disabled={smtpForm.processing}
                                            className="flex items-center gap-2 px-8 py-3 bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50"
                                        >
                                            <Save className="w-5 h-5" />
                                            حفظ إعدادات SMTP
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}

                        {activeTab === 'social' && (
                            <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                    <Share2 className="w-5 h-5 text-primary" />
                                    روابط حسابات التواصل الاجتماعي
                                </h3>
                                <form onSubmit={submitSocial} className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {Object.keys(socialForm.data).map((key) => (
                                        <div key={key} className="space-y-2">
                                            <label className="text-sm font-bold block capitalize">{key.replace('_', ' ')}</label>
                                            <input
                                                type="text"
                                                value={socialForm.data[key]}
                                                onChange={e => socialForm.setData(key, e.target.value)}
                                                className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all"
                                            />
                                        </div>
                                    ))}

                                    <div className="md:col-span-2 flex justify-end">
                                        <button
                                            disabled={socialForm.processing}
                                            className="flex items-center gap-2 px-8 py-3 bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50"
                                        >
                                            <Save className="w-5 h-5" />
                                            حفظ الروابط
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}

                        {activeTab === 'tracking' && (
                            <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                    <BarChart3 className="w-5 h-5 text-primary" />
                                    أكواد التتبع والتحليلات
                                </h3>
                                <form onSubmit={submitTracking} className="space-y-6">
                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">Google Analytics (G-XXXXXXX)</label>
                                        <textarea
                                            rows={4}
                                            value={trackingForm.google_analytics}
                                            onChange={e => trackingForm.setData('google_analytics', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all resize-none font-mono text-sm"
                                            placeholder="أدخل كود Google Analytics هنا..."
                                        ></textarea>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">Facebook Pixel</label>
                                        <textarea
                                            rows={4}
                                            value={trackingForm.facebook_pixel}
                                            onChange={e => trackingForm.setData('facebook_pixel', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all resize-none font-mono text-sm"
                                            placeholder="أدخل كود Facebook Pixel هنا..."
                                        ></textarea>
                                    </div>

                                    <div className="space-y-2">
                                        <label className="text-sm font-bold block">Google Search Console Meta Tag</label>
                                        <textarea
                                            rows={4}
                                            value={trackingForm.google_meta_tag}
                                            onChange={e => trackingForm.setData('google_meta_tag', e.target.value)}
                                            className="w-full px-4 py-3 bg-white dark:bg-[#1d2a3a] border-2 border-gray-200 dark:border-white/10 rounded-none focus:border-primary outline-none transition-all resize-none font-mono text-sm"
                                            placeholder='<meta name="google-site-verification" content="..." />'
                                        ></textarea>
                                    </div>

                                    <div className="flex justify-end">
                                        <button
                                            disabled={trackingForm.processing}
                                            className="flex items-center gap-2 px-8 py-3 bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50"
                                        >
                                            <Save className="w-5 h-5" />
                                            حفظ أكواد التتبع
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
