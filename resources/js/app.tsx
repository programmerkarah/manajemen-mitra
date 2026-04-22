import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let csrfGuardInitialized = false;

type InertiaMutationMethod = 'post' | 'put' | 'patch' | 'delete';

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
        if (!payload.has('_token')) {
            payload.append('_token', token);
        }
        return payload;
    }

    if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
        const payloadObject = payload as Record<string, unknown>;
        if (typeof payloadObject._token === 'undefined') {
            return {
                ...payloadObject,
                _token: token,
            } as T;
        }
    }

    return payload;
}

function initializeInertiaCsrfGuard(): void {
    if (csrfGuardInitialized) {
        return;
    }

    const methods: InertiaMutationMethod[] = ['post', 'put', 'patch', 'delete'];

    for (const method of methods) {
        const originalMethod = router[method].bind(router);

        if (method === 'delete') {
            router.delete = (url, options = {}) => {
                void (async () => {
                    const token = await refreshCsrfToken();
                    const nextOptions = {
                        ...options,
                        data: attachTokenToPayload(options.data ?? {}, token),
                    };

                    originalMethod(url, nextOptions);
                })();
            };

            continue;
        }

        router[method] = (url, data = {}, options = {}) => {
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
