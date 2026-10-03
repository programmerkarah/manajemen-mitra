import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
export const exportPdf = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});

exportPdf.definition = {
    methods: ['get', 'head'],
    url: '/analisis/petugas/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
exportPdf.url = (options?: RouteQueryOptions) => {
    return exportPdf.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
exportPdf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
exportPdf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportPdf.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
const exportPdfForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
exportPdfForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::exportPdf
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
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
const petugas = {
    exportPdf: Object.assign(exportPdf, exportPdf),
};

export default petugas;
