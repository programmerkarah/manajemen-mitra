import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
export const alt = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: alt.url(options),
    method: 'get',
});

alt.definition = {
    methods: ['get', 'head'],
    url: '/maintenance',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
alt.url = (options?: RouteQueryOptions) => {
    return alt.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
alt.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: alt.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
alt.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: alt.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
const altForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: alt.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
altForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: alt.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::alt
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
altForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: alt.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

alt.form = altForm;
const down = {
    alt: Object.assign(alt, alt),
};

export default down;
