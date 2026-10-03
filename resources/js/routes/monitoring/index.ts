import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import penggunaanAplikasi8991c6 from './penggunaan-aplikasi';
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
export const penggunaanAplikasi = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: penggunaanAplikasi.url(options),
    method: 'get',
});

penggunaanAplikasi.definition = {
    methods: ['get', 'post', 'head'],
    url: '/monlap-pa',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasi.url = (options?: RouteQueryOptions) => {
    return penggunaanAplikasi.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasi.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: penggunaanAplikasi.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasi.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: penggunaanAplikasi.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasi.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: penggunaanAplikasi.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
const penggunaanAplikasiForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: penggunaanAplikasi.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasiForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: penggunaanAplikasi.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasiForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: penggunaanAplikasi.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::penggunaanAplikasi
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
penggunaanAplikasiForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: penggunaanAplikasi.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

penggunaanAplikasi.form = penggunaanAplikasiForm;
const monitoring = {
    penggunaanAplikasi: Object.assign(
        penggunaanAplikasi,
        penggunaanAplikasi8991c6,
    ),
};

export default monitoring;
