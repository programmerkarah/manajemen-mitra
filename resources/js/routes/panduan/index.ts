import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
export const simantik = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: simantik.url(options),
    method: 'get',
});

simantik.definition = {
    methods: ['get', 'head'],
    url: '/panduan',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
simantik.url = (options?: RouteQueryOptions) => {
    return simantik.definition.url + queryParams(options);
};

/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
simantik.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: simantik.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
simantik.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: simantik.url(options),
    method: 'head',
});

/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
const simantikForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: simantik.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
simantikForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: simantik.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\ViewController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/ViewController.php:32
 * @route '/panduan'
 */
simantikForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: simantik.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

simantik.form = simantikForm;
const panduan = {
    simantik: Object.assign(simantik, simantik),
};

export default panduan;
