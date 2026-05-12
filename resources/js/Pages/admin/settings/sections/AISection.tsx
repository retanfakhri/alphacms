import React from 'react';
import SecretInput from '@/components/Admin/Form/SecretInput';
import ToggleSwitch from '@/components/Admin/Settings/ToggleSwitch';
import { Wand2 } from 'lucide-react';

export interface AIData {
    ai_enabled: boolean;
    ai_provider: 'openai' | 'gemini';
    openai_api_key: string;
    has_openai_api_key?: boolean;
    openai_model: string;
    gemini_api_key: string;
    has_gemini_api_key?: boolean;
    gemini_model: string;
}

interface AISectionProps {
    data: AIData;
    setData: <K extends keyof AIData>(key: K, value: AIData[K]) => void;
}

const AISection = ({ data, setData }: AISectionProps) => {
    const inputClass = "w-full px-4 py-3 border-[1.5px] border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-none text-gray-900 dark:text-white outline-none focus:border-primary transition-all";

    return (
        <div className="space-y-6">
            <div className="panel">
                <div className="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 className="text-lg font-bold flex items-center gap-2 dark:text-white">
                        <Wand2 className="w-5 h-5 text-primary" />
                        AI Settings
                    </h3>
                    <ToggleSwitch 
                        checked={data.ai_enabled} 
                        onChange={(val) => setData('ai_enabled', val)} 
                    />
                </div>

                <div className="space-y-6">
                    <div className="space-y-1.5">
                        <label className="text-sm font-bold dark:text-gray-200">AI Provider</label>
                        <div className="grid grid-cols-2 gap-4">
                            {(['openai', 'gemini'] as const).map((provider) => (
                                <button
                                    key={provider}
                                    type="button"
                                    onClick={() => setData('ai_provider', provider)}
                                    className={`px-4 py-3 text-sm font-bold border-2 transition-all rounded-none ${
                                        data.ai_provider === provider
                                            ? 'bg-primary border-primary text-white shadow-lg shadow-primary/20'
                                            : 'bg-transparent border-gray-200 dark:border-gray-700 text-gray-500 hover:border-primary/50 dark:text-gray-400'
                                    }`}
                                >
                                    {provider.toUpperCase()}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="pt-4 border-t border-gray-100 dark:border-gray-800">
                        {data.ai_provider === 'openai' ? (
                            <div className="space-y-6 animate-in fade-in slide-in-from-top-2 duration-300">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400">OpenAI Configuration</h4>
                                <SecretInput 
                                    label="OpenAI API Key" 
                                    value={data.openai_api_key} 
                                    onChange={val => setData('openai_api_key', val)} 
                                    hasSecret={data.has_openai_api_key} 
                                />
                                <div className="space-y-1.5">
                                    <label className="text-sm font-bold dark:text-gray-200">OpenAI Model</label>
                                    <input 
                                        type="text" 
                                        value={data.openai_model} 
                                        onChange={e => setData('openai_model', e.target.value)} 
                                        className={inputClass} 
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-6 animate-in fade-in slide-in-from-top-2 duration-300">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400">Google Gemini Configuration</h4>
                                <SecretInput 
                                    label="Gemini API Key" 
                                    value={data.gemini_api_key} 
                                    onChange={val => setData('gemini_api_key', val)} 
                                    hasSecret={data.has_gemini_api_key} 
                                />
                                <div className="space-y-1.5">
                                    <label className="text-sm font-bold dark:text-gray-200">Gemini Model</label>
                                    <input 
                                        type="text" 
                                        value={data.gemini_model} 
                                        onChange={e => setData('gemini_model', e.target.value)} 
                                        className={inputClass} 
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default React.memo(AISection);
