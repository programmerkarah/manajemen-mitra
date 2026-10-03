import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
const ResetUserTwoFactorController = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: ResetUserTwoFactorController.url(args, options),
    method: 'post',
});

ResetUserTwoFactorController.definition = {
    methods: ['post'],
    url: '/users/{user}/reset-2fa',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
ResetUserTwoFactorController.url = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { user: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            user: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        user: typeof args.user === 'object' ? args.user.id : args.user,
    };

    return (
        ResetUserTwoFactorController.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
ResetUserTwoFactorController.post = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: ResetUserTwoFactorController.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
const ResetUserTwoFactorControllerForm = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: ResetUserTwoFactorController.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
ResetUserTwoFactorControllerForm.post = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: ResetUserTwoFactorController.url(args, options),
    method: 'post',
});

ResetUserTwoFactorController.form = ResetUserTwoFactorControllerForm;
export default ResetUserTwoFactorController;
