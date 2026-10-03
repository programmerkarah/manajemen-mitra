import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
export const filter = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

filter.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/detail/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
filter.url = (options?: RouteQueryOptions) => {
    return filter.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
filter.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const filterForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
filterForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

filter.form = filterForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
export const post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
});

post.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/detail',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
post.url = (options?: RouteQueryOptions) => {
    return post.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
post.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
const postForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
postForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
});

post.form = postForm;
const detail = {
    filter: Object.assign(filter, filter),
    post: Object.assign(post, post),
};

export default detail;
