import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../../wayfinder';
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
const callbacke08b453c6f19636d154dfd3db724e5e1 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: callbacke08b453c6f19636d154dfd3db724e5e1.url(options),
    method: 'get',
});

callbacke08b453c6f19636d154dfd3db724e5e1.definition = {
    methods: ['get', 'head'],
    url: '/auth/sso/callback',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbacke08b453c6f19636d154dfd3db724e5e1.url = (
    options?: RouteQueryOptions,
) => {
    return (
        callbacke08b453c6f19636d154dfd3db724e5e1.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbacke08b453c6f19636d154dfd3db724e5e1.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: callbacke08b453c6f19636d154dfd3db724e5e1.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbacke08b453c6f19636d154dfd3db724e5e1.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: callbacke08b453c6f19636d154dfd3db724e5e1.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
const callbacke08b453c6f19636d154dfd3db724e5e1Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbacke08b453c6f19636d154dfd3db724e5e1.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbacke08b453c6f19636d154dfd3db724e5e1Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbacke08b453c6f19636d154dfd3db724e5e1.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/sso/callback'
 */
callbacke08b453c6f19636d154dfd3db724e5e1Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbacke08b453c6f19636d154dfd3db724e5e1.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

callbacke08b453c6f19636d154dfd3db724e5e1.form =
    callbacke08b453c6f19636d154dfd3db724e5e1Form;
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
const callbackc1f43345e7482b575af83000ca9b747e = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: callbackc1f43345e7482b575af83000ca9b747e.url(options),
    method: 'get',
});

callbackc1f43345e7482b575af83000ca9b747e.definition = {
    methods: ['get', 'head'],
    url: '/auth/callback',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
callbackc1f43345e7482b575af83000ca9b747e.url = (
    options?: RouteQueryOptions,
) => {
    return (
        callbackc1f43345e7482b575af83000ca9b747e.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
callbackc1f43345e7482b575af83000ca9b747e.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: callbackc1f43345e7482b575af83000ca9b747e.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
callbackc1f43345e7482b575af83000ca9b747e.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: callbackc1f43345e7482b575af83000ca9b747e.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
const callbackc1f43345e7482b575af83000ca9b747eForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbackc1f43345e7482b575af83000ca9b747e.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
callbackc1f43345e7482b575af83000ca9b747eForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbackc1f43345e7482b575af83000ca9b747e.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Auth\SsoOAuthController::callback
 * @see app/Http/Controllers/Auth/SsoOAuthController.php:83
 * @route '/auth/callback'
 */
callbackc1f43345e7482b575af83000ca9b747eForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: callbackc1f43345e7482b575af83000ca9b747e.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

callbackc1f43345e7482b575af83000ca9b747e.form =
    callbackc1f43345e7482b575af83000ca9b747eForm;

export const callback = {
    '/auth/sso/callback': callbacke08b453c6f19636d154dfd3db724e5e1,
    '/auth/callback': callbackc1f43345e7482b575af83000ca9b747e,
};

const SsoOAuthController = { redirect, callback };

export default SsoOAuthController;
