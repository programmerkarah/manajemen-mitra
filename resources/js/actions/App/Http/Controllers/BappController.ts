import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
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
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
export const downloadTemplate = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});

downloadTemplate.definition = {
    methods: ['get', 'head'],
    url: '/bapp/template',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
downloadTemplate.url = (options?: RouteQueryOptions) => {
    return downloadTemplate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
downloadTemplate.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
downloadTemplate.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadTemplate.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
const downloadTemplateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
 */
downloadTemplateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\BappController::downloadTemplate
 * @see app/Http/Controllers/BappController.php:1513
 * @route '/bapp/template'
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
 * @see \App\Http\Controllers\BappController::importRealisasi
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
export const importRealisasi = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importRealisasi.url(options),
    method: 'post',
});

importRealisasi.definition = {
    methods: ['post'],
    url: '/bapp/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::importRealisasi
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importRealisasi.url = (options?: RouteQueryOptions) => {
    return importRealisasi.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BappController::importRealisasi
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importRealisasi.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importRealisasi.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::importRealisasi
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
const importRealisasiForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importRealisasi.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::importRealisasi
 * @see app/Http/Controllers/BappController.php:1543
 * @route '/bapp/import'
 */
importRealisasiForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importRealisasi.url(options),
    method: 'post',
});

importRealisasi.form = importRealisasiForm;
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
 * @see \App\Http\Controllers\BappController::uploadFasihScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
export const uploadFasihScreenshot = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadFasihScreenshot.url(args, options),
    method: 'post',
});

uploadFasihScreenshot.definition = {
    methods: ['post'],
    url: '/bapp/{bapp}/upload-screenshot',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::uploadFasihScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadFasihScreenshot.url = (
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
        uploadFasihScreenshot.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::uploadFasihScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadFasihScreenshot.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadFasihScreenshot.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadFasihScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
const uploadFasihScreenshotForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadFasihScreenshot.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadFasihScreenshot
 * @see app/Http/Controllers/BappController.php:1635
 * @route '/bapp/{bapp}/upload-screenshot'
 */
uploadFasihScreenshotForm.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadFasihScreenshot.url(args, options),
    method: 'post',
});

uploadFasihScreenshot.form = uploadFasihScreenshotForm;
/**
 * @see \App\Http\Controllers\BappController::uploadSignedBapp
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
export const uploadSignedBapp = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSignedBapp.url(args, options),
    method: 'post',
});

uploadSignedBapp.definition = {
    methods: ['post'],
    url: '/bapp/{bapp}/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BappController::uploadSignedBapp
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSignedBapp.url = (
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
        uploadSignedBapp.definition.url
            .replace('{bapp}', parsedArgs.bapp.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\BappController::uploadSignedBapp
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSignedBapp.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSignedBapp.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadSignedBapp
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
const uploadSignedBappForm = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSignedBapp.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BappController::uploadSignedBapp
 * @see app/Http/Controllers/BappController.php:1767
 * @route '/bapp/{bapp}/upload-signed'
 */
uploadSignedBappForm.post = (
    args:
        | { bapp: number | { id: number } }
        | [bapp: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSignedBapp.url(args, options),
    method: 'post',
});

uploadSignedBapp.form = uploadSignedBappForm;
const BappController = {
    index,
    create,
    downloadTemplate,
    show,
    storeRealisasi,
    importRealisasi,
    generate,
    generateBatch,
    download,
    preview,
    downloadSigned,
    uploadFasihScreenshot,
    uploadSignedBapp,
};

export default BappController;
