import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
export const redirect = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
});

redirect.definition = {
    methods: ['get', 'head'],
    url: '/auth/sso/redirect',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
redirect.url = (options?: RouteQueryOptions) => {
    return redirect.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
redirect.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
redirect.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: redirect.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
const redirectForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
redirectForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::redirect
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:27
 * @route '/auth/sso/redirect'
 */
redirectForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: redirect.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

redirect.form = redirectForm;
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
export const callback = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
});

callback.definition = {
    methods: ['get', 'head'],
    url: '/auth/sso/callback',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callback.url = (options?: RouteQueryOptions) => {
    return callback.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callback.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callback.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: callback.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
const callbackForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbackForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbackForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callback.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

callback.form = callbackForm;
/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
export const syncComplete = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: syncComplete.url(options),
    method: 'get',
});

syncComplete.definition = {
    methods: ['get', 'head'],
    url: '/auth/sso/sync-complete',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
syncComplete.url = (options?: RouteQueryOptions) => {
    return syncComplete.definition.url + queryParams(options);
};

/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
syncComplete.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: syncComplete.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
syncComplete.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: syncComplete.url(options),
    method: 'head',
});

/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
const syncCompleteForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: syncComplete.url(options),
    method: 'get',
});

/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
syncCompleteForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: syncComplete.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:71
 * @route '/auth/sso/sync-complete'
 */
syncCompleteForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: syncComplete.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

syncComplete.form = syncCompleteForm;
const sso = {
    redirect: Object.assign(redirect, redirect),
    callback: Object.assign(callback, callback),
    syncComplete: Object.assign(syncComplete, syncComplete),
};

export default sso;
