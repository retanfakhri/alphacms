import React from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AdminLayout from '@/components/Admin/layout/AdminLayout';
import { Image, Upload, FolderOpen, HardDrive } from 'lucide-react';

export default function MediaIndexPage() {
    const { t } = useTranslation();

    return (
        <AdminLayout>
            <Head title={t('Media Manager')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            {t('Media Manager')}
                        </h1>
                        <p className="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            {t('Upload, organize, and manage media assets used across the site.')}
                        </p>
                    </div>
                </div>

                {/* Placeholder summary tiles — keep visual parity with other admin
                    list pages so the route doesn't look broken while the full
                    media library implementation is pending. */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <SummaryCard icon={Image} label={t('Images')} value="—" />
                    <SummaryCard icon={FolderOpen} label={t('Collections')} value="—" />
                    <SummaryCard icon={HardDrive} label={t('Storage Used')} value="—" />
                </div>

                {/* Empty state */}
                <div className="border-[1.5px] border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-12">
                    <div className="flex flex-col items-center justify-center text-center max-w-md mx-auto">
                        <div className="w-16 h-16 flex items-center justify-center bg-primary/10 text-primary mb-4">
                            <Upload className="w-8 h-8" />
                        </div>
                        <h2 className="text-lg font-black text-gray-900 dark:text-white mb-2">
                            {t('Media library coming soon')}
                        </h2>
                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                            {t(
                                'The unified media manager will let you upload images, browse collections, and attach files to articles, news, and settings from a single place.'
                            )}
                        </p>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

interface SummaryCardProps {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    value: string;
}

function SummaryCard({ icon: Icon, label, value }: SummaryCardProps) {
    return (
        <div className="bg-white dark:bg-gray-900 border-[1.5px] border-gray-200 dark:border-gray-700 p-5">
            <div className="flex items-center gap-3">
                <div className="w-10 h-10 flex items-center justify-center bg-primary/10 text-primary">
                    <Icon className="w-5 h-5" />
                </div>
                <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {label}
                    </p>
                    <p className="mt-0.5 text-xl font-black text-gray-900 dark:text-white">{value}</p>
                </div>
            </div>
        </div>
    );
}
