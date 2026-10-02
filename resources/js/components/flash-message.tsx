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

type FlashType = 'success' | 'error' | 'warning' | 'info';

export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState<{
        type: FlashType;
        text: string;
        title: string;
    } | null>(null);
    const previousFlashRef = useRef<string>('');
    const previousUrlRef = useRef<string>('');

    useEffect(() => {
        const currentUrl = window.location.href;
        if (currentUrl !== previousUrlRef.current) {
            previousFlashRef.current = '';
            previousUrlRef.current = currentUrl;
        }

        const currentFlashKey = JSON.stringify(flash);
        if (
            currentFlashKey === previousFlashRef.current ||
            currentFlashKey === '{}'
        ) {
            return;
        }

        const nextMessage =
            flash.success
                ? { type: 'success' as const, text: flash.success, title: 'Berhasil' }
                : flash.error
                  ? { type: 'error' as const, text: flash.error, title: 'Perhatian' }
                  : flash.warning
                    ? { type: 'warning' as const, text: flash.warning, title: 'Peringatan' }
                    : flash.info
                      ? { type: 'info' as const, text: flash.info, title: 'Informasi' }
                      : null;

        if (nextMessage) {
            setMessage(nextMessage);
            setVisible(true);
            previousFlashRef.current = currentFlashKey;
        }
    }, [flash]);

    useEffect(() => {
        if (!visible) return;
        const timer = setTimeout(() => setVisible(false), 6000);
        return () => clearTimeout(timer);
    }, [visible]);

    if (!visible || !message) return null;

    const variants: Record<
        FlashType,
        { icon: typeof Info; surface: string; iconClass: string }
    > = {
        success: {
            icon: CheckCircle2,
            surface: 'border-[var(--pastel-green)]/70 bg-card',
            iconClass: 'bg-[var(--pastel-green)]/35 text-foreground',
        },
        error: {
            icon: AlertCircle,
            surface: 'border-destructive/40 bg-card',
            iconClass: 'bg-destructive/10 text-destructive',
        },
        warning: {
            icon: AlertTriangle,
            surface: 'border-[var(--pastel-orange)]/70 bg-card',
            iconClass: 'bg-[var(--pastel-orange)]/35 text-foreground',
        },
        info: {
            icon: Info,
            surface: 'border-[var(--pastel-blue)]/70 bg-card',
            iconClass: 'bg-[var(--pastel-blue)]/35 text-foreground',
        },
    };

    const variant = variants[message.type];
    const Icon = variant.icon;

    return (
        <div className="fixed right-3 top-3 z-[9999] w-[calc(100%-1.5rem)] animate-in slide-in-from-top-2 duration-200 sm:right-4 sm:top-4 sm:w-full sm:max-w-sm">
            <div
                className={`relative rounded-xl border p-3 shadow-lg sm:p-4 ${variant.surface}`}
                role="status"
                aria-live="polite"
            >
                <div className="flex items-start gap-3">
                    <div
                        className={`flex size-9 shrink-0 items-center justify-center rounded-lg ${variant.iconClass}`}
                    >
                        <Icon className="size-5" strokeWidth={2.2} />
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="text-sm font-semibold text-foreground">
                            {message.title}
                        </div>
                        <div className="mt-0.5 text-sm leading-5 text-muted-foreground">
                            {message.text}
                        </div>
                    </div>

                    <button
                        onClick={() => setVisible(false)}
                        className="flex size-8 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Tutup"
                        type="button"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            </div>
        </div>
    );
}
