import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::store
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/deadline-bypass/request',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::store
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::store
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::store
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::store
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const DeadlineBypassRequestController = { store };

export default DeadlineBypassRequestController;
