import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AlertCircle, CheckCircle2, Info, AlertTriangle, X } from 'lucide-react';
import { type SharedData } from '@/types';

export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState<{
        type: 'success' | 'error' | 'warning' | 'info';
        text: string;
        title: string;
    } | null>(null);

    useEffect(() => {
        if (flash.success) {
            setMessage({ 
                type: 'success', 
                text: flash.success,
                title: 'Berhasil!'
            });
            setVisible(true);
        } else if (flash.error) {
            setMessage({ 
                type: 'error', 
                text: flash.error,
                title: 'Perhatian!'
            });
            setVisible(true);
        } else if (flash.warning) {
            setMessage({ 
                type: 'warning', 
                text: flash.warning,
                title: 'Peringatan!'
            });
            setVisible(true);
        } else if (flash.info) {
            setMessage({ 
                type: 'info', 
                text: flash.info,
                title: 'Informasi'
            });
            setVisible(true);
        }
    }, [flash]);

    useEffect(() => {
        if (visible) {
            // Durasi 6 detik - cukup untuk dibaca tapi tidak terlalu lama
            const timer = setTimeout(() => {
                setVisible(false);
            }, 6000);

            return () => clearTimeout(timer);
        }
    }, [visible]);

    if (!visible || !message) {
        return null;
    }

    const variants = {
        success: {
            className: 'border-2 border-green-500 bg-green-50 text-green-900 shadow-lg dark:bg-green-950 dark:text-green-50 dark:border-green-600',
            icon: CheckCircle2,
            iconColor: 'text-green-600 dark:text-green-400',
        },
        error: {
            className: 'border-2 border-red-500 bg-red-50 text-red-900 shadow-lg dark:bg-red-950 dark:text-red-50 dark:border-red-600',
            icon: AlertCircle,
            iconColor: 'text-red-600 dark:text-red-400',
        },
        warning: {
            className: 'border-2 border-amber-500 bg-amber-50 text-amber-900 shadow-lg dark:bg-amber-950 dark:text-amber-50 dark:border-amber-600',
            icon: AlertTriangle,
            iconColor: 'text-amber-600 dark:text-amber-400',
        },
        info: {
            className: 'border-2 border-blue-500 bg-blue-50 text-blue-900 shadow-lg dark:bg-blue-950 dark:text-blue-50 dark:border-blue-600',
            icon: Info,
            iconColor: 'text-blue-600 dark:text-blue-400',
        },  
    };

    const { className, icon: Icon, iconColor } = variants[message.type];

    return (
        <div className="fixed top-4 right-4 left-4 sm:left-auto z-[9999] w-full sm:max-w-md animate-in slide-in-from-top-4 duration-300">
            <div className={`relative rounded-lg shadow-lg p-4 ${className}`}>
                <div className="flex items-start gap-3">
                    {/* Icon */}
                    <div className={`shrink-0 ${iconColor}`}>
                        <Icon className="h-6 w-6" strokeWidth={2.5} />
                    </div>
                    
                    {/* Content */}
                    <div className="flex-1 min-w-0 pr-2">
                        <div className="text-base font-bold mb-1">
                            {message.title}
                        </div>
                        <div className="text-sm">
                            {message.text}
                        </div>
                    </div>

                    {/* Tombol tutup */}
                    <button
                        onClick={() => setVisible(false)}
                        className="shrink-0 rounded-md p-1 hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
                        aria-label="Tutup"
                        type="button"
                    >
                        <X className="h-5 w-5" strokeWidth={2.5} />
                    </button>
                </div>
            </div>
        </div>
    );
}
