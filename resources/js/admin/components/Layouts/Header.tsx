import { Link, usePage } from '@inertiajs/react';
import { useSidebar } from '@/admin/context/SidebarContext';
import { useSelector, useDispatch } from 'react-redux';
import { IRootState } from '@/admin/store';
import { toggleTheme } from '@/admin/store/themeConfigSlice';
import Dropdown from '@/admin/components/Dropdown';
import { IconBellBing, IconLogout, IconMenu, IconMoon, IconSearch, IconSun, IconUser } from '../Icon/Icons';

const Header = () => {
    const { toggleSidebar, toggleMobileSidebar, isExpanded, isHovered } = useSidebar();
    const themeConfig = useSelector((state: IRootState) => state.themeConfig);
    const dispatch = useDispatch();

    const { auth } = usePage().props as any;
    const user = auth.user;

    return (
        <header className="sticky top-0 z-30 flex w-full bg-white dark:bg-[#1c2434] border-b border-gray-200 dark:border-gray-800 transition-all duration-300 ease-in-out">
            <div className="flex flex-grow items-center justify-between px-4 py-4 md:px-6 2xl:px-11">
                <div className="flex items-center gap-2 sm:gap-4 lg:hidden">
                    {/* <!-- Hamburger Toggle BTN --> */}
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            toggleMobileSidebar();
                        }}
                        className="z-50 block rounded-sm border border-stroke bg-white p-1.5 shadow-sm dark:border-strokedark dark:bg-boxdark lg:hidden"
                    >
                        <IconMenu className="h-5 w-5" />
                    </button>
                    {/* <!-- Hamburger Toggle BTN --> */}
                </div>

                <div className="hidden sm:block">
                    <form action="" method="POST">
                        <div className="relative">
                            <button className="absolute left-0 top-1/2 -translate-y-1/2">
                                <IconSearch className="h-5 w-5 text-gray-400 hover:text-primary" />
                            </button>
                            <input
                                type="text"
                                placeholder="اكتب للبحث..."
                                className="w-full bg-transparent pl-9 pr-4 text-black focus:outline-none dark:text-white xl:w-125"
                            />
                        </div>
                    </form>
                </div>

                <div className="flex items-center gap-3 2xsm:gap-7">
                    <ul className="flex items-center gap-2 2xsm:gap-4">
                        {/* <!-- View Site --> */}
                        <Link
                            href="/"
                            className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-all"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            معاينة الموقع
                        </Link>

                        {/* <!-- Visitor Messages --> */}
                        <button className="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 hover:text-primary dark:text-white transition-all">
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <span className="absolute -top-0.5 right-0 z-1 h-2 w-2 rounded-full bg-blue-500"></span>
                        </button>

                        {/* <!-- Internal Chat --> */}
                        <button className="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 hover:text-primary dark:text-white transition-all">
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                        </button>

                        {/* <!-- Dark Mode Toggler --> */}
                        <button
                            onClick={() => dispatch(toggleTheme(themeConfig.theme === 'light' ? 'dark' : 'light'))}
                            className="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 hover:text-primary dark:text-white transition-all"
                        >
                            {themeConfig.theme === 'light' ? <IconSun className="h-5 w-5" /> : <IconMoon className="h-5 w-5" />}
                        </button>

                        {/* <!-- Notification Menu Area --> */}
                        <button className="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 hover:text-primary dark:text-white transition-all">
                            <IconBellBing className="h-5 w-5" />
                            <span className="absolute -top-0.5 right-0 z-1 h-2 w-2 rounded-full bg-red-500">
                                <span className="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                            </span>
                        </button>
                    </ul>

                    {/* <!-- User Area --> */}
                    <div className="dropdown shrink-0">
                        <Dropdown
                            offset={[0, 10]}
                            placement="bottom-start"
                            btnClassName="flex items-center gap-4 group"
                            button={
                                <>
                                    <span className="hidden text-right lg:block">
                                        <span className="block text-sm font-bold text-black dark:text-white group-hover:text-primary transition-colors">{user.name}</span>
                                        <span className="block text-xs text-gray-500">{user.email}</span>
                                    </span>
                                    <span className="h-11 w-11 rounded-full ring-2 ring-gray-100 dark:ring-white/10 overflow-hidden transition-all group-hover:ring-primary/30">
                                        <img src={user.avatar_url} className="h-full w-full object-cover" alt="User" />
                                    </span>
                                </>
                            }
                        >
                            <div className="w-[260px] bg-white dark:bg-[#1c2434] rounded-xl shadow-theme-lg border border-gray-100 dark:border-white/5 overflow-hidden">
                                {/* Header Info */}
                                <div className="px-6 py-5 border-b border-gray-100 dark:border-white/5 text-center sm:text-right">
                                    <p className="text-sm font-bold text-gray-900 dark:text-white">{user.name}</p>
                                    <p className="text-xs text-gray-500 mt-0.5">{user.email}</p>
                                    <div className="mt-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium border border-primary/20 bg-primary/5 text-primary">
                                        مدير النظام
                                    </div>
                                </div>

                                {/* Menu Items */}
                                <ul className="p-2">
                                    <li>
                                        <Link href="/admin/profile" className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary dark:hover:text-primary transition-all">
                                            <IconUser className="h-4.5 w-4.5 opacity-70" />
                                            الملف الشخصي
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href="/admin/settings" className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary dark:hover:text-primary transition-all">
                                            <IconMenu className="h-4.5 w-4.5 opacity-70" />
                                            إعدادات الحساب
                                        </Link>
                                    </li>
                                    <li>
                                        <button className="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary dark:hover:text-primary transition-all">
                                            <IconBellBing className="h-4.5 w-4.5 opacity-70" />
                                            قفل الشاشة
                                        </button>
                                    </li>
                                </ul>

                                {/* Logout */}
                                <div className="p-2 border-t border-gray-100 dark:border-white/5">
                                    <Link href="/logout" method="post" as="button" className="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                                        <IconLogout className="h-4.5 w-4.5" />
                                        تسجيل الخروج
                                    </Link>
                                </div>
                            </div>
                        </Dropdown>
                    </div>
                    {/* <!-- User Area --> */}
                </div>
            </div>
        </header>
    );
};

export default Header;
