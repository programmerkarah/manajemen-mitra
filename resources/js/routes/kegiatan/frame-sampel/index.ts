import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\KegiatanController::template
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
export const template = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: template.url(options),
    method: 'post',
});

template.definition = {
    methods: ['post'],
    url: '/kegiatan/frame-sampel/template',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::template
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
template.url = (options?: RouteQueryOptions) => {
    return template.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::template
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
template.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: template.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::template
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
const templateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: template.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::template
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
templateForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: template.url(options),
    method: 'post',
});

template.form = templateForm;
/**
 * @see \App\Http\Controllers\KegiatanController::importPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
export const importPreview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

importPreview.definition = {
    methods: ['post'],
    url: '/kegiatan/frame-sampel/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::importPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importPreview.url = (options?: RouteQueryOptions) => {
    return importPreview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::importPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importPreview.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::importPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
const importPreviewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::importPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importPreviewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

importPreview.form = importPreviewForm;
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
export const overview = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: overview.url(options),
    method: 'get',
});

overview.definition = {
    methods: ['get', 'head'],
    url: '/frame-sampel',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
overview.url = (options?: RouteQueryOptions) => {
    return overview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
overview.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: overview.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
overview.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: overview.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
const overviewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: overview.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
overviewForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: overview.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::overview
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:24
 * @route '/frame-sampel'
 */
overviewForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: overview.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

overview.form = overviewForm;
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
export const index = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}/frame-sampel',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
index.url = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { kegiatan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
    };

    return (
        index.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
index.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
index.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
const indexForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
indexForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::index
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:48
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
indexForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

index.form = indexForm;
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::store
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:58
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
export const store = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/frame-sampel',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::store
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:58
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
store.url = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { kegiatan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
    };

    return (
        store.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::store
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:58
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
store.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::store
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:58
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
const storeForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::store
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:58
 * @route '/kegiatan/{kegiatan}/frame-sampel'
 */
storeForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::update
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:105
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
export const update = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/kegiatan/{kegiatan}/frame-sampel/{frame}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::update
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:105
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
update.url = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            frame: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
        frame: typeof args.frame === 'object' ? args.frame.id : args.frame,
    };

    return (
        update.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::update
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:105
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
update.put = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::update
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:105
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
const updateForm = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
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
 * @see \App\Http\Controllers\KegiatanFrameSampelController::update
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:105
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
updateForm.put = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
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
 * @see \App\Http\Controllers\KegiatanFrameSampelController::destroy
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:141
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
export const destroy = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/kegiatan/{kegiatan}/frame-sampel/{frame}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::destroy
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:141
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
destroy.url = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            frame: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
        frame: typeof args.frame === 'object' ? args.frame.id : args.frame,
    };

    return (
        destroy.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::destroy
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:141
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
destroy.delete = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\KegiatanFrameSampelController::destroy
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:141
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
const destroyForm = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
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
 * @see \App\Http\Controllers\KegiatanFrameSampelController::destroy
 * @see app/Http/Controllers/KegiatanFrameSampelController.php:141
 * @route '/kegiatan/{kegiatan}/frame-sampel/{frame}'
 */
destroyForm.delete = (
    args:
        | { kegiatan: number | { id: number }; frame: number | { id: number } }
        | [kegiatan: number | { id: number }, frame: number | { id: number }],
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
const frameSampel = {
    template: Object.assign(template, template),
    importPreview: Object.assign(importPreview, importPreview),
    overview: Object.assign(overview, overview),
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default frameSampel;
