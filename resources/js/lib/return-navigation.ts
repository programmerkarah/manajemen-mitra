import { router } from '@inertiajs/react';

const RETURN_NAVIGATION_PREFIX = 'simantik:return-to:';
let isReturnNavigation = false;

function getCurrentNavigationKey(): string {
    return `${window.location.pathname}${window.location.search}`;
}

function normalizeInternalPath(value: string): string | null {
    try {
        const url = new URL(value, window.location.origin);

        if (url.origin !== window.location.origin) {
            return null;
        }

        const path = `${url.pathname}${url.search}${url.hash}`;

        if (
            !path.startsWith('/') ||
            path.startsWith('//') ||
            ['/login', '/logout', '/register'].includes(url.pathname)
        ) {
            return null;
        }

        return path;
    } catch {
        return null;
    }
}

function rememberReturnTarget(destination: string): void {
    const destinationPath = normalizeInternalPath(destination);
    if (!destinationPath) {
        return;
    }

    const currentPath = getCurrentNavigationKey();
    const destinationKey = destinationPath.split('#')[0];

    if (destinationKey === currentPath || isReturnNavigation) {
        return;
    }

    sessionStorage.setItem(
        `${RETURN_NAVIGATION_PREFIX}${destinationKey}`,
        currentPath,
    );
}

function getCurrentReturnTarget(): string | null {
    const stored = sessionStorage.getItem(
        `${RETURN_NAVIGATION_PREFIX}${getCurrentNavigationKey()}`,
    );

    return stored ? normalizeInternalPath(stored) : null;
}

function pageUsesReturnNavigation(): boolean {
    if (/\/(?:create|edit)(?:\/|$)/i.test(window.location.pathname)) {
        return true;
    }

    return Array.from(document.querySelectorAll('a, button')).some((element) =>
        /^Kembali(?:\s|$)/i.test(element.textContent?.trim() ?? ''),
    );
}

function attachReturnTargetToPayload(
    payload: unknown,
    returnTarget: string,
): unknown {
    if (payload instanceof FormData) {
        payload.set('_return_to', returnTarget);
        return payload;
    }

    if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
        return {
            ...(payload as Record<string, unknown>),
            _return_to: returnTarget,
        };
    }

    return payload;
}

export function initializeReturnNavigation(): void {
    const currentKey = getCurrentNavigationKey();
    const existing = sessionStorage.getItem(
        `${RETURN_NAVIGATION_PREFIX}${currentKey}`,
    );

    if (!existing && document.referrer) {
        const referrer = normalizeInternalPath(document.referrer);

        if (referrer && referrer.split('#')[0] !== currentKey) {
            sessionStorage.setItem(
                `${RETURN_NAVIGATION_PREFIX}${currentKey}`,
                referrer,
            );
        }
    }

    router.on('before', (event) => {
        const detail = event.detail as {
            visit?: {
                method?: string;
                url?: string | URL;
                data?: unknown;
            };
        };
        const visit = detail.visit;

        if (!visit) {
            return;
        }

        const method = String(visit.method ?? 'get').toLowerCase();
        const destination = String(visit.url ?? '');
        const destinationPath = normalizeInternalPath(destination);
        const destinationIsEditor = Boolean(
            destinationPath &&
            /\/(?:create|edit)(?:\/|\?|$)/i.test(destinationPath),
        );

        if (method === 'get' || destinationIsEditor) {
            if (!isReturnNavigation) {
                rememberReturnTarget(destination);
            }

            if (method === 'get') {
                return;
            }
        }

        if (
            ['post', 'put', 'patch'].includes(method) &&
            pageUsesReturnNavigation()
        ) {
            const returnTarget = getCurrentReturnTarget();

            if (returnTarget) {
                visit.data = attachReturnTargetToPayload(
                    visit.data,
                    returnTarget,
                );
            }
        }
    });

    document.addEventListener(
        'click',
        (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const action = target.closest('a, button');
            if (
                !action ||
                !/^Kembali(?:\s|$)/i.test(action.textContent?.trim() ?? '')
            ) {
                return;
            }

            const returnTarget = getCurrentReturnTarget();
            if (!returnTarget) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            isReturnNavigation = true;
            router.visit(returnTarget, {
                replace: true,
                onFinish: () => {
                    isReturnNavigation = false;
                },
            });
        },
        true,
    );
}
