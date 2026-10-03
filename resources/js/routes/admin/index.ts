import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import activityLogAd6c55 from './activity-log';
import systemSettings2a45d2 from './system-settings';
/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
export const dashboard = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
});

dashboard.definition = {
    methods: ['get', 'head'],
    url: '/admin/dashboard',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options);
};

/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
});

/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
const dashboardForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
});

/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
dashboardForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:252
 * @route '/admin/dashboard'
 */
dashboardForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

dashboard.form = dashboardForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
export const systemSettings = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: systemSettings.url(options),
    method: 'get',
});

systemSettings.definition = {
    methods: ['get', 'head'],
    url: '/admin/system-settings',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
systemSettings.url = (options?: RouteQueryOptions) => {
    return systemSettings.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
systemSettings.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: systemSettings.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
systemSettings.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: systemSettings.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
const systemSettingsForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: systemSettings.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
systemSettingsForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: systemSettings.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::systemSettings
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:41
 * @route '/admin/system-settings'
 */
systemSettingsForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: systemSettings.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

systemSettings.form = systemSettingsForm;
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
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseBackup
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
export const databaseBackup = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: databaseBackup.url(options),
    method: 'post',
});

databaseBackup.definition = {
    methods: ['post'],
    url: '/admin/database-backup',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseBackup
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
databaseBackup.url = (options?: RouteQueryOptions) => {
    return databaseBackup.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseBackup
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
databaseBackup.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: databaseBackup.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseBackup
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
const databaseBackupForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: databaseBackup.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseBackup
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:924
 * @route '/admin/database-backup'
 */
databaseBackupForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: databaseBackup.url(options),
    method: 'post',
});

databaseBackup.form = databaseBackupForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseRestore
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
export const databaseRestore = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: databaseRestore.url(options),
    method: 'post',
});

databaseRestore.definition = {
    methods: ['post'],
    url: '/admin/database-restore',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseRestore
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
databaseRestore.url = (options?: RouteQueryOptions) => {
    return databaseRestore.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseRestore
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
databaseRestore.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: databaseRestore.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseRestore
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
const databaseRestoreForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: databaseRestore.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::databaseRestore
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:987
 * @route '/admin/database-restore'
 */
databaseRestoreForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: databaseRestore.url(options),
    method: 'post',
});

databaseRestore.form = databaseRestoreForm;
/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
export const databaseListBackups = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: databaseListBackups.url(options),
    method: 'get',
});

databaseListBackups.definition = {
    methods: ['get', 'head'],
    url: '/admin/database-list-backups',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
databaseListBackups.url = (options?: RouteQueryOptions) => {
    return databaseListBackups.definition.url + queryParams(options);
};

/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
databaseListBackups.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: databaseListBackups.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
databaseListBackups.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: databaseListBackups.url(options),
    method: 'head',
});

/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
const databaseListBackupsForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseListBackups.url(options),
    method: 'get',
});

/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
databaseListBackupsForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseListBackups.url(options),
    method: 'get',
});
/**
 * @see routes/web.php:323
 * @route '/admin/database-list-backups'
 */
databaseListBackupsForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: databaseListBackups.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

databaseListBackups.form = databaseListBackupsForm;
const admin = {
    dashboard: Object.assign(dashboard, dashboard),
    systemSettings: Object.assign(systemSettings, systemSettings2a45d2),
    activityLog: Object.assign(activityLog, activityLogAd6c55),
    databaseStatus: Object.assign(databaseStatus, databaseStatus),
    databaseBackup: Object.assign(databaseBackup, databaseBackup),
    databaseRestore: Object.assign(databaseRestore, databaseRestore),
    databaseListBackups: Object.assign(
        databaseListBackups,
        databaseListBackups,
    ),
};

export default admin;
