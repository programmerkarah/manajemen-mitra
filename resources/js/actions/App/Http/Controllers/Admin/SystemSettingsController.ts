import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
export const deadlineManagement = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: deadlineManagement.url(options),
    method: 'get',
});

deadlineManagement.definition = {
    methods: ['get', 'head'],
    url: '/manage-deadline',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
deadlineManagement.url = (options?: RouteQueryOptions) => {
    return deadlineManagement.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
deadlineManagement.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: deadlineManagement.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
deadlineManagement.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: deadlineManagement.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
const deadlineManagementForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: deadlineManagement.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
deadlineManagementForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: deadlineManagement.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineManagement
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:183
 * @route '/manage-deadline'
 */
deadlineManagementForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: deadlineManagement.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

deadlineManagement.form = deadlineManagementForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/admin/system-settings',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::index
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
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
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateMaintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
export const updateMaintenance = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateMaintenance.url(options),
    method: 'post',
});

updateMaintenance.definition = {
    methods: ['post'],
    url: '/admin/system-settings/maintenance',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateMaintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
updateMaintenance.url = (options?: RouteQueryOptions) => {
    return updateMaintenance.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateMaintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
updateMaintenance.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateMaintenance.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateMaintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
const updateMaintenanceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateMaintenance.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateMaintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
updateMaintenanceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateMaintenance.url(options),
    method: 'post',
});

updateMaintenance.form = updateMaintenanceForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateSsoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
export const updateSsoSync = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateSsoSync.url(options),
    method: 'post',
});

updateSsoSync.definition = {
    methods: ['post'],
    url: '/admin/system-settings/sso-sync',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateSsoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
updateSsoSync.url = (options?: RouteQueryOptions) => {
    return updateSsoSync.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateSsoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
updateSsoSync.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateSsoSync.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateSsoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
const updateSsoSyncForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateSsoSync.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateSsoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
updateSsoSyncForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateSsoSync.url(options),
    method: 'post',
});

updateSsoSync.form = updateSsoSyncForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateFeatureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
export const updateFeatureToggle = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateFeatureToggle.url(options),
    method: 'post',
});

updateFeatureToggle.definition = {
    methods: ['post'],
    url: '/admin/system-settings/feature-toggle',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateFeatureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
updateFeatureToggle.url = (options?: RouteQueryOptions) => {
    return updateFeatureToggle.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateFeatureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
updateFeatureToggle.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateFeatureToggle.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateFeatureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
const updateFeatureToggleForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateFeatureToggle.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateFeatureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
updateFeatureToggleForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateFeatureToggle.url(options),
    method: 'post',
});

updateFeatureToggle.form = updateFeatureToggleForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateDeadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
export const updateDeadlineRule = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateDeadlineRule.url(options),
    method: 'post',
});

updateDeadlineRule.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-rule',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateDeadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
updateDeadlineRule.url = (options?: RouteQueryOptions) => {
    return updateDeadlineRule.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateDeadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
updateDeadlineRule.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateDeadlineRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateDeadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
const updateDeadlineRuleForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateDeadlineRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::updateDeadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
updateDeadlineRuleForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateDeadlineRule.url(options),
    method: 'post',
});

updateDeadlineRule.form = updateDeadlineRuleForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::grantDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
export const grantDeadlineBypass = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: grantDeadlineBypass.url(options),
    method: 'post',
});

grantDeadlineBypass.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::grantDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
grantDeadlineBypass.url = (options?: RouteQueryOptions) => {
    return grantDeadlineBypass.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::grantDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
grantDeadlineBypass.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: grantDeadlineBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::grantDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
const grantDeadlineBypassForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: grantDeadlineBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::grantDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
grantDeadlineBypassForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: grantDeadlineBypass.url(options),
    method: 'post',
});

grantDeadlineBypass.form = grantDeadlineBypassForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revokeDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
export const revokeDeadlineBypass = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revokeDeadlineBypass.url(args, options),
    method: 'post',
});

revokeDeadlineBypass.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass/{bypassId}/revoke',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revokeDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revokeDeadlineBypass.url = (
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
        revokeDeadlineBypass.definition.url
            .replace('{bypassId}', parsedArgs.bypassId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revokeDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revokeDeadlineBypass.post = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revokeDeadlineBypass.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revokeDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
const revokeDeadlineBypassForm = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revokeDeadlineBypass.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::revokeDeadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:612
 * @route '/admin/system-settings/deadline-bypass/{bypassId}/revoke'
 */
revokeDeadlineBypassForm.post = (
    args:
        | { bypassId: string | number }
        | [bypassId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revokeDeadlineBypass.url(args, options),
    method: 'post',
});

revokeDeadlineBypass.form = revokeDeadlineBypassForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approveDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
export const approveDeadlineBypassRequest = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approveDeadlineBypassRequest.url(args, options),
    method: 'post',
});

