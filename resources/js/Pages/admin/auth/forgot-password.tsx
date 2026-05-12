import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/components/Admin/auth/AuthLayout';
import { AtSign, Send, ArrowLeft } from 'lucide-react';

interface ForgotPasswordProps {
    status?: string | null;
}

export default function ForgotPasswordPage({ status }: ForgotPasswordProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/auth/forgot-password');
    };

    const inputClass =
        'w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all placeholder-gray-400 dark:placeholder-gray-500';

    return (
        <AuthLayout title={t('Forgot Password')}>
            <Head title={t('Forgot Password')} />

            {/* Always-same status message — admin reset flow uses an isolated
                broker that returns INVALID_USER for non-admin emails. The same
                "if your account is admin-eligible, we sent a link" copy is
                shown for every outcome to prevent user enumeration. */}
            {status && (
                <div
                    role="status"
                    className="mb-6 px-4 py-3 border-[1.5px] border-green-200 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm font-bold"
                >
                    {t(
                        'If your account is eligible for admin access, a password reset link has been sent to your email.'
                    )}
                </div>
            )}

            <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {t(
                    'Enter the email associated with your admin account. We will send you a reset link if the account exists.'
                )}
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
                            autoFocus
                            required
                        />
                    </div>
                    {errors.email && (
                        <p className="text-xs text-red-500 font-bold mt-1">{errors.email}</p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full flex items-center justify-center gap-2 px-10 py-4 bg-primary text-white font-bold hover:opacity-90 transition-all rounded-none shadow-lg disabled:opacity-50"
                >
                    {processing ? (
                        t('Sending...')
                    ) : (
                        <>
                            <Send className="w-5 h-5" /> {t('Send Reset Link')}
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
