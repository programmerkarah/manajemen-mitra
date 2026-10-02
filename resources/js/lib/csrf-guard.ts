import { router } from '@inertiajs/react';

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

let initialized = false;

function updateCsrfMetaToken(token: string): void {
    document
        .querySelector('meta[name="csrf-token"]')
        ?.setAttribute('content', token);
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

        const { token } = (await response.json()) as { token?: string };

        if (typeof token === 'string' && token.length > 0) {
            updateCsrfMetaToken(token);
            return token;
        }
    } catch {
        // Mutations still continue with Laravel's cookie/meta token fallback.
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
        return {
            ...(payload as Record<string, unknown>),
            _token: token,
        } as T;
    }

    return payload;
}

export function initializeInertiaCsrfGuard(): void {
    if (initialized) {
        return;
    }

    const mutationRouter = router as unknown as MutationRouter;
    const dataMethods: InertiaDataMutationMethod[] = ['post', 'put', 'patch'];
    const originalDeleteMethod = mutationRouter.delete.bind(router);

    mutationRouter.delete = (url: string, options: DeleteVisitOptions = {}) => {
        void refreshCsrfToken().then((token) => {
            originalDeleteMethod(url, {
                ...options,
                data: attachTokenToPayload(options.data ?? {}, token),
            });
        });
    };

    for (const method of dataMethods) {
        const originalMethod = mutationRouter[method].bind(router);

        mutationRouter[method] = (
            url: string,
            data: MutationPayload = {},
            options: MutationVisitOptions = {},
        ) => {
            void refreshCsrfToken().then((token) => {
                originalMethod(url, attachTokenToPayload(data, token), options);
            });
        };
    }

    initialized = true;
}
