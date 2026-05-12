import { Head, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { 
    Users, Shield, Flame, MapPin, MessageCircle, 
    Smartphone, Save, Wand2, type LucideIcon
} from 'lucide-react';
import SettingsSidebar from '@/components/Admin/Settings/SettingsSidebar';
import SocialLoginSection, { type SocialLoginData } from '../sections/SocialLoginSection';
import RecaptchaSection, { type RecaptchaData } from '../sections/RecaptchaSection';
import AISection, { type AIData } from '../sections/AISection';
import SecretInput from '@/components/Admin/Form/SecretInput';
import ToggleSwitch from '@/components/Admin/Settings/ToggleSwitch';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import '@/../css/admin.css';

export interface ThirdPartySettingsData extends SocialLoginData, RecaptchaData, AIData {
    google_maps_key: string;
    has_google_maps_server_key?: boolean;
    whatsapp_enabled: boolean;
    whatsapp_instance_id: string;
    whatsapp_token: string;
    has_whatsapp_token?: boolean;
    firebase_enabled: boolean;
    firebase_project: string;
    firebase_api_key: string;
    has_firebase_api_key?: boolean;
    firebase_messaging_sender_id: string;
    google_play_url: string;
    apple_store_url: string;
}

interface ThirdPartySettingsProps {
    settings: ThirdPartySettingsData;
}

type ThirdPartyTab = 'social_login' | 'recaptcha' | 'firebase' | 'google_maps' | 'ai' | 'whatsapp' | 'app_links';

export default function ThirdPartySettings({ settings }: ThirdPartySettingsProps) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<ThirdPartyTab>('social_login');
    const inputClass = "w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all placeholder-gray-400 dark:placeholder-gray-500";
    
    const form = useForm<ThirdPartySettingsData>({
        ...settings,
        google_client_secret: '',
        facebook_client_secret: '',
        captcha_secret_key: '',
        openai_api_key: '',
        gemini_api_key: '',
        whatsapp_token: '',
        firebase_api_key: '',
        google_maps_key: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/settings/third-party', {
            preserveScroll: true,
            onSuccess: () => toast.success(t('Settings updated successfully')),
            onError: () => toast.error(t('Failed to update settings')),
        });
    };

    const tabs: { id: ThirdPartyTab; title: string; icon: LucideIcon }[] = [
        { id: 'social_login', title: 'Social Login', icon: Users },
        { id: 'recaptcha', title: 'reCAPTCHA', icon: Shield },
        { id: 'firebase', title: 'Firebase', icon: Flame },
        { id: 'google_maps', title: 'Google Maps', icon: MapPin },
        { id: 'ai', title: 'Artificial Intelligence', icon: Wand2 },
        { id: 'whatsapp', title: 'WhatsApp', icon: MessageCircle },
        { id: 'app_links', title: 'App Links', icon: Smartphone },
    ];

    return (
        <div className="font-tajawal animate-in fade-in duration-500 pb-12">
            <Head title={t('Third Party Settings')} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white">{t('Third Party Settings')}</h2>
                </div>

                <div className="flex flex-col md:flex-row gap-6">
                    <SettingsSidebar 
                        tabs={tabs} 
                        activeTab={activeTab} 
                        onTabChange={setActiveTab} 
                    />

                    <div className="flex-1">
                        <form onSubmit={submit} className="space-y-6">
                            {activeTab === 'social_login' && (
                                <SocialLoginSection data={form.data} setData={form.setData} />
                            )}

                            {activeTab === 'recaptcha' && (
                                <RecaptchaSection data={form.data} setData={form.setData} />
                            )}

                            {activeTab === 'ai' && (
                                <AISection data={form.data} setData={form.setData} />
                            )}

                            {activeTab === 'google_maps' && (
                                <div className="panel">
                                    <h3 className="text-lg font-bold mb-6 border-b border-gray-100 dark:border-gray-800 pb-4 flex items-center gap-2 dark:text-white">
                                        <MapPin className="w-5 h-5 text-primary" />
                                        {t('Google Maps API')}
                                    </h3>
                                    <div className="grid grid-cols-1 gap-4">
                                        <SecretInput 
                                            label="API Key" 
                                            value={form.data.google_maps_key} 
                                            onChange={val => form.setData('google_maps_key', val)} 
                                            hasSecret={settings.has_google_maps_server_key} 
                                            description="This key is used for Google Maps JS API. Ensure it is restricted in Google Cloud Console."
                                        />
                                    </div>
                                </div>
                            )}

                            {activeTab === 'whatsapp' && (
                                <div className="panel">
                                    <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                                        <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                                            <MessageCircle className="w-5 h-5 text-primary" />
                                            {t('WhatsApp (UltraMsg)')}
                                        </h3>
                                        <ToggleSwitch 
                                            checked={form.data.whatsapp_enabled} 
                                            onChange={(val) => form.setData('whatsapp_enabled', val)} 
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 gap-6">
                                        <div className="space-y-1.5">
                                            <label className="text-sm font-bold dark:text-gray-200">{t('Instance ID')}</label>
                                            <input 
                                                type="text" 
                                                value={form.data.whatsapp_instance_id} 
                                                onChange={e => form.setData('whatsapp_instance_id', e.target.value)} 
                                                className={inputClass} 
                                            />
                                        </div>
                                        <SecretInput 
                                            label="Token" 
                                            value={form.data.whatsapp_token} 
                                            onChange={val => form.setData('whatsapp_token', val)} 
                                            hasSecret={settings.has_whatsapp_token} 
                                        />
                                    </div>
                                </div>
                            )}

                            {activeTab === 'firebase' && (
                                <div className="panel">
                                    <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                                        <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                                            <Flame className="w-5 h-5 text-primary" />
                                            {t('Firebase FCM')}
                                        </h3>
                                        <ToggleSwitch 
                                            checked={form.data.firebase_enabled} 
                                            onChange={(val) => form.setData('firebase_enabled', val)} 
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-1.5">
                                            <label className="text-sm font-bold dark:text-gray-200">{t('Project ID')}</label>
                                            <input type="text" value={form.data.firebase_project} onChange={e => form.setData('firebase_project', e.target.value)} className={inputClass} />
                                        </div>
                                        <SecretInput 
                                            label="API Key" 
                                            value={form.data.firebase_api_key} 
                                            onChange={val => form.setData('firebase_api_key', val)} 
                                            hasSecret={settings.has_firebase_api_key} 
                                            type="text"
                                        />
                                        <div className="space-y-1.5 md:col-span-2">
                                            <label className="text-sm font-bold dark:text-gray-200">{t('Messaging Sender ID')}</label>
                                            <input type="text" value={form.data.firebase_messaging_sender_id} onChange={e => form.setData('firebase_messaging_sender_id', e.target.value)} className={inputClass} />
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'app_links' && (
                                <div className="panel">
                                    <h3 className="text-lg font-bold mb-6 border-b border-gray-100 dark:border-gray-800 pb-4 flex items-center gap-2 dark:text-white">
                                        <Smartphone className="w-5 h-5 text-primary" />
                                        {t('App Store Links')}
                                    </h3>
                                    <div className="grid grid-cols-1 gap-6">
                                        <div className="space-y-1.5">
                                            <label className="text-sm font-bold dark:text-gray-200">{t('Google Play URL')}</label>
                                            <input type="url" value={form.data.google_play_url} onChange={e => form.setData('google_play_url', e.target.value)} className={inputClass} placeholder="https://play.google.com/store/apps/..." />
                                        </div>
                                        <div className="space-y-1.5">
                                            <label className="text-sm font-bold dark:text-gray-200">{t('Apple App Store URL')}</label>
                                            <input type="url" value={form.data.apple_store_url} onChange={e => form.setData('apple_store_url', e.target.value)} className={inputClass} placeholder="https://apps.apple.com/app/..." />
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end pt-4">
                                <button
                                    disabled={form.processing}
                                    className="flex items-center gap-2 px-10 py-4 bg-primary text-white font-bold hover:opacity-90 transition-all disabled:opacity-50 rounded-none shadow-lg shadow-primary/20"
                                >
                                    {form.processing ? (
                                        <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    ) : (
                                        <Save className="w-5 h-5" />
                                    )}
                                    {t('Save Third-Party Settings')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
