import { ReactNode, Suspense, useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { IRootState } from '@/admin/store';
import { toggleRTL } from '@/admin/store/themeConfigSlice';
import { SidebarProvider, useSidebar } from '@/admin/context/SidebarContext';
import Sidebar from '@/admin/components/Layouts/Sidebar';
import Header from '@/admin/components/Layouts/Header';
import Backdrop from '@/admin/components/Layouts/Backdrop';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'sonner';
import '@/../css/admin.css';
import { Loader2 } from 'lucide-react';

interface AdminLayoutProps {
    children: ReactNode;
}

const LayoutContent = ({ children }: AdminLayoutProps) => {
    const { isExpanded, isHovered, isMobileOpen } = useSidebar();
    const themeConfig = useSelector((state: IRootState) => state.themeConfig);
    const dispatch = useDispatch();
    const { flash } = usePage().props as any;
    const [isLoading, setIsLoading] = useState(false);

    // Toast Handler
    useEffect(() => {
        if (flash?.toast) {
            const { type, message } = flash.toast;
            switch (type) {
                case 'success': toast.success(message); break;
                case 'error': toast.error(message); break;
                case 'warning': toast.warning(message); break;
                default: toast(message);
            }
        }
    }, [flash]);

    // Global Loading Spinner Handler
    useEffect(() => {
        const unbindStart = router.on('start', () => setIsLoading(true));
        const unbindFinish = router.on('finish', () => setIsLoading(false));
        const unbindError = router.on('error', () => setIsLoading(false));

        return () => {
            unbindStart();
            unbindFinish();
            unbindError();
        };
    }, []);

    useEffect(() => {
        dispatch(toggleRTL('rtl'));
        document.documentElement.setAttribute('dir', 'rtl');
        document.documentElement.classList.add('rtl');
    }, [dispatch]);

    useEffect(() => {
        if (themeConfig.theme === 'dark' || (themeConfig.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }, [themeConfig.theme]);

    return (
        <div className="min-h-screen xl:flex dark:bg-[#111827] font-tajawal relative overflow-x-hidden">
            {/* Global Page Loader Overlay */}
            {isLoading && (
                <div className="fixed inset-0 z-[9999] bg-white/60 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center animate-in fade-in duration-300">
                    <div className="bg-white dark:bg-[#1e293b] p-8 shadow-2xl border-2 border-blue-600/20 flex flex-col items-center gap-4 rounded-none">
                        <Loader2 className="w-12 h-12 text-blue-600 animate-spin" />
                        <div className="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest">جاري تحميل الصفحة...</div>
                    </div>
                </div>
            )}

            <div>
                <Sidebar />
                <Backdrop />
            </div>
            <div
                className={`flex-1 transition-all duration-300 ease-in-out ${
                    isExpanded || isHovered ? "lg:ms-[290px]" : "lg:ms-[90px]"
                } ${isMobileOpen ? "ms-0" : ""}`}
            >
                <Header />
                <div className="p-4 mx-auto max-w-screen-2xl md:p-6 2xl:p-10 min-h-[calc(100vh-140px)]">
                    <Suspense fallback={<div className="flex h-full items-center justify-center p-20 text-gray-500">جاري التحميل...</div>}>
                        {children}
                    </Suspense>
                </div>
                
                <footer className="p-4 md:p-6 2xl:p-10 pt-0 mt-auto text-center sm:text-end text-[#64748b] text-sm">
                    © {new Date().getFullYear()}. AlphaCMS جميع الحقوق محفوظة.
                </footer>
            </div>
        </div>
    );
};

export default function AdminLayout({ children }: AdminLayoutProps) {
    return (
        <SidebarProvider>
            <LayoutContent>{children}</LayoutContent>
        </SidebarProvider>
    );
}
