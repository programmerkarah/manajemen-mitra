import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
export const refresh = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: refresh.url(options),
    method: 'get',
});

refresh.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/detail/filter',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
refresh.url = (options?: RouteQueryOptions) => {
    return refresh.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
refresh.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: refresh.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
refresh.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: refresh.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const refreshForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: refresh.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
refreshForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: refresh.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::refresh
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
refreshForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: refresh.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

refresh.form = refreshForm;
