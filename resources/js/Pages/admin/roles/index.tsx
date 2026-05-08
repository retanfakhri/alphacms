import { Head, Link, router } from '@inertiajs/react';
import { Shield, Plus, Edit2, Trash2, Key, Info, ShieldCheck } from 'lucide-react';

export default function RoleIndex({ roles }: any) {
    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen">
            <Head title="إدارة الأدوار - AlphaMedia" />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">إدارة الأدوار</span>
            </div>

            <div className="bg-white dark:bg-[#1e293b] shadow-xl border border-gray-100 dark:border-white/5 rounded-sm">
                <div className="px-6 py-5 border-b border-gray-100 dark:border-white/5 flex justify-between items-center bg-gray-50/50 dark:bg-white/2">
                    <h5 className="font-black text-lg text-gray-800 dark:text-white flex items-center gap-2">
                        <Shield className="w-5 h-5 text-blue-600" /> إدارة الأدوار والمجموعات
                    </h5>
                    <Link href="/admin/roles/create" className="px-6 py-2.5 bg-blue-600 text-white font-black text-xs uppercase rounded-sm shadow-lg shadow-blue-600/20 hover:scale-[1.02] transition-all flex items-center gap-2">
                        <Plus className="w-4 h-4" /> إضافة دور جديد
                    </Link>
                </div>

                <div className="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {roles.map((role: any) => (
                        <div key={role.id} className="bg-gray-50 dark:bg-[#0f172a] p-6 border border-gray-100 dark:border-white/5 rounded-sm relative group overflow-hidden">
                            <div className="absolute top-0 right-0 w-1 h-full bg-blue-600 transition-all group-hover:w-2"></div>
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h6 className="font-black text-gray-800 dark:text-white text-base flex items-center gap-2">
                                        {role.name}
                                        {role.name === 'admin' && <ShieldCheck className="w-4 h-4 text-yellow-500" />}
                                    </h6>
                                    <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-1">صلاحية النظام: {role.guard_name}</p>
                                </div>
                                <div className="text-2xl opacity-20">🛡️</div>
                            </div>
                            
                            <div className="flex items-center gap-2 mb-6 bg-white dark:bg-[#1e293b] p-3 rounded-sm border border-gray-100 dark:border-white/5">
                                <Key className="w-4 h-4 text-blue-500" />
                                <span className="text-xs font-black text-gray-600 dark:text-gray-300">
                                    {role.permissions_count} صلاحية مسجلة
                                </span>
                            </div>

                            <div className="flex items-center gap-2 pt-4 border-t border-gray-200 dark:border-white/5">
                                <Link href={`/admin/roles/${role.id}/edit`} className="flex-grow py-2.5 bg-blue-500/10 text-blue-600 text-[10px] font-black uppercase text-center hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center gap-2">
                                    <Edit2 className="w-3 h-3" /> تعديل الصلاحيات
                                </Link>
                                <button onClick={() => confirm('حذف هذا الدور؟') && router.delete(`/admin/roles/${role.id}`)} className="p-2.5 bg-red-500/10 text-red-600 hover:bg-red-600 hover:text-white transition-all">
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Info Section */}
            <div className="bg-blue-600 p-8 rounded-sm shadow-xl shadow-blue-600/20 text-white relative overflow-hidden">
                <div className="absolute -right-10 -bottom-10 text-[150px] opacity-10">🛡️</div>
                <div className="relative z-10 flex items-center gap-6">
                    <div className="w-16 h-16 bg-white/20 flex items-center justify-center text-3xl rounded-sm">💡</div>
                    <div>
                        <h4 className="text-xl font-black mb-2 uppercase tracking-tighter">نظام الصلاحيات المتقدم</h4>
                        <p className="text-sm font-bold opacity-90 max-w-2xl leading-relaxed">
                            يمكنك من هنا التحكم الكامل في من يستطيع فعل ماذا؛ قم بتعيين أدوار مخصصة لكل قسم في موقعك (محررين، مبرمجين، مدراء) وخصص صلاحياتهم بدقة متناهية.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
