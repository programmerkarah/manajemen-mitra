import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/sk-kpa',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SkKpaController::index
 * @see app/Http/Controllers/SkKpaController.php:33
 * @route '/sk-kpa'
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
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
export const listByKegiatan = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: listByKegiatan.url(args, options),
    method: 'get',
});

listByKegiatan.definition = {
    methods: ['get', 'head'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
listByKegiatan.url = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatanHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatanHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatanHashedId: args.kegiatanHashedId,
    };

    return (
        listByKegiatan.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
listByKegiatan.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: listByKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
listByKegiatan.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: listByKegiatan.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
const listByKegiatanForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByKegiatan.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
listByKegiatanForm.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::listByKegiatan
 * @see app/Http/Controllers/SkKpaController.php:163
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}'
 */
listByKegiatanForm.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByKegiatan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

listByKegiatan.form = listByKegiatanForm;
/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
export const show = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/sk-kpa/{skKpa}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
show.url = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: args.skKpa,
    };

    return (
        show.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
show.get = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
show.head = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
const showForm = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
showForm.get = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::show
 * @see app/Http/Controllers/SkKpaController.php:300
 * @route '/sk-kpa/{skKpa}'
 */
showForm.head = (
    args:
        | { skKpa: string | number }
        | [skKpa: string | number]
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
 * @see \App\Http\Controllers\SkKpaController::uploadSigned
 * @see app/Http/Controllers/SkKpaController.php:437
 * @route '/sk-kpa/{skKpaHashedId}/upload-signed'
 */
export const uploadSigned = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.definition = {
    methods: ['post'],
    url: '/sk-kpa/{skKpaHashedId}/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::uploadSigned
 * @see app/Http/Controllers/SkKpaController.php:437
 * @route '/sk-kpa/{skKpaHashedId}/upload-signed'
 */
uploadSigned.url = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpaHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            skKpaHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpaHashedId: args.skKpaHashedId,
    };

    return (
        uploadSigned.definition.url
            .replace('{skKpaHashedId}', parsedArgs.skKpaHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::uploadSigned
 * @see app/Http/Controllers/SkKpaController.php:437
 * @route '/sk-kpa/{skKpaHashedId}/upload-signed'
 */
uploadSigned.post = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::uploadSigned
 * @see app/Http/Controllers/SkKpaController.php:437
 * @route '/sk-kpa/{skKpaHashedId}/upload-signed'
 */
const uploadSignedForm = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::uploadSigned
 * @see app/Http/Controllers/SkKpaController.php:437
 * @route '/sk-kpa/{skKpaHashedId}/upload-signed'
 */
uploadSignedForm.post = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.form = uploadSignedForm;
/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
export const create = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
create.url = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatanHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatanHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatanHashedId: args.kegiatanHashedId,
    };

    return (
        create.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
create.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
create.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
const createForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForm.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::create
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForm.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

create.form = createForm;
/**
 * @see \App\Http\Controllers\SkKpaController::previewSk
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
export const previewSk = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSk.url(args, options),
    method: 'post',
});

previewSk.definition = {
    methods: ['post'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::previewSk
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
previewSk.url = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatanHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatanHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatanHashedId: args.kegiatanHashedId,
    };

    return (
        previewSk.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::previewSk
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
previewSk.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::previewSk
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
const previewSkForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::previewSk
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
previewSkForm.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSk.url(args, options),
    method: 'post',
});

previewSk.form = previewSkForm;
/**
 * @see \App\Http\Controllers\SkKpaController::generateSk
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
export const generateSk = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateSk.url(args, options),
    method: 'post',
});

generateSk.definition = {
    methods: ['post'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/generate',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::generateSk
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generateSk.url = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatanHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatanHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatanHashedId: args.kegiatanHashedId,
    };

    return (
        generateSk.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::generateSk
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generateSk.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateSk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::generateSk
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
const generateSkForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateSk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::generateSk
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generateSkForm.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateSk.url(args, options),
    method: 'post',
});

generateSk.form = generateSkForm;
/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
export const edit = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/sk-kpa/{skKpa}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
edit.url = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { skKpa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: typeof args.skKpa === 'object' ? args.skKpa.id : args.skKpa,
    };

    return (
        edit.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
edit.get = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
edit.head = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
const editForm = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
editForm.get = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::edit
 * @see app/Http/Controllers/SkKpaController.php:389
 * @route '/sk-kpa/{skKpa}/edit'
 */
editForm.head = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
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
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
const updatec3be97829a2909a8c16d675e1c4ce37a = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, options),
    method: 'put',
});

updatec3be97829a2909a8c16d675e1c4ce37a.definition = {
    methods: ['put', 'patch'],
    url: '/sk-kpa/{skKpa}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
updatec3be97829a2909a8c16d675e1c4ce37a.url = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { skKpa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: typeof args.skKpa === 'object' ? args.skKpa.id : args.skKpa,
    };

    return (
        updatec3be97829a2909a8c16d675e1c4ce37a.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
updatec3be97829a2909a8c16d675e1c4ce37a.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
updatec3be97829a2909a8c16d675e1c4ce37a.patch = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
const updatec3be97829a2909a8c16d675e1c4ce37aForm = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
updatec3be97829a2909a8c16d675e1c4ce37aForm.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}/edit'
 */
updatec3be97829a2909a8c16d675e1c4ce37aForm.patch = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatec3be97829a2909a8c16d675e1c4ce37a.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatec3be97829a2909a8c16d675e1c4ce37a.form =
    updatec3be97829a2909a8c16d675e1c4ce37aForm;
/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
const updatecc403d759f8bd268a6c3187fdb3684a5 = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, options),
    method: 'put',
});

