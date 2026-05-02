import { useEffect, useLayoutEffect, useRef } from 'react';

const SSO_FORM_PRESERVE_MAX_AGE_MS = 5 * 60 * 1000; // 5 minutes

interface SsoFormStateEntry<T> {
    data: T;
    timestamp: number;
    url: string;
}

function getCurrentUrl(): string {
    return (
        window.location.pathname + window.location.search + window.location.hash
    );
}

function getFormStateKey(url: string): string {
    return `sso:form:${url}`;
}

/**
 * Preserve React form state across an SSO redirect.
 *
 * Usage in a form page component:
 *   useSsoFormPreserve(
 *     () => ({ enabledJenisPenugasan, formData }),
 *     ({ enabledJenisPenugasan: savedEJP, formData: savedFD }) => {
 *       setEnabledJenisPenugasan(savedEJP);
 *       setFormData(savedFD);
 *     },
 *   );
 *
 * - Before the SSO redirect, `getState()` is called and the result is
 *   serialised to sessionStorage under the current URL.
 * - On mount (after the redirect returns to the same URL), the saved state
 *   is read from sessionStorage and passed to `onRestore`.
 */
export function useSsoFormPreserve<T>(
    getState: () => T,
    onRestore: (state: T) => void,
): void {
    // Keep refs so the callbacks always capture the latest values without
    // requiring them to be listed as effect dependencies.
    const getStateRef = useRef(getState);
    const onRestoreRef = useRef(onRestore);

    // Keep refs in sync with the latest callbacks without triggering effects.
    useLayoutEffect(() => {
        getStateRef.current = getState;
        onRestoreRef.current = onRestore;
    });

    // Restore state on mount (once, immediately after React renders the page).
    useEffect(() => {
        const url = getCurrentUrl();
        const key = getFormStateKey(url);

        try {
            const raw = sessionStorage.getItem(key);
            if (!raw) {
                return;
            }

            sessionStorage.removeItem(key);
            const entry = JSON.parse(raw) as SsoFormStateEntry<T>;

            if (entry.url !== url) {
                return;
            }

            if (Date.now() - entry.timestamp > SSO_FORM_PRESERVE_MAX_AGE_MS) {
                return;
            }

            onRestoreRef.current(entry.data);
        } catch {
            // Ignore parse / storage errors.
        }
    }, []);

    // Save state whenever the SSO sync is about to navigate away.
    useEffect(() => {
        const handleBeforeRedirect = () => {
            const url = getCurrentUrl();
            const key = getFormStateKey(url);
            const entry: SsoFormStateEntry<T> = {
                data: getStateRef.current(),
                timestamp: Date.now(),
                url,
            };

            try {
                sessionStorage.setItem(key, JSON.stringify(entry));
            } catch {
                // Ignore storage errors.
            }
        };

        window.addEventListener(
            'sso:before-redirect',
            handleBeforeRedirect as EventListener,
        );

        return () => {
            window.removeEventListener(
                'sso:before-redirect',
                handleBeforeRedirect as EventListener,
            );
        };
    }, []);
}
