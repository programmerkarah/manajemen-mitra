import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import pkppContracts from './pkpp-contracts';
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/spk/petugas-pengganti',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
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
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/sensus-ekonomi/replacements',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const seReplacements = {
    index: Object.assign(index, index),
    pkppContracts: Object.assign(pkppContracts, pkppContracts),
    store: Object.assign(store, store),
};

export default seReplacements;
