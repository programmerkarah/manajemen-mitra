import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let csrfGuardInitialized = false;


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
    const currentPath = window.location.pathname;

    if (/\/(?:create|edit)(?:\/|$)/i.test(currentPath)) {
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

function initializeReturnNavigation(): void {
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
            if (!action) {
                return;
            }

            if (
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

type InertiaMutationMethod = 'post' | 'put' | 'patch' | 'delete';
type InertiaDataMutationMethod = Exclude<InertiaMutationMethod, 'delete'>;
type MutationPayload = FormData | Record<string, unknown>;
type MutationVisitOptions = Record<string, unknown>;
type DeleteVisitOptions = MutationVisitOptions & { data?: MutationPayload };
type DataMutationMethodHandler = (
    url: string,
    data?: MutationPayload,
    options?: MutationVisitOptions,
) => void;
type DeleteMutationMethodHandler = (
    url: string,
    options?: DeleteVisitOptions,
) => void;
type MutationRouter = Record<
    InertiaDataMutationMethod,
    DataMutationMethodHandler
> & {
    delete: DeleteMutationMethodHandler;
};

function updateCsrfMetaToken(token: string): void {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        csrfMeta.setAttribute('content', token);
    }
}

async function refreshCsrfToken(): Promise<string | null> {
    try {
        const response = await fetch('/csrf-token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Cache-Control': 'no-cache',
            },
        });

        if (!response.ok) {
            return null;
        }

        const payload = (await response.json()) as { token?: string };
        const token = payload?.token;

        if (typeof token === 'string' && token.length > 0) {
            updateCsrfMetaToken(token);
            return token;
        }
    } catch {
        return null;
    }

    return null;
}

function attachTokenToPayload<T>(payload: T, token: string | null): T {
    if (!token) {
        return payload;
    }

    if (payload instanceof FormData) {
        payload.set('_token', token);
        return payload;
    }

    if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
        const payloadObject = payload as Record<string, unknown>;

        return {
            ...payloadObject,
            _token: token,
        } as T;
    }

    return payload;
}

function initializeInertiaCsrfGuard(): void {
    if (csrfGuardInitialized) {
        return;
    }

    const mutationRouter = router as unknown as MutationRouter;
    const dataMethods: InertiaDataMutationMethod[] = ['post', 'put', 'patch'];

    const originalDeleteMethod = mutationRouter.delete.bind(router);

    mutationRouter.delete = (url: string, options: DeleteVisitOptions = {}) => {
        void (async () => {
            const token = await refreshCsrfToken();
            const nextOptions = {
                ...options,
                data: attachTokenToPayload(options.data ?? {}, token),
            };

            originalDeleteMethod(url, nextOptions);
        })();
    };

    for (const method of dataMethods) {
        const originalMethod = mutationRouter[method].bind(router);

        mutationRouter[method] = (
            url: string,
            data: MutationPayload = {},
            options: MutationVisitOptions = {},
        ) => {
            void (async () => {
                const token = await refreshCsrfToken();
                const payloadWithToken = attachTokenToPayload(data, token);

                originalMethod(url, payloadWithToken, options);
            })();
        };
    }

    csrfGuardInitialized = true;
}

initializeInertiaCsrfGuard();
initializeReturnNavigation();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        );

        return (page as { default?: unknown }).default ?? page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
