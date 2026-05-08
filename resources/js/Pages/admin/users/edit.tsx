import { Head, Link, useForm } from '@inertiajs/react';
import { 
    User, Mail, Phone, Lock, Shield, 
    CheckCircle, XCircle, Camera, Save, ArrowRight,
    ShieldCheck, Key, Globe, Info, UserCircle, Check, Settings,
    Facebook, Instagram, Youtube, Linkedin, Twitter, Link as LinkIcon, AlignLeft,
    ChevronDown, ChevronUp, Layers
} from 'lucide-react';
import { useState, useRef } from 'react';

export default function UserEdit({ user, roles, permissionGroups }: any) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'put',
        name: user.name || '',
        username: user.username || '',
        email: user.email || '',
        phone_code: user.phone_code || '+962',
        phone: user.phone || '',
        password: '',
        password_confirmation: '',
        type: Array.isArray(user.user_type) ? user.user_type : [user.user_type || 'user'],
        is_active: !!user.is_active,
        account_status: user.account_status || 'active',
        roles: user.roles?.map((r: any) => r.id) || [],
        permissions: user.permissions?.map((p: any) => p.name) || [],
        avatar: null as File | null,
        bio: user.bio || '',
        facebook_url: user.facebook_url || '',
        instagram_url: user.instagram_url || '',
        tiktok_url: user.tiktok_url || '',
        linkedin_url: user.linkedin_url || '',
        youtube_url: user.youtube_url || '',
        x_url: user.x_url || '',
        website_url: user.website_url || '',
    });

    const [preview, setPreview] = useState<string | null>(user.avatar_url || null);
    const [showCustomPermissions, setShowCustomPermissions] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);
    const isSuperAdmin = user.email === 'admin@admin.com' || user.id === 1;

    const handleAvatarChange = (e: any) => {
        const file = e.target.files[0];
        if (file) {
            setData('avatar', file);
            setPreview(URL.createObjectURL(file));
        }
    };

    const toggleType = (type: string) => {
        const current = [...data.type];
        const index = current.indexOf(type);
        if (index > -1) {
            if (current.length > 1) {
                current.splice(index, 1);
            }
        } else {
            current.push(type);
        }
        setData('type', current);
    };

    const togglePermission = (name: string) => {
        const current = [...data.permissions];
        const index = current.indexOf(name);
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(name);
        }
        setData('permissions', current);
    };

    const toggleGroup = (groupPermissions: any[]) => {
        const names = groupPermissions.map(p => p.name);
        const allSelected = names.every(name => data.permissions.includes(name));
        
        let newPermissions;
        if (allSelected) {
            newPermissions = data.permissions.filter(name => !names.includes(name));
        } else {
            newPermissions = Array.from(new Set([...data.permissions, ...names]));
        }
        setData('permissions', newPermissions);
    };

    const submit = (e: any) => {
        e.preventDefault();
        post(`/admin/users/${user.id}`, {
            forceFormData: true,
            onSuccess: () => {
                // Handle success
            },
        });
    };

    const isAdmin = data.type.includes('admin');

    // Calculate effective permissions from selected roles
    const inheritedPermissions = roles
        .filter((role: any) => data.roles.includes(role.id))
        .flatMap((role: any) => role.permissions.map((p: any) => p.name));
    
    const allEffectivePermissions = Array.from(new Set([...data.permissions, ...inheritedPermissions]));

    return (
        <div className="p-6 space-y-6 font-tajawal animate-in fade-in duration-500 bg-gray-50 dark:bg-[#0f172a] min-h-screen">
            <Head title={`تعديل: ${user.name} - AlphaMedia`} />

            {/* Breadcrumbs */}
            <div className="flex gap-2 text-sm font-bold mb-4">
                <Link href="/admin" className="text-primary hover:underline">لوحة التحكم</Link>
                <span className="text-gray-400">/</span>
                <Link href="/admin/users" className="text-primary hover:underline">إدارة المستخدمين</Link>
                <span className="text-gray-400">/</span>
                <span className="text-gray-500 dark:text-gray-400 font-black tracking-tight">تعديل الحساب</span>
            </div>

            {isSuperAdmin && (
                <div className="bg-red-600 p-4 shadow-xl border border-red-700 rounded-sm flex items-center gap-3 animate-pulse">
                    <ShieldCheck className="w-8 h-8 text-white" />
                    <div>
                        <h6 className="font-black text-white text-lg leading-tight uppercase tracking-tight">حساب مدير عام محمي</h6>
                        <p className="text-[10px] text-white/80 font-bold uppercase tracking-widest">فقط المدير العام يمكنه التعديل على بياناته الخاصة</p>
                    </div>
                </div>
            )}

            <form onSubmit={submit} className="flex flex-col gap-6 pb-24">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column: Avatar & Status & Permissions */}
                    <div className="space-y-6">
                        {/* Avatar Panel */}
                        <div className="bg-white dark:bg-[#1e293b] p-8 shadow-sm border-2 border-gray-200 dark:border-white/10 flex flex-col items-center gap-5 rounded-sm relative overflow-hidden h-fit">
                            <div className="absolute top-0 right-0 w-1 h-full bg-blue-600"></div>
                            <div className="relative cursor-pointer" onClick={() => fileInput.current?.click()}>
                                <div className="w-36 h-36 rounded-none overflow-hidden border-4 border-gray-50 dark:border-[#0f172a] shadow-xl bg-gray-100 dark:bg-[#0f172a] flex items-center justify-center">
                                    {preview ? <img src={preview} className="w-full h-full object-cover" alt="" /> : <User className="w-16 h-16 text-gray-300 dark:text-gray-700" />}
                                </div>
                                <div className="absolute inset-0 rounded-none bg-black/40 opacity-0 hover:opacity-100 flex items-center justify-center transition-all">
                                    <Camera className="w-8 h-8 text-white" />
                                </div>
                            </div>
                            <input type="file" ref={fileInput} className="hidden" accept="image/*" onChange={handleAvatarChange} />
                            <div className="text-center">
                                <h6 className="font-black text-gray-800 dark:text-white mb-1">تغيير الصورة</h6>
                                <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest leading-relaxed">JPG, PNG, WEBP<br/>MAX SIZE 2MB</p>
                            </div>
                        </div>

                        {/* Status Panel */}
                        {!isSuperAdmin && (
                            <div className="bg-white dark:bg-[#1e293b] p-6 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative">
                                <div className="absolute top-0 right-0 w-1 h-full bg-red-600"></div>
                                <h6 className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">حالة الحساب</h6>
                                <div className="space-y-3">
                                    <label className="flex items-center justify-between p-3 border-2 border-gray-200 dark:border-white/10 rounded-sm bg-gray-50 dark:bg-[#0f172a] cursor-pointer">
                                        <span className="text-[10px] font-black text-gray-600 dark:text-gray-300 uppercase">تفعيل الحساب</span>
                                        <input type="checkbox" className="w-4 h-4 accent-blue-600" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} />
                                    </label>
                                    <select className="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white rounded-sm outline-none" value={data.account_status} onChange={e => setData('account_status', e.target.value)}>
                                        <option value="active">نشط (Active)</option>
                                        <option value="pending">قيد المراجعة (Pending)</option>
                                        <option value="blocked">محظور (Blocked)</option>
                                        <option value="suspended">معلق (Suspended)</option>
                                    </select>
                                </div>
                            </div>
                        )}

                        {/* Access Level (Types) Panel */}
                        <div className="bg-white dark:bg-[#1e293b] p-6 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative">
                            <div className="absolute top-0 right-0 w-1 h-full bg-blue-600"></div>
                            <h6 className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">نوع المستخدم / مستوى الوصول <span className="text-red-500">*</span></h6>
                            <div className="space-y-2">
                                {['admin', 'writer', 'user'].map((t) => (
                                    <label key={t} className={`flex items-center gap-3 p-3 border rounded-sm cursor-pointer transition-all ${data.type.includes(t) ? 'bg-blue-600 border-blue-600 shadow-md shadow-blue-600/10' : 'bg-gray-50 dark:bg-[#0f172a] border-gray-200 dark:border-white/10'}`}>
                                        <div className={`w-4 h-4 rounded-sm border-2 flex items-center justify-center transition-all ${data.type.includes(t) ? 'bg-white border-white' : 'border-gray-300 dark:border-white/10'}`}>
                                            {data.type.includes(t) && <Check className="w-3 h-3 text-blue-600" strokeWidth={4} />}
                                        </div>
                                        <input type="checkbox" className="hidden" disabled={isSuperAdmin && t === 'admin'} checked={data.type.includes(t)} onChange={() => toggleType(t)} />
                                        <span className={`text-[9px] font-black uppercase tracking-widest ${data.type.includes(t) ? 'text-white' : 'text-gray-600 dark:text-gray-300'}`}>
                                            {t === 'admin' ? 'مدير نظام' : t === 'writer' ? 'كاتب محتوى' : 'عضو مسجل'}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Administrative Roles & Permissions Section */}
                        {isAdmin && !isSuperAdmin && (
                            <div className="bg-white dark:bg-[#1e293b] p-6 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative animate-in fade-in slide-in-from-top-4 duration-700 ease-out">
                                <div className="absolute top-0 right-0 w-1 h-full bg-cyan-500"></div>
                                <h6 className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2"><ShieldCheck className="w-4 h-4 text-cyan-600" /> الأدوار الإدارية</h6>
                                <div className="space-y-2">
                                    {roles?.map((role: any) => (
                                        <label key={role.id} className={`flex items-center gap-3 px-4 py-3 border rounded-sm cursor-pointer transition-all ${data.roles.includes(role.id) ? 'bg-cyan-600 text-white border-cyan-600 shadow-md shadow-cyan-600/20' : 'bg-gray-50 dark:bg-[#0f172a] border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400'}`}>
                                            <input type="checkbox" className="hidden" checked={data.roles.includes(role.id)} onChange={(e) => {
                                                const ids = e.target.checked ? [...data.roles, role.id] : data.roles.filter(id => id !== role.id);
                                                setData('roles', ids);
                                            }} />
                                            <Layers className={`w-4 h-4 ${data.roles.includes(role.id) ? 'text-white' : 'text-gray-300'}`} />
                                            <div className="flex flex-col">
                                                <span className="text-[10px] font-black uppercase tracking-widest">{role.name}</span>
                                                <span className={`text-[7px] font-black uppercase ${data.roles.includes(role.id) ? 'text-white/70' : 'text-gray-400'}`}>موروثة</span>
                                            </div>
                                        </label>
                                    ))}
                                </div>

                                {/* Custom Permissions Toggle */}
                                <div className="mt-6 pt-6 border-t border-gray-50 dark:border-white/5">
                                    <button type="button" onClick={() => setShowCustomPermissions(!showCustomPermissions)} className="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-700 transition-all w-full justify-between">
                                        <span className="flex items-center gap-2">
                                            {showCustomPermissions ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                                            تخصيص صلاحيات إضافية
                                        </span>
                                        {data.permissions.length > 0 && <span className="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[8px]">{data.permissions.length}</span>}
                                    </button>
                                    {showCustomPermissions && (
                                        <div className="mt-4 space-y-4 animate-in slide-in-from-top duration-500">
                                            {permissionGroups?.map((group: any) => {
                                                const groupNames = group.permissions.map((p: any) => p.name);
                                                const allSelected = groupNames.every((name: any) => data.permissions.includes(name));
                                                return (
                                                    <div key={group.id} className="bg-gray-50 dark:bg-white/2 border-2 border-gray-200 dark:border-white/10 rounded-sm p-3">
                                                        <div className="flex items-center justify-between mb-3 pb-2 border-b dark:border-white/5">
                                                            <span className="text-[9px] font-black uppercase">{group.name}</span>
                                                            <button type="button" onClick={() => toggleGroup(group.permissions)} className="text-[7px] font-black uppercase text-blue-600">
                                                                {allSelected ? 'إلغاء' : 'تحديد'}
                                                            </button>
                                                        </div>
                                                        <div className="grid grid-cols-1 gap-1.5">
                                                            {group.permissions.map((p: any) => {
                                                                const isInherited = inheritedPermissions.includes(p.name);
                                                                return (
                                                                    <label key={p.id} className={`flex items-center gap-2 cursor-pointer group ${isInherited ? 'opacity-60 cursor-not-allowed' : ''}`}>
                                                                        <input 
                                                                            type="checkbox" 
                                                                            className="w-3 h-3 accent-blue-600" 
                                                                            checked={data.permissions.includes(p.name) || isInherited} 
                                                                            disabled={isInherited}
                                                                            onChange={() => !isInherited && togglePermission(p.name)} 
                                                                        />
                                                                        <div className="flex flex-col">
                                                                            <span className={`text-[8px] font-bold ${isInherited ? 'text-blue-500' : 'text-gray-500 group-hover:text-blue-600'} transition-colors`}>
                                                                                {p.display_name}
                                                                            </span>
                                                                        </div>
                                                                    </label>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Column: Detailed Info */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Personal Information Panel */}
                        <div className="bg-white dark:bg-[#1e293b] p-8 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative h-fit">
                            <div className="absolute top-0 right-0 w-1 h-full bg-blue-600"></div>
                            <div className="flex items-center gap-3 mb-8 border-b border-gray-50 dark:border-white/5 pb-5">
                                <div className="w-12 h-12 bg-blue-500/10 flex items-center justify-center text-xl shadow-inner rounded-sm"><Info className="w-6 h-6 text-blue-600" /></div>
                                <div><h6 className="text-lg font-black text-gray-800 dark:text-white">تحديث البيانات</h6><p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">تعديل المعلومات الأساسية للحساب</p></div>
                            </div>
                            
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">الاسم بالكامل <span className="text-red-500">*</span></label>
                                    <input type="text" className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500 transition-all" value={data.name} onChange={e => setData('name', e.target.value)} />
                                    {errors.name && <p className="text-red-500 text-[10px] font-bold">{errors.name}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">البريد الإلكتروني <span className="text-red-500">*</span></label>
                                    <input type="email" className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500 transition-all" value={data.email} onChange={e => setData('email', e.target.value)} dir="ltr" />
                                    {errors.email && <p className="text-red-500 text-[10px] font-bold">{errors.email}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">اسم المستخدم <span className="text-red-500">*</span></label>
                                    <div className="flex group">
                                        <span className="flex items-center justify-center px-4 bg-gray-100 dark:bg-white/10 border border-l-0 dark:border-white/10 text-gray-500 dark:text-gray-400 font-black text-xs">@</span>
                                        <input type="text" className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-l-sm text-sm font-bold outline-none focus:border-blue-500 transition-all" value={data.username} onChange={e => setData('username', e.target.value)} dir="ltr" />
                                    </div>
                                    {errors.username && <p className="text-red-500 text-[10px] font-bold">{errors.username}</p>}
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">رقم الهاتف</label>
                                    <div className="flex">
                                        <input type="text" className="w-20 px-3 py-3 bg-gray-100 dark:bg-white/5 border border-l-0 border-gray-300 dark:border-white/20 text-gray-600 dark:text-gray-300 font-bold text-sm outline-none" value={data.phone_code} onChange={e => setData('phone_code', e.target.value)} dir="ltr" />
                                        <input type="text" className="w-full pr-10 pl-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-l-sm text-sm font-bold outline-none focus:border-blue-500 transition-all" value={data.phone} onChange={e => setData('phone', e.target.value)} dir="ltr" />
                                    </div>
                                    {errors.phone && <p className="text-red-500 text-[10px] font-bold">{errors.phone}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Password Panel */}
                        {!isSuperAdmin && (
                            <div className="bg-white dark:bg-[#1e293b] p-8 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative">
                                <div className="absolute top-0 right-0 w-1 h-full bg-red-600"></div>
                                <div className="flex items-center gap-3 mb-8 border-b border-gray-50 dark:border-white/5 pb-5">
                                    <div className="w-12 h-12 bg-red-500/10 flex items-center justify-center text-xl shadow-inner rounded-sm"><Lock className="w-6 h-6 text-red-600" /></div>
                                    <div><h6 className="text-lg font-black text-gray-800 dark:text-white">تغيير كلمة المرور</h6><p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">اترك الحقول فارغة إذا كنت لا تريد التغيير</p></div>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div className="space-y-1.5">
                                        <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">كلمة مرور جديدة</label>
                                        <input type="password" placeholder="كلمة مرور جديدة" className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500" value={data.password} onChange={e => setData('password', e.target.value)} />
                                        {errors.password && <p className="text-red-500 text-[10px] font-bold">{errors.password}</p>}
                                    </div>
                                    <div className="space-y-1.5">
                                        <label className="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tight">تأكيد كلمة المرور</label>
                                        <input type="password" placeholder="تأكيد كلمة المرور" className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-blue-500" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Bio & Social Links Panel */}
                        <div className="bg-white dark:bg-[#1e293b] p-8 shadow-sm border-2 border-gray-200 dark:border-white/10 rounded-sm relative h-fit">
                            <div className="absolute top-0 right-0 w-1 h-full bg-emerald-500"></div>
                            <h6 className="text-lg font-black text-gray-800 dark:text-white mb-4">السيرة الذاتية و الروابط</h6>
                            <textarea className="w-full px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-sm font-bold outline-none focus:border-emerald-500 min-h-[100px] mb-6" value={data.bio} onChange={e => setData('bio', e.target.value)} />
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="text" className="px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-xs font-bold outline-none" value={data.facebook_url} onChange={e => setData('facebook_url', e.target.value)} placeholder="Facebook" dir="ltr" />
                                <input type="text" className="px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-xs font-bold outline-none" value={data.instagram_url} onChange={e => setData('instagram_url', e.target.value)} placeholder="Instagram" dir="ltr" />
                                <input type="text" className="px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-xs font-bold outline-none" value={data.youtube_url} onChange={e => setData('youtube_url', e.target.value)} placeholder="YouTube" dir="ltr" />
                                <input type="text" className="px-4 py-3 bg-gray-50 dark:bg-[#0f172a] border-2 border-gray-300 dark:border-white/20 dark:text-white rounded-sm text-xs font-bold outline-none" value={data.website_url} onChange={e => setData('website_url', e.target.value)} placeholder="Website" dir="ltr" />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="fixed bottom-6 left-6 right-6 lg:left-12 lg:right-72 z-50">
                    <div className="bg-white/80 dark:bg-[#1e293b]/80 backdrop-blur-xl p-4 shadow-2xl border-2 border-gray-200 dark:border-white/10 flex justify-between items-center rounded-sm">
                        <Link href="/admin/users" className="px-10 py-3 border-2 border-gray-300 dark:border-white/20 text-gray-500 dark:text-gray-400 font-black text-xs uppercase hover:bg-red-600 hover:text-white transition-all flex items-center gap-2"><ArrowRight className="w-4 h-4" /> إلغاء</Link>
                        <button type="submit" className="px-12 py-3 bg-blue-600 text-white font-black text-xs uppercase shadow-xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50" disabled={processing}>{processing ? 'جاري الحفظ...' : 'حفظ التغييرات'} <Save className="w-4 h-4" /></button>
                    </div>
                </div>
            </form>
        </div>
    );
}
