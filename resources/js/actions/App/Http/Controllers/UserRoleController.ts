import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/users',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\UserRoleController::index
 * @see app/Http/Controllers/UserRoleController.php:22
 * @route '/users'
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
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
export const edit = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/users/{user}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
edit.url = (
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
        edit.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
edit.get = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
edit.head = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
const editForm = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
editForm.get = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\UserRoleController::edit
 * @see app/Http/Controllers/UserRoleController.php:75
 * @route '/users/{user}/edit'
 */
editForm.head = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

edit.form = editForm;
/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
const update7c8ee6634e997e7396d4cd79528adad3 = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update7c8ee6634e997e7396d4cd79528adad3.url(args, options),
    method: 'put',
});

update7c8ee6634e997e7396d4cd79528adad3.definition = {
    methods: ['put', 'patch'],
    url: '/users/{user}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
update7c8ee6634e997e7396d4cd79528adad3.url = (
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
        update7c8ee6634e997e7396d4cd79528adad3.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
update7c8ee6634e997e7396d4cd79528adad3.put = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update7c8ee6634e997e7396d4cd79528adad3.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
update7c8ee6634e997e7396d4cd79528adad3.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update7c8ee6634e997e7396d4cd79528adad3.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
const update7c8ee6634e997e7396d4cd79528adad3Form = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update7c8ee6634e997e7396d4cd79528adad3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
update7c8ee6634e997e7396d4cd79528adad3Form.put = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update7c8ee6634e997e7396d4cd79528adad3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}/edit'
 */
update7c8ee6634e997e7396d4cd79528adad3Form.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update7c8ee6634e997e7396d4cd79528adad3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update7c8ee6634e997e7396d4cd79528adad3.form =
    update7c8ee6634e997e7396d4cd79528adad3Form;
/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
const updatef898f2daa993cc45af847e1a1f899673 = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatef898f2daa993cc45af847e1a1f899673.url(args, options),
    method: 'patch',
});

updatef898f2daa993cc45af847e1a1f899673.definition = {
    methods: ['patch'],
    url: '/users/{user}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
updatef898f2daa993cc45af847e1a1f899673.url = (
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
        updatef898f2daa993cc45af847e1a1f899673.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
updatef898f2daa993cc45af847e1a1f899673.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatef898f2daa993cc45af847e1a1f899673.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
const updatef898f2daa993cc45af847e1a1f899673Form = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatef898f2daa993cc45af847e1a1f899673.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
updatef898f2daa993cc45af847e1a1f899673Form.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatef898f2daa993cc45af847e1a1f899673.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatef898f2daa993cc45af847e1a1f899673.form =
    updatef898f2daa993cc45af847e1a1f899673Form;

export const update = {
    '/users/{user}/edit': update7c8ee6634e997e7396d4cd79528adad3,
    '/users/{user}': updatef898f2daa993cc45af847e1a1f899673,
};

const UserRoleController = { index, edit, update };

export default UserRoleController;
