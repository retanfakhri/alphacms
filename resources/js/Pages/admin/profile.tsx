import { Head, useForm, router, Link } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { 
    Camera, Facebook, Instagram, Linkedin, Globe, Twitter, Youtube, 
    Phone, Shield, Bell, Languages, User, Save, Trash2, Key, ShieldCheck, Lock,
    Monitor, Smartphone, Tablet, Cpu, MapPin, Clock, ExternalLink, Globe2, LogOut
} from 'lucide-react';
import '@/../css/admin.css';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import ProfileSessions from '@/routes/admin/profile/sessions';

export default function AdminProfile({
    user,
    topics = [],
    notificationTypes = [],
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
    sessions = [],
}: {
    user: any;
    topics: { name: string, value: string }[];
    notificationTypes: { name: string, value: string }[];
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    sessions: any[];
}) {
    const [avatarPreview, setAvatarPreview] = useState(user?.avatar_url || '');
    const [coverPreview, setCoverPreview] = useState(user?.cover_url || '/assets/images/menu-heade.jpg');
    const [activeTab, setActiveTab] = useState('personal');

    // Password Form
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // Profile Form
    const profileForm = useForm({
        name: user?.name || '',
        username: user?.username || '',
        email: user?.email || '',
        phone: user?.phone || '',
        phone_code: user?.phone_code || '',
        bio: user?.bio || '',
        facebook_url: user?.facebook_url || '',
        instagram_url: user?.instagram_url || '',
        tiktok_url: user?.tiktok_url || '',
        linkedin_url: user?.linkedin_url || '',
        youtube_url: user?.youtube_url || '',
        website_url: user?.website_url || '',
        x_url: user?.x_url || '',
        preferred_locale: user?.preferred_locale || 'ar',
        preferred_topics: user?.preferred_topics || [],
        is_private: user?.is_private || false,
        comments_blocked: user?.comments_blocked || false,
    });

    const imageForm = useForm({
        avatar: null as File | null,
        cover: null as File | null,
    });

    const twoFactorForm = useForm({});
    const [showLogoutModal, setShowLogoutModal] = useState(false);
    const logoutOtherSessionsForm = useForm({
        password: '',
    });

    // 2FA Hook and State
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors: twoFactorErrors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }
        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.patch('/user/profile-settings', {
            preserveScroll: true,
        });
    };

    const submitPassword = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const submitImages = (e: React.FormEvent) => {
        e.preventDefault();
        imageForm.post('/settings/profile/images', {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const enableTwoFactor = (e: React.FormEvent) => {
        e.preventDefault();
        twoFactorForm.post('/user/two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => setShowSetupModal(true),
        });
    };

    const disableTwoFactor = (e: React.FormEvent) => {
        e.preventDefault();
        twoFactorForm.delete('/user/two-factor-authentication', {
            preserveScroll: true,
        });
    };

    const logoutOtherSessions = (e: React.FormEvent) => {
        e.preventDefault();
        logoutOtherSessionsForm.delete(ProfileSessions.purge.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setShowLogoutModal(false);
                logoutOtherSessionsForm.reset();
            },
        });
    };

    const tabs = [
        { id: 'personal', title: 'المعلومات الشخصية', icon: User },
        { id: 'social', title: 'وسائل التواصل', icon: Globe },
        { id: 'preferences', title: 'التفضيلات', icon: Bell },
        { id: 'account', title: 'إعدادات الحساب والأمان', icon: Shield },
        { id: 'sessions', title: 'الجلسات النشطة', icon: Cpu },
    ];

    const getDeviceIcon = (type: string) => {
        switch (type.toLowerCase()) {
            case 'mobile': return <Smartphone className="w-5 h-5" />;
            case 'tablet': return <Tablet className="w-5 h-5" />;
            default: return <Monitor className="w-5 h-5" />;
        }
    };

    return (
        <div className="font-tajawal animate-in fade-in duration-500">
            <Head title="إعدادات الملف الشخصي" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold text-[#1c2434] dark:text-white">إعدادات الملف الشخصي</h2>
                </div>

                {/* Profile Images Section */}
                <div className="panel p-0 overflow-hidden border-2 border-gray-300 dark:border-white/20 shadow-sm bg-white dark:bg-[#1c2434] rounded-none">
                    <form onSubmit={submitImages}>
                        <div className="relative h-48 md:h-64 bg-gray-100 dark:bg-[#1d2939]">
                            <img 
                                src={coverPreview || '/assets/images/menu-heade.jpg'} 
                                alt="Cover" 
                                className="w-full h-full object-cover"
                                onError={(e) => {
                                    (e.target as HTMLImageElement).src = '/assets/images/menu-heade.jpg';
                                }}
                            />
                            <label className="absolute top-4 right-4 p-2 bg-white/90 dark:bg-black/50 rounded-none cursor-pointer hover:bg-white dark:hover:bg-black transition-all shadow-lg group z-10">
                                <Camera className="w-5 h-5 text-[#1c2434] dark:text-white group-hover:scale-110 transition-transform" />
                                <input type="file" className="hidden" onChange={(e) => {
                                    const file = e.target.files?.[0];
                                    if (file) {
                                        setCoverPreview(URL.createObjectURL(file));
                                        imageForm.setData('cover', file);
                                    }
                                }} />
                            </label>
                        </div>
                        <div className="px-8 pb-6">
                            <div className="relative -mt-16 inline-block">
                                <div className="h-32 w-32 rounded-none border-4 border-white dark:border-[#1c2434] overflow-hidden bg-gray-100 shadow-2xl">
                                    <img src={avatarPreview} alt="Avatar" className="w-full h-full object-cover" />
                                </div>
                                <label className="absolute bottom-1 right-1 p-2 bg-primary rounded-none cursor-pointer hover:scale-110 transition-transform shadow-xl">
                                    <Camera className="w-4 h-4 text-white" />
                                    <input type="file" className="hidden" onChange={(e) => {
                                        const file = e.target.files?.[0];
                                        if (file) {
                                            setAvatarPreview(URL.createObjectURL(file));
                                            imageForm.setData('avatar', file);
                                        }
                                    }} />
                                </label>
                            </div>
                            <div className="mt-4 flex justify-between items-center">
                                <div>
                                    <h3 className="text-xl font-bold text-[#1c2434] dark:text-white">{user?.name}</h3>
                                    <p className="text-[#64748b] text-sm">@{user?.username}</p>
                                    {(imageForm.errors.avatar || imageForm.errors.cover) && (
                                        <div className="mt-2 text-red-500 text-xs font-bold space-y-1">
                                            {imageForm.errors.avatar && <p>{imageForm.errors.avatar}</p>}
                                            {imageForm.errors.cover && <p>{imageForm.errors.cover}</p>}
                                        </div>
                                    )}
                                </div>
                                {(imageForm.wasSuccessful || imageForm.isDirty) && (
                                    <button 
                                        type="submit"
                                        disabled={imageForm.processing}
                                        className="flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-none hover:bg-primary/90 transition-all shadow-lg disabled:opacity-50 font-bold"
                                    >
                                        {imageForm.processing ? (
                                            <div className="h-4 w-4 border-2 border-white border-t-transparent rounded-none animate-spin"></div>
                                        ) : (
                                            <Save className="w-4 h-4" />
                                        )}
                                        {imageForm.processing ? 'جاري الحفظ...' : 'حفظ الصور'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </form>
                </div>

                {/* Content Tabs */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div className="lg:col-span-1 space-y-2">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`w-full flex items-center gap-3 px-4 py-3.5 rounded-none transition-all duration-300 ${
                                    activeTab === tab.id 
                                    ? 'bg-primary text-white shadow-lg shadow-primary/20 scale-[1.02]' 
                                    : 'bg-white dark:bg-[#1c2434] text-[#64748b] dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 border border-gray-300 dark:border-white/5'
                                }`}
                            >
                                <tab.icon className={`w-5 h-5 ${activeTab === tab.id ? 'text-white' : 'text-primary'}`} />
                                <span className="font-bold">{tab.title}</span>
                            </button>
                        ))}
                    </div>

                    <div className="lg:col-span-3">
                        <div className="transition-all duration-300">
                            {activeTab === 'personal' && (
                                <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20">
                                    <form onSubmit={submitProfile} className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-2">
                                                <label className="text-sm font-bold text-[#1c2434] dark:text-white">الاسم الكامل</label>
                                                <input 
                                                    className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white focus:ring-2 focus:ring-primary/20 outline-none transition-all"
                                                    value={profileForm.data.name} 
                                                    onChange={e => profileForm.setData('name', e.target.value)} 
                                                />
                                                {profileForm.errors.name && <p className="text-red-500 text-xs font-bold mt-1">{profileForm.errors.name}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-bold text-[#1c2434] dark:text-white">اسم المستخدم</label>
                                                <input 
                                                    className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white focus:ring-2 focus:ring-primary/20 outline-none transition-all"
                                                    value={profileForm.data.username} 
                                                    onChange={e => profileForm.setData('username', e.target.value)} 
                                                />
                                                {profileForm.errors.username && <p className="text-red-500 text-xs font-bold mt-1">{profileForm.errors.username}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-bold text-[#1c2434] dark:text-white">البريد الإلكتروني</label>
                                                <input 
                                                    type="email"
                                                    className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white focus:ring-2 focus:ring-primary/20 outline-none transition-all"
                                                    value={profileForm.data.email} 
                                                    onChange={e => profileForm.setData('email', e.target.value)} 
                                                />
                                                {profileForm.errors.email && <p className="text-red-500 text-xs font-bold mt-1">{profileForm.errors.email}</p>}
                                            </div>
                                            <div className="space-y-2">
                                                <label className="text-sm font-bold text-[#1c2434] dark:text-white">رقم الهاتف</label>
                                                <div className="flex gap-2" dir="ltr">
                                                    <input 
                                                        className="w-24 px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none"
                                                        placeholder="+966"
                                                        value={profileForm.data.phone_code} 
                                                        onChange={e => profileForm.setData('phone_code', e.target.value)} 
                                                    />
                                                    <input 
                                                        className="flex-1 px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none"
                                                        placeholder="50xxxxxxx"
                                                        value={profileForm.data.phone} 
                                                        onChange={e => profileForm.setData('phone', e.target.value)} 
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-sm font-bold text-[#1c2434] dark:text-white">السيرة الذاتية (Bio)</label>
                                            <textarea 
                                                className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white focus:ring-2 focus:ring-primary/20 outline-none transition-all h-32"
                                                value={profileForm.data.bio} 
                                                onChange={e => profileForm.setData('bio', e.target.value)}
                                            />
                                        </div>
                                        <div className="flex justify-end pt-4">
                                            <button type="submit" disabled={profileForm.processing} className="px-8 py-3 bg-primary text-white rounded-none hover:bg-primary/90 transition-all shadow-lg font-bold">
                                                حفظ التغييرات
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'social' && (
                                <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20">
                                    <form onSubmit={submitProfile} className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {[
                                                { id: 'facebook_url', title: 'Facebook', icon: Facebook, color: 'text-blue-600' },
                                                { id: 'x_url', title: 'X (Twitter)', icon: Twitter, color: 'text-[#1c2434] dark:text-white' },
                                                { id: 'instagram_url', title: 'Instagram', icon: Instagram, color: 'text-pink-600' },
                                                { id: 'linkedin_url', title: 'LinkedIn', icon: Linkedin, color: 'text-blue-700' },
                                                { id: 'youtube_url', title: 'YouTube', icon: Youtube, color: 'text-red-600' },
                                                { id: 'website_url', title: 'Website', icon: Globe, color: 'text-primary' },
                                            ].map(social => (
                                                <div key={social.id} className="space-y-2">
                                                    <label className="flex items-center gap-2 text-sm font-bold text-[#1c2434] dark:text-white">
                                                        <social.icon className={`w-4 h-4 ${social.color}`} />
                                                        {social.title}
                                                    </label>
                                                    <input 
                                                        className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:ring-2 focus:ring-primary/20"
                                                        value={(profileForm.data as any)[social.id]} 
                                                        onChange={e => profileForm.setData(social.id as any, e.target.value)}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                        <div className="flex justify-end pt-4">
                                            <button type="submit" disabled={profileForm.processing} className="px-8 py-3 bg-primary text-white rounded-none hover:bg-primary/90 transition-all font-bold shadow-lg">
                                                تحديث الروابط
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'preferences' && (
                                <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20 space-y-8">
                                    <form onSubmit={submitProfile}>
                                        <div className="space-y-4">
                                            <h4 className="font-bold text-lg flex items-center gap-2 text-[#1c2434] dark:text-white">
                                                <Languages className="w-5 h-5 text-primary" />
                                                اللغة والمنطقة
                                            </h4>
                                            <select 
                                                className="w-full max-w-xs px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:ring-2 focus:ring-primary/20"
                                                value={profileForm.data.preferred_locale} 
                                                onChange={e => profileForm.setData('preferred_locale', e.target.value)}
                                            >
                                                <option value="ar">العربية</option>
                                                <option value="en">English</option>
                                            </select>
                                        </div>

                                        <div className="space-y-4 border-t border-gray-300 dark:border-white/5 pt-6 mt-6">
                                            <h4 className="font-bold text-lg flex items-center gap-2 text-[#1c2434] dark:text-white">
                                                <User className="w-5 h-5 text-primary" />
                                                الاهتمامات المفضلة
                                            </h4>
                                            <div className="grid grid-cols-2 md:grid-cols-3 gap-6">
                                                {topics.map(topic => (
                                                    <label key={topic.value} className="flex items-center gap-3 cursor-pointer group">
                                                        <input 
                                                            type="checkbox"
                                                            className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                                                            checked={profileForm.data.preferred_topics.includes(topic.value)}
                                                            onChange={(e) => {
                                                                const current = profileForm.data.preferred_topics;
                                                                if (e.target.checked) {
                                                                    profileForm.setData('preferred_topics', [...current, topic.value]);
                                                                } else {
                                                                    profileForm.setData('preferred_topics', current.filter((t: string) => t !== topic.value));
                                                                }
                                                            }}
                                                        />
                                                        <span className="text-[#64748b] dark:text-gray-400 group-hover:text-primary font-medium transition-colors">{topic.name}</span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="flex justify-end pt-6">
                                            <button type="submit" disabled={profileForm.processing} className="px-8 py-3 bg-primary text-white rounded-none hover:bg-primary/90 transition-all font-bold shadow-lg">
                                                حفظ التفضيلات
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'account' && (
                                <div className="space-y-6">
                                    {/* Password Change Form */}
                                    <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20">
                                        <h4 className="font-bold text-lg mb-6 flex items-center gap-2 text-[#1c2434] dark:text-white">
                                            <Key className="w-5 h-5 text-primary" />
                                            تغيير كلمة السر
                                        </h4>
                                        <form onSubmit={submitPassword} className="space-y-4">
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div className="space-y-2">
                                                    <label className="text-sm font-bold text-[#1c2434] dark:text-white">كلمة السر الحالية</label>
                                                    <input 
                                                        type="password"
                                                        className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:ring-2 focus:ring-primary/20"
                                                        value={passwordForm.data.current_password}
                                                        onChange={e => passwordForm.setData('current_password', e.target.value)}
                                                    />
                                                    {passwordForm.errors.current_password && <p className="text-red-500 text-xs font-bold">{passwordForm.errors.current_password}</p>}
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-sm font-bold text-[#1c2434] dark:text-white">كلمة السر الجديدة</label>
                                                    <input 
                                                        type="password"
                                                        className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:ring-2 focus:ring-primary/20"
                                                        value={passwordForm.data.password}
                                                        onChange={e => passwordForm.setData('password', e.target.value)}
                                                    />
                                                    {passwordForm.errors.password && <p className="text-red-500 text-xs font-bold">{passwordForm.errors.password}</p>}
                                                </div>
                                                <div className="space-y-2">
                                                    <label className="text-sm font-bold text-[#1c2434] dark:text-white">تأكيد كلمة السر</label>
                                                    <input 
                                                        type="password"
                                                        className="w-full px-4 py-3 rounded-none border border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:ring-2 focus:ring-primary/20"
                                                        value={passwordForm.data.password_confirmation}
                                                        onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                                                    />
                                                </div>
                                            </div>
                                            <div className="flex justify-end pt-2">
                                                <button disabled={passwordForm.processing} className="px-6 py-2.5 bg-[#1c2434] dark:bg-white dark:text-[#1c2434] text-white rounded-none font-bold hover:opacity-90 transition-all shadow-lg">
                                                    تحديث كلمة السر
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    {/* Two Factor Authentication */}
                                    {canManageTwoFactor && (
                                        <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20">
                                            <div className="flex items-center justify-between mb-6">
                                                <h4 className="font-bold text-lg flex items-center gap-2 text-[#1c2434] dark:text-white">
                                                    <ShieldCheck className="w-5 h-5 text-green-500" />
                                                    التحقق بخطوتين (2FA)
                                                </h4>
                                                <div className={`px-3 py-1 rounded-none text-xs font-bold ${twoFactorEnabled ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
                                                    {twoFactorEnabled ? 'مفعل' : 'غير مفعل'}
                                                </div>
                                            </div>

                                            <div className="space-y-4">
                                                <p className="text-sm text-[#64748b]">
                                                    عند تفعيل التحقق بخطوتين، سيُطلب منك إدخال رمز أمان عشوائي أثناء تسجيل الدخول. يمكنك الحصول على هذا الرمز من تطبيق Google Authenticator على هاتفك.
                                                </p>

                                                <div className="flex gap-3">
                                                    {twoFactorEnabled ? (
                                                        <div className="space-y-4 w-full">
                                                            <button 
                                                                onClick={disableTwoFactor}
                                                                disabled={twoFactorForm.processing}
                                                                className="px-6 py-2.5 bg-red-600 text-white rounded-none font-bold hover:bg-red-700 transition-all shadow-lg disabled:opacity-50"
                                                            >
                                                                {twoFactorForm.processing ? 'جاري التعطيل...' : 'تعطيل التحقق بخطوتين'}
                                                            </button>

                                                            <TwoFactorRecoveryCodes
                                                                recoveryCodesList={recoveryCodesList}
                                                                fetchRecoveryCodes={fetchRecoveryCodes}
                                                                errors={twoFactorErrors}
                                                            />
                                                        </div>
                                                    ) : (
                                                        <div>
                                                            {hasSetupData ? (
                                                                <button 
                                                                    onClick={() => setShowSetupModal(true)}
                                                                    className="px-6 py-2.5 bg-primary text-white rounded-none font-bold hover:bg-primary/90 transition-all shadow-lg flex items-center gap-2"
                                                                >
                                                                    <ShieldCheck className="w-4 h-4" />
                                                                    إكمال الإعداد
                                                                </button>
                                                            ) : (
                                                                <button 
                                                                    onClick={enableTwoFactor}
                                                                    disabled={twoFactorForm.processing}
                                                                    className="px-6 py-2.5 bg-primary text-white rounded-none font-bold hover:bg-primary/90 transition-all shadow-lg disabled:opacity-50"
                                                                >
                                                                    {twoFactorForm.processing ? 'جاري التفعيل...' : 'تفعيل التحقق بخطوتين'}
                                                                </button>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            <TwoFactorSetupModal
                                                isOpen={showSetupModal}
                                                onClose={() => setShowSetupModal(false)}
                                                requiresConfirmation={requiresConfirmation}
                                                twoFactorEnabled={twoFactorEnabled}
                                                qrCodeSvg={qrCodeSvg}
                                                manualSetupKey={manualSetupKey}
                                                clearSetupData={clearSetupData}
                                                fetchSetupData={fetchSetupData}
                                                errors={twoFactorErrors}
                                            />
                                        </div>
                                    )}

                                    {/* Privacy Settings */}
                                    <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20 space-y-6">
                                        <h4 className="font-bold text-lg text-[#1c2434] dark:text-white flex items-center gap-2">
                                            <Lock className="w-5 h-5 text-blue-500" />
                                            إعدادات الخصوصية
                                        </h4>
                                        <form onSubmit={submitProfile} className="space-y-4">
                                            <label className="flex items-start gap-4 cursor-pointer group p-4 rounded-none border border-gray-50 dark:border-white/5 hover:border-primary/20 hover:bg-primary/5 transition-all">
                                                <input 
                                                    type="checkbox"
                                                    className="mt-1 w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
                                                    checked={profileForm.data.is_private}
                                                    onChange={e => profileForm.setData('is_private', e.target.checked)}
                                                />
                                                <div>
                                                    <p className="font-bold text-[#1c2434] dark:text-white group-hover:text-primary transition-colors">حساب خاص</p>
                                                    <p className="text-xs text-[#64748b] mt-1">فقط المتابعون الموافق عليهم يمكنهم رؤية بروفايلك ومنشوراتك.</p>
                                                </div>
                                            </label>
                                            <label className="flex items-start gap-4 cursor-pointer group p-4 rounded-none border border-gray-50 dark:border-white/5 hover:border-primary/20 hover:bg-primary/5 transition-all">
                                                <input 
                                                    type="checkbox"
                                                    className="mt-1 w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
                                                    checked={profileForm.data.comments_blocked}
                                                    onChange={e => profileForm.setData('comments_blocked', e.target.checked)}
                                                />
                                                <div>
                                                    <p className="font-bold text-[#1c2434] dark:text-white group-hover:text-primary transition-colors">منع التعليقات</p>
                                                    <p className="text-xs text-[#64748b] mt-1">منع الآخرين من التعليق على منشوراتك.</p>
                                                </div>
                                            </label>
                                            <div className="flex justify-end pt-4">
                                                <button type="submit" disabled={profileForm.processing} className="px-8 py-3 bg-primary text-white rounded-none hover:bg-primary/90 transition-all font-bold shadow-lg">
                                                    تحديث الخصوصية
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    {/* Danger Zone */}
                                    <div className="panel border-2 border-red-600/20 bg-red-50/50 dark:bg-red-900/5 p-6 rounded-none shadow-sm">
                                        <h4 className="font-bold text-lg text-red-600 dark:text-red-400">منطقة الخطر</h4>
                                        <p className="text-sm text-red-500/80 mt-1">بمجرد حذف حسابك، سيتم حذف جميع بياناتك ومواردك بشكل دائم ولا يمكن استعادتها.</p>
                                        <button type="button" className="mt-6 px-6 py-3 bg-red-600 text-white rounded-none hover:bg-red-700 transition-all flex items-center gap-2 font-bold shadow-lg shadow-red-500/20">
                                            <Trash2 className="w-4 h-4" />
                                            حذف الحساب نهائياً
                                        </button>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'sessions' && (
                                <div className="space-y-6">
                                    <div className="panel bg-white dark:bg-[#1c2434] p-6 rounded-none shadow-sm border-2 border-gray-300 dark:border-white/20">
                                        <div className="flex items-center justify-between mb-8">
                                            <div>
                                                <h4 className="font-bold text-xl flex items-center gap-2 text-[#1c2434] dark:text-white">
                                                    <Cpu className="w-6 h-6 text-primary" />
                                                    إدارة الجلسات والأجهزة
                                                </h4>
                                                <p className="text-sm text-[#64748b] mt-1">عرض وتتبع جميع الأجهزة التي سجلت الدخول إلى حسابك حالياً</p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <button 
                                                    onClick={() => setShowLogoutModal(true)}
                                                    className="px-4 py-2 bg-red-50 dark:bg-red-500/10 text-red-500 rounded-none text-sm font-bold hover:bg-red-100 dark:hover:bg-red-500/20 transition-all border border-red-100 dark:border-red-500/20 flex items-center gap-2"
                                                >
                                                    <LogOut className="w-4 h-4" />
                                                    خروج من كل الأجهزة
                                                </button>
                                                <div className="bg-primary/10 text-primary px-4 py-2 rounded-none text-sm font-bold">
                                                    {sessions.length} جلسات نشطة
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-8">
                                            {sessions.length === 0 ? (
                                                <div className="text-center py-20 bg-gray-50 dark:bg-white/2 rounded-none border-2 border-dashed border-gray-300 dark:border-white/20">
                                                    <Monitor className="w-16 h-16 text-gray-200 mx-auto mb-4" />
                                                    <p className="text-gray-500 font-bold">لا توجد جلسات نشطة حالياً</p>
                                                </div>
                                            ) : (
                                                <div className="space-y-6">
                                                    {/* Current Session Header */}
                                                    <div className="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest px-2">
                                                        <div className="w-2 h-2 rounded-none bg-green-500 animate-pulse" />
                                                        الجلسة الحالية
                                                    </div>

                                                    {sessions.filter((s: any) => s.session_id === user.session_id).map((session: any) => (
                                                        <div 
                                                            key={session.id} 
                                                            className="relative overflow-hidden p-6 rounded-none border-2 border-primary/30 bg-primary/5 shadow-xl shadow-primary/5 group"
                                                        >
                                                            <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                                                                <ShieldCheck className="w-24 h-24 text-primary" />
                                                            </div>
                                                            <div className="relative flex flex-wrap md:flex-nowrap items-start gap-6">
                                                                <div className="p-4 bg-primary text-white rounded-none shadow-lg shadow-primary/30">
                                                                    {getDeviceIcon(session.device_type)}
                                                                </div>
                                                                
                                                                <div className="flex-1 min-w-0 space-y-4">
                                                                    <div className="flex items-center gap-3">
                                                                        <h5 className="text-lg font-black text-[#1c2434] dark:text-white">
                                                                            {session.device_name || 'جهازك الحالي'}
                                                                        </h5>
                                                                        <span className="px-3 py-1 bg-green-500 text-white text-[10px] font-black rounded-none uppercase shadow-sm">
                                                                            نشط الآن
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                                                        <div className="space-y-1">
                                                                            <p className="text-[10px] text-gray-400 uppercase font-black">المتصفح والنظام</p>
                                                                            <div className="flex items-center gap-2 text-sm font-bold text-[#1c2434] dark:text-white">
                                                                                <Globe2 className="w-4 h-4 text-primary" />
                                                                                {session.location?.browser} ({session.location?.platform})
                                                                            </div>
                                                                        </div>
                                                                        <div className="space-y-1">
                                                                            <p className="text-[10px] text-gray-400 uppercase font-black">الموقع الجغرافي</p>
                                                                            <div className="flex items-center gap-2 text-sm font-bold text-[#1c2434] dark:text-white">
                                                                                <MapPin className="w-4 h-4 text-red-500" />
                                                                                {session.location?.city}، {session.location?.country}
                                                                            </div>
                                                                        </div>
                                                                        <div className="space-y-1">
                                                                            <p className="text-[10px] text-gray-400 uppercase font-black">عنوان الـ IP</p>
                                                                            <div className="flex items-center gap-2 text-sm font-bold text-[#1c2434] dark:text-white font-mono">
                                                                                <Shield className="w-4 h-4 text-blue-500" />
                                                                                {session.ip_address}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}

                                                    {/* Other Sessions Header */}
                                                    {sessions.some((s: any) => s.session_id !== user.session_id) && (
                                                        <div className="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest px-2 pt-4">
                                                            <div className="w-2 h-2 rounded-none bg-gray-300" />
                                                            جلسات أخرى نشطة
                                                        </div>
                                                    )}

                                                    <div className="grid grid-cols-1 gap-4">
                                                        {sessions.filter((s: any) => s.session_id !== user.session_id).map((session: any) => (
                                                            <div 
                                                                key={session.id} 
                                                                className="p-5 rounded-none border-2 border-gray-300 dark:border-white/20 bg-gray-50/50 dark:bg-white/2 hover:border-primary/20 transition-all group"
                                                            >
                                                                <div className="flex items-center gap-5">
                                                                    <div className="p-3 bg-white dark:bg-[#1d2939] text-gray-400 rounded-none border border-gray-300 dark:border-white/20 group-hover:text-primary transition-colors">
                                                                        {getDeviceIcon(session.device_type)}
                                                                    </div>
                                                                    
                                                                    <div className="flex-1 min-w-0">
                                                                        <div className="flex items-center justify-between">
                                                                            <h5 className="font-bold text-[#1c2434] dark:text-white truncate">
                                                                                {session.device_name || 'جهاز غير معروف'}
                                                                            </h5>
                                                                            <Link 
                                                                                href={ProfileSessions.destroy.url(session.session_id)}
                                                                                method="delete"
                                                                                as="button"
                                                                                preserveScroll
                                                                                className="text-xs font-bold text-red-500 hover:text-red-600 flex items-center gap-1 p-2 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-none transition-all border-2 border-red-500/20"
                                                                            >
                                                                                <LogOut className="w-3.5 h-3.5" />
                                                                                تسجيل الخروج
                                                                            </Link>
                                                                        </div>
                                                                        
                                                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
                                                                            <div className="flex items-center gap-2 text-xs text-[#64748b]">
                                                                                <Globe2 className="w-3.5 h-3.5" />
                                                                                <span className="font-medium">{session.location?.browser} • {session.location?.platform}</span>
                                                                            </div>
                                                                            <div className="flex items-center gap-2 text-xs text-[#64748b]">
                                                                                <MapPin className="w-3.5 h-3.5" />
                                                                                <span className="font-medium">{session.location?.city}، {session.location?.country}</span>
                                                                            </div>
                                                                            <div className="flex items-center gap-2 text-xs text-[#64748b]">
                                                                                <Shield className="w-3.5 h-3.5" />
                                                                                <span className="font-medium">{session.ip_address}</span>
                                                                            </div>
                                                                            <div className="flex items-center gap-2 text-xs text-[#64748b]">
                                                                                <Clock className="w-3.5 h-3.5" />
                                                                                <span className="font-medium">نشط: {new Date(session.last_activity_at).toLocaleDateString('ar-EG')}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Advanced Stats */}
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div className="panel bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-none text-white shadow-lg shadow-blue-500/20 border-2 border-blue-400/30">
                                            <div className="flex items-center gap-4">
                                                <div className="p-3 bg-white/20 rounded-none">
                                                    <ShieldCheck className="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <p className="text-white/70 text-xs font-bold uppercase tracking-wider">حالة الأمان</p>
                                                    <h6 className="text-xl font-black">حسابك محمي</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="panel bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-none text-white shadow-lg shadow-purple-500/20 border-2 border-purple-400/30">
                                            <div className="flex items-center gap-4">
                                                <div className="p-3 bg-white/20 rounded-none">
                                                    <Globe2 className="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <p className="text-white/70 text-xs font-bold uppercase tracking-wider">الدخول الأخير من</p>
                                                    <h6 className="text-xl font-black">{sessions[0]?.location?.country || 'غير معروف'}</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="panel bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 rounded-none text-white shadow-lg shadow-emerald-500/20 border-2 border-emerald-400/30">
                                            <div className="flex items-center gap-4">
                                                <div className="p-3 bg-white/20 rounded-none">
                                                    <Smartphone className="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <p className="text-white/70 text-xs font-bold uppercase tracking-wider">الأجهزة الموثوقة</p>
                                                    <h6 className="text-xl font-black">{sessions.filter((s: any) => s.is_trusted_device).length} جهاز</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Logout Other Sessions Modal */}
            {showLogoutModal && (
                <div className="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="bg-white dark:bg-[#1c2434] w-full max-w-md rounded-none p-8 shadow-2xl border-4 border-red-600 animate-in zoom-in-95 duration-300">
                        <div className="flex flex-col items-center text-center space-y-4">
                            <div className="p-4 bg-red-50 dark:bg-red-500/10 rounded-none border-2 border-red-600/20">
                                <LogOut className="w-8 h-8 text-red-600" />
                            </div>
                            <h3 className="text-xl font-black text-[#1c2434] dark:text-white">تسجيل الخروج من جميع الأجهزة</h3>
                            <p className="text-sm text-[#64748b]">
                                يرجى إدخال كلمة المرور الخاصة بك لتأكيد رغبتك في تسجيل الخروج من جميع الأجهزة المتصلة بحسابك حالياً باستثناء هذا الجهاز.
                            </p>
                        </div>

                        <form onSubmit={logoutOtherSessions} className="mt-8 space-y-4">
                            <div className="space-y-2">
                                <label className="text-sm font-bold text-[#1c2434] dark:text-white px-1">كلمة المرور</label>
                                <input 
                                    type="password"
                                    className="w-full px-5 py-4 rounded-none border-2 border-gray-300 dark:bg-[#1d2939] dark:border-white/20 dark:text-white outline-none focus:border-red-600 transition-all text-center"
                                    placeholder="••••••••"
                                    value={logoutOtherSessionsForm.data.password}
                                    onChange={e => logoutOtherSessionsForm.setData('password', e.target.value)}
                                    autoFocus
                                />
                                {logoutOtherSessionsForm.errors.password && (
                                    <p className="text-red-500 text-xs font-bold text-center mt-2">{logoutOtherSessionsForm.errors.password}</p>
                                )}
                            </div>

                            <div className="flex gap-3 pt-4">
                                <button 
                                    type="button"
                                    onClick={() => {
                                        setShowLogoutModal(false);
                                        logoutOtherSessionsForm.reset();
                                        logoutOtherSessionsForm.clearErrors();
                                    }}
                                    className="flex-1 px-6 py-4 bg-gray-100 dark:bg-white/5 text-[#64748b] rounded-none border-2 border-gray-200 dark:border-white/10 font-bold hover:bg-gray-200 dark:hover:bg-white/10 transition-all"
                                >
                                    إلغاء
                                </button>
                                <button 
                                    type="submit"
                                    disabled={logoutOtherSessionsForm.processing}
                                    className="flex-1 px-6 py-4 bg-red-600 text-white rounded-none border-2 border-red-700 font-bold hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 disabled:opacity-50"
                                >
                                    {logoutOtherSessionsForm.processing ? 'جاري التنفيذ...' : 'تأكيد الخروج'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
