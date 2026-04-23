import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let csrfGuardInitialized = false;

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
