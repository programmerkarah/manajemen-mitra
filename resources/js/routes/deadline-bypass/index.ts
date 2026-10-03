import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::request
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
export const request = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: request.url(options),
    method: 'post',
});

request.definition = {
    methods: ['post'],
    url: '/deadline-bypass/request',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::request
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
request.url = (options?: RouteQueryOptions) => {
    return request.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::request
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
request.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: request.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::request
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
const requestForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DeadlineBypassRequestController::request
 * @see app/Http/Controllers/DeadlineBypassRequestController.php:17
 * @route '/deadline-bypass/request'
 */
requestForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(options),
    method: 'post',
});

request.form = requestForm;
const deadlineBypass = {
    request: Object.assign(request, request),
};

export default deadlineBypass;
