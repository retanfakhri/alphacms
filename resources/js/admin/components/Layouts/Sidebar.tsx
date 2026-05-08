import { Link, usePage } from '@inertiajs/react';
import { useSidebar } from '@/admin/context/SidebarContext';
import { useState } from 'react';
import IconMenuDashboard from '../Icon/Menu/IconMenuDashboard';
import { IconMenu, IconUser, IconBellBing } from '../Icon/Icons';

const Sidebar = () => {
    const { url, auth } = usePage<any>().props;
    const { isExpanded, isMobileOpen, isHovered, setIsHovered } = useSidebar();
    const [openMenus, setOpenMenus] = useState<string[]>([]);

    const toggleMenu = (menu: string) => {
        setOpenMenus((prev) => 
            prev.includes(menu) ? prev.filter(i => i !== menu) : [...prev, menu]
        );
    };

    const menuItems = [
        {
            title: 'لوحة التحكم',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin',
        },
        {
            title: 'الملف الشخصي',
            icon: <IconUser className="h-5 w-5" />,
            link: '/admin/profile',
        },
        {
            title: 'إدارة الأخبار',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/news',
        },
        {
            title: 'التعليقات',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/comments',
        },
        {
            title: 'الريلز',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/reels',
        },
        {
            title: 'الفيديو',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/videos',
        },
        {
            title: 'البث المباشر',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/live',
        },
        {
            title: 'أقسام الموقع',
            icon: <IconMenuDashboard className="h-5 w-5" />,
            link: '/admin/categories',
        },
    ];

    const adManagerItems = [
        { title: 'إضافة إعلان', link: '/admin/ads/create' },
        { title: 'إضافة زون', link: '/admin/ads/zones' },
    ];

    const whatsappItems = [
        { title: 'الحملات', link: '/admin/whatsapp/campaigns' },
        { title: 'المستخدمين', link: '/admin/whatsapp/users' },
        { title: 'المجموعات', link: '/admin/whatsapp/groups' },
    ];

    const userManagementItems = [
        { title: 'المستخدمين', link: '/admin/users' },
        { title: 'الأدوار', link: '/admin/roles' },
        { title: 'الصلاحيات', link: '/admin/permissions' },
    ];

    const settingsItems = [
        { title: 'الإعدادات العامة', link: '/admin/settings/general' },
        { title: 'إعدادات الطرف الثالث', link: '/admin/settings/third-party' },
        { title: 'إعدادات الـ CDN', link: '/admin/settings/cdn' },
    ];

    return (
        <aside
            className={`fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 right-0 bg-white dark:bg-[#1c2434] text-gray-900 dark:text-white h-screen transition-all duration-300 ease-in-out z-50 border-l border-gray-200 dark:border-gray-800
                ${
                    isExpanded || isMobileOpen
                        ? "w-[290px]"
                        : isHovered
                        ? "w-[290px]"
                        : "w-[90px]"
                }
                ${isMobileOpen ? "translate-x-0" : "translate-x-full"}
                lg:translate-x-0`}
            onMouseEnter={() => !isExpanded && setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
        >
            <div
                className={`py-8 flex items-center ${
                    !isExpanded && !isHovered ? "lg:justify-center" : "justify-start px-2"
                }`}
            >
                <Link href="/admin">
                    {isExpanded || isHovered || isMobileOpen ? (
                        <div className="flex items-center gap-3">
                            <img src="/assets/images/logo.svg" alt="Logo" className="h-8 w-8 dark:invert" />
                            <span className="text-2xl font-bold dark:text-white font-tajawal">TailAdmin</span>
                        </div>
                    ) : (
                        <img src="/assets/images/logo.svg" alt="Logo" className="h-8 w-8 dark:invert" />
                    )}
                </Link>
            </div>

            <div className="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar flex-1">
                <nav className="mb-6">
                    <div className="flex flex-col gap-4 px-2">
                        <div>
                            <h3 className={`mb-4 text-xs uppercase flex leading-[20px] text-gray-400 ${
                                !isExpanded && !isHovered ? "lg:justify-center" : "justify-start px-2"
                            }`}>
                                {isExpanded || isHovered || isMobileOpen ? "القائمة" : "..."}
                            </h3>
                            
                            <ul className="flex flex-col gap-2">
                                {menuItems.map((item, index) => {
                                    const isActive = url === item.link;
                                    return (
                                        <li key={index}>
                                            <Link
                                                href={item.link}
                                                className={`flex items-center gap-3 px-4 py-3 rounded-md transition-all duration-200 ${
                                                    isActive 
                                                        ? "bg-brand-500 text-white shadow-lg shadow-brand-500/20" 
                                                        : "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white"
                                                } ${!isExpanded && !isHovered ? "lg:justify-center px-2" : ""}`}
                                            >
                                                <span className={`${isActive ? "text-white" : "text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"}`}>
                                                    {item.icon}
                                                </span>
                                                {(isExpanded || isHovered || isMobileOpen) && (
                                                    <span className="font-medium whitespace-nowrap">{item.title}</span>
                                                )}
                                            </Link>
                                        </li>
                                    );
                                })}

                                {/* Ad Manager */}
                                <li>
                                    <button
                                        onClick={() => toggleMenu('ads')}
                                        className={`w-full flex items-center justify-between gap-3 px-4 py-3 rounded-md transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white ${
                                            !isExpanded && !isHovered ? "lg:justify-center px-2" : ""
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <IconMenuDashboard className="h-5 w-5 text-gray-400" />
                                            {(isExpanded || isHovered || isMobileOpen) && (
                                                <span className="font-medium whitespace-nowrap">مدير الإعلانات</span>
                                            )}
                                        </div>
                                        {(isExpanded || isHovered || isMobileOpen) && (
                                            <svg className={`h-4 w-4 transition-transform duration-200 ${openMenus.includes('ads') ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                        )}
                                    </button>
                                    {openMenus.includes('ads') && (isExpanded || isHovered || isMobileOpen) && (
                                        <ul className="mt-2 flex flex-col gap-1 pr-11">
                                            {adManagerItems.map((subItem, subIndex) => (
                                                <li key={subIndex}>
                                                    <Link href={subItem.link} className={`block py-2 text-sm text-gray-500 hover:text-brand-500 transition-colors`}>
                                                        {subItem.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>

                                {[{ title: 'استطلاعات الرأي', link: '/admin/polls', icon: <IconMenuDashboard className="h-5 w-5" /> }].map((item, index) => (
                                    <li key={index}>
                                        <Link href={item.link} className="flex items-center gap-3 px-4 py-3 rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5">
                                            <span className="text-gray-400">{item.icon}</span>
                                            {(isExpanded || isHovered || isMobileOpen) && <span className="font-medium">{item.title}</span>}
                                        </Link>
                                    </li>
                                ))}

                                {/* WhatsApp Campaigns */}
                                <li>
                                    <button
                                        onClick={() => toggleMenu('whatsapp')}
                                        className={`w-full flex items-center justify-between gap-3 px-4 py-3 rounded-md transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white ${
                                            !isExpanded && !isHovered ? "lg:justify-center px-2" : ""
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <IconMenuDashboard className="h-5 w-5 text-gray-400" />
                                            {(isExpanded || isHovered || isMobileOpen) && (
                                                <span className="font-medium whitespace-nowrap">حملات الواتساب</span>
                                            )}
                                        </div>
                                        {(isExpanded || isHovered || isMobileOpen) && (
                                            <svg className={`h-4 w-4 transition-transform duration-200 ${openMenus.includes('whatsapp') ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                        )}
                                    </button>
                                    {openMenus.includes('whatsapp') && (isExpanded || isHovered || isMobileOpen) && (
                                        <ul className="mt-2 flex flex-col gap-1 pr-11">
                                            {whatsappItems.map((subItem, subIndex) => (
                                                <li key={subIndex}>
                                                    <Link href={subItem.link} className={`block py-2 text-sm text-gray-500 hover:text-brand-500 transition-colors`}>
                                                        {subItem.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>

                                {/* User Management */}
                                <li>
                                    <button
                                        onClick={() => toggleMenu('users')}
                                        className={`w-full flex items-center justify-between gap-3 px-4 py-3 rounded-md transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white ${
                                            !isExpanded && !isHovered ? "lg:justify-center px-2" : ""
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <IconUser className="h-5 w-5 text-gray-400" />
                                            {(isExpanded || isHovered || isMobileOpen) && (
                                                <span className="font-medium whitespace-nowrap">إدارة المستخدمين</span>
                                            )}
                                        </div>
                                        {(isExpanded || isHovered || isMobileOpen) && (
                                            <svg className={`h-4 w-4 transition-transform duration-200 ${openMenus.includes('users') ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                        )}
                                    </button>
                                    {openMenus.includes('users') && (isExpanded || isHovered || isMobileOpen) && (
                                        <ul className="mt-2 flex flex-col gap-1 pr-11">
                                            {userManagementItems.map((subItem, subIndex) => (
                                                <li key={subIndex}>
                                                    <Link href={subItem.link} className={`block py-2 text-sm text-gray-500 hover:text-brand-500 transition-colors`}>
                                                        {subItem.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>
                            </ul>
                        </div>

                        <div className="mt-4">
                            <h3 className={`mb-4 text-xs uppercase flex leading-[20px] text-gray-400 ${
                                !isExpanded && !isHovered ? "lg:justify-center" : "justify-start px-2"
                            }`}>
                                {isExpanded || isHovered || isMobileOpen ? "النظام" : "..."}
                            </h3>
                            
                            <ul className="flex flex-col gap-2">
                                {/* Settings */}
                                <li>
                                    <button
                                        onClick={() => toggleMenu('settings')}
                                        className={`w-full flex items-center justify-between gap-3 px-4 py-3 rounded-md transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white ${
                                            !isExpanded && !isHovered ? "lg:justify-center px-2" : ""
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <IconMenu className="h-5 w-5 text-gray-400" />
                                            {(isExpanded || isHovered || isMobileOpen) && (
                                                <span className="font-medium whitespace-nowrap">الإعدادات</span>
                                            )}
                                        </div>
                                        {(isExpanded || isHovered || isMobileOpen) && (
                                            <svg className={`h-4 w-4 transition-transform duration-200 ${openMenus.includes('settings') ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                            </svg>
                                        )}
                                    </button>
                                    {openMenus.includes('settings') && (isExpanded || isHovered || isMobileOpen) && (
                                        <ul className="mt-2 flex flex-col gap-1 pr-11">
                                            {settingsItems.map((subItem, subIndex) => (
                                                <li key={subIndex}>
                                                    <Link href={subItem.link} className={`block py-2 text-sm text-gray-500 hover:text-brand-500 transition-colors`}>
                                                        {subItem.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>

            {/* User Info Bottom */}
            {(isExpanded || isHovered || isMobileOpen) && auth.user && (
                <div className="mt-auto p-4 mb-4 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-3">
                        <img 
                            src={auth.user.avatar_url || "/assets/images/user-profile.jpeg"} 
                            className="h-10 w-10 rounded-full object-cover border-2 border-primary/20" 
                            alt={auth.user.name} 
                        />
                        <div className="flex-1 truncate">
                            <p className="text-sm font-bold dark:text-white truncate">{auth.user.name}</p>
                            <p className="text-xs text-gray-500 truncate">{auth.user.email}</p>
                        </div>
                    </div>
                </div>
            )}
        </aside>
    );
};

export default Sidebar;
