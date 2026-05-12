import React, { useId } from 'react';
import { useTranslation } from 'react-i18next';

interface ToggleSwitchProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label?: string;
    className?: string;
    description?: string;
}

const ToggleSwitch = ({ checked, onChange, label, className = '', description }: ToggleSwitchProps) => {
    const { t } = useTranslation();
    const labelId = useId();

    return (
        <div className={`flex items-start gap-3 ${className}`}>
            <div className="relative inline-flex items-center">
                <button
                    type="button"
                    role="switch"
                    aria-checked={checked}
                    aria-labelledby={label ? labelId : undefined}
                    onClick={() => onChange(!checked)}
                    className={`relative inline-flex h-6 w-12 shrink-0 cursor-pointer rounded-none transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900 ${
                        checked ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-700'
                    }`}
                >
                    <span
                        aria-hidden="true"
                        className={`pointer-events-none absolute top-1 h-4 w-4 rounded-none bg-white shadow-sm transition-all duration-200 ease-in-out ${
                            checked ? 'left-7' : 'left-1'
                        }`}
                    />
                </button>
            </div>
            {(label || description) && (
                <div className="flex flex-col gap-0.5">
                    {label && (
                        <span 
                            id={labelId}
                            className="text-sm font-bold cursor-pointer select-none dark:text-white"
                            onClick={() => onChange(!checked)}
                        >
                            {t(label)}
                        </span>
                    )}
                    {description && (
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {t(description)}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
};

export default React.memo(ToggleSwitch);
