import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { 
    LayoutDashboard, Users, ShieldCheck, Settings, 
    FileText, Newspaper, Image, BarChart3, 
    ChevronDown, UserCircle, LogOut 
} from 'lucide-react';

const Sidebar = () => {
    const { t } = useTranslation();
    const { url, auth } = usePage<any>().props;
    const [openMenus, setOpenMenus] = useState<string[]>([]);

    const toggleMenu = (menu: string) => {
        setOpenMenus(prev => 
            prev.includes(menu) ? prev.filter(m => m !== menu) : [...prev, menu]
        );
    };

    const hasPermission = (permission: string) => auth.user?.can?.[permission] || false;
    const isSuperAdmin = auth.user?.roles?.includes('super_admin');

    const menuGroups = [
        {
            title: t('Core'),
            show: true,
            items: [
                { title: t('Dashboard'), icon: LayoutDashboard, link: '/admin', show: true },
                { title: t('Profile'), icon: UserCircle, link: '/admin/profile', show: true },
            ]
        },
        {
            title: t('Content'),
            show: hasPermission('manage_content') || hasPermission('create_articles') || hasPermission('create_news'),
            items: [
                { title: t('Articles'), icon: FileText, link: '/admin/articles', show: hasPermission('manage_content') || hasPermission('create_articles') },
                { title: t('News'), icon: Newspaper, link: '/admin/news', show: hasPermission('manage_content') || hasPermission('create_news') },
                { title: t('Media Manager'), icon: Image, link: '/admin/media', show: hasPermission('manage_content') },
            ]
        },
        {
            title: t('System Management'),
            show: hasPermission('manage_users') || hasPermission('manage_roles'),
            items: [
                { 
                    title: t('User Management'), 
                    icon: Users, 
                    id: 'users',
                    show: hasPermission('manage_users'),
                    children: [
                        { title: t('All Users'), link: '/admin/users' },
                        { title: t('Trash'), link: '/admin/users/trash' },
                    ]
                },
                { 
                    title: t('RBAC'), 
                    icon: ShieldCheck, 
                    id: 'rbac',
                    show: hasPermission('manage_roles'),
                    children: [
                        { title: t('Roles'), link: '/admin/roles' },
                        { title: t('Permissions'), link: '/admin/permissions' },
                    ]
                },
            ]
        },
        {
            title: t('Settings & Analytics'),
            show: hasPermission('manage_general_settings') || hasPermission('view_analytics'),
            items: [
                { 
                    title: t('Settings'), 
                    icon: Settings, 
                    id: 'settings',
                    show: hasPermission('manage_general_settings'),
                    children: [
                        { title: t('General'), link: '/admin/settings/general' },
                        { title: t('Mail'), link: '/admin/settings/mail' },
                        { title: t('CDN'), link: '/admin/settings/cdn' },
                        { title: t('Social'), link: '/admin/settings/social' },
                    ]
                },
                { title: t('Analytics'), icon: BarChart3, link: '/admin/analytics', show: hasPermission('view_analytics') },
            ]
        }
    ];

    return (
        <aside className="w-64 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col h-screen sticky top-0 font-tajawal shadow-sm">
            <div className="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <img src="/assets/images/logo.svg" className="h-8 w-8 dark:invert" alt="Logo" />
                <span className="font-black text-xl tracking-tighter dark:text-white uppercase">AlphaCMS</span>
            </div>

            <nav className="flex-1 overflow-y-auto p-4 space-y-8 no-scrollbar">
                {menuGroups.filter(g => g.show).map((group, gIdx) => (
                    <div key={gIdx} className="space-y-2">
                        <h3 className="px-4 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
                            {group.title}
                        </h3>
                        <div className="space-y-1">
                            {group.items.filter(i => i.show).map((item, iIdx) => {
                                const Icon = item.icon;
                                const isActive = url.startsWith(item.link);
                                const isMenuOpen = openMenus.includes(item.id || '');

                                if (item.children) {
                                    return (
                                        <div key={iIdx} className="space-y-1">
                                            <button 
                                                onClick={() => toggleMenu(item.id!)}
                                                className={`w-full flex items-center justify-between px-4 py-3 text-sm font-bold transition-all ${
                                                    isActive ? 'text-primary' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5'
                                                }`}
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Icon className="w-5 h-5" />
                                                    {item.title}
                                                </div>
                                                <ChevronDown className={`w-4 h-4 transition-transform ${isMenuOpen ? 'rotate-180' : ''}`} />
                                            </button>
                                            {isMenuOpen && (
                                                <div className="ltr:ml-4 rtl:mr-4 ltr:border-l rtl:border-r border-gray-100 dark:border-gray-800 space-y-1">
                                                    {item.children.map((child, cIdx) => (
                                                        <Link 
                                                            key={cIdx} 
                                                            href={child.link}
                                                            className={`block px-8 py-2 text-xs font-bold transition-all ${
                                                                url === child.link ? 'text-primary' : 'text-gray-500 hover:text-primary'
                                                            }`}
                                                        >
                                                            {child.title}
                                                        </Link>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                }

                                return (
                                    <Link 
                                        key={iIdx}
                                        href={item.link}
                                        className={`flex items-center gap-3 px-4 py-3 text-sm font-bold transition-all rounded-none ${
                                            isActive 
                                                ? 'bg-primary text-white shadow-lg shadow-primary/20' 
                                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5'
                                        }`}
                                    >
                                        <Icon className="w-5 h-5" />
                                        {item.title}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/2">
                <Link 
                    method="post" 
                    href="/admin/auth/logout" 
                    as="button"
                    className="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-500 hover:bg-red-50 transition-all"
                >
                    <LogOut className="w-5 h-5" />
                    {t('Sign Out')}
                </Link>
            </div>
        </aside>
    );
};

export default Sidebar;
