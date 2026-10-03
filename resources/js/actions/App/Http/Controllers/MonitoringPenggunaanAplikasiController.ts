import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/monlap-pa',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::index
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:24
 * @route '/monlap-pa'
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
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
export const exportPdf = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});

exportPdf.definition = {
    methods: ['get', 'head'],
    url: '/monlap-pa/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
exportPdf.url = (options?: RouteQueryOptions) => {
    return exportPdf.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
exportPdf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
exportPdf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportPdf.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
const exportPdfForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
exportPdfForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPenggunaanAplikasiController::exportPdf
 * @see app/Http/Controllers/MonitoringPenggunaanAplikasiController.php:31
 * @route '/monlap-pa/export-pdf'
 */
exportPdfForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportPdf.form = exportPdfForm;
const MonitoringPenggunaanAplikasiController = { index, exportPdf };

export default MonitoringPenggunaanAplikasiController;
