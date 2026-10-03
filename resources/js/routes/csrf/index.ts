import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
export const token = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: token.url(options),
    method: 'get',
});

token.definition = {
    methods: ['get', 'head'],
    url: '/csrf-token',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
token.url = (options?: RouteQueryOptions) => {
    return token.definition.url + queryParams(options);
};

/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
token.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: token.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
token.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: token.url(options),
    method: 'head',
});

/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
const tokenForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: token.url(options),
    method: 'get',
});

/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
tokenForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: token.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:62
 * @route '/csrf-token'
 */
tokenForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: token.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

token.form = tokenForm;
const csrf = {
    token: Object.assign(token, token),
};

export default csrf;
