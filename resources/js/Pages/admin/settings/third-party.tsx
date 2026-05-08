import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Users, Shield, Flame, MapPin, Cpu, MessageCircle, 
    Smartphone, Save, Globe, Lock, Code, Wand2, Send,
    Settings, Zap, Check
} from 'lucide-react';
import '@/../css/admin.css';

interface ThirdPartySettingsProps {
    settings: any;
}

export default function ThirdPartySettings({ settings }: ThirdPartySettingsProps) {
    const [activeTab, setActiveTab] = useState('social_login');
    const form = useForm(settings);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/settings/third-party', {
            preserveScroll: true,
        });
    };

    const tabs = [
        { id: 'social_login', title: 'تسجيل الدخول الاجتماعي', icon: Users },
        { id: 'recaptcha', title: 'reCAPTCHA', icon: Shield },
        { id: 'firebase', title: 'Firebase', icon: Flame },
        { id: 'google_maps', title: 'Google Maps', icon: MapPin },
        { id: 'ai', title: 'الذكاء الاصطناعي (AI)', icon: Wand2 },
        { id: 'whatsapp', title: 'WhatsApp (UltraMsg)', icon: MessageCircle },
        { id: 'app_links', title: 'روابط التطبيقات', icon: Smartphone },
    ];

    return (
        <div className="font-tajawal animate-in fade-in duration-500 pb-12">
            <Head title="إعدادات الطرف الثالث" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-[#1c2434] dark:text-white">إعدادات الطرف الثالث</h2>
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
                            {activeTab === 'social_login' && (
                                <div className="space-y-6">
                                    {/* Google */}
                                    <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                        <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                            <h3 className="text-lg font-bold flex items-center gap-2">
                                                <Globe className="w-5 h-5 text-primary" />
                                                Google OAuth
                                            </h3>
                                            <div className="flex items-center gap-2 relative">
                                                <span className="text-sm font-bold">تفعيل</span>
                                                <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.google_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                    <div className="w-4 h-4 bg-white transition-all"></div>
                                                </div>
                                                <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.google_enabled} onChange={e => form.setData('google_enabled', e.target.checked)} />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-1 gap-4">
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">Client ID</label>
                                                <input type="text" value={form.google_client_id} onChange={e => form.setData('google_client_id', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">Client Secret</label>
                                                <input type="password" value={form.google_client_secret} onChange={e => form.setData('google_client_secret', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">Redirect URL</label>
                                                <input type="text" value={form.google_redirect_url} onChange={e => form.setData('google_redirect_url', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/20 rounded-none outline-none text-gray-500" readOnly />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Facebook */}
                                    <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                        <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                            <h3 className="text-lg font-bold flex items-center gap-2">
                                                <Zap className="w-5 h-5 text-primary" />
                                                Facebook OAuth
                                            </h3>
                                            <div className="flex items-center gap-2 relative">
                                                <span className="text-sm font-bold">تفعيل</span>
                                                <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.facebook_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                    <div className="w-4 h-4 bg-white transition-all"></div>
                                                </div>
                                                <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.facebook_enabled} onChange={e => form.setData('facebook_enabled', e.target.checked)} />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-1 gap-4">
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">App ID</label>
                                                <input type="text" value={form.facebook_client_id} onChange={e => form.setData('facebook_client_id', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">App Secret</label>
                                                <input type="password" value={form.facebook_client_secret} onChange={e => form.setData('facebook_client_secret', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">Redirect URL</label>
                                                <input type="text" value={form.facebook_redirect_url} onChange={e => form.setData('facebook_redirect_url', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/20 rounded-none outline-none text-gray-500" readOnly />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'recaptcha' && (
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <h3 className="text-lg font-bold flex items-center gap-2">
                                            <Shield className="w-5 h-5 text-primary" />
                                            Google reCAPTCHA v3
                                        </h3>
                                        <div className="flex items-center gap-2 relative">
                                            <span className="text-sm font-bold">تفعيل</span>
                                            <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.captcha_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                <div className="w-4 h-4 bg-white transition-all"></div>
                                            </div>
                                            <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.captcha_enabled} onChange={e => form.setData('captcha_enabled', e.target.checked)} />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Site Key</label>
                                            <input type="text" value={form.captcha_site_key} onChange={e => form.setData('captcha_site_key', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Secret Key</label>
                                            <input type="password" value={form.captcha_secret_key} onChange={e => form.setData('captcha_secret_key', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Minimum Score (0.1 - 1.0)</label>
                                            <input type="number" step="0.1" value={form.captcha_score} onChange={e => form.setData('captcha_score', parseFloat(e.target.value))} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'ai' && (
                                <div className="space-y-6">
                                    <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                        <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                            <h3 className="text-lg font-bold flex items-center gap-2">
                                                <Wand2 className="w-5 h-5 text-primary" />
                                                إعدادات الذكاء الاصطناعي
                                            </h3>
                                            <div className="flex items-center gap-2 relative">
                                                <span className="text-sm font-bold">تفعيل AI</span>
                                                <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.ai_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                    <div className="w-4 h-4 bg-white transition-all"></div>
                                                </div>
                                                <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.ai_enabled} onChange={e => form.setData('ai_enabled', e.target.checked)} />
                                            </div>
                                        </div>
                                        
                                        <div className="space-y-4">
                                            <div className="space-y-1">
                                                <label className="text-sm font-bold">المزود الافتراضي</label>
                                                <select value={form.ai_provider} onChange={e => form.setData('ai_provider', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none">
                                                    <option value="openai">OpenAI (ChatGPT)</option>
                                                    <option value="gemini">Google Gemini</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {form.ai_provider === 'openai' ? (
                                        <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                            <h4 className="text-md font-bold mb-4">إعدادات OpenAI</h4>
                                            <div className="grid grid-cols-1 gap-4">
                                                <div className="space-y-1">
                                                    <label className="text-sm font-bold">API Key</label>
                                                    <input type="password" value={form.openai_api_key} onChange={e => form.setData('openai_api_key', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                                </div>
                                                <div className="space-y-1">
                                                    <label className="text-sm font-bold">الموديل (Model)</label>
                                                    <input type="text" value={form.openai_model} onChange={e => form.setData('openai_model', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                            <h4 className="text-md font-bold mb-4">إعدادات Google Gemini</h4>
                                            <div className="grid grid-cols-1 gap-4">
                                                <div className="space-y-1">
                                                    <label className="text-sm font-bold">API Key</label>
                                                    <input type="password" value={form.gemini_api_key} onChange={e => form.setData('gemini_api_key', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                                </div>
                                                <div className="space-y-1">
                                                    <label className="text-sm font-bold">الموديل (Model)</label>
                                                    <input type="text" value={form.gemini_model} onChange={e => form.setData('gemini_model', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeTab === 'whatsapp' && (
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <h3 className="text-lg font-bold flex items-center gap-2">
                                            <MessageCircle className="w-5 h-5 text-primary" />
                                            WhatsApp (UltraMsg)
                                        </h3>
                                        <div className="flex items-center gap-2 relative">
                                            <span className="text-sm font-bold">تفعيل</span>
                                            <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.whatsapp_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                <div className="w-4 h-4 bg-white transition-all"></div>
                                            </div>
                                            <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.whatsapp_enabled} onChange={e => form.setData('whatsapp_enabled', e.target.checked)} />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Instance ID</label>
                                            <input type="text" value={form.whatsapp_instance_id} onChange={e => form.setData('whatsapp_instance_id', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Token</label>
                                            <input type="password" value={form.whatsapp_token} onChange={e => form.setData('whatsapp_token', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'firebase' && (
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <h3 className="text-lg font-bold flex items-center gap-2">
                                            <Flame className="w-5 h-5 text-primary" />
                                            Firebase Cloud Messaging
                                        </h3>
                                        <div className="flex items-center gap-2 relative">
                                            <span className="text-sm font-bold">تفعيل</span>
                                            <div className={`w-12 h-6 flex items-center p-1 transition-colors ${form.firebase_enabled ? 'bg-primary justify-end' : 'bg-gray-300 dark:bg-gray-600 justify-start'}`}>
                                                <div className="w-4 h-4 bg-white transition-all"></div>
                                            </div>
                                            <input type="checkbox" className="absolute inset-0 opacity-0 cursor-pointer" checked={form.firebase_enabled} onChange={e => form.setData('firebase_enabled', e.target.checked)} />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Project ID</label>
                                            <input type="text" value={form.firebase_project} onChange={e => form.setData('firebase_project', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">API Key</label>
                                            <input type="text" value={form.firebase_api_key} onChange={e => form.setData('firebase_api_key', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1 md:col-span-2">
                                            <label className="text-sm font-bold">Messaging Sender ID</label>
                                            <input type="text" value={form.firebase_messaging_sender_id} onChange={e => form.setData('firebase_messaging_sender_id', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'app_links' && (
                                <div className="panel bg-white dark:bg-[#1c2434] border-2 border-gray-300 dark:border-white/10 rounded-none p-6">
                                    <h3 className="text-lg font-bold mb-6 flex items-center gap-2 border-b border-gray-100 dark:border-white/5 pb-4">
                                        <Smartphone className="w-5 h-5 text-primary" />
                                        روابط التطبيقات على المتاجر
                                    </h3>
                                    <div className="grid grid-cols-1 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Google Play Store URL</label>
                                            <input type="url" value={form.google_play_url} onChange={e => form.setData('google_play_url', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-sm font-bold">Apple App Store URL</label>
                                            <input type="url" value={form.apple_store_url} onChange={e => form.setData('apple_store_url', e.target.value)} className="w-full px-4 py-2 border-2 border-gray-200 dark:border-white/10 bg-transparent rounded-none focus:border-primary outline-none" />
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
                                    حفظ كافة إعدادات الطرف الثالث
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
