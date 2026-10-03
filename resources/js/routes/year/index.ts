import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\YearSwitchController::switchMethod
 * @see app/Http/Controllers/YearSwitchController.php:12
 * @route '/switch-year'
 */
export const switchMethod = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
});

switchMethod.definition = {
    methods: ['post'],
    url: '/switch-year',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\YearSwitchController::switchMethod
 * @see app/Http/Controllers/YearSwitchController.php:12
 * @route '/switch-year'
 */
switchMethod.url = (options?: RouteQueryOptions) => {
    return switchMethod.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\YearSwitchController::switchMethod
 * @see app/Http/Controllers/YearSwitchController.php:12
 * @route '/switch-year'
 */
switchMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\YearSwitchController::switchMethod
 * @see app/Http/Controllers/YearSwitchController.php:12
 * @route '/switch-year'
 */
const switchMethodForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\YearSwitchController::switchMethod
 * @see app/Http/Controllers/YearSwitchController.php:12
 * @route '/switch-year'
 */
switchMethodForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
});

switchMethod.form = switchMethodForm;
const year = {
    switch: Object.assign(switchMethod, switchMethod),
};

export default year;
