import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::index
 * @see app/Http/Controllers/BastController.php:2184
 * @route '/berita-acara'
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
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
export const listByMonth = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: listByMonth.url(options),
    method: 'get',
});

listByMonth.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/list',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
listByMonth.url = (options?: RouteQueryOptions) => {
    return listByMonth.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
listByMonth.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listByMonth.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
listByMonth.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listByMonth.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
const listByMonthForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
listByMonthForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::listByMonth
 * @see app/Http/Controllers/BastController.php:2401
 * @route '/berita-acara/list'
 */
listByMonthForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

listByMonth.form = listByMonthForm;
/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::create
 * @see app/Http/Controllers/BastController.php:2856
 * @route '/berita-acara/create'
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
 * @see \App\Http\Controllers\BastController::generateBatch
 * @see app/Http/Controllers/BastController.php:3224
 * @route '/berita-acara/generate-batch'
 */
export const generateBatch = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatch.url(options),
    method: 'post',
});

generateBatch.definition = {
    methods: ['post'],
    url: '/berita-acara/generate-batch',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::generateBatch
 * @see app/Http/Controllers/BastController.php:3224
 * @route '/berita-acara/generate-batch'
 */
generateBatch.url = (options?: RouteQueryOptions) => {
    return generateBatch.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::generateBatch
 * @see app/Http/Controllers/BastController.php:3224
 * @route '/berita-acara/generate-batch'
 */
generateBatch.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatch.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::generateBatch
 * @see app/Http/Controllers/BastController.php:3224
 * @route '/berita-acara/generate-batch'
 */
const generateBatchForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatch.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::generateBatch
 * @see app/Http/Controllers/BastController.php:3224
 * @route '/berita-acara/generate-batch'
 */
generateBatchForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatch.url(options),
    method: 'post',
});

generateBatch.form = generateBatchForm;
/**
 * @see \App\Http\Controllers\BastController::previewForSpk
 * @see app/Http/Controllers/BastController.php:4383
 * @route '/berita-acara/preview-bast'
 */
export const previewForSpk = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewForSpk.url(options),
    method: 'post',
});

previewForSpk.definition = {
    methods: ['post'],
    url: '/berita-acara/preview-bast',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::previewForSpk
 * @see app/Http/Controllers/BastController.php:4383
 * @route '/berita-acara/preview-bast'
 */
previewForSpk.url = (options?: RouteQueryOptions) => {
    return previewForSpk.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::previewForSpk
 * @see app/Http/Controllers/BastController.php:4383
 * @route '/berita-acara/preview-bast'
 */
previewForSpk.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewForSpk.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::previewForSpk
 * @see app/Http/Controllers/BastController.php:4383
 * @route '/berita-acara/preview-bast'
 */
const previewForSpkForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewForSpk.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::previewForSpk
 * @see app/Http/Controllers/BastController.php:4383
 * @route '/berita-acara/preview-bast'
 */
previewForSpkForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewForSpk.url(options),
    method: 'post',
});

previewForSpk.form = previewForSpkForm;
/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
export const downloadAll = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadAll.url(options),
    method: 'get',
});

downloadAll.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/download-all',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
downloadAll.url = (options?: RouteQueryOptions) => {
    return downloadAll.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
downloadAll.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadAll.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
downloadAll.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadAll.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
const downloadAllForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAll.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
downloadAllForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAll.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadAll
 * @see app/Http/Controllers/BastController.php:5757
 * @route '/berita-acara/download-all'
 */
downloadAllForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAll.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadAll.form = downloadAllForm;
/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
export const createForKegiatan = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createForKegiatan.url(args, options),
    method: 'get',
});

createForKegiatan.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/kegiatan/{kegiatan}/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
createForKegiatan.url = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
    };

    return (
        createForKegiatan.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
createForKegiatan.get = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createForKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
createForKegiatan.head = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: createForKegiatan.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
const createForKegiatanForm = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createForKegiatan.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
createForKegiatanForm.get = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createForKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::createForKegiatan
 * @see app/Http/Controllers/BastController.php:0
 * @route '/berita-acara/kegiatan/{kegiatan}/create'
 */
createForKegiatanForm.head = (
    args:
        | { kegiatan: string | number }
        | [kegiatan: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createForKegiatan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

createForKegiatan.form = createForKegiatanForm;
/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:5927
 * @route '/berita-acara/preview'
 */
export const preview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
});

preview.definition = {
    methods: ['post'],
    url: '/berita-acara/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:5927
 * @route '/berita-acara/preview'
 */
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:5927
 * @route '/berita-acara/preview'
 */
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:5927
 * @route '/berita-acara/preview'
 */
const previewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:5927
 * @route '/berita-acara/preview'
 */
previewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
});

