import React from 'react';
import { type LucideIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface Tab<T extends string> {
    id: T;
    title: string;
    icon: LucideIcon;
}

interface SettingsSidebarProps<T extends string> {
    tabs: Tab<T>[];
    activeTab: T;
    onTabChange: (id: T) => void;
    className?: string;
}

function SettingsSidebar<T extends string>({ 
    tabs, 
    activeTab, 
    onTabChange,
    className = ''
}: SettingsSidebarProps<T>) {
    const { t } = useTranslation();
    return (
        <div className={`w-full md:w-72 space-y-2 ${className}`}>
            {tabs.map((tab) => (
                <button
                    key={tab.id}
                    type="button"
                    onClick={() => onTabChange(tab.id)}
                    aria-pressed={activeTab === tab.id}
                    className={`w-full flex items-center gap-3 px-4 py-3 text-sm font-medium transition-all rounded-none border-2 ${
                        activeTab === tab.id
                            ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20'
                            : 'bg-white text-gray-600 border-gray-100 hover:border-primary/50 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-800 dark:hover:border-primary/30'
                    }`}
                >
                    <tab.icon className={`w-5 h-5 ${activeTab === tab.id ? 'text-white' : 'text-gray-400 group-hover:text-primary'}`} />
                    <span>{t(tab.title)}</span>
                </button>
            ))}
        </div>
    );
}

export default React.memo(SettingsSidebar) as typeof SettingsSidebar;
