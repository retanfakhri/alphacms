import { Head, Link } from '@inertiajs/react';
import { Key, Shield, Info, Layers } from 'lucide-react';

export default function PermissionIndex({ groups }: any) {
    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen">
            <Head title="الصلاحيات المسجلة - AlphaMedia" />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">عرض الصلاحيات</span>
            </div>

            <div className="bg-white dark:bg-[#1e293b] shadow-xl border border-gray-100 dark:border-white/5 rounded-sm overflow-hidden">
                <div className="px-6 py-5 border-b border-gray-100 dark:border-white/5 flex justify-between items-center bg-gray-50/50 dark:bg-white/2">
                    <h5 className="font-black text-lg text-gray-800 dark:text-white flex items-center gap-2">
                        <Key className="w-5 h-5 text-blue-600" /> قائمة الصلاحيات بالنظام
                    </h5>
                    <Link href="/admin/permissions/groups" className="px-6 py-2.5 bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-white font-black text-xs uppercase rounded-sm hover:bg-gray-200 transition-all flex items-center gap-2">
                        <Layers className="w-4 h-4" /> إدارة المجموعات
                    </Link>
                </div>

                <div className="p-6 space-y-8">
                    {groups.map((group: any) => (
                        <div key={group.id} className="space-y-4">
                            <div className="flex items-center gap-3 border-r-4 border-blue-600 pr-4">
                                <h6 className="font-black text-gray-800 dark:text-white text-base">{group.name}</h6>
                                <span className="text-[10px] text-gray-400 font-black uppercase tracking-widest bg-gray-50 dark:bg-white/5 px-2 py-0.5 rounded-sm">KEY: {group.key}</span>
                            </div>
                            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                {group.permissions.map((p: any) => (
                                    <div key={p.id} className="p-3 bg-gray-50 dark:bg-[#0f172a] border border-gray-100 dark:border-white/5 rounded-sm text-center">
                                        <div className="text-[10px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-tighter mb-1">{p.display_name}</div>
                                        <div className="text-[8px] text-gray-400 font-mono truncate" dir="ltr">{p.name}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
