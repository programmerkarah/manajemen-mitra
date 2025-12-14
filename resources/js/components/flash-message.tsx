import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, CheckCircle2, Info, AlertTriangle, X } from 'lucide-react';
import { type SharedData } from '@/types';

export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState<{
        type: 'success' | 'error' | 'warning' | 'info';
        text: string;
    } | null>(null);

    useEffect(() => {
        if (flash.success) {
            setMessage({ type: 'success', text: flash.success });
            setVisible(true);
        } else if (flash.error) {
            setMessage({ type: 'error', text: flash.error });
            setVisible(true);
        } else if (flash.warning) {
            setMessage({ type: 'warning', text: flash.warning });
            setVisible(true);
        } else if (flash.info) {
            setMessage({ type: 'info', text: flash.info });
            setVisible(true);
        }
    }, [flash]);

    useEffect(() => {
        if (visible) {
            const timer = setTimeout(() => {
                setVisible(false);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [visible]);

    if (!visible || !message) {
        return null;
    }

    const variants = {
        success: {
            className: 'border-green-500 bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-50',
            icon: CheckCircle2,
        },
        error: {
            className: 'border-red-500 bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-50',
            icon: AlertCircle,
        },
        warning: {
            className: 'border-yellow-500 bg-yellow-50 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-50',
            icon: AlertTriangle,
        },
        info: {
            className: 'border-blue-500 bg-blue-50 text-blue-900 dark:bg-blue-950 dark:text-blue-50',
            icon: Info,
        },
    };

    const { className, icon: Icon } = variants[message.type];

    return (
        <div className="fixed top-4 right-4 z-50 w-full max-w-md animate-in slide-in-from-top-2">
            <Alert className={className}>
                <Icon className="h-4 w-4" />
                <AlertDescription className="flex items-start justify-between">
                    <span>{message.text}</span>
                    <button
                        onClick={() => setVisible(false)}
                        className="ml-4 opacity-70 hover:opacity-100 transition-opacity"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </AlertDescription>
            </Alert>
        </div>
    );
}
