import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/dasar-hukum',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::index
 * @see app/Http/Controllers/DasarHukumController.php:16
 * @route '/dasar-hukum'
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
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/dasar-hukum/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::create
 * @see app/Http/Controllers/DasarHukumController.php:52
 * @route '/dasar-hukum/create'
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
 * @see \App\Http\Controllers\DasarHukumController::store
 * @see app/Http/Controllers/DasarHukumController.php:73
 * @route '/dasar-hukum/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/dasar-hukum/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::store
 * @see app/Http/Controllers/DasarHukumController.php:73
 * @route '/dasar-hukum/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DasarHukumController::store
 * @see app/Http/Controllers/DasarHukumController.php:73
 * @route '/dasar-hukum/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::store
 * @see app/Http/Controllers/DasarHukumController.php:73
 * @route '/dasar-hukum/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::store
 * @see app/Http/Controllers/DasarHukumController.php:73
 * @route '/dasar-hukum/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'post', 'head'],
    url: '/dasar-hukum/edit',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
edit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: edit.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
const editForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
editForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
editForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: edit.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\DasarHukumController::edit
 * @see app/Http/Controllers/DasarHukumController.php:155
 * @route '/dasar-hukum/edit'
 */
editForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

edit.form = editForm;
/**
 * @see \App\Http\Controllers\DasarHukumController::update
 * @see app/Http/Controllers/DasarHukumController.php:201
 * @route '/dasar-hukum/{dasarHukum}'
 */
export const update = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

update.definition = {
    methods: ['patch'],
    url: '/dasar-hukum/{dasarHukum}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::update
 * @see app/Http/Controllers/DasarHukumController.php:201
 * @route '/dasar-hukum/{dasarHukum}'
 */
update.url = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { dasarHukum: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { dasarHukum: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            dasarHukum: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        dasarHukum:
            typeof args.dasarHukum === 'object'
                ? args.dasarHukum.id
                : args.dasarHukum,
    };

    return (
        update.definition.url
            .replace('{dasarHukum}', parsedArgs.dasarHukum.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DasarHukumController::update
 * @see app/Http/Controllers/DasarHukumController.php:201
 * @route '/dasar-hukum/{dasarHukum}'
 */
update.patch = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::update
 * @see app/Http/Controllers/DasarHukumController.php:201
 * @route '/dasar-hukum/{dasarHukum}'
 */
const updateForm = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
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
 * @see \App\Http\Controllers\DasarHukumController::update
 * @see app/Http/Controllers/DasarHukumController.php:201
 * @route '/dasar-hukum/{dasarHukum}'
 */
updateForm.patch = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
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
 * @see \App\Http\Controllers\DasarHukumController::destroy
 * @see app/Http/Controllers/DasarHukumController.php:284
 * @route '/dasar-hukum/{dasarHukum}'
 */
export const destroy = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/dasar-hukum/{dasarHukum}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\DasarHukumController::destroy
 * @see app/Http/Controllers/DasarHukumController.php:284
 * @route '/dasar-hukum/{dasarHukum}'
 */
destroy.url = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { dasarHukum: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { dasarHukum: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            dasarHukum: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        dasarHukum:
            typeof args.dasarHukum === 'object'
                ? args.dasarHukum.id
                : args.dasarHukum,
    };

    return (
        destroy.definition.url
            .replace('{dasarHukum}', parsedArgs.dasarHukum.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DasarHukumController::destroy
 * @see app/Http/Controllers/DasarHukumController.php:284
 * @route '/dasar-hukum/{dasarHukum}'
 */
destroy.delete = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\DasarHukumController::destroy
 * @see app/Http/Controllers/DasarHukumController.php:284
 * @route '/dasar-hukum/{dasarHukum}'
 */
const destroyForm = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
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
 * @see \App\Http\Controllers\DasarHukumController::destroy
 * @see app/Http/Controllers/DasarHukumController.php:284
 * @route '/dasar-hukum/{dasarHukum}'
 */
destroyForm.delete = (
    args:
        | { dasarHukum: number | { id: number } }
        | [dasarHukum: number | { id: number }]
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
const dasarHukum = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default dasarHukum;