approveDeadlineBypassRequest.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass-request/{requestId}/approve',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approveDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approveDeadlineBypassRequest.url = (
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
        approveDeadlineBypassRequest.definition.url
            .replace('{requestId}', parsedArgs.requestId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approveDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approveDeadlineBypassRequest.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approveDeadlineBypassRequest.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approveDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
const approveDeadlineBypassRequestForm = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approveDeadlineBypassRequest.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::approveDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:541
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/approve'
 */
approveDeadlineBypassRequestForm.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approveDeadlineBypassRequest.url(args, options),
    method: 'post',
});

approveDeadlineBypassRequest.form = approveDeadlineBypassRequestForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::rejectDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
export const rejectDeadlineBypassRequest = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rejectDeadlineBypassRequest.url(args, options),
    method: 'post',
});

rejectDeadlineBypassRequest.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass-request/{requestId}/reject',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::rejectDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
rejectDeadlineBypassRequest.url = (
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
        rejectDeadlineBypassRequest.definition.url
            .replace('{requestId}', parsedArgs.requestId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::rejectDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
rejectDeadlineBypassRequest.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rejectDeadlineBypassRequest.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::rejectDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
const rejectDeadlineBypassRequestForm = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rejectDeadlineBypassRequest.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::rejectDeadlineBypassRequest
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:672
 * @route '/admin/system-settings/deadline-bypass-request/{requestId}/reject'
 */
rejectDeadlineBypassRequestForm.post = (
    args:
        | { requestId: string | number }
        | [requestId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rejectDeadlineBypassRequest.url(args, options),
    method: 'post',
});

rejectDeadlineBypassRequest.form = rejectDeadlineBypassRequestForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
export const activityLog = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: activityLog.url(options),
    method: 'get',
});

activityLog.definition = {
    methods: ['get', 'post', 'head'],
    url: '/admin/activity-log',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLog.url = (options?: RouteQueryOptions) => {
    return activityLog.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLog.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: activityLog.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLog.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activityLog.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLog.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: activityLog.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
const activityLogForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: activityLog.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLogForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: activityLog.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLogForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: activityLog.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::activityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:720
 * @route '/admin/activity-log'
 */
activityLogForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: activityLog.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

activityLog.form = activityLogForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
export const exportActivityLog = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportActivityLog.url(options),
    method: 'get',
});

exportActivityLog.definition = {
    methods: ['get', 'head'],
    url: '/admin/activity-log/export',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportActivityLog.url = (options?: RouteQueryOptions) => {
    return exportActivityLog.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportActivityLog.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportActivityLog.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportActivityLog.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportActivityLog.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
const exportActivityLogForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportActivityLog.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportActivityLogForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportActivityLog.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportActivityLog
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportActivityLogForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportActivityLog.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportActivityLog.form = exportActivityLogForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
export const databaseStatus = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: databaseStatus.url(options),
    method: 'get',
});

databaseStatus.definition = {
    methods: ['get', 'head'],
    url: '/admin/database-status',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
databaseStatus.url = (options?: RouteQueryOptions) => {
    return databaseStatus.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
databaseStatus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: databaseStatus.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
databaseStatus.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: databaseStatus.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
const databaseStatusForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseStatus.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
databaseStatusForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseStatus.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseStatus
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:887
 * @route '/admin/database-status'
 */
databaseStatusForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

databaseStatus.form = databaseStatusForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::backupDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
export const backupDatabase = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: backupDatabase.url(options),
    method: 'post',
});

backupDatabase.definition = {
    methods: ['post'],
    url: '/admin/database-backup',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::backupDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
backupDatabase.url = (options?: RouteQueryOptions) => {
    return backupDatabase.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::backupDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
backupDatabase.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: backupDatabase.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::backupDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
const backupDatabaseForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: backupDatabase.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::backupDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
backupDatabaseForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: backupDatabase.url(options),
    method: 'post',
});

backupDatabase.form = backupDatabaseForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::restoreDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
export const restoreDatabase = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: restoreDatabase.url(options),
    method: 'post',
});

restoreDatabase.definition = {
    methods: ['post'],
    url: '/admin/database-restore',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::restoreDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
restoreDatabase.url = (options?: RouteQueryOptions) => {
    return restoreDatabase.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::restoreDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
restoreDatabase.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: restoreDatabase.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::restoreDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
const restoreDatabaseForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: restoreDatabase.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::restoreDatabase
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
restoreDatabaseForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: restoreDatabase.url(options),
    method: 'post',
});

restoreDatabase.form = restoreDatabaseForm;
const SystemSettingsController = {
    deadlineManagement,
    index,
    updateMaintenance,
    updateSsoSync,
    updateFeatureToggle,
    updateDeadlineRule,
    grantDeadlineBypass,
    revokeDeadlineBypass,
    approveDeadlineBypassRequest,
    rejectDeadlineBypassRequest,
    activityLog,
    exportActivityLog,
    databaseStatus,
    backupDatabase,
    restoreDatabase,
};

export default SystemSettingsController;
