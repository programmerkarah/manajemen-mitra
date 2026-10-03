import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
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
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
export const createForKegiatan = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createForKegiatan.url(args, options),
    method: 'get',
});

createForKegiatan.definition = {
    methods: ['get', 'head'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForKegiatan.url = (
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
        createForKegiatan.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForKegiatan.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createForKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForKegiatan.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: createForKegiatan.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
const createForKegiatanForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createForKegiatan.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForKegiatanForm.get = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createForKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SkKpaController::createForKegiatan
 * @see app/Http/Controllers/SkKpaController.php:208
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/create'
 */
createForKegiatanForm.head = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
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
 * @see \App\Http\Controllers\SkKpaController::preview
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
export const preview = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
});

preview.definition = {
    methods: ['post'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::preview
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
preview.url = (
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
        preview.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::preview
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
preview.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::preview
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
const previewForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::preview
 * @see app/Http/Controllers/SkKpaController.php:494
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/preview'
 */
previewForm.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(args, options),
    method: 'post',
});

preview.form = previewForm;
/**
 * @see \App\Http\Controllers\SkKpaController::generate
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
export const generate = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generate.url(args, options),
    method: 'post',
});

generate.definition = {
    methods: ['post'],
    url: '/sk-kpa/kegiatan/{kegiatanHashedId}/generate',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SkKpaController::generate
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generate.url = (
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
        generate.definition.url
            .replace(
                '{kegiatanHashedId}',
                parsedArgs.kegiatanHashedId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::generate
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generate.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::generate
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
const generateForm = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SkKpaController::generate
 * @see app/Http/Controllers/SkKpaController.php:724
 * @route '/sk-kpa/kegiatan/{kegiatanHashedId}/generate'
 */
generateForm.post = (
    args:
        | { kegiatanHashedId: string | number }
        | [kegiatanHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(args, options),
    method: 'post',
});

generate.form = generateForm;
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
 * @route '/sk-kpa/{skKpa}'
 */
export const update = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/sk-kpa/{skKpa}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
update.url = (
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
        update.definition.url
            .replace('{skKpa}', parsedArgs.skKpa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
update.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
const updateForm = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
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
 * @see \App\Http\Controllers\SkKpaController::update
 * @see app/Http/Controllers/SkKpaController.php:397
 * @route '/sk-kpa/{skKpa}'
 */
updateForm.put = (
    args:
        | { skKpa: number | { id: number } }
        | [skKpa: number | { id: number }]
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
const skKpa = {
    index: Object.assign(index, index),
    listByKegiatan: Object.assign(listByKegiatan, listByKegiatan),
    show: Object.assign(show, show),
    uploadSigned: Object.assign(uploadSigned, uploadSigned),
    createForKegiatan: Object.assign(createForKegiatan, createForKegiatan),
    preview: Object.assign(preview, preview),
    generate: Object.assign(generate, generate),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    acknowledgeRevision: Object.assign(
        acknowledgeRevision,
        acknowledgeRevision,
    ),
};

export default skKpa;
