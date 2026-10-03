import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
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
 * @route '/users/{user}'
 */
export const update = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

update.definition = {
    methods: ['patch'],
    url: '/users/{user}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
update.url = (
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
        update.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
update.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\UserRoleController::update
 * @see app/Http/Controllers/UserRoleController.php:92
 * @route '/users/{user}'
 */
const updateForm = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
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
updateForm.patch = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update.form = updateForm;
/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
export const reset2fa = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reset2fa.url(args, options),
    method: 'post',
});

reset2fa.definition = {
    methods: ['post'],
    url: '/users/{user}/reset-2fa',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
reset2fa.url = (
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
        reset2fa.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
reset2fa.post = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reset2fa.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
const reset2faForm = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reset2fa.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\ResetUserTwoFactorController::__invoke
 * @see app/Http/Controllers/ResetUserTwoFactorController.php:12
 * @route '/users/{user}/reset-2fa'
 */
reset2faForm.post = (
    args:
        | { user: number | { id: number } }
        | [user: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reset2fa.url(args, options),
    method: 'post',
});

reset2fa.form = reset2faForm;
const users = {
    index: Object.assign(index, index),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    reset2fa: Object.assign(reset2fa, reset2fa),
};

export default users;