preview.form = previewForm;
/**
 * @see \App\Http\Controllers\BastController::store
 * @see app/Http/Controllers/BastController.php:6130
 * @route '/berita-acara'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/berita-acara',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::store
 * @see app/Http/Controllers/BastController.php:6130
 * @route '/berita-acara'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::store
 * @see app/Http/Controllers/BastController.php:6130
 * @route '/berita-acara'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::store
 * @see app/Http/Controllers/BastController.php:6130
 * @route '/berita-acara'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::store
 * @see app/Http/Controllers/BastController.php:6130
 * @route '/berita-acara'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
export const edit = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/{bast}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
edit.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        edit.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
edit.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
edit.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
const editForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
editForm.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::edit
 * @see app/Http/Controllers/BastController.php:6962
 * @route '/berita-acara/{bast}/edit'
 */
editForm.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
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
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
const update260878126d0d30d6dc63a5d48eaf553b = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update260878126d0d30d6dc63a5d48eaf553b.url(args, options),
    method: 'put',
});

update260878126d0d30d6dc63a5d48eaf553b.definition = {
    methods: ['put', 'patch'],
    url: '/berita-acara/{bast}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
update260878126d0d30d6dc63a5d48eaf553b.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        update260878126d0d30d6dc63a5d48eaf553b.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
update260878126d0d30d6dc63a5d48eaf553b.put = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update260878126d0d30d6dc63a5d48eaf553b.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
update260878126d0d30d6dc63a5d48eaf553b.patch = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update260878126d0d30d6dc63a5d48eaf553b.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
const update260878126d0d30d6dc63a5d48eaf553bForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update260878126d0d30d6dc63a5d48eaf553b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
update260878126d0d30d6dc63a5d48eaf553bForm.put = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update260878126d0d30d6dc63a5d48eaf553b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}/edit'
 */
update260878126d0d30d6dc63a5d48eaf553bForm.patch = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update260878126d0d30d6dc63a5d48eaf553b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update260878126d0d30d6dc63a5d48eaf553b.form =
    update260878126d0d30d6dc63a5d48eaf553bForm;
/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
const update98cd777186f77500f39c7ce32aad0b2c = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update98cd777186f77500f39c7ce32aad0b2c.url(args, options),
    method: 'put',
});

update98cd777186f77500f39c7ce32aad0b2c.definition = {
    methods: ['put'],
    url: '/berita-acara/{bast}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2c.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        update98cd777186f77500f39c7ce32aad0b2c.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2c.put = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update98cd777186f77500f39c7ce32aad0b2c.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
const update98cd777186f77500f39c7ce32aad0b2cForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update98cd777186f77500f39c7ce32aad0b2c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2cForm.put = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update98cd777186f77500f39c7ce32aad0b2c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update98cd777186f77500f39c7ce32aad0b2c.form =
    update98cd777186f77500f39c7ce32aad0b2cForm;
/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
const update98cd777186f77500f39c7ce32aad0b2c = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update98cd777186f77500f39c7ce32aad0b2c.url(args, options),
    method: 'patch',
});

