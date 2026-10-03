import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
export const downloadTemplate = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});

downloadTemplate.definition = {
    methods: ['get', 'head'],
    url: '/petugas/template/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
downloadTemplate.url = (options?: RouteQueryOptions) => {
    return downloadTemplate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
downloadTemplate.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
downloadTemplate.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadTemplate.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
const downloadTemplateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
downloadTemplateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::downloadTemplate
 * @see app/Http/Controllers/PetugasController.php:427
 * @route '/petugas/template/download'
 */
downloadTemplateForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadTemplate.form = downloadTemplateForm;
/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
export const downloadExisting = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadExisting.url(options),
    method: 'get',
});

downloadExisting.definition = {
    methods: ['get', 'head'],
    url: '/petugas/existing/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
downloadExisting.url = (options?: RouteQueryOptions) => {
    return downloadExisting.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
downloadExisting.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadExisting.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
downloadExisting.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadExisting.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
const downloadExistingForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadExisting.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
downloadExistingForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadExisting.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PetugasController::downloadExisting
 * @see app/Http/Controllers/PetugasController.php:435
 * @route '/petugas/existing/download'
 */
downloadExistingForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadExisting.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadExisting.form = downloadExistingForm;
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
 * @route '/petugas/{petugas}/edit'
 */
const updatee4ea2b8f04e8e553886d4819f5dfa14f = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, options),
    method: 'put',
});

updatee4ea2b8f04e8e553886d4819f5dfa14f.definition = {
    methods: ['put', 'patch'],
    url: '/petugas/{petugas}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}/edit'
 */
updatee4ea2b8f04e8e553886d4819f5dfa14f.url = (
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
        updatee4ea2b8f04e8e553886d4819f5dfa14f.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}/edit'
 */
updatee4ea2b8f04e8e553886d4819f5dfa14f.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}/edit'
 */
updatee4ea2b8f04e8e553886d4819f5dfa14f.patch = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}/edit'
 */
const updatee4ea2b8f04e8e553886d4819f5dfa14fForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, {
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
 * @route '/petugas/{petugas}/edit'
 */
updatee4ea2b8f04e8e553886d4819f5dfa14fForm.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, {
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
 * @route '/petugas/{petugas}/edit'
 */
updatee4ea2b8f04e8e553886d4819f5dfa14fForm.patch = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee4ea2b8f04e8e553886d4819f5dfa14f.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatee4ea2b8f04e8e553886d4819f5dfa14f.form =
    updatee4ea2b8f04e8e553886d4819f5dfa14fForm;
/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
const update9cd52b91506bb668d65269839d08575b = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update9cd52b91506bb668d65269839d08575b.url(args, options),
    method: 'put',
});

update9cd52b91506bb668d65269839d08575b.definition = {
    methods: ['put'],
    url: '/petugas/{petugas}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update9cd52b91506bb668d65269839d08575b.url = (
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
        update9cd52b91506bb668d65269839d08575b.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update9cd52b91506bb668d65269839d08575b.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update9cd52b91506bb668d65269839d08575b.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
const update9cd52b91506bb668d65269839d08575bForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update9cd52b91506bb668d65269839d08575b.url(args, {
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
update9cd52b91506bb668d65269839d08575bForm.put = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update9cd52b91506bb668d65269839d08575b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update9cd52b91506bb668d65269839d08575b.form =
    update9cd52b91506bb668d65269839d08575bForm;
/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
const update9cd52b91506bb668d65269839d08575b = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update9cd52b91506bb668d65269839d08575b.url(args, options),
    method: 'patch',
});

update9cd52b91506bb668d65269839d08575b.definition = {
    methods: ['patch'],
    url: '/petugas/{petugas}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update9cd52b91506bb668d65269839d08575b.url = (
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
        update9cd52b91506bb668d65269839d08575b.definition.url
            .replace('{petugas}', parsedArgs.petugas.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
update9cd52b91506bb668d65269839d08575b.patch = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update9cd52b91506bb668d65269839d08575b.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\PetugasController::update
 * @see app/Http/Controllers/PetugasController.php:360
 * @route '/petugas/{petugas}'
 */
const update9cd52b91506bb668d65269839d08575bForm = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update9cd52b91506bb668d65269839d08575b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
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
update9cd52b91506bb668d65269839d08575bForm.patch = (
    args:
        | { petugas: string | number }
        | [petugas: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update9cd52b91506bb668d65269839d08575b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update9cd52b91506bb668d65269839d08575b.form =
    update9cd52b91506bb668d65269839d08575bForm;

export const update = {
    '/petugas/{petugas}/edit': updatee4ea2b8f04e8e553886d4819f5dfa14f,
    '/petugas/{petugas}': update9cd52b91506bb668d65269839d08575b,
    '/petugas/{petugas}': update9cd52b91506bb668d65269839d08575b,
};

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
const PetugasController = {
    downloadTemplate,
    downloadExisting,
    importPreview,
    importMethod,
    batchUpdate,
    create,
    store,
    edit,
    update,
    destroy,
    index,
    show,
    import: importMethod,
};

export default PetugasController;
