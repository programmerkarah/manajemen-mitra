import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import {
    AlertCircle,
    AlertTriangle,
    CheckCircle2,
    Info,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState<{
        type: 'success' | 'error' | 'warning' | 'info';
        text: string;
        title: string;
    } | null>(null);
    const previousFlashRef = useRef<string>('');
    const previousUrlRef = useRef<string>('');

    useEffect(() => {
        // Reset tracking when URL changes (navigation)
        const currentUrl = window.location.href;
        if (currentUrl !== previousUrlRef.current) {
            previousFlashRef.current = '';
            previousUrlRef.current = currentUrl;
        }

        // Create a unique key from the current flash message
        const currentFlashKey = JSON.stringify(flash);

        // Only show if flash message is different from the previous one
        if (
            currentFlashKey !== previousFlashRef.current &&
            currentFlashKey !== '{}'
        ) {
            if (flash.success) {
                // eslint-disable-next-line react-hooks/set-state-in-effect -- Setting state in response to flash message prop change is intentional
                setMessage({
                    type: 'success',
                    text: flash.success,
                    title: 'Berhasil!',
                });

                setVisible(true);
                previousFlashRef.current = currentFlashKey;
            } else if (flash.error) {
                setMessage({
                    type: 'error',
                    text: flash.error,
                    title: 'Perhatian!',
                });

                setVisible(true);
                previousFlashRef.current = currentFlashKey;
            } else if (flash.warning) {
                setMessage({
                    type: 'warning',
                    text: flash.warning,
                    title: 'Peringatan!',
                });

                setVisible(true);
                previousFlashRef.current = currentFlashKey;
            } else if (flash.info) {
                setMessage({
                    type: 'info',
                    text: flash.info,
                    title: 'Informasi',
                });

                setVisible(true);
                previousFlashRef.current = currentFlashKey;
            }
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
            className:
                'border border-green-400/30 bg-gradient-to-br from-green-500/10 via-green-400/5 to-green-300/10 backdrop-blur-xl text-green-900 shadow-2xl dark:text-green-50 dark:border-green-500/20 dark:from-green-600/10 dark:via-green-500/5 dark:to-green-400/10',
            icon: CheckCircle2,
            iconColor: 'text-green-600 dark:text-green-400',
        },
        error: {
            className:
                'border border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 backdrop-blur-xl text-red-900 shadow-2xl dark:text-red-50 dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10',
            icon: AlertCircle,
            iconColor: 'text-red-600 dark:text-red-400',
        },
        warning: {
            className:
                'border border-amber-400/30 bg-gradient-to-br from-amber-500/10 via-amber-400/5 to-amber-300/10 backdrop-blur-xl text-amber-900 shadow-2xl dark:text-amber-50 dark:border-amber-500/20 dark:from-amber-600/10 dark:via-amber-500/5 dark:to-amber-400/10',
            icon: AlertTriangle,
            iconColor: 'text-amber-600 dark:text-amber-400',
        },
        info: {
            className:
                'border border-blue-400/30 bg-gradient-to-br from-blue-500/10 via-blue-400/5 to-blue-300/10 backdrop-blur-xl text-blue-900 shadow-2xl dark:text-blue-50 dark:border-blue-500/20 dark:from-blue-600/10 dark:via-blue-500/5 dark:to-blue-400/10',
            icon: Info,
            iconColor: 'text-blue-600 dark:text-blue-400',
        },
    };

    const { className, icon: Icon, iconColor } = variants[message.type];

    return (
        <div className="fixed top-4 right-4 left-4 z-[9999] w-full animate-in duration-300 slide-in-from-top-4 sm:left-auto sm:max-w-md">
            <div className={`relative rounded-2xl p-4 shadow-2xl ${className}`}>
                <div className="flex items-start gap-3">
                    {/* Icon */}
                    <div className={`shrink-0 ${iconColor}`}>
                        <Icon className="h-6 w-6" strokeWidth={2.5} />
                    </div>

                    {/* Content */}
                    <div className="min-w-0 flex-1 pr-2">
                        <div className="mb-1 text-base font-bold">
                            {message.title}
                        </div>
                        <div className="text-sm">{message.text}</div>
                    </div>

                    {/* Tombol tutup */}
                    <button
                        onClick={() => setVisible(false)}
                        className="shrink-0 rounded-md p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/10"
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
