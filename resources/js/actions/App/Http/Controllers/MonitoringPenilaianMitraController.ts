import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
const indexf8ad8922adb4a28b861385d1b4e6bd24 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});

indexf8ad8922adb4a28b861385d1b4e6bd24.definition = {
    methods: ['get', 'post', 'head'],
    url: '/monitoring-penilaian-mitra',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.url = (options?: RouteQueryOptions) => {
    return (
        indexf8ad8922adb4a28b861385d1b4e6bd24.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
const indexf8ad8922adb4a28b861385d1b4e6bd24Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

indexf8ad8922adb4a28b861385d1b4e6bd24.form =
    indexf8ad8922adb4a28b861385d1b4e6bd24Form;
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
const indexf8ad8922adb4a28b861385d1b4e6bd24 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});

indexf8ad8922adb4a28b861385d1b4e6bd24.definition = {
    methods: ['get', 'head'],
    url: '/monitoring-penilaian-mitra',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.url = (options?: RouteQueryOptions) => {
    return (
        indexf8ad8922adb4a28b861385d1b4e6bd24.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
const indexf8ad8922adb4a28b861385d1b4e6bd24Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenilaianMitraController::index
 * @see app/Http/Controllers/MonitoringPenilaianMitraController.php:16
 * @route '/monitoring-penilaian-mitra'
 */
indexf8ad8922adb4a28b861385d1b4e6bd24Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: indexf8ad8922adb4a28b861385d1b4e6bd24.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

indexf8ad8922adb4a28b861385d1b4e6bd24.form =
    indexf8ad8922adb4a28b861385d1b4e6bd24Form;

export const index = {
    '/monitoring-penilaian-mitra': indexf8ad8922adb4a28b861385d1b4e6bd24,
    '/monitoring-penilaian-mitra': indexf8ad8922adb4a28b861385d1b4e6bd24,
};

const MonitoringPenilaianMitraController = { index };

export default MonitoringPenilaianMitraController;
