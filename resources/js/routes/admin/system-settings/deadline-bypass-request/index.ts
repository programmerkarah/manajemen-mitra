import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approve
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
export const approve = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

approve.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass-request/{requestId}/approve',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approve
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approve.url = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { requestId: args };
    }

    if (Array.isArray(args)) {
        args = {
            requestId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        requestId: args.requestId,
    };

    return (
        approve.definition.url
            .replace('{requestId}', parsedArgs.requestId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approve
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approve.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approve
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
const approveForm = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approve
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approveForm.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

approve.form = approveForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::reject
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
export const reject = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

reject.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass-request/{requestId}/reject',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::reject
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
reject.url = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { requestId: args };
    }

    if (Array.isArray(args)) {
        args = {
            requestId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        requestId: args.requestId,
    };

    return (
        reject.definition.url
            .replace('{requestId}', parsedArgs.requestId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::reject
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
reject.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::reject
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
const rejectForm = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::reject
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
rejectForm.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

reject.form = rejectForm;
const deadlineBypassRequest = {
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
};

export default deadlineBypassRequest;
