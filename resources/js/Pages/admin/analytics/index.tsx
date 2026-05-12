import React from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AdminLayout from '@/components/Admin/layout/AdminLayout';
import { BarChart3, TrendingUp, Eye, Users } from 'lucide-react';

export default function AnalyticsIndexPage() {
    const { t } = useTranslation();

    return (
        <AdminLayout>
            <Head title={t('Analytics')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            {t('Analytics')}
                        </h1>
                        <p className="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            {t('Traffic, engagement, and content performance for the site.')}
                        </p>
                    </div>
                </div>

                {/* Placeholder KPI tiles — match the visual language of other
                    admin dashboards so the route is not obviously broken
                    while the analytics pipeline is pending. */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <KpiCard icon={Eye} label={t('Page Views')} value="—" hint={t('last 30 days')} />
                    <KpiCard icon={Users} label={t('Unique Visitors')} value="—" hint={t('last 30 days')} />
                    <KpiCard icon={TrendingUp} label={t('Engagement Rate')} value="—" hint={t('average')} />
                    <KpiCard icon={BarChart3} label={t('Top Article')} value="—" hint={t('by views')} />
                </div>

                {/* Empty state */}
                <div className="border-[1.5px] border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-12">
                    <div className="flex flex-col items-center justify-center text-center max-w-md mx-auto">
                        <div className="w-16 h-16 flex items-center justify-center bg-primary/10 text-primary mb-4">
                            <BarChart3 className="w-8 h-8" />
                        </div>
                        <h2 className="text-lg font-black text-gray-900 dark:text-white mb-2">
                            {t('Analytics dashboard coming soon')}
                        </h2>
                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                            {t(
                                'Once the analytics pipeline is connected, this page will show traffic, content performance, and audience insights.'
                            )}
                        </p>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

interface KpiCardProps {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    value: string;
    hint?: string;
}

function KpiCard({ icon: Icon, label, value, hint }: KpiCardProps) {
    return (
        <div className="bg-white dark:bg-gray-900 border-[1.5px] border-gray-200 dark:border-gray-700 p-5">
            <div className="flex items-start justify-between mb-3">
                <p className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {label}
                </p>
                <div className="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary">
                    <Icon className="w-4.5 h-4.5" />
                </div>
            </div>
            <p className="text-2xl font-black text-gray-900 dark:text-white">{value}</p>
            {hint && (
                <p className="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">{hint}</p>
            )}
        </div>
    );
}
