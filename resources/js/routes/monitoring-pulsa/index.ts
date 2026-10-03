import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/monitoring-pulsa',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::index
 * @see app/Http/Controllers/MonitoringPulsaController.php:74
 * @route '/monitoring-pulsa'
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
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
export const exportPdf = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});

exportPdf.definition = {
    methods: ['get', 'post', 'head'],
    url: '/monitoring-pulsa/export-pdf',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdf.url = (options?: RouteQueryOptions) => {
    return exportPdf.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdf.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: exportPdf.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportPdf.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
const exportPdfForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdfForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
 */
exportPdfForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: exportPdf.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\MonitoringPulsaController::exportPdf
 * @see app/Http/Controllers/MonitoringPulsaController.php:107
 * @route '/monitoring-pulsa/export-pdf'
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
const monitoringPulsa = {
    index: Object.assign(index, index),
    exportPdf: Object.assign(exportPdf, exportPdf),
};

export default monitoringPulsa;
