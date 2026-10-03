import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
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
const penggunaanAplikasi = {
    exportPdf: Object.assign(exportPdf, exportPdf),
};

export default penggunaanAplikasi;
