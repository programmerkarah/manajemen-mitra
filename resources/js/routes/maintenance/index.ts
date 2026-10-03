import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import down872484 from './down';
/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
export const down = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: down.url(options),
    method: 'get',
});

down.definition = {
    methods: ['get', 'head'],
    url: '/mt',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
down.url = (options?: RouteQueryOptions) => {
    return down.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
down.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: down.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
down.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: down.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
const downForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: down.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
downForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: down.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::down
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
downForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: down.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

down.form = downForm;
/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
export const bypass = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: bypass.url(options),
    method: 'get',
});

bypass.definition = {
    methods: ['get', 'head'],
    url: '/bypass',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
bypass.url = (options?: RouteQueryOptions) => {
    return bypass.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
bypass.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bypass.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
bypass.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: bypass.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
const bypassForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: bypass.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
bypassForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: bypass.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::bypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
bypassForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: bypass.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

bypass.form = bypassForm;
/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
export const up = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: up.url(options),
    method: 'get',
});

up.definition = {
    methods: ['get', 'head'],
    url: '/up',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
up.url = (options?: RouteQueryOptions) => {
    return up.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
up.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: up.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
up.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: up.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
const upForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: up.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
upForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: up.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::up
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
upForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: up.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

up.form = upForm;
const maintenance = {
    down: Object.assign(down, down872484),
    bypass: Object.assign(bypass, bypass),
    up: Object.assign(up, up),
};

export default maintenance;
