import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
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
 * @route '/penandatangan/{penandatangan}/edit'
 */
const updatea1f1ece836a3bd0a77f95a5fa4e15df6 = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, options),
    method: 'put',
});

updatea1f1ece836a3bd0a77f95a5fa4e15df6.definition = {
    methods: ['put', 'patch'],
    url: '/penandatangan/{penandatangan}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}/edit'
 */
updatea1f1ece836a3bd0a77f95a5fa4e15df6.url = (
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
        updatea1f1ece836a3bd0a77f95a5fa4e15df6.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}/edit'
 */
updatea1f1ece836a3bd0a77f95a5fa4e15df6.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}/edit'
 */
updatea1f1ece836a3bd0a77f95a5fa4e15df6.patch = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}/edit'
 */
const updatea1f1ece836a3bd0a77f95a5fa4e15df6Form = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, {
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
 * @route '/penandatangan/{penandatangan}/edit'
 */
updatea1f1ece836a3bd0a77f95a5fa4e15df6Form.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, {
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
 * @route '/penandatangan/{penandatangan}/edit'
 */
updatea1f1ece836a3bd0a77f95a5fa4e15df6Form.patch = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatea1f1ece836a3bd0a77f95a5fa4e15df6.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatea1f1ece836a3bd0a77f95a5fa4e15df6.form =
    updatea1f1ece836a3bd0a77f95a5fa4e15df6Form;
/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
const updatec49c049fa58e64cea019a34895c7a09a = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatec49c049fa58e64cea019a34895c7a09a.url(args, options),
    method: 'put',
});

updatec49c049fa58e64cea019a34895c7a09a.definition = {
    methods: ['put'],
    url: '/penandatangan/{penandatangan}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
updatec49c049fa58e64cea019a34895c7a09a.url = (
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
        updatec49c049fa58e64cea019a34895c7a09a.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
updatec49c049fa58e64cea019a34895c7a09a.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatec49c049fa58e64cea019a34895c7a09a.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
const updatec49c049fa58e64cea019a34895c7a09aForm = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec49c049fa58e64cea019a34895c7a09a.url(args, {
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
updatec49c049fa58e64cea019a34895c7a09aForm.put = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec49c049fa58e64cea019a34895c7a09a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatec49c049fa58e64cea019a34895c7a09a.form =
    updatec49c049fa58e64cea019a34895c7a09aForm;
/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
const updatec49c049fa58e64cea019a34895c7a09a = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatec49c049fa58e64cea019a34895c7a09a.url(args, options),
    method: 'patch',
});

updatec49c049fa58e64cea019a34895c7a09a.definition = {
    methods: ['patch'],
    url: '/penandatangan/{penandatangan}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
updatec49c049fa58e64cea019a34895c7a09a.url = (
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
        updatec49c049fa58e64cea019a34895c7a09a.definition.url
            .replace('{penandatangan}', parsedArgs.penandatangan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
updatec49c049fa58e64cea019a34895c7a09a.patch = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatec49c049fa58e64cea019a34895c7a09a.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\PenandatanganController::update
 * @see app/Http/Controllers/PenandatanganController.php:136
 * @route '/penandatangan/{penandatangan}'
 */
const updatec49c049fa58e64cea019a34895c7a09aForm = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec49c049fa58e64cea019a34895c7a09a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
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
updatec49c049fa58e64cea019a34895c7a09aForm.patch = (
    args:
        | { penandatangan: number | { id: number } }
        | [penandatangan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec49c049fa58e64cea019a34895c7a09a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatec49c049fa58e64cea019a34895c7a09a.form =
    updatec49c049fa58e64cea019a34895c7a09aForm;

export const update = {
    '/penandatangan/{penandatangan}/edit':
        updatea1f1ece836a3bd0a77f95a5fa4e15df6,
    '/penandatangan/{penandatangan}': updatec49c049fa58e64cea019a34895c7a09a,
    '/penandatangan/{penandatangan}': updatec49c049fa58e64cea019a34895c7a09a,
};

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
const PenandatanganController = { index, create, store, edit, update, destroy };

export default PenandatanganController;
