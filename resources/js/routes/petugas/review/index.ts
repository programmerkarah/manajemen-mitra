import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/petugas/review',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasReviewController::index
 * @see app/Http/Controllers/PetugasReviewController.php:24
 * @route '/petugas/review'
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
 * @see \App\Http\Controllers\PetugasReviewController::store
 * @see app/Http/Controllers/PetugasReviewController.php:138
 * @route '/petugas/review'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/petugas/review',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PetugasReviewController::store
 * @see app/Http/Controllers/PetugasReviewController.php:138
 * @route '/petugas/review'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasReviewController::store
 * @see app/Http/Controllers/PetugasReviewController.php:138
 * @route '/petugas/review'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasReviewController::store
 * @see app/Http/Controllers/PetugasReviewController.php:138
 * @route '/petugas/review'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasReviewController::store
 * @see app/Http/Controllers/PetugasReviewController.php:138
 * @route '/petugas/review'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const review = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
};

export default review;
