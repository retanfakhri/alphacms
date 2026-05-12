import React, { useState, useEffect } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { 
    Bell, Search, Menu, X, 
    Moon, Sun, Globe, 
    User, Settings, LogOut,
    ChevronDown, Command
} from 'lucide-react';

const Header = () => {
    const { t, i18n } = useTranslation();
    const { auth } = usePage<any>().props;
    const [isProfileOpen, setIsProfileOpen] = useState(false);
    const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            setIsDark(true);
            document.documentElement.classList.add('dark');
        } else {
            setIsDark(false);
            document.documentElement.classList.remove('dark');
        }
    }, []);

    const toggleDarkMode = () => {
        if (isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
            setIsDark(false);
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
            setIsDark(true);
        }
    };

    const toggleLanguage = () => {
        const newLang = i18n.language === 'ar' ? 'en' : 'ar';
        i18n.changeLanguage(newLang);
        document.dir = newLang === 'ar' ? 'rtl' : 'ltr';
        document.documentElement.lang = newLang;
    };

    return (
        <header className="sticky top-0 z-40 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 h-16 flex items-center justify-between px-6 font-tajawal">
            <div className="flex items-center gap-4 flex-1">
                <button className="lg:hidden p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 transition-all">
                    <Menu className="w-5 h-5" />
                </button>
                
                <div className="relative max-w-md w-full hidden md:block">
                    <Command className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input 
                        type="text" 
                        placeholder={t('Quick search (Ctrl+K)')} 
                        className="w-full ltr:pl-10 rtl:pr-10 py-2 bg-gray-50 dark:bg-white/5 border-transparent focus:bg-white dark:focus:bg-gray-800 focus:ring-primary focus:border-primary text-xs font-bold transition-all"
                    />
                </div>
            </div>

            <div className="flex items-center gap-2">
                <button 
                    onClick={toggleLanguage}
                    className="p-2.5 text-gray-500 hover:text-primary hover:bg-primary/5 transition-all flex items-center gap-2 text-xs font-black uppercase tracking-widest"
                >
                    <Globe className="w-4 h-4" />
                    {i18n.language === 'ar' ? 'EN' : 'AR'}
                </button>

                <button 
                    onClick={toggleDarkMode}
                    className="p-2.5 text-gray-500 hover:text-primary hover:bg-primary/5 transition-all"
                >
                    {isDark ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
                </button>

                <div className="relative">
                    <button 
                        onClick={() => setIsNotificationsOpen(!isNotificationsOpen)}
                        className="p-2.5 text-gray-500 hover:text-primary hover:bg-primary/5 transition-all relative"
                    >
                        <Bell className="w-4 h-4" />
                        <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white dark:border-gray-900"></span>
                    </button>
                </div>

                <div className="h-6 w-[1px] bg-gray-100 dark:bg-gray-800 mx-2"></div>

                <div className="relative">
                    <button 
                        onClick={() => setIsProfileOpen(!isProfileOpen)}
                        className="flex items-center gap-3 p-1.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-all group"
                    >
                        <div className="w-8 h-8 bg-primary text-white flex items-center justify-center text-xs font-black uppercase">
                            {auth.user?.name?.charAt(0)}
                        </div>
                        <div className="hidden lg:flex flex-col text-right">
                            <span className="text-xs font-black dark:text-white uppercase tracking-tight">{auth.user?.name}</span>
                            <span className="text-[10px] text-gray-400 font-bold uppercase">{auth.user?.roles?.[0]?.name || 'Admin'}</span>
                        </div>
                        <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isProfileOpen ? 'rotate-180' : ''}`} />
                    </button>

                    {isProfileOpen && (
                        <div className="absolute ltr:right-0 rtl:left-0 mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl animate-in fade-in slide-in-from-top-2 duration-200 py-2">
                            <Link href="/admin/profile" className="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary transition-all">
                                <User className="w-4 h-4" />
                                {t('My Profile')}
                            </Link>
                            <Link href="/admin/settings/general" className="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary transition-all">
                                <Settings className="w-4 h-4" />
                                {t('Admin Settings')}
                            </Link>
                            <div className="h-[1px] bg-gray-100 dark:border-gray-800 my-2"></div>
                            <Link method="post" href="/admin/auth/logout" as="button" className="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 transition-all">
                                <LogOut className="w-4 h-4" />
                                {t('Sign Out')}
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
};

export default Header;
