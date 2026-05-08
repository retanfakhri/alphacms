import { Head, Link, useForm } from '@inertiajs/react';
import { Layers, Plus, Save, Info, Hash } from 'lucide-react';

export default function PermissionGroups({ groups }: any) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        key: '',
        guard_name: 'admin',
        sort_order: 0,
    });

    const submit = (e: any) => {
        e.preventDefault();
        post('/admin/permissions/groups', {
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen">
            <Head title="إدارة مجموعات الصلاحيات - AlphaMedia" />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <Link href="/admin/permissions" className="text-primary hover:underline">الصلاحيات</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">إدارة المجموعات</span>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Create Group Form */}
                <div className="lg:col-span-1">
                    <form onSubmit={submit} className="bg-white dark:bg-[#1e293b] p-6 shadow-xl border border-gray-100 dark:border-white/5 rounded-sm sticky top-6">
                        <div className="flex items-center gap-3 mb-6 border-b border-gray-50 dark:border-white/5 pb-4">
                            <Plus className="w-5 h-5 text-blue-600" />
                            <h6 className="font-black text-gray-800 dark:text-white uppercase tracking-tight">إضافة مجموعة جديدة</h6>
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase">اسم المجموعة</label>
                                <input type="text" className="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="مثال: إدارة الأخبار" />
                                {errors.name && <p className="text-red-500 text-[10px] font-bold">{errors.name}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase">مفتاح المجموعة (Unique Key)</label>
                                <input type="text" className="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500" value={data.key} onChange={e => setData('key', e.target.value)} placeholder="news, users, etc." dir="ltr" />
                                {errors.key && <p className="text-red-500 text-[10px] font-bold">{errors.key}</p>}
                            </div>
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase">الترتيب</label>
                                <input type="number" className="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500" value={data.sort_order} onChange={e => setData('sort_order', parseInt(e.target.value))} />
                            </div>

                            <button type="submit" className="w-full py-3 bg-blue-600 text-white font-black text-xs uppercase shadow-lg shadow-blue-600/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2" disabled={processing}>
                                {processing ? 'جاري الحفظ...' : <>حفظ المجموعة <Save className="w-4 h-4" /></>}
                            </button>
                        </div>
                    </form>
                </div>

                {/* Groups List */}
                <div className="lg:col-span-2">
                    <div className="bg-white dark:bg-[#1e293b] shadow-xl border border-gray-100 dark:border-white/5 rounded-sm overflow-hidden">
                        <div className="px-6 py-5 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/2">
                            <h5 className="font-black text-gray-800 dark:text-white flex items-center gap-2">
                                <Layers className="w-5 h-5 text-blue-600" /> مجموعات الصلاحيات المسجلة
                            </h5>
                        </div>
                        <div className="divide-y divide-gray-50 dark:divide-white/5">
                            {groups.map((group: any) => (
                                <div key={group.id} className="p-5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-white/2 transition-colors">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 bg-blue-500/10 text-blue-600 flex items-center justify-center font-black text-xs rounded-sm border border-blue-500/20 shadow-inner">
                                            {group.sort_order}
                                        </div>
                                        <div>
                                            <div className="font-black text-gray-800 dark:text-white text-sm">{group.name}</div>
                                            <div className="text-[10px] text-gray-400 font-mono" dir="ltr">{group.key}</div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="px-3 py-1 bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest">{group.guard_name}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
