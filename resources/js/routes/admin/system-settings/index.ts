import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
import deadlineBypass9a1daf from './deadline-bypass';
import deadlineBypassRequest from './deadline-bypass-request';
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::maintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
export const maintenance = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: maintenance.url(options),
    method: 'post',
});

maintenance.definition = {
    methods: ['post'],
    url: '/admin/system-settings/maintenance',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::maintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
maintenance.url = (options?: RouteQueryOptions) => {
    return maintenance.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::maintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
maintenance.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: maintenance.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::maintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
const maintenanceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: maintenance.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::maintenance
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:1032
 * @route '/admin/system-settings/maintenance'
 */
maintenanceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: maintenance.url(options),
    method: 'post',
});

maintenance.form = maintenanceForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::ssoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
export const ssoSync = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: ssoSync.url(options),
    method: 'post',
});

ssoSync.definition = {
    methods: ['post'],
    url: '/admin/system-settings/sso-sync',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::ssoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
ssoSync.url = (options?: RouteQueryOptions) => {
    return ssoSync.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::ssoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
ssoSync.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ssoSync.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::ssoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
const ssoSyncForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: ssoSync.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::ssoSync
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:338
 * @route '/admin/system-settings/sso-sync'
 */
ssoSyncForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: ssoSync.url(options),
    method: 'post',
});

ssoSync.form = ssoSyncForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::featureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
export const featureToggle = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: featureToggle.url(options),
    method: 'post',
});

featureToggle.definition = {
    methods: ['post'],
    url: '/admin/system-settings/feature-toggle',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::featureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
featureToggle.url = (options?: RouteQueryOptions) => {
    return featureToggle.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::featureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
featureToggle.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: featureToggle.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::featureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
const featureToggleForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: featureToggle.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::featureToggle
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:306
 * @route '/admin/system-settings/feature-toggle'
 */
featureToggleForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: featureToggle.url(options),
    method: 'post',
});

featureToggle.form = featureToggleForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
export const deadlineRule = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: deadlineRule.url(options),
    method: 'post',
});

deadlineRule.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-rule',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
deadlineRule.url = (options?: RouteQueryOptions) => {
    return deadlineRule.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
deadlineRule.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deadlineRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
const deadlineRuleForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: deadlineRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineRule
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:366
 * @route '/admin/system-settings/deadline-rule'
 */
deadlineRuleForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: deadlineRule.url(options),
    method: 'post',
});

deadlineRule.form = deadlineRuleForm;
/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
export const deadlineBypass = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: deadlineBypass.url(options),
    method: 'post',
});

deadlineBypass.definition = {
    methods: ['post'],
    url: '/admin/system-settings/deadline-bypass',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
deadlineBypass.url = (options?: RouteQueryOptions) => {
    return deadlineBypass.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
deadlineBypass.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: deadlineBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
const deadlineBypassForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: deadlineBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Admin\SystemSettingsController::deadlineBypass
 * @see app/Http/Controllers/Admin/SystemSettingsController.php:417
 * @route '/admin/system-settings/deadline-bypass'
 */
deadlineBypassForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: deadlineBypass.url(options),
    method: 'post',
});

deadlineBypass.form = deadlineBypassForm;
const systemSettings = {
    maintenance: Object.assign(maintenance, maintenance),
    ssoSync: Object.assign(ssoSync, ssoSync),
    featureToggle: Object.assign(featureToggle, featureToggle),
    deadlineRule: Object.assign(deadlineRule, deadlineRule),
    deadlineBypass: Object.assign(deadlineBypass, deadlineBypass9a1daf),
    deadlineBypassRequest: Object.assign(
        deadlineBypassRequest,
        deadlineBypassRequest,
    ),
};

export default systemSettings;
