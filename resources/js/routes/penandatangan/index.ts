import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/penandatangan',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::index
 * @see app/Http/Controllers/PenandatanganController.php:18
 * @route '/penandatangan'
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
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/penandatangan/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::create
 * @see app/Http/Controllers/PenandatanganController.php:76
 * @route '/penandatangan/create'
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
 * @see \App\Http\Controllers\PenandatanganController::store
 * @see app/Http/Controllers/PenandatanganController.php:84
 * @route '/penandatangan/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/penandatangan/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::store
 * @see app/Http/Controllers/PenandatanganController.php:84
 * @route '/penandatangan/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PenandatanganController::store
 * @see app/Http/Controllers/PenandatanganController.php:84
 * @route '/penandatangan/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::store
 * @see app/Http/Controllers/PenandatanganController.php:84
 * @route '/penandatangan/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::store
 * @see app/Http/Controllers/PenandatanganController.php:84
 * @route '/penandatangan/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
export const edit = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/penandatangan/{penandatangan}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
edit.url = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { penandatangan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { penandatangan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            penandatangan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        penandatangan:
            typeof args.penandatangan === 'object'
                ? args.penandatangan.id
                : args.penandatangan,
    };

    return (
        edit.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
edit.get = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
edit.head = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
const editForm = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
editForm.get = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::edit
 * @see app/Http/Controllers/PenandatanganController.php:126
 * @route '/penandatangan/{penandatangan}/edit'
 */
editForm.head = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
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
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
export const update = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/penandatangan/{penandatangan}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
update.url = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { penandatangan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { penandatangan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            penandatangan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        penandatangan:
            typeof args.penandatangan === 'object'
                ? args.penandatangan.id
                : args.penandatangan,
    };

    return (
        update.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
update.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
const updateForm = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
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
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
updateForm.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
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
 * @see \App\Http\Controllers\PenandatanganController::destroy
 * @see app/Http/Controllers/PenandatanganController.php:170
 * @route '/penandatangan/{penandatangan}'
 */
export const destroy = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/penandatangan/{penandatangan}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::destroy
 * @see app/Http/Controllers/PenandatanganController.php:170
 * @route '/penandatangan/{penandatangan}'
 */
destroy.url = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { penandatangan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { penandatangan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            penandatangan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        penandatangan:
            typeof args.penandatangan === 'object'
                ? args.penandatangan.id
                : args.penandatangan,
    };

    return (
        destroy.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::destroy
 * @see app/Http/Controllers/PenandatanganController.php:170
 * @route '/penandatangan/{penandatangan}'
 */
destroy.delete = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::destroy
 * @see app/Http/Controllers/PenandatanganController.php:170
 * @route '/penandatangan/{penandatangan}'
 */
const destroyForm = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
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
 * @see \App\Http\Controllers\PenandatanganController::destroy
 * @see app/Http/Controllers/PenandatanganController.php:170
 * @route '/penandatangan/{penandatangan}'
 */
destroyForm.delete = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
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
const penandatangan = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default penandatangan;
