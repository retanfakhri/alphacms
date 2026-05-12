import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/components/Admin/auth/AuthLayout';
import { AtSign, Lock, KeyRound, ArrowLeft } from 'lucide-react';

interface ResetPasswordProps {
    token: string;
    email?: string;
}

export default function ResetPasswordPage({ token, email }: ResetPasswordProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        token: token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/auth/reset-password');
    };

    const inputClass =
        'w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all placeholder-gray-400 dark:placeholder-gray-500';

    return (
        <AuthLayout title={t('Reset Password')}>
            <Head title={t('Reset Password')} />

            <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {t('Choose a new password for your admin account.')}
            </p>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">{t('Email Address')}</label>
                    <div className="relative">
                        <AtSign className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className={`${inputClass} ltr:pl-10 rtl:pr-10 ${errors.email ? 'border-red-500' : ''}`}
                            placeholder="admin@example.com"
                            autoComplete="username"
                            required
                            readOnly={!!email}
                        />
                    </div>
                    {errors.email && (
                        <p className="text-xs text-red-500 font-bold mt-1">{errors.email}</p>
                    )}
                </div>

                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">{t('New Password')}</label>
                    <div className="relative">
                        <Lock className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className={`${inputClass} ltr:pl-10 rtl:pr-10 ${errors.password ? 'border-red-500' : ''}`}
                            placeholder="••••••••"
                            autoComplete="new-password"
                            autoFocus
                            required
                            minLength={8}
                        />
                    </div>
                    {errors.password && (
                        <p className="text-xs text-red-500 font-bold mt-1">{errors.password}</p>
                    )}
                </div>

                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">{t('Confirm New Password')}</label>
                    <div className="relative">
                        <Lock className="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className={`${inputClass} ltr:pl-10 rtl:pr-10 ${errors.password_confirmation ? 'border-red-500' : ''}`}
                            placeholder="••••••••"
                            autoComplete="new-password"
                            required
                            minLength={8}
                        />
                    </div>
                    {errors.password_confirmation && (
                        <p className="text-xs text-red-500 font-bold mt-1">
                            {errors.password_confirmation}
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full flex items-center justify-center gap-2 px-10 py-4 bg-primary text-white font-bold hover:opacity-90 transition-all rounded-none shadow-lg disabled:opacity-50"
                >
                    {processing ? (
                        t('Resetting...')
                    ) : (
                        <>
                            <KeyRound className="w-5 h-5" /> {t('Reset Password')}
                        </>
                    )}
                </button>

                <div className="text-center pt-2">
                    <Link
                        href="/admin/auth/login"
                        className="inline-flex items-center gap-1.5 text-xs text-primary font-bold hover:underline"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        {t('Back to login')}
                    </Link>
                </div>
            </form>
        </AuthLayout>
    );
}
