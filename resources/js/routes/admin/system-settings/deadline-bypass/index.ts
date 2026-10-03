import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revoke
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
export const revoke = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revoke.url(args, options),
    method: 'post',
});

revoke.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass/{bypassId}/revoke',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revoke
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revoke.url = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bypassId: args };
    }

    if (Array.isArray(args)) {
        args = {
            bypassId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bypassId: args.bypassId,
    };

    return (
        revoke.definition.url
            .replace('{bypassId}', parsedArgs.bypassId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revoke
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revoke.post = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revoke.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revoke
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
const revokeForm = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revoke
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revokeForm.post = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, options),
    method: 'post',
});

revoke.form = revokeForm;
const deadlineBypass = {
    revoke: Object.assign(revoke, revoke),
};

export default deadlineBypass;
