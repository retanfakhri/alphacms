import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/components/Admin/auth/AuthLayout';
import { ShieldCheck, KeyRound, ArrowLeft } from 'lucide-react';

/**
 * Admin two-factor authentication challenge page.
 *
 * Sits at /admin/auth/two-factor-challenge — part of the self-contained
 * admin authentication boundary. POSTs to the admin TwoFactorChallengeController,
 * which validates either a TOTP `code` or a single-use `recovery_code`.
 *
 * The user has NOT yet been authenticated at this point; the admin session
 * holds only `login.id`. On success the controller redirects to the
 * `intended` URL (defaults to admin.dashboard).
 */
export default function TwoFactorChallengePage() {
    const { t } = useTranslation();
    const [useRecovery, setUseRecovery] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/auth/two-factor-challenge');
    };

    const toggleRecovery = () => {
        // Only one of the two fields should be submitted at a time.
        reset('code', 'recovery_code');
        setUseRecovery((v) => !v);
    };

    const inputClass =
        'w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all placeholder-gray-400 dark:placeholder-gray-500';

    return (
        <AuthLayout title={t('Two-Factor Authentication')}>
            <Head title={t('Two-Factor Authentication')} />

            <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {useRecovery
                    ? t('Enter one of your recovery codes to continue.')
                    : t('Enter the 6-digit code from your authenticator app to continue.')}
            </p>

            <form onSubmit={submit} className="space-y-6">
                {!useRecovery ? (
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">
                            {t('Authentication Code')}
                        </label>
                        <input
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9]*"
                            autoComplete="one-time-code"
                            autoFocus
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            className={`${inputClass} ${errors.code ? 'border-red-500' : ''}`}
                            placeholder="123456"
                            maxLength={6}
                        />
                        {errors.code && (
                            <p className="text-xs text-red-500 font-bold mt-1">{errors.code}</p>
                        )}
                    </div>
                ) : (
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">
                            {t('Recovery Code')}
                        </label>
                        <input
                            type="text"
                            autoComplete="one-time-code"
                            autoFocus
                            value={data.recovery_code}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                            className={`${inputClass} ${errors.recovery_code ? 'border-red-500' : ''}`}
                            placeholder="xxxxxxxxxx-xxxxxxxxxx"
                        />
                        {errors.recovery_code && (
                            <p className="text-xs text-red-500 font-bold mt-1">
                                {errors.recovery_code}
                            </p>
                        )}
                    </div>
                )}

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full flex items-center justify-center gap-2 px-10 py-4 bg-primary text-white font-bold hover:opacity-90 transition-all rounded-none shadow-lg disabled:opacity-50"
                >
                    {processing ? (
                        t('Verifying...')
                    ) : (
                        <>
                            {useRecovery ? <KeyRound className="w-5 h-5" /> : <ShieldCheck className="w-5 h-5" />}
                            {t('Verify')}
                        </>
                    )}
                </button>

                <div className="flex flex-col items-center gap-2 pt-2">
                    <button
                        type="button"
                        onClick={toggleRecovery}
                        className="text-xs text-primary font-bold hover:underline"
                    >
                        {useRecovery
                            ? t('Use authenticator code instead')
                            : t('Use a recovery code instead')}
                    </button>

                    <Link
                        href="/admin/auth/login"
                        className="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-primary font-bold"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        {t('Back to login')}
                    </Link>
                </div>
            </form>
        </AuthLayout>
    );
}
