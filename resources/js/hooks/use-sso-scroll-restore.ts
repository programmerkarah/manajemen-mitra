import { popSavedScrollState } from '@/hooks/use-sso-session-sync';
import { useEffect } from 'react';

const SSO_SCROLL_MAX_AGE_MS = 5 * 60 * 1000; // 5 minutes

export function useSsoScrollRestore(): void {
    useEffect(() => {
        const state = popSavedScrollState();

        if (!state) {
            return;
        }

        const currentUrl =
            window.location.pathname +
            window.location.search +
            window.location.hash;

        if (state.url !== currentUrl) {
            return;
        }

        if (Date.now() - state.timestamp > SSO_SCROLL_MAX_AGE_MS) {
            return;
        }

        if (state.scrollY <= 0) {
            return;
        }

        requestAnimationFrame(() => {
            window.scrollTo({ top: state.scrollY, behavior: 'instant' });
        });
    }, []);
}
