import {
    applyUrlDefaults,
    queryParams,
    validateParameters,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/sbml',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlController::index
 * @see app/Http/Controllers/SbmlController.php:23
 * @route '/sbml'
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
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
export const show = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/sbml/{tahun}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
show.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        show.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
show.get = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
show.head = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
const showForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
showForm.get = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::show
 * @see app/Http/Controllers/SbmlController.php:127
 * @route '/sbml/{tahun}'
 */
showForm.head = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

show.form = showForm;
/**
 * @see \App\Http\Controllers\SbmlController::destroyYear
 * @see app/Http/Controllers/SbmlController.php:249
 * @route '/sbml/year/{tahun}'
 */
export const destroyYear = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyYear.url(args, options),
    method: 'delete',
});

destroyYear.definition = {
    methods: ['delete'],
    url: '/sbml/year/{tahun}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SbmlController::destroyYear
 * @see app/Http/Controllers/SbmlController.php:249
 * @route '/sbml/year/{tahun}'
 */
destroyYear.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        destroyYear.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::destroyYear
 * @see app/Http/Controllers/SbmlController.php:249
 * @route '/sbml/year/{tahun}'
 */
destroyYear.delete = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyYear.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SbmlController::destroyYear
 * @see app/Http/Controllers/SbmlController.php:249
 * @route '/sbml/year/{tahun}'
 */
const destroyYearForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyYear.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SbmlController::destroyYear
 * @see app/Http/Controllers/SbmlController.php:249
 * @route '/sbml/year/{tahun}'
 */
destroyYearForm.delete = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyYear.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroyYear.form = destroyYearForm;
/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/sbml/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::create
 * @see app/Http/Controllers/SbmlController.php:46
 * @route '/sbml/create'
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
 * @see \App\Http\Controllers\SbmlController::store
 * @see app/Http/Controllers/SbmlController.php:77
 * @route '/sbml'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/sbml',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SbmlController::store
 * @see app/Http/Controllers/SbmlController.php:77
 * @route '/sbml'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SbmlController::store
 * @see app/Http/Controllers/SbmlController.php:77
 * @route '/sbml'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SbmlController::store
 * @see app/Http/Controllers/SbmlController.php:77
 * @route '/sbml'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SbmlController::store
 * @see app/Http/Controllers/SbmlController.php:77
 * @route '/sbml'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
export const edit = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/sbml/{tahun}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
edit.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        edit.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
edit.get = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
edit.head = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
const editForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
editForm.get = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::edit
 * @see app/Http/Controllers/SbmlController.php:154
 * @route '/sbml/{tahun}/edit'
 */
editForm.head = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\SbmlController::update
 * @see app/Http/Controllers/SbmlController.php:190
 * @route '/sbml/{tahun}'
 */
export const update = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

update.definition = {
    methods: ['patch'],
    url: '/sbml/{tahun}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\SbmlController::update
 * @see app/Http/Controllers/SbmlController.php:190
 * @route '/sbml/{tahun}'
 */
update.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        update.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::update
 * @see app/Http/Controllers/SbmlController.php:190
 * @route '/sbml/{tahun}'
 */
update.patch = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SbmlController::update
 * @see app/Http/Controllers/SbmlController.php:190
 * @route '/sbml/{tahun}'
 */
const updateForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\SbmlController::update
 * @see app/Http/Controllers/SbmlController.php:190
 * @route '/sbml/{tahun}'
 */
updateForm.patch = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\SbmlController::destroy
 * @see app/Http/Controllers/SbmlController.php:225
 * @route '/sbml/{tahun}'
 */
export const destroy = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/sbml/{tahun}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SbmlController::destroy
 * @see app/Http/Controllers/SbmlController.php:225
 * @route '/sbml/{tahun}'
 */
destroy.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        destroy.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::destroy
 * @see app/Http/Controllers/SbmlController.php:225
 * @route '/sbml/{tahun}'
 */
destroy.delete = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SbmlController::destroy
 * @see app/Http/Controllers/SbmlController.php:225
 * @route '/sbml/{tahun}'
 */
const destroyForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\SbmlController::destroy
 * @see app/Http/Controllers/SbmlController.php:225
 * @route '/sbml/{tahun}'
 */
destroyForm.delete = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
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
/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
export const exportTemplate = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});

exportTemplate.definition = {
    methods: ['get', 'head'],
    url: '/sbml/{tahun}/export/{type?}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
exportTemplate.url = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
            type: args[1],
        };
    }

    args = applyUrlDefaults(args);

    validateParameters(args, ['type']);

    const parsedArgs = {
        tahun: args.tahun,
        type: args.type,
    };

    return (
        exportTemplate.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{type?}', parsedArgs.type?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
exportTemplate.get = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
exportTemplate.head = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportTemplate.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
const exportTemplateForm = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
exportTemplateForm.get = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlController::exportTemplate
 * @see app/Http/Controllers/SbmlController.php:273
 * @route '/sbml/{tahun}/export/{type?}'
 */
exportTemplateForm.head = (
    args:
        | { tahun: string | number; type?: string | number }
        | [tahun: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportTemplate.form = exportTemplateForm;
/**
 * @see \App\Http\Controllers\SbmlController::importMethod
 * @see app/Http/Controllers/SbmlController.php:284
 * @route '/sbml/{tahun}/import'
 */
export const importMethod = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

importMethod.definition = {
    methods: ['post'],
    url: '/sbml/{tahun}/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SbmlController::importMethod
 * @see app/Http/Controllers/SbmlController.php:284
 * @route '/sbml/{tahun}/import'
 */
importMethod.url = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tahun: args };
    }

    if (Array.isArray(args)) {
        args = {
            tahun: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        tahun: args.tahun,
    };

    return (
        importMethod.definition.url
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SbmlController::importMethod
 * @see app/Http/Controllers/SbmlController.php:284
 * @route '/sbml/{tahun}/import'
 */
importMethod.post = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SbmlController::importMethod
 * @see app/Http/Controllers/SbmlController.php:284
 * @route '/sbml/{tahun}/import'
 */
const importMethodForm = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SbmlController::importMethod
 * @see app/Http/Controllers/SbmlController.php:284
 * @route '/sbml/{tahun}/import'
 */
importMethodForm.post = (
    args:
        | { tahun: string | number }
        | [tahun: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

importMethod.form = importMethodForm;
/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
export const report = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
});

report.definition = {
    methods: ['get', 'post', 'head'],
    url: '/rekap-honor',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
report.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: report.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
report.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: report.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
report.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: report.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
const reportForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: report.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
reportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: report.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
reportForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: report.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SbmlReportController::report
 * @see app/Http/Controllers/SbmlReportController.php:21
 * @route '/rekap-honor'
 */
reportForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: report.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

report.form = reportForm;
const sbml = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    destroyYear: Object.assign(destroyYear, destroyYear),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    exportTemplate: Object.assign(exportTemplate, exportTemplate),
    import: Object.assign(importMethod, importMethod),
    report: Object.assign(report, report),
};

export default sbml;
