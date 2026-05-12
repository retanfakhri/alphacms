import React, { useState, useId } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface SecretInputProps {
    label: string;
    value: string;
    onChange: (val: string) => void;
    hasSecret?: boolean;
    type?: 'password' | 'text';
    placeholder?: string;
    description?: string;
    error?: string;
}

const SecretInput = ({ 
    label, 
    value, 
    onChange, 
    hasSecret, 
    type = 'password', 
    placeholder,
    description,
    error
}: SecretInputProps) => {
    const { t } = useTranslation();
    const [show, setShow] = useState(false);
    const inputId = useId();
    const inputType = type === 'password' ? (show ? 'text' : 'password') : type;

    return (
        <div className="space-y-1.5 w-full">
            <div className="flex justify-between items-center">
                <label htmlFor={inputId} className="text-sm font-bold cursor-pointer dark:text-gray-200">
                    {t(label)}
                </label>
                {hasSecret && !value && (
                    <span className="text-[10px] bg-success-50 text-success-600 px-2 py-0.5 border border-success-200 font-bold uppercase rounded-none dark:bg-success-500/10 dark:text-success-400 dark:border-success-500/20">
                        {t('Configured')}
                    </span>
                )}
            </div>
            
            <div className="relative group">
                <input 
                    id={inputId}
                    type={inputType} 
                    value={value} 
                    onChange={e => onChange(e.target.value)} 
                    placeholder={placeholder ? t(placeholder) : (hasSecret ? '••••••••••••••••' : `${t('Enter')} ${t(label)}...`)}
                    className={`w-full px-4 py-3 ${type === 'password' ? 'ltr:pr-12 rtl:pl-12' : ''} border-[1.5px] bg-gray-50 dark:bg-gray-800 rounded-none outline-none transition-all ${
                        error 
                            ? 'border-error-500 focus:border-error-500' 
                            : 'border-gray-200 dark:border-gray-700 focus:border-primary dark:focus:border-primary'
                    } text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500`} 
                />
                
                {type === 'password' && (
                    <button
                        type="button"
                        onClick={() => setShow(!show)}
                        className="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors p-2 flex items-center justify-center"
                        aria-label={show ? t("Hide secret") : t("Show secret")}
                    >
                        {show ? <EyeOff className="w-4.5 h-4.5" /> : <Eye className="w-4.5 h-4.5" />}
                    </button>
                )}
            </div>

            {description && <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{t(description)}</p>}
            {error && <p className="text-xs text-error-500 mt-1">{t(error)}</p>}
        </div>
    );
};

export default React.memo(SecretInput);
