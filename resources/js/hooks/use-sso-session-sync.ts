import { useEffect } from 'react';

interface UseSsoSessionSyncOptions {
    enabled: boolean;
    userId: number | null | undefined;
    focusCooldownSeconds: number;
    intervalSeconds: number;
}

const LAST_SYNC_AT_KEY = 'sso:last-sync-at';
const SSO_SYNC_IFRAME_ID = 'sso-sync-transport-iframe';

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

// ---------------------------------------------------------------------------
// Form state preservation
// ---------------------------------------------------------------------------

/** Broad selector used for stable DOM-index based matching. */
const FORM_ELEMENTS_SELECTOR = 'input, select, textarea';

export interface FormFieldEntry {
    /** Zero-based index in querySelectorAll('input, select, textarea') */
    domIndex: number;
    value: string;
    /** Non-null only for checkbox / radio inputs */
    checked: boolean | null;
}

function collectFormFields(): FormFieldEntry[] {
    const elements = document.querySelectorAll<HTMLElement>(
        FORM_ELEMENTS_SELECTOR,
    );
    const entries: FormFieldEntry[] = [];

    elements.forEach((el, domIndex) => {
        if (el instanceof HTMLInputElement) {
            if (
                [
                    'submit',
                    'button',
                    'reset',
                    'image',
                    'file',
                    'password',
                    'hidden',
                ].includes(el.type)
            ) {
                return;
            }
            if (el.disabled) {
                return;
            }
            entries.push({
                domIndex,
                value: el.value,
                checked:
                    el.type === 'checkbox' || el.type === 'radio'
                        ? el.checked
                        : null,
            });
        } else if (el instanceof HTMLSelectElement && !el.disabled) {
            entries.push({ domIndex, value: el.value, checked: null });
        } else if (el instanceof HTMLTextAreaElement && !el.disabled) {
            entries.push({ domIndex, value: el.value, checked: null });
        }
    });

    return entries;
}

/**
 * Apply saved form-field entries back to the DOM using native prototype
 * setters so React's synthetic event system picks up the changes.
 */
export function applyFormFields(entries: FormFieldEntry[]): void {
    const elements = document.querySelectorAll<HTMLElement>(
        FORM_ELEMENTS_SELECTOR,
    );

    for (const entry of entries) {
        const el = elements[entry.domIndex];
        if (!el) {
            continue;
        }

        if (
            el instanceof HTMLInputElement &&
            (el.type === 'checkbox' || el.type === 'radio')
        ) {
            const target = entry.checked ?? false;
            if (el.checked === target) {
                continue;
            }
            Object.getOwnPropertyDescriptor(
                HTMLInputElement.prototype,
                'checked',
            )?.set?.call(el, target);
            el.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (
            el instanceof HTMLInputElement ||
            el instanceof HTMLTextAreaElement
        ) {
            if (el.value === entry.value) {
                continue;
            }
            const proto =
                el instanceof HTMLInputElement
                    ? HTMLInputElement.prototype
                    : HTMLTextAreaElement.prototype;
            Object.getOwnPropertyDescriptor(proto, 'value')?.set?.call(
                el,
                entry.value,
            );
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (el instanceof HTMLSelectElement) {
            if (el.value === entry.value) {
                continue;
            }
            Object.getOwnPropertyDescriptor(
                HTMLSelectElement.prototype,
                'value',
            )?.set?.call(el, entry.value);
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}

// ---------------------------------------------------------------------------
// Scroll + form page-state
// ---------------------------------------------------------------------------

const SSO_SCROLL_STATE_KEY = 'sso:pre-sync-scroll';

interface SsoScrollState {
    url: string;
    scrollY: number;
    timestamp: number;
    /** Serialized form-field values captured before the SSO redirect. */
    formFields?: FormFieldEntry[];
}

function saveScrollState(): void {
    try {
        const state: SsoScrollState = {
            url: getCurrentPathWithQueryAndHash(),
            scrollY: window.scrollY,
            timestamp: Date.now(),
            formFields: collectFormFields(),
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
        transport: 'iframe',
        reason,
        return_to: getCurrentPathWithQueryAndHash(),
    });

    return `/auth/sso/redirect?${params.toString()}`;
}

function getOrCreateSyncIframe(): HTMLIFrameElement {
    const existing = document.getElementById(SSO_SYNC_IFRAME_ID);

    if (existing instanceof HTMLIFrameElement) {
        return existing;
    }

    const iframe = document.createElement('iframe');
    iframe.id = SSO_SYNC_IFRAME_ID;
    iframe.title = 'SSO Session Sync Transport';
    iframe.style.display = 'none';
    iframe.setAttribute('aria-hidden', 'true');
    document.body.appendChild(iframe);

    return iframe;
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
            const iframe = getOrCreateSyncIframe();
            iframe.src = `${buildSyncUrl(reason)}&t=${now}`;
        };

        const onSyncMessage = (event: MessageEvent) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            const payload = event.data as
                | { type?: string; status?: string }
                | null;

            if (payload?.type !== 'sso-sync-complete') {
                return;
            }

            if (payload.status === 'login_required') {
                window.location.assign(
                    '/login?message=' +
                        encodeURIComponent(
                            'Sesi SSO Anda sudah berakhir. Silakan login ulang.',
                        ),
                );
            }
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
        window.addEventListener('message', onSyncMessage);

        return () => {
            window.clearInterval(intervalId);
            window.removeEventListener('focus', onWindowFocus);
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
            window.removeEventListener('message', onSyncMessage);
        };
    }, [enabled, focusCooldownSeconds, intervalSeconds, userId]);
}
