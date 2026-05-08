import { Head, Link, useForm } from '@inertiajs/react';
import { Shield, Save, ArrowRight, CheckCircle, Lock, Key, Globe } from 'lucide-react';

export default function RoleCreate({ groups }: any) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
    });

    const togglePermission = (name: string) => {
        const ids = data.permissions.includes(name)
            ? data.permissions.filter(id => id !== name)
            : [...data.permissions, name];
        setData('permissions', ids);
    };

    const toggleGroup = (groupPermissions: any[], checked: boolean) => {
        const groupNames = groupPermissions.map(p => p.name);
        const otherPermissions = data.permissions.filter(id => !groupNames.includes(id));
        setData('permissions', checked ? [...otherPermissions, ...groupNames] : otherPermissions);
    };

    const submit = (e: any) => {
        e.preventDefault();
        post('/admin/roles');
    };

    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen">
            <Head title="إضافة دور جديد - AlphaMedia" />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <Link href="/admin/roles" className="text-primary hover:underline">إدارة الأدوار</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">إضافة دور جديد</span>
            </div>

            <form onSubmit={submit} className="space-y-6">
                {/* Role Identity */}
                <div className="bg-white dark:bg-[#1e293b] p-8 shadow-sm border border-gray-100 dark:border-white/5 rounded-sm relative">
                    <div className="absolute top-0 right-0 w-1 h-full bg-blue-600"></div>
                    <div className="flex items-center gap-3 mb-8 border-b border-gray-50 dark:border-white/5 pb-5">
                        <div className="w-12 h-12 bg-blue-500/10 flex items-center justify-center shadow-inner rounded-sm">
                            <Shield className="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <h6 className="text-lg font-black text-gray-800 dark:text-white">هوية الدور</h6>
                            <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">تعريف مسمى الدور الجديد في النظام</p>
                        </div>
                    </div>
                    
                    <div className="max-w-md space-y-1.5">
                        <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">اسم الدور (باللغة الإنجليزية) <span className="text-red-500">*</span></label>
                        <input type="text" className={`w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border ${errors.name ? 'border-red-500' : 'border-gray-200 dark:border-white/10'} dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500 transition-all`} value={data.name} onChange={e => setData('name', e.target.value)} placeholder="مثال: editor, writer, moderator" />
                        {errors.name && <p className="text-red-500 text-[10px] font-bold">{errors.name}</p>}
                    </div>
                </div>

                {/* Permissions Grid */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between bg-white dark:bg-[#1e293b] p-4 border border-gray-100 dark:border-white/5 rounded-sm shadow-sm">
                        <h6 className="font-black text-gray-800 dark:text-white flex items-center gap-2">
                            <Key className="w-5 h-5 text-blue-600" /> توزيع الصلاحيات
                        </h6>
                        <div className="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            تم اختيار {data.permissions.length} صلاحية
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {groups.map((group: any) => {
                            const allChecked = group.permissions.every((p: any) => data.permissions.includes(p.name));
                            return (
                                <div key={group.id} className="bg-white dark:bg-[#1e293b] shadow-sm border border-gray-100 dark:border-white/5 rounded-sm overflow-hidden flex flex-col">
                                    <div className="px-5 py-4 bg-gray-50 dark:bg-white/2 border-b dark:border-white/5 flex justify-between items-center">
                                        <h5 className="text-xs font-black text-gray-700 dark:text-white uppercase tracking-tight">{group.name}</h5>
                                        <label className="flex items-center gap-2 cursor-pointer group">
                                            <span className="text-[9px] font-black text-gray-400 uppercase group-hover:text-blue-500 transition-colors">تحديد الكل</span>
                                            <input type="checkbox" className="w-4 h-4 accent-blue-600" checked={allChecked} onChange={(e) => toggleGroup(group.permissions, e.target.checked)} />
                                        </label>
                                    </div>
                                    <div className="p-5 grid grid-cols-2 gap-3 flex-grow">
                                        {group.permissions.map((p: any) => (
                                            <label key={p.id} className={`flex items-center gap-2 p-3 border rounded-sm cursor-pointer transition-all ${data.permissions.includes(p.name) ? 'bg-blue-500/5 border-blue-500/30 text-blue-600' : 'bg-transparent border-gray-100 dark:border-white/5 text-gray-400 hover:border-gray-300 dark:hover:border-white/20'}`}>
                                                <input type="checkbox" className="w-4 h-4 accent-blue-600" checked={data.permissions.includes(p.name)} onChange={() => togglePermission(p.name)} />
                                                <span className="text-[10px] font-black uppercase tracking-tighter">{p.display_name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )
                        })}
                    </div>
                </div>

                {/* Actions */}
                <div className="bg-white dark:bg-[#1e293b] p-6 shadow-xl border border-gray-100 dark:border-white/5 flex justify-between items-center rounded-sm">
                    <Link href="/admin/roles" className="px-10 py-3 border-2 border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400 font-black text-xs uppercase hover:bg-red-600 hover:text-white hover:border-red-600 transition-all flex items-center gap-2">
                        <ArrowRight className="w-4 h-4" /> إلغاء العملية
                    </Link>
                    <button type="submit" className="px-12 py-3 bg-blue-600 text-white font-black text-xs uppercase shadow-xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50" disabled={processing}>
                        {processing ? 'جاري الحفظ...' : <>إضافة الدور <Save className="w-4 h-4" /></>}
                    </button>
                </div>
            </form>
        </div>
    );
}
