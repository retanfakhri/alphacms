import React from 'react';
import SecretInput from '@/components/Admin/Form/SecretInput';
import ToggleSwitch from '@/components/Admin/Settings/ToggleSwitch';
import { Shield } from 'lucide-react';

export interface RecaptchaData {
    captcha_enabled: boolean;
    captcha_site_key: string;
    captcha_secret_key: string;
    has_captcha_secret_key?: boolean;
    captcha_score: number;
}

interface RecaptchaSectionProps {
    data: RecaptchaData;
    setData: <K extends keyof RecaptchaData>(key: K, value: RecaptchaData[K]) => void;
}

const RecaptchaSection = ({ data, setData }: RecaptchaSectionProps) => {
    const inputClass = "w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all";

    return (
        <div className="panel">
            <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                    <Shield className="w-5 h-5 text-primary" />
                    Google reCAPTCHA v3
                </h3>
                <ToggleSwitch 
                    checked={data.captcha_enabled} 
                    onChange={(val) => setData('captcha_enabled', val)} 
                />
            </div>
            
            <div className="grid grid-cols-1 gap-6">
                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">Site Key</label>
                    <input 
                        type="text" 
                        value={data.captcha_site_key} 
                        onChange={e => setData('captcha_site_key', e.target.value)} 
                        className={inputClass} 
                    />
                </div>
                <SecretInput 
                    label="Secret Key" 
                    value={data.captcha_secret_key} 
                    onChange={val => setData('captcha_secret_key', val)} 
                    hasSecret={data.has_captcha_secret_key} 
                />
                <div className="space-y-1.5">
                    <label className="text-sm font-bold dark:text-gray-200">Minimum Score (0.1 - 1.0)</label>
                    <input 
                        type="number" 
                        step="0.1" 
                        value={data.captcha_score} 
                        onChange={e => {
                            const val = parseFloat(e.target.value);
                            setData('captcha_score', isNaN(val) ? 0.5 : Math.min(1, Math.max(0.1, val)));
                        }} 
                        className={inputClass} 
                    />
                </div>
            </div>
        </div>
    );
};

export default React.memo(RecaptchaSection);
