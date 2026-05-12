import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/components/Admin/auth/AuthLayout';
import { AtSign, Lock, LogIn } from 'lucide-react';

export default function LoginPage() {
    const { t } = useTranslation();

    // No `remember` field: remember-me is disabled for admin sessions by
    // design. The backend ignores any client-supplied remember value.
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/auth/login');
    };

    const inputClass = "w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all placeholder-gray-400 dark:placeholder-gray-500";

    return (
        <AuthLayout title="Login">
            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">{t('Email Address')}</label>
                    <div className="relative">
                        <AtSign className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            type="email"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                            className={`${inputClass} ltr:pl-10 rtl:pr-10 ${errors.email ? 'border-red-500' : ''}`}
                            placeholder="admin@example.com"
                        />
                    </div>
                    {errors.email && <p className="text-xs text-red-500 font-bold mt-1">{errors.email}</p>}
                </div>

                <div className="space-y-1.5">
                    <div className="flex justify-between items-center">
                        <label className="text-sm font-bold dark:text-gray-200">{t('Password')}</label>
                        <Link href="/admin/auth/forgot-password" className="text-xs text-primary font-bold hover:underline">
                            {t('Forgot Password?')}
                        </Link>
                    </div>
                    <div className="relative">
                        <Lock className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            type="password"
                            value={data.password}
                            onChange={e => setData('password', e.target.value)}
                            className={`${inputClass} ltr:pl-10 rtl:pr-10 ${errors.password ? 'border-red-500' : ''}`}
                            placeholder="••••••••"
                        />
                    </div>
                    {errors.password && <p className="text-xs text-red-500 font-bold mt-1">{errors.password}</p>}
                </div>

                <button
                    disabled={processing}
                    className="w-full flex items-center justify-center gap-2 px-10 py-4 bg-primary text-white font-bold hover:opacity-90 transition-all rounded-none shadow-lg disabled:opacity-50"
                >
                    {processing ? t('Signing in...') : <><LogIn className="w-5 h-5" /> {t('Sign In')}</>}
                </button>
            </form>
        </AuthLayout>
    );
}