update98cd777186f77500f39c7ce32aad0b2c.definition = {
    methods: ['patch'],
    url: '/berita-acara/{bast}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2c.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        update98cd777186f77500f39c7ce32aad0b2c.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2c.patch = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update98cd777186f77500f39c7ce32aad0b2c.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
const update98cd777186f77500f39c7ce32aad0b2cForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update98cd777186f77500f39c7ce32aad0b2c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::update
 * @see app/Http/Controllers/BastController.php:6970
 * @route '/berita-acara/{bast}'
 */
update98cd777186f77500f39c7ce32aad0b2cForm.patch = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update98cd777186f77500f39c7ce32aad0b2c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update98cd777186f77500f39c7ce32aad0b2c.form =
    update98cd777186f77500f39c7ce32aad0b2cForm;

export const update = {
    '/berita-acara/{bast}/edit': update260878126d0d30d6dc63a5d48eaf553b,
    '/berita-acara/{bast}': update98cd777186f77500f39c7ce32aad0b2c,
    '/berita-acara/{bast}': update98cd777186f77500f39c7ce32aad0b2c,
};

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:6886
 * @route '/berita-acara/{bast}/upload-signed'
 */
export const uploadSigned = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.definition = {
    methods: ['post'],
    url: '/berita-acara/{bast}/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:6886
 * @route '/berita-acara/{bast}/upload-signed'
 */
uploadSigned.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        uploadSigned.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:6886
 * @route '/berita-acara/{bast}/upload-signed'
 */
uploadSigned.post = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:6886
 * @route '/berita-acara/{bast}/upload-signed'
 */
const uploadSignedForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:6886
 * @route '/berita-acara/{bast}/upload-signed'
 */
uploadSignedForm.post = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.form = uploadSignedForm;
/**
 * @see \App\Http\Controllers\BastController::destroy
 * @see app/Http/Controllers/BastController.php:6978
 * @route '/berita-acara/{bast}'
 */
export const destroy = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/berita-acara/{bast}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\BastController::destroy
 * @see app/Http/Controllers/BastController.php:6978
 * @route '/berita-acara/{bast}'
 */
destroy.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        destroy.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::destroy
 * @see app/Http/Controllers/BastController.php:6978
 * @route '/berita-acara/{bast}'
 */
destroy.delete = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\BastController::destroy
 * @see app/Http/Controllers/BastController.php:6978
 * @route '/berita-acara/{bast}'
 */
const destroyForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
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
 * @see \App\Http\Controllers\BastController::destroy
 * @see app/Http/Controllers/BastController.php:6978
 * @route '/berita-acara/{bast}'
 */
destroyForm.delete = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
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
/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
export const openDetailByPetugas = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: openDetailByPetugas.url(options),
    method: 'get',
});

openDetailByPetugas.definition = {
    methods: ['get', 'post', 'head'],
    url: '/berita-acara/open-detail',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugas.url = (options?: RouteQueryOptions) => {
    return openDetailByPetugas.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugas.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: openDetailByPetugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugas.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: openDetailByPetugas.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugas.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: openDetailByPetugas.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
const openDetailByPetugasForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: openDetailByPetugas.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugasForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: openDetailByPetugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugasForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: openDetailByPetugas.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\BastController::openDetailByPetugas
 * @see app/Http/Controllers/BastController.php:1366
 * @route '/berita-acara/open-detail'
 */
openDetailByPetugasForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: openDetailByPetugas.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

openDetailByPetugas.form = openDetailByPetugasForm;
/**
 * @see \App\Http\Controllers\BastController::previewLampiranByReference
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
export const previewLampiranByReference = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewLampiranByReference.url(options),
    method: 'post',
});

previewLampiranByReference.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::previewLampiranByReference
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
previewLampiranByReference.url = (options?: RouteQueryOptions) => {
    return previewLampiranByReference.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::previewLampiranByReference
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
previewLampiranByReference.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewLampiranByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::previewLampiranByReference
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
const previewLampiranByReferenceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewLampiranByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::previewLampiranByReference
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
previewLampiranByReferenceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewLampiranByReference.url(options),
    method: 'post',
});

previewLampiranByReference.form = previewLampiranByReferenceForm;
/**
 * @see \App\Http\Controllers\BastController::downloadLampiranByReference
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
export const downloadLampiranByReference = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: downloadLampiranByReference.url(options),
    method: 'post',
});

downloadLampiranByReference.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/download',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::downloadLampiranByReference
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
downloadLampiranByReference.url = (options?: RouteQueryOptions) => {
    return downloadLampiranByReference.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::downloadLampiranByReference
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
downloadLampiranByReference.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: downloadLampiranByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::downloadLampiranByReference
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
const downloadLampiranByReferenceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadLampiranByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::downloadLampiranByReference
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
downloadLampiranByReferenceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadLampiranByReference.url(options),
    method: 'post',
});

downloadLampiranByReference.form = downloadLampiranByReferenceForm;
/**
 * @see \App\Http\Controllers\BastController::uploadLampiranSignedByReference
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
export const uploadLampiranSignedByReference = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadLampiranSignedByReference.url(options),
    method: 'post',
});

uploadLampiranSignedByReference.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranSignedByReference
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadLampiranSignedByReference.url = (options?: RouteQueryOptions) => {
    return (
        uploadLampiranSignedByReference.definition.url + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranSignedByReference
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadLampiranSignedByReference.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadLampiranSignedByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranSignedByReference
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
const uploadLampiranSignedByReferenceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadLampiranSignedByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranSignedByReference
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadLampiranSignedByReferenceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadLampiranSignedByReference.url(options),
    method: 'post',
});

uploadLampiranSignedByReference.form = uploadLampiranSignedByReferenceForm;
/**
 * @see \App\Http\Controllers\BastController::uploadLampiranFasihScreenshotByReference
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
export const uploadLampiranFasihScreenshotByReference = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadLampiranFasihScreenshotByReference.url(options),
    method: 'post',
});

uploadLampiranFasihScreenshotByReference.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/upload-fasih-screenshot',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranFasihScreenshotByReference
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadLampiranFasihScreenshotByReference.url = (
    options?: RouteQueryOptions,
) => {
    return (
        uploadLampiranFasihScreenshotByReference.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranFasihScreenshotByReference
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadLampiranFasihScreenshotByReference.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadLampiranFasihScreenshotByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranFasihScreenshotByReference
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
const uploadLampiranFasihScreenshotByReferenceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadLampiranFasihScreenshotByReference.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadLampiranFasihScreenshotByReference
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadLampiranFasihScreenshotByReferenceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadLampiranFasihScreenshotByReference.url(options),
    method: 'post',
});

uploadLampiranFasihScreenshotByReference.form =
    uploadLampiranFasihScreenshotByReferenceForm;
/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
export const downloadPdf = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
});

downloadPdf.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/{bast}/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
downloadPdf.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        downloadPdf.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
downloadPdf.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
downloadPdf.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadPdf.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
const downloadPdfForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
downloadPdfForm.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadPdf
 * @see app/Http/Controllers/BastController.php:5468
 * @route '/berita-acara/{bast}/download'
 */
downloadPdfForm.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadPdf.form = downloadPdfForm;
/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
export const downloadSignedPdf = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadSignedPdf.url(args, options),
    method: 'get',
});

