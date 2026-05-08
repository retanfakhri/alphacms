import { Head, Link, router } from '@inertiajs/react';
import { RotateCcw, Trash2, ArrowRight, User, Search, AlertCircle } from 'lucide-react';
import { useState } from 'react';

export default function UserTrash({ users }: { users: any }) {
    const [search, setSearch] = useState('');

    const restoreUser = (id: number) => {
        if (confirm('هل أنت متأكد من استعادة هذا المستخدم؟')) {
            router.post(`/admin/users/${id}/restore`);
        }
    };

    const forceDeleteUser = (id: number) => {
        if (confirm('تحذير: سيتم حذف هذا المستخدم نهائياً ولا يمكن استعادته. هل أنت متأكد؟')) {
            router.delete(`/admin/users/${id}/force`);
        }
    };

    return (
        <div className="space-y-6 font-tajawal animate-in fade-in duration-500">
            <Head title="سلة محذوفات المستخدمين" />

            <div className="flex items-center gap-4">
                <Link href="/admin/users" className="p-2 bg-white dark:bg-[#1c2434] rounded-xl hover:bg-gray-100 transition-all border border-gray-100 dark:border-white/5">
                    <ArrowRight className="w-5 h-5 text-gray-500" />
                </Link>
                <div>
                    <h2 className="text-2xl font-bold text-[#1c2434] dark:text-white">سلة المحذوفات</h2>
                    <p className="text-sm text-gray-500 mt-1">إدارة المستخدمين المحذوفين مؤقتاً</p>
                </div>
            </div>

            {/* Warning Message */}
            <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 p-4 rounded-2xl flex gap-4 items-start text-amber-800 dark:text-amber-400">
                <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
                <p className="text-sm font-medium">
                    الحسابات الموجودة هنا تم حذفها "حذفاً ناعماً". يمكنك استعادتها في أي وقت، أو حذفها نهائياً لتفريغ قاعدة البيانات.
                </p>
            </div>

            {/* Trash Table */}
            <div className="panel bg-white dark:bg-[#1c2434] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden">
                {users.data.length === 0 ? (
                    <div className="py-20 text-center">
                        <Trash2 className="w-16 h-16 text-gray-200 mx-auto mb-4" />
                        <p className="text-gray-500 font-bold">سلة المحذوفات فارغة حالياً</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-right">
                            <thead>
                                <tr className="bg-gray-50 dark:bg-white/2 border-b border-gray-100 dark:border-white/5 text-gray-500 text-sm font-bold">
                                    <th className="px-6 py-4">المستخدم</th>
                                    <th className="px-6 py-4">تاريخ الحذف</th>
                                    <th className="px-6 py-4">العمليات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-white/5">
                                {users.data.map((user: any) => (
                                    <tr key={user.id} className="hover:bg-gray-50 dark:hover:bg-white/2 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="h-10 w-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center font-bold">
                                                    {user.name.charAt(0)}
                                                </div>
                                                <div>
                                                    <p className="font-bold text-[#1c2434] dark:text-white">{user.name}</p>
                                                    <p className="text-xs text-gray-500">{user.email}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {new Date(user.deleted_at).toLocaleString('ar-EG')}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2">
                                                <button 
                                                    onClick={() => restoreUser(user.id)}
                                                    className="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-100 transition-all font-bold text-xs"
                                                >
                                                    <RotateCcw className="w-3.5 h-3.5" />
                                                    استعادة
                                                </button>
                                                <button 
                                                    onClick={() => forceDeleteUser(user.id)}
                                                    className="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 transition-all font-bold text-xs"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                    حذف نهائي
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                {users.links.length > 3 && (
                    <div className="px-6 py-4 bg-gray-50 dark:bg-white/2 border-t border-gray-100 dark:border-white/5 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            {users.links.map((link: any, index: number) => (
                                <Link
                                    key={index}
                                    href={link.url}
                                    className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${
                                        link.active 
                                        ? 'bg-primary text-white' 
                                        : 'bg-white dark:bg-[#1d2939] text-gray-600 dark:text-gray-400 hover:bg-gray-100'
                                    } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
