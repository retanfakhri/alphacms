import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { 
    Plus, Search, Edit2, Trash2, Power, 
    RotateCcw, ShieldAlert, User, MoreHorizontal,
    Mail, Phone, Clock, Globe, Shield, ShieldCheck,
    UserCircle, Edit3, Users, CheckCircle, Filter, 
    Layers, Briefcase, ChevronLeft, ChevronRight, ChevronDown,
    AlertTriangle, X, Info
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

/**
 * PREMIUM VERSION - ALPHA MEDIA STYLE
 * High Aesthetics + Dark Mode Support + Live Filters + Custom Sweet-Alert Modals
 */
export default function UserIndex({ users, stats, filters, roles }: any) {
    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [online, setOnline] = useState(filters?.online || '');
    const [trash, setTrash] = useState(filters?.trash || 'active');
    const [perPage, setPerPage] = useState(filters?.perPage || '10');
    const [type, setType] = useState(filters?.type || '');
    const [role, setRole] = useState(filters?.role || '');

    // Delete Modal State
    const [deleteModal, setDeleteModal] = useState({ open: false, userId: null, userName: '', isForce: false });

    const isFirstMount = useRef(true);

    // Live Filters Logic
    useEffect(() => {
        if (isFirstMount.current) {
            isFirstMount.current = false;
            return;
        }
        const timer = setTimeout(() => {
            router.get('/admin/users', { search, status, online, trash, perPage, type, role }, {
                preserveState: true,
                replace: true,
                only: ['users', 'stats']
            });
        }, 400);
        return () => clearTimeout(timer);
    }, [search, status, online, trash, perPage, type, role]);

    const isOnline = (last: any) => {
        if (!last) return false;
        return (new Date().getTime() - new Date(last).getTime()) < (5 * 60 * 1000);
    };

    const getUserTypeLabels = (types: any) => {
        const typesArray = Array.isArray(types) ? types : [types || 'user'];
        return typesArray.map(type => {
            switch (type) {
                case 'admin': return { label: 'مدير نظام', color: 'bg-red-600' };
                case 'writer': return { label: 'كاتب محتوى', color: 'bg-blue-600' };
                default: return { label: 'عضو مسجل', color: 'bg-gray-600' };
            }
        });
    };

    const SelectWrapper = ({ children }: { children: React.ReactNode }) => (
        <div className="relative group">
            {children}
            <ChevronDown className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-hover:text-blue-500 pointer-events-none transition-colors" />
        </div>
    );

    const selectClass = "w-full appearance-none pr-4 pl-10 py-2.5 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 text-gray-800 dark:text-white text-xs font-bold rounded-none outline-none cursor-pointer focus:border-blue-500 transition-all";

    const handleDelete = () => {
        const route = deleteModal.isForce ? `/admin/users/${deleteModal.userId}/force` : `/admin/users/${deleteModal.userId}`;
        router.delete(route, {
            onSuccess: () => setDeleteModal({ open: false, userId: null, userName: '', isForce: false }),
        });
    };

    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen text-right" dir="rtl">
            <Head title="إدارة المستخدمين - AlphaMedia" />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4 items-center">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">إدارة المستخدمين</span>
            </div>

            {/* Stats Section */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                    { label: 'إجمالي المستخدمين', value: stats?.total, color: 'primary', icon: <Users className="w-8 h-8" /> },
                    { label: 'حسابات فعالة', value: stats?.active, color: 'success', icon: <CheckCircle className="w-8 h-8" /> },
                    { label: 'أونلاين الآن', value: stats?.online, color: 'blue-light', icon: <Globe className="w-8 h-8" /> },
                    { label: 'في المهملات', value: stats?.trashed, color: 'error', icon: <Trash2 className="w-8 h-8" /> }
                ].map((stat, i) => (
                    <div key={i} className="bg-white dark:bg-[#1e293b] p-6 shadow-sm border-2 border-gray-300 dark:border-white/20 relative overflow-hidden group">
                        <div className={`absolute top-0 right-0 w-1.5 h-full bg-${stat.color === 'primary' ? 'blue-600' : stat.color === 'success' ? 'green-500' : stat.color === 'blue-light' ? 'cyan-500' : 'red-600'}`}></div>
                        <div className="flex items-center justify-between">
                            <div>
                                <div className={`text-3xl font-black mb-1 text-${stat.color === 'primary' ? 'blue-600' : stat.color === 'success' ? 'green-600' : stat.color === 'blue-light' ? 'cyan-600' : 'red-600'}`}>{stat.value || 0}</div>
                                <div className="text-[10px] font-black text-gray-400 uppercase tracking-widest">{stat.label}</div>
                            </div>
                            <div className={`opacity-20 group-hover:opacity-100 transition-opacity text-${stat.color === 'primary' ? 'blue-600' : stat.color === 'success' ? 'green-600' : stat.color === 'blue-light' ? 'cyan-600' : 'red-600'}`}>{stat.icon}</div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="bg-white dark:bg-[#1e293b] shadow-xl border-2 border-gray-300 dark:border-white/20 rounded-sm overflow-hidden">
                <div className="px-6 py-5 border-b-2 border-gray-300 dark:border-white/20 flex justify-between items-center bg-gray-50/50 dark:bg-white/2">
                    <h5 className="font-black text-lg text-gray-800 dark:text-white flex items-center gap-2">
                        <Users className="w-5 h-5 text-blue-600" /> إدارة الحسابات
                    </h5>
                    <Link href="/admin/users/create" className="px-6 py-2.5 bg-blue-600 text-white font-black text-xs uppercase rounded-none shadow-lg shadow-blue-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                        <Plus className="w-4 h-4" /> إضافة حساب جديد
                    </Link>
                </div>

                {/* Filter Bar */}
                <div className="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3 bg-white dark:bg-[#1e293b]">
                    <div className="relative col-span-1 md:col-span-2">
                        <input
                            type="text"
                            className="w-full pr-10 pl-4 py-2.5 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 text-gray-800 dark:text-white text-xs font-bold rounded-none outline-none focus:border-blue-500 transition-all placeholder:text-gray-400"
                            placeholder="بحث في المستخدمين..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    </div>

                    <SelectWrapper>
                        <select value={type} onChange={(e) => setType(e.target.value)} className={selectClass}>
                            <option value="">نوع الحساب</option>
                            <option value="admin">مدير نظام</option>
                            <option value="writer">كاتب محتوى</option>
                            <option value="user">عضو مسجل</option>
                        </select>
                    </SelectWrapper>

                    <SelectWrapper>
                        <select value={role} onChange={(e) => setRole(e.target.value)} className={selectClass}>
                            <option value="">كل الصلاحيات</option>
                            {roles?.map((r: any) => (
                                <option key={r.id} value={r.id}>{r.name}</option>
                            ))}
                        </select>
                    </SelectWrapper>

                    <SelectWrapper>
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className={selectClass}>
                            <option value="">كل الحالات</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غير فعال</option>
                            <option value="blocked">محظور</option>
                        </select>
                    </SelectWrapper>

                    <SelectWrapper>
                        <select value={online} onChange={(e) => setOnline(e.target.value)} className={selectClass}>
                            <option value="">كل الاتصالات</option>
                            <option value="online">أونلاين</option>
                            <option value="offline">أوفلاين</option>
                        </select>
                    </SelectWrapper>

                    <SelectWrapper>
                        <select value={trash} onChange={(e) => setTrash(e.target.value)} className={selectClass}>
                            <option value="active">النشط</option>
                            <option value="trashed">المحذوف</option>
                            <option value="all">الكل</option>
                        </select>
                    </SelectWrapper>

                    <SelectWrapper>
                        <select value={perPage} onChange={(e) => setPerPage(e.target.value)} className={selectClass}>
                            <option value="10">10 / صفحة</option>
                            <option value="25">25 / صفحة</option>
                            <option value="50">50 / صفحة</option>
                        </select>
                    </SelectWrapper>
                </div>

                {/* Table */}
                <div className="mx-6 mb-6 border-2 border-gray-300 dark:border-white/20 overflow-hidden shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-right border-collapse">
                            <thead>
                                <tr className="bg-gray-100 dark:bg-white/5 text-[10px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b-2 border-gray-300 dark:border-white/20">
                                    <th className="px-6 py-5">المستخدم</th>
                                    <th className="px-6 py-5">نوع الحساب</th>
                                    <th className="px-6 py-5">التواصل</th>
                                    <th className="px-6 py-5 text-center">آخر ظهور / الحالة</th>
                                    <th className="px-6 py-5 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y-2 divide-gray-100 dark:divide-white/5">
                                {users?.data?.map((u: any) => {
                                    const onlineStatus = isOnline(u.last_active_at);
                                    const typeInfos = getUserTypeLabels(u.user_type);
                                    const isSuperAdmin = u.email === 'admin@admin.com' || u.id === 1;
                                    const isDeleted = u.deleted_at !== null;
                                    
                                    return (
                                        <tr key={u.id} className={`hover:bg-gray-50/50 dark:hover:bg-white/2 transition-colors group ${isDeleted ? 'bg-red-50/10' : ''}`}>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex items-center gap-3">
                                                    <div className="relative">
                                                        <img src={u.avatar_url || '/assets/images/default-avatar.png'} className="w-12 h-12 rounded-none object-cover border-2 border-white dark:border-gray-800 shadow-sm transition-transform group-hover:scale-105" alt="" />
                                                        {onlineStatus && <span className="absolute -bottom-1 -left-1 w-4 h-4 bg-green-500 border-2 border-white dark:border-[#1e293b] rounded-none shadow-sm animate-pulse"></span>}
                                                    </div>
                                                    <div>
                                                        <div className="font-black text-gray-800 dark:text-white text-base leading-tight flex items-center gap-1.5">
                                                            {u.name}
                                                            {isSuperAdmin && <ShieldCheck className="w-3.5 h-3.5 text-yellow-500" />}
                                                        </div>
                                                        <div className="text-xs text-gray-400 font-black tracking-tight mt-0.5">@{u.username || 'user'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex flex-wrap gap-1">
                                                    {typeInfos.map((info, idx) => (
                                                        <div key={idx} className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-none text-[8px] font-black uppercase tracking-tight ${info.color} text-white shadow-sm`}>
                                                            {info.label}
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex flex-col gap-2 items-start font-black">
                                                    <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                                        <Mail className="w-4 h-4 text-blue-500" />
                                                        <span>{u.email}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400" dir="ltr">
                                                        <Phone className="w-4 h-4 text-green-500" />
                                                        <span>{u.phone_code} {u.phone || '—'}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex flex-col items-center gap-2">
                                                    <div className="flex items-center gap-1.5 text-xs font-black">
                                                        <Clock className={`w-4 h-4 ${onlineStatus ? 'text-green-500' : 'text-gray-400'}`} />
                                                        <span className={onlineStatus ? 'text-green-500' : 'text-gray-400'}>
                                                            {onlineStatus ? 'نشط الآن' : (u.last_active_at ? new Date(u.last_active_at).toLocaleString('ar-EG') : 'غير مسجل')}
                                                        </span>
                                                    </div>
                                                    <span className={`px-2.5 py-1 text-[9px] font-black uppercase tracking-widest ${u.is_active ? 'bg-green-500/10 text-green-600 border-2 border-green-500/20' : 'bg-red-500/10 text-red-600 border-2 border-red-500/20'}`}>
                                                        {u.is_active ? 'فعال' : 'معطل'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-5">
                                                {!isSuperAdmin ? (
                                                    <div className="flex items-center justify-center gap-2">
                                                        {!isDeleted ? (
                                                            <>
                                                                <Link href={`/admin/users/${u.id}/edit`} className="p-2 bg-blue-50 dark:bg-blue-500/10 text-blue-600 border-2 border-blue-100 dark:border-blue-500/20 hover:bg-blue-600 hover:text-white transition-all shadow-sm rounded-none">
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </Link>
                                                                <button onClick={() => router.post(`/admin/users/${u.id}/toggle-status`, { type: 'active', value: !u.is_active })} className={`p-2 border-2 transition-all shadow-sm rounded-none ${u.is_active ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 border-orange-100 dark:border-orange-500/20 hover:bg-orange-600' : 'bg-green-50 dark:bg-green-500/10 text-green-600 border-green-100 dark:border-green-500/20 hover:bg-green-600'} hover:text-white`}>
                                                                    <Power className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button onClick={() => setDeleteModal({ open: true, userId: u.id, userName: u.name, isForce: false })} className="p-2 bg-red-50 dark:bg-red-500/10 text-red-600 border-2 border-red-100 dark:border-red-500/20 hover:bg-red-600 hover:text-white transition-all shadow-sm rounded-none">
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <button onClick={() => router.post(`/admin/users/${u.id}/restore`)} className="p-2 bg-green-50 dark:bg-green-500/10 text-green-600 border-2 border-green-100 dark:border-green-500/20 hover:bg-green-600 hover:text-white transition-all shadow-sm rounded-none" title="استعادة">
                                                                    <RotateCcw className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button onClick={() => setDeleteModal({ open: true, userId: u.id, userName: u.name, isForce: true })} className="p-2 bg-red-600 text-white border-2 border-red-600 hover:bg-red-700 transition-all shadow-sm rounded-none" title="حذف نهائي">
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div className="flex items-center justify-center">
                                                        <ShieldCheck className="w-5 h-5 text-red-600 animate-pulse" title="حساب مدير عام محمي" />
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                <div className="px-6 py-8 bg-gray-50/50 dark:bg-white/2 flex justify-center gap-3 border-t-2 border-gray-300 dark:border-white/20">
                    {users?.links?.map((l: any, i: number) => {
                        let label = String(l.label);
                        let Icon = null;

                        if (label.includes('pagination.previous') || label.includes('Previous')) {
                            label = 'السابق';
                            Icon = <ChevronRight className="w-4 h-4 ml-1" />;
                        } else if (label.includes('pagination.next') || label.includes('Next')) {
                            label = 'التالي';
                            Icon = <ChevronLeft className="w-4 h-4 mr-1" />;
                        }

                        const content = (
                            <div className="flex items-center justify-center min-w-[32px] h-8 px-3">
                                {label === 'السابق' && Icon}
                                <span dangerouslySetInnerHTML={{ __html: label }} className="mx-1" />
                                {label === 'التالي' && Icon}
                            </div>
                        );

                        return l.url ? (
                            <Link 
                                key={i} 
                                href={l.url} 
                                className={`flex items-center justify-center border-2 transition-all ${l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-[#0f172a] text-gray-500 dark:text-gray-400 border-gray-300 dark:border-white/20 hover:bg-gray-100 dark:hover:bg-white/5'} text-[10px] font-black rounded-none`}
                            >
                                {content}
                            </Link>
                        ) : (
                            <span key={i} className="flex items-center justify-center border-2 border-gray-100 dark:border-white/5 opacity-50 cursor-default text-[10px] font-black text-gray-300 dark:text-gray-600 bg-gray-50/50 rounded-none">
                                {content}
                            </span>
                        );
                    })}
                </div>
            </div>

            {/* Custom Sweet-Alert Style Delete Confirmation Modal */}
            <Dialog open={deleteModal.open} onOpenChange={(open) => !open && setDeleteModal({ ...deleteModal, open: false })}>
                <DialogContent className="sm:max-w-[400px] border-none p-0 rounded-none overflow-hidden bg-white dark:bg-[#1e293b] font-tajawal shadow-2xl" dir="rtl">
                    <div className="p-8 flex flex-col items-center text-center">
                        <div className={`w-20 h-20 flex items-center justify-center mb-6 animate-bounce`}>
                            {deleteModal.isForce ? (
                                <div className="w-full h-full bg-red-100 dark:bg-red-500/20 text-red-600 flex items-center justify-center border-4 border-red-600 shadow-xl">
                                    <AlertTriangle className="w-10 h-10" />
                                </div>
                            ) : (
                                <div className="w-full h-full bg-orange-100 dark:bg-orange-500/20 text-orange-600 flex items-center justify-center border-4 border-orange-600 shadow-xl">
                                    <Trash2 className="w-10 h-10" />
                                </div>
                            )}
                        </div>
                        
                        <h3 className="text-xl font-black text-gray-800 dark:text-white mb-2 uppercase tracking-tighter">
                            {deleteModal.isForce ? 'حذف نهائي للأبد؟' : 'هل أنت متأكد؟'}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 font-bold leading-relaxed px-4">
                            {deleteModal.isForce 
                                ? `أنت على وشك حذف حساب "${deleteModal.userName}" بشكل نهائي. لا يمكن استرجاع البيانات بعد هذه الخطوة!`
                                : `سيتم نقل حساب "${deleteModal.userName}" إلى المهملات. يمكنك استعادته لاحقاً.`}
                        </p>
                    </div>
                    
                    <div className="flex border-t-2 border-gray-100 dark:border-white/5">
                        <button 
                            onClick={() => setDeleteModal({ ...deleteModal, open: false })}
                            className="flex-1 py-4 text-xs font-black text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-all border-l-2 border-gray-100 dark:border-white/5"
                        >
                            تراجع
                        </button>
                        <button 
                            onClick={handleDelete}
                            className={`flex-1 py-4 text-xs font-black text-white transition-all shadow-inner ${deleteModal.isForce ? 'bg-red-600 hover:bg-red-700' : 'bg-orange-600 hover:bg-orange-700'}`}
                        >
                            {deleteModal.isForce ? 'نعم، احذف نهائياً' : 'نعم، انقل للمهملات'}
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}
