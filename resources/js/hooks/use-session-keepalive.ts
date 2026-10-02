import { useEffect } from 'react';

interface UseSessionKeepAliveOptions {
    enabled: boolean;
    intervalSeconds: number;
}

const ACTIVITY_EVENTS: Array<keyof WindowEventMap> = [
    'pointerdown',
    'keydown',
    'touchstart',
];

export function useSessionKeepAlive({
    enabled,
    intervalSeconds,
}: UseSessionKeepAliveOptions): void {
    useEffect(() => {
        if (!enabled || typeof window === 'undefined') {
            return;
        }

        const intervalMs = Math.max(intervalSeconds, 60) * 1000;
        let lastSentAt = 0;
        let inFlight = false;

        const getCsrfToken = () =>
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        const sendHeartbeat = () => {
            if (document.visibilityState !== 'visible' || inFlight) {
                return;
            }

            const now = Date.now();
            if (now - lastSentAt < intervalMs) {
                return;
            }

            inFlight = true;

            void fetch('/session/heartbeat', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                keepalive: true,
            })
                .then((response) => {
                    if (response.ok) {
                        lastSentAt = now;
                    }
                })
                .catch(() => {
                    // A transient heartbeat failure should not disturb the UI.
                })
                .finally(() => {
                    inFlight = false;
                });
        };

        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                sendHeartbeat();
            }
        };

        ACTIVITY_EVENTS.forEach((eventName) => {
            window.addEventListener(eventName, sendHeartbeat, {
                passive: true,
            });
        });
        document.addEventListener('visibilitychange', onVisible);

        return () => {
            ACTIVITY_EVENTS.forEach((eventName) => {
                window.removeEventListener(eventName, sendHeartbeat);
            });
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, [enabled, intervalSeconds]);
}
