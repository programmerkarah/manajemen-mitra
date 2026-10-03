import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/monitoring-penilaian-mitra',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
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
const monitoringPenilaianMitra = {
    index: Object.assign(index, index),
};

export default monitoringPenilaianMitra;
