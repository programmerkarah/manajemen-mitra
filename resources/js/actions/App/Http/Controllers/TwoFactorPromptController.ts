import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
const TwoFactorPromptController = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: TwoFactorPromptController.url(options),
    method: 'get',
});

TwoFactorPromptController.definition = {
    methods: ['get', 'head'],
    url: '/two-factor/prompt',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
TwoFactorPromptController.url = (options?: RouteQueryOptions) => {
    return TwoFactorPromptController.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
TwoFactorPromptController.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: TwoFactorPromptController.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
TwoFactorPromptController.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: TwoFactorPromptController.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
const TwoFactorPromptControllerForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: TwoFactorPromptController.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
TwoFactorPromptControllerForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: TwoFactorPromptController.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\TwoFactorPromptController::__invoke
 * @see app/Http/Controllers/TwoFactorPromptController.php:14
 * @route '/two-factor/prompt'
 */
TwoFactorPromptControllerForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: TwoFactorPromptController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

TwoFactorPromptController.form = TwoFactorPromptControllerForm;
export default TwoFactorPromptController;