updatecc403d759f8bd268a6c3187fdb3684a5.definition = {
    methods: ['put'],
    url: '/sk-kpa/{skKpa}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5.url = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { skKpa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: typeof args.skKpa === 'object' ? args.skKpa.id : args.skKpa,
    };

    return (
        updatecc403d759f8bd268a6c3187fdb3684a5.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
const updatecc403d759f8bd268a6c3187fdb3684a5Form = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5Form.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatecc403d759f8bd268a6c3187fdb3684a5.form =
    updatecc403d759f8bd268a6c3187fdb3684a5Form;
/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
const updatecc403d759f8bd268a6c3187fdb3684a5 = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, options),
    method: 'patch',
});

updatecc403d759f8bd268a6c3187fdb3684a5.definition = {
    methods: ['patch'],
    url: '/sk-kpa/{skKpa}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5.url = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { skKpa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: typeof args.skKpa === 'object' ? args.skKpa.id : args.skKpa,
    };

    return (
        updatecc403d759f8bd268a6c3187fdb3684a5.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5.patch = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
const updatecc403d759f8bd268a6c3187fdb3684a5Form = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updatecc403d759f8bd268a6c3187fdb3684a5Form.patch = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatecc403d759f8bd268a6c3187fdb3684a5.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatecc403d759f8bd268a6c3187fdb3684a5.form =
    updatecc403d759f8bd268a6c3187fdb3684a5Form;

export const update = {
    '/sk-kpa/{skKpa}/edit': updatec3be97829a2909a8c16d675e1c4ce37a,
    '/sk-kpa/{skKpa}': updatecc403d759f8bd268a6c3187fdb3684a5,
    '/sk-kpa/{skKpa}': updatecc403d759f8bd268a6c3187fdb3684a5,
};

/**
 * @see \App\Http\Controllers\SkKpaController::destroy
 * @see app/Http/Controllers/SkKpaController.php:405
 * @route '/sk-kpa/{skKpa}'
 */
export const destroy = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/sk-kpa/{skKpa}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SkKpaController::destroy
 * @see app/Http/Controllers/SkKpaController.php:405
 * @route '/sk-kpa/{skKpa}'
 */
destroy.url = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { skKpa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            skKpa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpa: typeof args.skKpa === 'object' ? args.skKpa.id : args.skKpa,
    };

    return (
        destroy.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::destroy
 * @see app/Http/Controllers/SkKpaController.php:405
 * @route '/sk-kpa/{skKpa}'
 */
destroy.delete = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SkKpaController::destroy
 * @see app/Http/Controllers/SkKpaController.php:405
 * @route '/sk-kpa/{skKpa}'
 */
const destroyForm = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
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
 * @see \App\Http\Controllers\SkKpaController::destroy
 * @see app/Http/Controllers/SkKpaController.php:405
 * @route '/sk-kpa/{skKpa}'
 */
destroyForm.delete = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
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
 * @see \App\Http\Controllers\SkKpaController::acknowledgeRevision
 * @see app/Http/Controllers/SkKpaController.php:414
 * @route '/sk-kpa/{skKpaHashedId}/acknowledge-revision'
 */
export const acknowledgeRevision = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: acknowledgeRevision.url(args, options),
    method: 'post',
});

acknowledgeRevision.definition = {
    methods: ['post'],
    url: '/sk-kpa/{skKpaHashedId}/acknowledge-revision',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::acknowledgeRevision
 * @see app/Http/Controllers/SkKpaController.php:414
 * @route '/sk-kpa/{skKpaHashedId}/acknowledge-revision'
 */
acknowledgeRevision.url = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { skKpaHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            skKpaHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        skKpaHashedId: args.skKpaHashedId,
    };

    return (
        acknowledgeRevision.definition.url
            .replace('{skKpaHashedId}', parsedArgs.skKpaHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::acknowledgeRevision
 * @see app/Http/Controllers/SkKpaController.php:414
 * @route '/sk-kpa/{skKpaHashedId}/acknowledge-revision'
 */
acknowledgeRevision.post = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: acknowledgeRevision.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::acknowledgeRevision
 * @see app/Http/Controllers/SkKpaController.php:414
 * @route '/sk-kpa/{skKpaHashedId}/acknowledge-revision'
 */
const acknowledgeRevisionForm = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: acknowledgeRevision.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::acknowledgeRevision
 * @see app/Http/Controllers/SkKpaController.php:414
 * @route '/sk-kpa/{skKpaHashedId}/acknowledge-revision'
 */
acknowledgeRevisionForm.post = (
    args:
        | { skKpaHashedId: string | number }
        | [skKpaHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: acknowledgeRevision.url(args, options),
    method: 'post',
});

acknowledgeRevision.form = acknowledgeRevisionForm;
const SkKpaController = {
    index,
    listByKegiatan,
    show,
    uploadSigned,
    create,
    previewSk,
    generateSk,
    edit,
    update,
    destroy,
    acknowledgeRevision,
};

export default SkKpaController;
