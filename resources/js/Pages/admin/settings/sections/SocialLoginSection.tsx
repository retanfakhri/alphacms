import React from 'react';
import SecretInput from '@/components/Admin/Form/SecretInput';
import ToggleSwitch from '@/components/Admin/Settings/ToggleSwitch';
import { Users } from 'lucide-react';

export interface SocialLoginData {
    google_enabled: boolean;
    google_client_id: string;
    google_client_secret: string;
    has_google_client_secret?: boolean;
    google_redirect_url: string;
    facebook_enabled: boolean;
    facebook_client_id: string;
    facebook_client_secret: string;
    has_facebook_client_secret?: boolean;
    facebook_redirect_url: string;
}

interface SocialLoginSectionProps {
    data: SocialLoginData;
    setData: <K extends keyof SocialLoginData>(key: K, value: SocialLoginData[K]) => void;
}

const SocialLoginSection = ({ data, setData }: SocialLoginSectionProps) => {
    const inputClass = "w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all";

    return (
        <div className="space-y-6">
            {/* Google */}
            <div className="panel">
                <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                        <Users className="w-5 h-5 text-primary" />
                        Google Login
                    </h3>
                    <ToggleSwitch 
                        checked={data.google_enabled} 
                        onChange={(val) => setData('google_enabled', val)} 
                    />
                </div>
                
                <div className="grid grid-cols-1 gap-6">
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">Client ID</label>
                        <input 
                            type="text" 
                            value={data.google_client_id} 
                            onChange={e => setData('google_client_id', e.target.value)} 
                            className={inputClass} 
                        />
                    </div>
                    <SecretInput 
                        label="Client Secret" 
                        value={data.google_client_secret} 
                        onChange={val => setData('google_client_secret', val)} 
                        hasSecret={data.has_google_client_secret} 
                    />
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">Redirect URL</label>
                        <input 
                            type="text" 
                            value={data.google_redirect_url} 
                            className={`${inputClass} bg-gray-100 dark:bg-gray-900/50 text-gray-500 cursor-not-allowed`} 
                            readOnly 
                        />
                    </div>
                </div>
            </div>

            {/* Facebook */}
            <div className="panel">
                <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                        <Users className="w-5 h-5 text-primary" />
                        Facebook Login
                    </h3>
                    <ToggleSwitch 
                        checked={data.facebook_enabled} 
                        onChange={(val) => setData('facebook_enabled', val)} 
                    />
                </div>
                
                <div className="grid grid-cols-1 gap-6">
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">App ID</label>
                        <input 
                            type="text" 
                            value={data.facebook_client_id} 
                            onChange={e => setData('facebook_client_id', e.target.value)} 
                            className={inputClass} 
                        />
                    </div>
                    <SecretInput 
                        label="App Secret" 
                        value={data.facebook_client_secret} 
                        onChange={val => setData('facebook_client_secret', val)} 
                        hasSecret={data.has_facebook_client_secret} 
                    />
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">Redirect URL</label>
                        <input 
                            type="text" 
                            value={data.facebook_redirect_url} 
                            className={`${inputClass} bg-gray-100 dark:bg-gray-900/50 text-gray-500 cursor-not-allowed`} 
                            readOnly 
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default React.memo(SocialLoginSection);
