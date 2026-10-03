import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
export const post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
});

post.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/create',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
post.url = (options?: RouteQueryOptions) => {
    return post.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
post.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const postForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::post
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
postForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
});

post.form = postForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
export const filter = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

filter.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/create/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
filter.url = (options?: RouteQueryOptions) => {
    return filter.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
filter.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
const filterForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
filterForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

filter.form = filterForm;
const create = {
    post: Object.assign(post, post),
    filter: Object.assign(filter, filter),
};

export default create;
