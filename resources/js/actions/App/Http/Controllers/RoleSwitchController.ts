import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\RoleSwitchController::switchMethod
 * @see app/Http/Controllers/RoleSwitchController.php:15
 * @route '/switch-role'
 */
export const switchMethod = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
});

switchMethod.definition = {
    methods: ['post'],
    url: '/switch-role',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\RoleSwitchController::switchMethod
 * @see app/Http/Controllers/RoleSwitchController.php:15
 * @route '/switch-role'
 */
switchMethod.url = (options?: RouteQueryOptions) => {
    return switchMethod.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\RoleSwitchController::switchMethod
 * @see app/Http/Controllers/RoleSwitchController.php:15
 * @route '/switch-role'
 */
switchMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\RoleSwitchController::switchMethod
 * @see app/Http/Controllers/RoleSwitchController.php:15
 * @route '/switch-role'
 */
const switchMethodForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\RoleSwitchController::switchMethod
 * @see app/Http/Controllers/RoleSwitchController.php:15
 * @route '/switch-role'
 */
switchMethodForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
});

switchMethod.form = switchMethodForm;
const RoleSwitchController = { switchMethod, switch: switchMethod };

export default RoleSwitchController;
