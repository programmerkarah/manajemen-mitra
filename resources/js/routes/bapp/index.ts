import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/bapp',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::index
 * @see app/Http/Controllers/BappController.php:981
 * @route '/bapp'
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
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/bapp/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::create
 * @see app/Http/Controllers/BappController.php:1039
 * @route '/bapp/create'
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
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
export const template = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});

template.definition = {
    methods: ['get', 'head'],
    url: '/bapp/template',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
template.url = (options?: RouteQueryOptions) => {
    return template.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
template.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
template.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: template.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
const templateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
templateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::template
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
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
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
export const show = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/bapp/termin/{terminHashed}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
show.url = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { terminHashed: args };
    }

    if (Array.isArray(args)) {
        args = {
            terminHashed: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        terminHashed: args.terminHashed,
    };

    return (
        show.definition.url
            .replace('{terminHashed}', parsedArgs.terminHashed.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
show.get = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
show.head = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
const showForm = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
showForm.get = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::show
 * @see app/Http/Controllers/BappController.php:1654
 * @route '/bapp/termin/{terminHashed}'
 */
showForm.head = (
    args:
        | { terminHashed: string | number }
        | [terminHashed: string | number]
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
 * @see \App\Http\Controllers\BappController::storeRealisasi
 * @see app/Http/Controllers/BappController.php:1180
 * @route '/bapp/realisasi'
 */
export const storeRealisasi = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeRealisasi.url(options),
    method: 'post',
});

storeRealisasi.definition = {
    methods: ['post'],
    url: '/bapp/realisasi',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::storeRealisasi
 * @see app/Http/Controllers/BappController.php:1180
 * @route '/bapp/realisasi'
 */
storeRealisasi.url = (options?: RouteQueryOptions) => {
    return storeRealisasi.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::storeRealisasi
 * @see app/Http/Controllers/BappController.php:1180
 * @route '/bapp/realisasi'
 */
storeRealisasi.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeRealisasi.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::storeRealisasi
 * @see app/Http/Controllers/BappController.php:1180
 * @route '/bapp/realisasi'
 */
const storeRealisasiForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRealisasi.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::storeRealisasi
 * @see app/Http/Controllers/BappController.php:1180
 * @route '/bapp/realisasi'
 */
storeRealisasiForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRealisasi.url(options),
    method: 'post',
});

storeRealisasi.form = storeRealisasiForm;
/**
 * @see \App\Http\Controllers\BappController::importMethod
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
export const importMethod = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(options),
    method: 'post',
});

importMethod.definition = {
    methods: ['post'],
    url: '/bapp/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::importMethod
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importMethod.url = (options?: RouteQueryOptions) => {
    return importMethod.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::importMethod
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::importMethod
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
const importMethodForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::importMethod
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importMethodForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(options),
    method: 'post',
});

importMethod.form = importMethodForm;
/**
 * @see \App\Http\Controllers\BappController::generate
 * @see app/Http/Controllers/BappController.php:1293
 * @route '/bapp/generate'
 */
export const generate = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
});

generate.definition = {
    methods: ['post'],
    url: '/bapp/generate',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::generate
 * @see app/Http/Controllers/BappController.php:1293
 * @route '/bapp/generate'
 */
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::generate
 * @see app/Http/Controllers/BappController.php:1293
 * @route '/bapp/generate'
 */
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::generate
 * @see app/Http/Controllers/BappController.php:1293
 * @route '/bapp/generate'
 */
const generateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::generate
 * @see app/Http/Controllers/BappController.php:1293
 * @route '/bapp/generate'
 */
generateForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
});

generate.form = generateForm;
/**
 * @see \App\Http\Controllers\BappController::generateBatch
 * @see app/Http/Controllers/BappController.php:1362
 * @route '/bapp/generate-batch'
 */
export const generateBatch = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatch.url(options),
    method: 'post',
});

generateBatch.definition = {
    methods: ['post'],
    url: '/bapp/generate-batch',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::generateBatch
 * @see app/Http/Controllers/BappController.php:1362
 * @route '/bapp/generate-batch'
 */
generateBatch.url = (options?: RouteQueryOptions) => {
    return generateBatch.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::generateBatch
 * @see app/Http/Controllers/BappController.php:1362
 * @route '/bapp/generate-batch'
 */
generateBatch.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatch.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::generateBatch
 * @see app/Http/Controllers/BappController.php:1362
 * @route '/bapp/generate-batch'
 */
const generateBatchForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatch.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::generateBatch
 * @see app/Http/Controllers/BappController.php:1362
 * @route '/bapp/generate-batch'
 */
generateBatchForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatch.url(options),
    method: 'post',
});

