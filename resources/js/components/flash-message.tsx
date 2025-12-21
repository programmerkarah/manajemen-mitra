import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
            <Alert className={`${className} p-3 sm:p-4`}>
                <div className="flex items-start gap-2.5 sm:gap-3">
                    {/* Icon lebih compact */}
                    <div className={`shrink-0 ${iconColor}`}>
                        <Icon className="h-5 w-5 sm:h-6 sm:w-6" strokeWidth={2.5} />
                    </div>
                    
                    {/* Content dengan word wrap yang baik */}
                    <div className="flex-1 min-w-0">
                        <AlertTitle className="text-sm sm:text-base font-bold mb-0.5 break-words leading-tight">
                            {message.title}
                        </AlertTitle>
                        <AlertDescription className="text-xs sm:text-sm leading-snug break-words whitespace-normal">
                            {message.text}
                        </AlertDescription>
                    </div>

                    {/* Tombol tutup compact */}
                    <button
                        onClick={() => setVisible(false)}
                        className="shrink-0 rounded-md p-1 hover:bg-black/5 dark:hover:bg-white/10 transition-colors touch-manipulation"
                        aria-label="Tutup"
                        type="button"
                    >
                        <X className="h-4 w-4 sm:h-5 sm:w-5" strokeWidth={2.5} />
                    </button>
                </div>
            </Alert>
        </div>
    );
}
