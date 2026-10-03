import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import review from './review';
/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
export const template = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});

template.definition = {
    methods: ['get', 'head'],
    url: '/petugas/template/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
template.url = (options?: RouteQueryOptions) => {
    return template.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
template.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
template.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: template.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
const templateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
templateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::template
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
templateForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

template.form = templateForm;
/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
export const existing = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: existing.url(options),
    method: 'get',
});

existing.definition = {
    methods: ['get', 'head'],
    url: '/petugas/existing/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
existing.url = (options?: RouteQueryOptions) => {
    return existing.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
existing.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: existing.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
existing.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: existing.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
const existingForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: existing.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
existingForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: existing.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::existing
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
existingForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: existing.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

existing.form = existingForm;
/**
 * @see \App\Http\Controllers\PetugasController::importPreview
 * @see app/Http/Controllers/PetugasController.php:443
 * @route '/petugas/import-preview'
 */
export const importPreview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

importPreview.definition = {
    methods: ['post'],
    url: '/petugas/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PetugasController::importPreview
 * @see app/Http/Controllers/PetugasController.php:443
 * @route '/petugas/import-preview'
 */
importPreview.url = (options?: RouteQueryOptions) => {
    return importPreview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::importPreview
 * @see app/Http/Controllers/PetugasController.php:443
 * @route '/petugas/import-preview'
 */
importPreview.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::importPreview
 * @see app/Http/Controllers/PetugasController.php:443
 * @route '/petugas/import-preview'
 */
const importPreviewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::importPreview
 * @see app/Http/Controllers/PetugasController.php:443
 * @route '/petugas/import-preview'
 */
importPreviewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

importPreview.form = importPreviewForm;
/**
 * @see \App\Http\Controllers\PetugasController::importMethod
 * @see app/Http/Controllers/PetugasController.php:474
 * @route '/petugas/import'
 */
export const importMethod = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(options),
    method: 'post',
});

importMethod.definition = {
    methods: ['post'],
    url: '/petugas/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PetugasController::importMethod
 * @see app/Http/Controllers/PetugasController.php:474
 * @route '/petugas/import'
 */
importMethod.url = (options?: RouteQueryOptions) => {
    return importMethod.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::importMethod
 * @see app/Http/Controllers/PetugasController.php:474
 * @route '/petugas/import'
 */
importMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::importMethod
 * @see app/Http/Controllers/PetugasController.php:474
 * @route '/petugas/import'
 */
const importMethodForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::importMethod
 * @see app/Http/Controllers/PetugasController.php:474
 * @route '/petugas/import'
 */
importMethodForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(options),
    method: 'post',
});

importMethod.form = importMethodForm;
/**
 * @see \App\Http\Controllers\PetugasController::batchUpdate
 * @see app/Http/Controllers/PetugasController.php:554
 * @route '/petugas/batch-update'
 */
export const batchUpdate = (
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: batchUpdate.url(options),
    method: 'put',
});

batchUpdate.definition = {
    methods: ['put'],
    url: '/petugas/batch-update',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\PetugasController::batchUpdate
 * @see app/Http/Controllers/PetugasController.php:554
 * @route '/petugas/batch-update'
 */
batchUpdate.url = (options?: RouteQueryOptions) => {
    return batchUpdate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::batchUpdate
 * @see app/Http/Controllers/PetugasController.php:554
 * @route '/petugas/batch-update'
 */
batchUpdate.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: batchUpdate.url(options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\PetugasController::batchUpdate
 * @see app/Http/Controllers/PetugasController.php:554
 * @route '/petugas/batch-update'
 */
const batchUpdateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: batchUpdate.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::batchUpdate
 * @see app/Http/Controllers/PetugasController.php:554
 * @route '/petugas/batch-update'
 */
batchUpdateForm.put = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: batchUpdate.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

batchUpdate.form = batchUpdateForm;
/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/petugas/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::create
 * @see app/Http/Controllers/PetugasController.php:89
 * @route '/petugas/create'
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
 * @see \App\Http\Controllers\PetugasController::store
 * @see app/Http/Controllers/PetugasController.php:97
 * @route '/petugas'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/petugas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PetugasController::store
 * @see app/Http/Controllers/PetugasController.php:97
 * @route '/petugas'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::store
 * @see app/Http/Controllers/PetugasController.php:97
 * @route '/petugas'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::store
 * @see app/Http/Controllers/PetugasController.php:97
 * @route '/petugas'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PetugasController::store
 * @see app/Http/Controllers/PetugasController.php:97
 * @route '/petugas'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
export const edit = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/petugas/{petugas}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
edit.url = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petugas: args };
    }

    if (Array.isArray(args)) {
        args = {
            petugas: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        petugas: args.petugas,
    };

    return (
        edit.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
edit.get = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
edit.head = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
const editForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
editForm.get = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::edit
 * @see app/Http/Controllers/PetugasController.php:338
 * @route '/petugas/{petugas}/edit'
 */
editForm.head = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
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
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
export const update = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/petugas/{petugas}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update.url = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petugas: args };
    }

    if (Array.isArray(args)) {
        args = {
            petugas: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        petugas: args.petugas,
    };

    return (
        update.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
const updateForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
updateForm.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
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
 * @see \App\Http\Controllers\PetugasController::destroy
 * @see app/Http/Controllers/PetugasController.php:392
 * @route '/petugas/{petugas}'
 */
export const destroy = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/petugas/{petugas}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\PetugasController::destroy
 * @see app/Http/Controllers/PetugasController.php:392
 * @route '/petugas/{petugas}'
 */
destroy.url = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petugas: args };
    }

    if (Array.isArray(args)) {
        args = {
            petugas: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        petugas: args.petugas,
    };

    return (
        destroy.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::destroy
 * @see app/Http/Controllers/PetugasController.php:392
 * @route '/petugas/{petugas}'
 */
destroy.delete = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\PetugasController::destroy
 * @see app/Http/Controllers/PetugasController.php:392
 * @route '/petugas/{petugas}'
 */
const destroyForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
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
 * @see \App\Http\Controllers\PetugasController::destroy
 * @see app/Http/Controllers/PetugasController.php:392
 * @route '/petugas/{petugas}'
 */
destroyForm.delete = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
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
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/petugas',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::index
 * @see app/Http/Controllers/PetugasController.php:33
 * @route '/petugas'
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
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
export const show = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/petugas/{petugas}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
show.url = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petugas: args };
    }

    if (Array.isArray(args)) {
        args = {
            petugas: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        petugas: args.petugas,
    };

    return (
        show.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
show.get = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
show.head = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
const showForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
showForm.get = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::show
 * @see app/Http/Controllers/PetugasController.php:167
 * @route '/petugas/{petugas}'
 */
showForm.head = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
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
const petugas = {
    review: Object.assign(review, review),
    template: Object.assign(template, template),
    existing: Object.assign(existing, existing),
    importPreview: Object.assign(importPreview, importPreview),
    import: Object.assign(importMethod, importMethod),
    batchUpdate: Object.assign(batchUpdate, batchUpdate),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    index: Object.assign(index, index),
    show: Object.assign(show, show),
};

export default petugas;
