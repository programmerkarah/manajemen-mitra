import { useEffect } from 'react';

interface UseSsoSessionSyncOptions {
    enabled: boolean;
    userId: number | null | undefined;
    focusCooldownSeconds: number;
    intervalSeconds: number;
}

const LAST_SYNC_AT_KEY = 'sso:last-sync-at';

function getLastSyncAt(): number {
    if (typeof window === 'undefined') {
        return 0;
    }

    const raw = window.localStorage.getItem(LAST_SYNC_AT_KEY);
    const parsed = Number(raw ?? '0');

    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function setLastSyncAt(timestamp: number): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(LAST_SYNC_AT_KEY, String(timestamp));
}

function getCurrentPathWithQueryAndHash(): string {
    return (
        window.location.pathname + window.location.search + window.location.hash
    );
}

const SSO_SCROLL_STATE_KEY = 'sso:pre-sync-scroll';

interface SsoScrollState {
    url: string;
    scrollY: number;
    timestamp: number;
}

function saveScrollState(): void {
    try {
        const state: SsoScrollState = {
            url: getCurrentPathWithQueryAndHash(),
            scrollY: window.scrollY,
            timestamp: Date.now(),
        };
        sessionStorage.setItem(SSO_SCROLL_STATE_KEY, JSON.stringify(state));
    } catch {
        // ignore storage errors
    }
}

export function popSavedScrollState(): SsoScrollState | null {
    try {
        const raw = sessionStorage.getItem(SSO_SCROLL_STATE_KEY);
        if (!raw) {
            return null;
        }
        sessionStorage.removeItem(SSO_SCROLL_STATE_KEY);
        return JSON.parse(raw) as SsoScrollState;
    } catch {
        return null;
    }
}

function buildSyncUrl(reason: 'focus' | 'interval' | 'initial'): string {
    const params = new URLSearchParams({
        sync: '1',
        reason,
        return_to: getCurrentPathWithQueryAndHash(),
    });

    return `/auth/sso/redirect?${params.toString()}`;
}

export function useSsoSessionSync({
    enabled,
    userId,
    focusCooldownSeconds,
    intervalSeconds,
}: UseSsoSessionSyncOptions): void {
    useEffect(() => {
        if (!enabled || !userId) {
            return;
        }

        const focusCooldownMs = Math.max(focusCooldownSeconds, 30) * 1000;
        const intervalMs = Math.max(intervalSeconds, 60) * 1000;

        const shouldSkipSyncOnCurrentPage = () =>
            window.location.pathname.startsWith('/auth/sso');

        const triggerSync = (
            reason: 'focus' | 'interval' | 'initial',
            minIntervalMs: number,
        ) => {
            if (shouldSkipSyncOnCurrentPage()) {
                return;
            }

            const now = Date.now();
            const lastSyncAt = getLastSyncAt();

            if (now - lastSyncAt < minIntervalMs) {
                return;
            }

            setLastSyncAt(now);
            saveScrollState();
            window.location.assign(buildSyncUrl(reason));
        };

        const onWindowFocus = () => {
            triggerSync('focus', focusCooldownMs);
        };

        const onVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                triggerSync('focus', focusCooldownMs);
            }
        };

        triggerSync('initial', intervalMs);

        const intervalId = window.setInterval(() => {
            triggerSync('interval', intervalMs);
        }, intervalMs);

        window.addEventListener('focus', onWindowFocus);
        document.addEventListener('visibilitychange', onVisibilityChange);

        return () => {
            window.clearInterval(intervalId);
            window.removeEventListener('focus', onWindowFocus);
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
        };
    }, [enabled, focusCooldownSeconds, intervalSeconds, userId]);
}
