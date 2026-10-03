import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\ViewAsUserController::set
 * @see app/Http/Controllers/ViewAsUserController.php:15
 * @route '/view-as-user/set'
 */
export const set = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: set.url(options),
    method: 'post',
});

set.definition = {
    methods: ['post'],
    url: '/view-as-user/set',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\ViewAsUserController::set
 * @see app/Http/Controllers/ViewAsUserController.php:15
 * @route '/view-as-user/set'
 */
set.url = (options?: RouteQueryOptions) => {
    return set.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\ViewAsUserController::set
 * @see app/Http/Controllers/ViewAsUserController.php:15
 * @route '/view-as-user/set'
 */
set.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: set.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::set
 * @see app/Http/Controllers/ViewAsUserController.php:15
 * @route '/view-as-user/set'
 */
const setForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: set.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::set
 * @see app/Http/Controllers/ViewAsUserController.php:15
 * @route '/view-as-user/set'
 */
setForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: set.url(options),
    method: 'post',
});

set.form = setForm;
/**
 * @see \App\Http\Controllers\ViewAsUserController::clear
 * @see app/Http/Controllers/ViewAsUserController.php:47
 * @route '/view-as-user/clear'
 */
export const clear = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: clear.url(options),
    method: 'post',
});

clear.definition = {
    methods: ['post'],
    url: '/view-as-user/clear',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\ViewAsUserController::clear
 * @see app/Http/Controllers/ViewAsUserController.php:47
 * @route '/view-as-user/clear'
 */
clear.url = (options?: RouteQueryOptions) => {
    return clear.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\ViewAsUserController::clear
 * @see app/Http/Controllers/ViewAsUserController.php:47
 * @route '/view-as-user/clear'
 */
clear.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clear.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::clear
 * @see app/Http/Controllers/ViewAsUserController.php:47
 * @route '/view-as-user/clear'
 */
const clearForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: clear.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::clear
 * @see app/Http/Controllers/ViewAsUserController.php:47
 * @route '/view-as-user/clear'
 */
clearForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: clear.url(options),
    method: 'post',
});

clear.form = clearForm;
/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
export const search = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
});

search.definition = {
    methods: ['get', 'head'],
    url: '/view-as-user/search',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
const searchForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\ViewAsUserController::search
 * @see app/Http/Controllers/ViewAsUserController.php:70
 * @route '/view-as-user/search'
 */
searchForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: search.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

search.form = searchForm;
const viewAsUser = {
    set: Object.assign(set, set),
    clear: Object.assign(clear, clear),
    search: Object.assign(search, search),
};

export default viewAsUser;