generateBatch.form = generateBatchForm;
/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
export const download = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});

download.definition = {
    methods: ['get', 'head'],
    url: '/bapp/{bapp}/download',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
download.url = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bapp: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bapp: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bapp: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bapp: typeof args.bapp === 'object' ? args.bapp.id : args.bapp,
    };

    return (
        download.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
download.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
download.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
const downloadForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
downloadForm.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::download
 * @see app/Http/Controllers/BappController.php:1481
 * @route '/bapp/{bapp}/download'
 */
downloadForm.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

download.form = downloadForm;
/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
export const preview = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: preview.url(args, options),
    method: 'get',
});

preview.definition = {
    methods: ['get', 'head'],
    url: '/bapp/{bapp}/preview',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
preview.url = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bapp: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bapp: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bapp: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bapp: typeof args.bapp === 'object' ? args.bapp.id : args.bapp,
    };

    return (
        preview.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
preview.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: preview.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
preview.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: preview.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
const previewForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: preview.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
previewForm.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: preview.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::preview
 * @see app/Http/Controllers/BappController.php:1449
 * @route '/bapp/{bapp}/preview'
 */
previewForm.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: preview.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

preview.form = previewForm;
/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
export const downloadSigned = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadSigned.url(args, options),
    method: 'get',
});

downloadSigned.definition = {
    methods: ['get', 'head'],
    url: '/bapp/{bapp}/download-signed',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
downloadSigned.url = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bapp: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bapp: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bapp: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bapp: typeof args.bapp === 'object' ? args.bapp.id : args.bapp,
    };

    return (
        downloadSigned.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
downloadSigned.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadSigned.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
downloadSigned.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadSigned.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
const downloadSignedForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSigned.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
downloadSignedForm.get = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSigned.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::downloadSigned
 * @see app/Http/Controllers/BappController.php:1797
 * @route '/bapp/{bapp}/download-signed'
 */
downloadSignedForm.head = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadSigned.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadSigned.form = downloadSignedForm;
/**
 * @see \App\Http\Controllers\BappController::uploadScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
export const uploadScreenshot = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadScreenshot.url(args, options),
    method: 'post',
});

uploadScreenshot.definition = {
    methods: ['post'],
    url: '/bapp/{bapp}/upload-screenshot',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::uploadScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadScreenshot.url = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bapp: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bapp: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bapp: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bapp: typeof args.bapp === 'object' ? args.bapp.id : args.bapp,
    };

    return (
        uploadScreenshot.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::uploadScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadScreenshot.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadScreenshot.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
const uploadScreenshotForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadScreenshot.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadScreenshotForm.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadScreenshot.url(args, options),
    method: 'post',
});

uploadScreenshot.form = uploadScreenshotForm;
/**
 * @see \App\Http\Controllers\BappController::uploadSigned
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
export const uploadSigned = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.definition = {
    methods: ['post'],
    url: '/bapp/{bapp}/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::uploadSigned
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSigned.url = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bapp: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { bapp: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            bapp: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        bapp: typeof args.bapp === 'object' ? args.bapp.id : args.bapp,
    };

    return (
        uploadSigned.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::uploadSigned
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSigned.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadSigned
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
const uploadSignedForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadSigned
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSignedForm.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.form = uploadSignedForm;
const bapp = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    template: Object.assign(template, template),
    show: Object.assign(show, show),
    storeRealisasi: Object.assign(storeRealisasi, storeRealisasi),
    import: Object.assign(importMethod, importMethod),
    generate: Object.assign(generate, generate),
    generateBatch: Object.assign(generateBatch, generateBatch),
    download: Object.assign(download, download),
    preview: Object.assign(preview, preview),
    downloadSigned: Object.assign(downloadSigned, downloadSigned),
    uploadScreenshot: Object.assign(uploadScreenshot, uploadScreenshot),
    uploadSigned: Object.assign(uploadSigned, uploadSigned),
};

export default bapp;
