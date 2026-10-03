import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
export const exportMethod = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
});

exportMethod.definition = {
    methods: ['get', 'head'],
    url: '/admin/activity-log/export',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
const exportMethodForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportMethodForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::exportMethod
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:831
 * @route '/admin/activity-log/export'
 */
exportMethodForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportMethod.form = exportMethodForm;
const activityLog = {
    export: Object.assign(exportMethod, exportMethod),
};

export default activityLog;
