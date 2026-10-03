import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/dipa',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DipaController::index
 * @see app/Http/Controllers/DipaController.php:19
 * @route '/dipa'
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
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/dipa/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::create
 * @see app/Http/Controllers/DipaController.php:86
 * @route '/dipa/create'
 */
createForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

create.form = createForm;
/**
 * @see \App\Http\Controllers\DipaController::store
 * @see app/Http/Controllers/DipaController.php:100
 * @route '/dipa/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/dipa/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\DipaController::store
 * @see app/Http/Controllers/DipaController.php:100
 * @route '/dipa/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DipaController::store
 * @see app/Http/Controllers/DipaController.php:100
 * @route '/dipa/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DipaController::store
 * @see app/Http/Controllers/DipaController.php:100
 * @route '/dipa/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DipaController::store
 * @see app/Http/Controllers/DipaController.php:100
 * @route '/dipa/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
export const edit = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/dipa/{dipa}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
edit.url = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { dipa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { dipa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            dipa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        dipa: typeof args.dipa === 'object' ? args.dipa.id : args.dipa,
    };

    return (
        edit.definition.url
            .replace('{dipa}', parsedArgs.dipa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
edit.get = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
edit.head = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
const editForm = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
editForm.get = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DipaController::edit
 * @see app/Http/Controllers/DipaController.php:146
 * @route '/dipa/{dipa}/edit'
 */
editForm.head = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
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
 * @see \App\Http\Controllers\DipaController::update
 * @see app/Http/Controllers/DipaController.php:163
 * @route '/dipa/{dipa}'
 */
export const update = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/dipa/{dipa}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\DipaController::update
 * @see app/Http/Controllers/DipaController.php:163
 * @route '/dipa/{dipa}'
 */
update.url = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { dipa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { dipa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            dipa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        dipa: typeof args.dipa === 'object' ? args.dipa.id : args.dipa,
    };

    return (
        update.definition.url
            .replace('{dipa}', parsedArgs.dipa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DipaController::update
 * @see app/Http/Controllers/DipaController.php:163
 * @route '/dipa/{dipa}'
 */
update.put = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\DipaController::update
 * @see app/Http/Controllers/DipaController.php:163
 * @route '/dipa/{dipa}'
 */
const updateForm = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DipaController::update
 * @see app/Http/Controllers/DipaController.php:163
 * @route '/dipa/{dipa}'
 */
updateForm.put = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update.form = updateForm;
/**
 * @see \App\Http\Controllers\DipaController::destroy
 * @see app/Http/Controllers/DipaController.php:202
 * @route '/dipa/{dipa}'
 */
export const destroy = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/dipa/{dipa}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\DipaController::destroy
 * @see app/Http/Controllers/DipaController.php:202
 * @route '/dipa/{dipa}'
 */
destroy.url = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { dipa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { dipa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            dipa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        dipa: typeof args.dipa === 'object' ? args.dipa.id : args.dipa,
    };

    return (
        destroy.definition.url
            .replace('{dipa}', parsedArgs.dipa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DipaController::destroy
 * @see app/Http/Controllers/DipaController.php:202
 * @route '/dipa/{dipa}'
 */
destroy.delete = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\DipaController::destroy
 * @see app/Http/Controllers/DipaController.php:202
 * @route '/dipa/{dipa}'
 */
const destroyForm = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DipaController::destroy
 * @see app/Http/Controllers/DipaController.php:202
 * @route '/dipa/{dipa}'
 */
destroyForm.delete = (
    args:
        | { dipa: number | { id: number } }
        | [dipa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroy.form = destroyForm;
const dipa = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default dipa;
