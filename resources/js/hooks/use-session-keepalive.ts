import { useEffect } from 'react';

interface UseSessionKeepAliveOptions {
    enabled: boolean;
    intervalSeconds: number;
}

const ACTIVITY_EVENTS: Array<keyof WindowEventMap> = [
    'click',
    'keydown',
    'mousemove',
    'scroll',
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

        const csrfToken =
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

            fetch('/session/heartbeat', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(() => {
                    lastSentAt = now;
                })
                .catch(() => {
                    // Ignore transient heartbeat failures.
                })
                .finally(() => {
                    inFlight = false;
                });
        };

        const onActivity = () => {
            sendHeartbeat();
        };

        ACTIVITY_EVENTS.forEach((eventName) => {
            window.addEventListener(eventName, onActivity, { passive: true });
        });

        return () => {
            ACTIVITY_EVENTS.forEach((eventName) => {
                window.removeEventListener(eventName, onActivity);
            });
        };
    }, [enabled, intervalSeconds]);
}
