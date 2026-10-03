import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
const RedirectController6d8dcb84106e012ead9e7436010d4b11 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'get',
});

RedirectController6d8dcb84106e012ead9e7436010d4b11.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/preview-perjanjian-kerja',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectController6d8dcb84106e012ead9e7436010d4b11.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
const RedirectController6d8dcb84106e012ead9e7436010d4b11Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/preview-perjanjian-kerja'
 */
RedirectController6d8dcb84106e012ead9e7436010d4b11Form.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController6d8dcb84106e012ead9e7436010d4b11.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectController6d8dcb84106e012ead9e7436010d4b11.form =
    RedirectController6d8dcb84106e012ead9e7436010d4b11Form;
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
const RedirectController4b87d2df7e3aa853f6720faea796e36c = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'get',
});

RedirectController4b87d2df7e3aa853f6720faea796e36c.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/settings',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectController4b87d2df7e3aa853f6720faea796e36c.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36c.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
const RedirectController4b87d2df7e3aa853f6720faea796e36cForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings'
 */
RedirectController4b87d2df7e3aa853f6720faea796e36cForm.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController4b87d2df7e3aa853f6720faea796e36c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectController4b87d2df7e3aa853f6720faea796e36c.form =
    RedirectController4b87d2df7e3aa853f6720faea796e36cForm;
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
const RedirectControllerfc6874003af373efc88e5e18eecd9c17 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
});

RedirectControllerfc6874003af373efc88e5e18eecd9c17.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/settings/profile',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectControllerfc6874003af373efc88e5e18eecd9c17.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
const RedirectControllerfc6874003af373efc88e5e18eecd9c17Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/profile'
 */
RedirectControllerfc6874003af373efc88e5e18eecd9c17Form.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllerfc6874003af373efc88e5e18eecd9c17.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectControllerfc6874003af373efc88e5e18eecd9c17.form =
    RedirectControllerfc6874003af373efc88e5e18eecd9c17Form;
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
const RedirectController3d83238d90f1e3969ebe570175cfec66 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
});

RedirectController3d83238d90f1e3969ebe570175cfec66.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/settings/password',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectController3d83238d90f1e3969ebe570175cfec66.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
const RedirectController3d83238d90f1e3969ebe570175cfec66Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/password'
 */
RedirectController3d83238d90f1e3969ebe570175cfec66Form.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController3d83238d90f1e3969ebe570175cfec66.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectController3d83238d90f1e3969ebe570175cfec66.form =
    RedirectController3d83238d90f1e3969ebe570175cfec66Form;
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
const RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
});

RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/settings/appearance',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
const RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/appearance'
 */
RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5.form =
    RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5Form;
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
const RedirectController0ad619344ffe7d8e6e4b1c508de96456 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'get',
});

RedirectController0ad619344ffe7d8e6e4b1c508de96456.definition = {
    methods: ['get', 'head', 'post', 'put', 'patch', 'delete', 'options'],
    url: '/settings/two-factor',
} satisfies RouteDefinition<
    ['get', 'head', 'post', 'put', 'patch', 'delete', 'options']
>;

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.url = (
    options?: RouteQueryOptions,
) => {
    return (
        RedirectController0ad619344ffe7d8e6e4b1c508de96456.definition.url +
        queryParams(options)
    );
};

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'head',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.put = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'put',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.patch = (
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'patch',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.delete = (
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'delete',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456.options = (
    options?: RouteQueryOptions,
): RouteDefinition<'options'> => ({
    url: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'options',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
const RedirectController0ad619344ffe7d8e6e4b1c508de96456Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'get',
});

/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url(options),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.patch = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.delete = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \Illuminate\Routing\RedirectController::__invoke
 * @see vendor/laravel/framework/src/Illuminate/Routing/RedirectController.php:19
 * @route '/settings/two-factor'
 */
RedirectController0ad619344ffe7d8e6e4b1c508de96456Form.options = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: RedirectController0ad619344ffe7d8e6e4b1c508de96456.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'OPTIONS',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

RedirectController0ad619344ffe7d8e6e4b1c508de96456.form =
    RedirectController0ad619344ffe7d8e6e4b1c508de96456Form;

const RedirectController = {
    '/preview-perjanjian-kerja':
        RedirectController6d8dcb84106e012ead9e7436010d4b11,
    '/settings': RedirectController4b87d2df7e3aa853f6720faea796e36c,
    '/settings/profile': RedirectControllerfc6874003af373efc88e5e18eecd9c17,
    '/settings/password': RedirectController3d83238d90f1e3969ebe570175cfec66,
    '/settings/appearance': RedirectControllere19ee86e9cf603ce1a59a1ec5d21dec5,
    '/settings/two-factor': RedirectController0ad619344ffe7d8e6e4b1c508de96456,
};

export default RedirectController;
