import React from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface AuthLayoutProps {
    children: React.ReactNode;
    title: string;
}

const AuthLayout = ({ children, title }: AuthLayoutProps) => {
    const { t } = useTranslation();

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-[#1c2434] font-tajawal p-4">
            <Head title={t(title)} />
            
            <div className="w-full max-w-md">
                <div className="flex flex-col items-center mb-8">
                    <img src="/assets/images/logo.svg" alt="Logo" className="h-12 w-12 mb-4 dark:invert" />
                    <h1 className="text-2xl font-bold dark:text-white uppercase tracking-wider">AlphaCMS Admin</h1>
                </div>

                <div className="bg-white dark:bg-gray-800 p-8 shadow-2xl rounded-none border-t-4 border-primary">
                    {children}
                </div>

                <div className="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; {new Date().getFullYear()} AlphaCMS. All rights reserved.
                </div>
            </div>
        </div>
    );
};

export default AuthLayout;