downloadSignedPdf.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/{bast}/download-signed',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
downloadSignedPdf.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        downloadSignedPdf.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
downloadSignedPdf.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadSignedPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
downloadSignedPdf.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadSignedPdf.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
const downloadSignedPdfForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSignedPdf.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
downloadSignedPdfForm.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSignedPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadSignedPdf
 * @see app/Http/Controllers/BastController.php:5492
 * @route '/berita-acara/{bast}/download-signed'
 */
downloadSignedPdfForm.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSignedPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadSignedPdf.form = downloadSignedPdfForm;
/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
export const downloadCompiledBast = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadCompiledBast.url(args, options),
    method: 'get',
});

downloadCompiledBast.definition = {
    methods: ['get', 'head'],
    url: '/berita-acara/{bast}/download-compiled',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
downloadCompiledBast.url = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bast: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bast: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bast: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bast: typeof args.bast === 'object' ? args.bast.id : args.bast,
    };

    return (
        downloadCompiledBast.definition.url
            .replace('{bast}', parsedArgs.bast.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
downloadCompiledBast.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadCompiledBast.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
downloadCompiledBast.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadCompiledBast.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
const downloadCompiledBastForm = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadCompiledBast.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
downloadCompiledBastForm.get = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadCompiledBast.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BastController::downloadCompiledBast
 * @see app/Http/Controllers/BastController.php:5516
 * @route '/berita-acara/{bast}/download-compiled'
 */
downloadCompiledBastForm.head = (
    args:
        | { bast: number | { id: number } }
        | [bast: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadCompiledBast.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadCompiledBast.form = downloadCompiledBastForm;
const BastController = {
    index,
    listByMonth,
    create,
    generateBatch,
    previewForSpk,
    downloadAll,
    createForKegiatan,
    preview,
    store,
    edit,
    update,
    uploadSigned,
    destroy,
    openDetailByPetugas,
    previewLampiranByReference,
    downloadLampiranByReference,
    uploadLampiranSignedByReference,
    uploadLampiranFasihScreenshotByReference,
    downloadPdf,
    downloadSignedPdf,
    downloadCompiledBast,
};

export default BastController;
