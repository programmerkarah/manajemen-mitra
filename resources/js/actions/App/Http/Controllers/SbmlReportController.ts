import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/rekap-honor',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::index
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

index.form = indexForm;
const SbmlReportController = { index };

export default SbmlReportController;
