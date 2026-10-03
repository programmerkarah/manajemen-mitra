import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import frameSampel from './frame-sampel';
import periode from './periode';
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
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
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/alokasi',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
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
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
export const show = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        show.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
const showForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
showForm.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
showForm.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
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
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/alokasi/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
export const storeMultiple = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeMultiple.url(args, options),
    method: 'post',
});

storeMultiple.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/store-multiple',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultiple.url = (
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
        storeMultiple.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultiple.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeMultiple.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
const storeMultipleForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeMultiple.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultipleForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeMultiple.url(args, options),
    method: 'post',
});

storeMultiple.form = storeMultipleForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
export const importCreate = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importCreate.url(args, options),
    method: 'post',
});

importCreate.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreate.url = (
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
        importCreate.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreate.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importCreate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
const importCreateForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importCreate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreateForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importCreate.url(args, options),
    method: 'post',
});

importCreate.form = importCreateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
export const importPreview = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(args, options),
    method: 'post',
});

importPreview.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreview.url = (
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
        importPreview.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreview.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
const importPreviewForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreviewForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(args, options),
    method: 'post',
});

importPreview.form = importPreviewForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
export const edit = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/{alokasi}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        edit.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
const editForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
editForm.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
editForm.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
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
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
export const update = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put', 'patch'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        update.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
const updateForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
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
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
updateForm.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
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
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
updateForm.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
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
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
export const destroy = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroy.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        destroy.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroy.delete = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
const destroyForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
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
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroyForm.delete = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
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
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
export const exportTemplateCreate = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'get',
});

exportTemplateCreate.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/export/{type}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.url = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { type: args };
    }

    if (Array.isArray(args)) {
        args = {
            type: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        type: args.type,
    };

    return (
        exportTemplateCreate.definition.url
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.get = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.head = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
const exportTemplateCreateForm = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreateForm.get = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreateForm.head = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportTemplateCreate.form = exportTemplateCreateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
export const exportTemplate = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});

exportTemplate.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{periodeAlokasiHash}/export/{type}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.url = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            periodeAlokasiHash: args[0],
            type: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeAlokasiHash: args.periodeAlokasiHash,
        type: args.type,
    };

    return (
        exportTemplate.definition.url
            .replace(
                '{periodeAlokasiHash}',
                parsedArgs.periodeAlokasiHash.toString(),
            )
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.get = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.head = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportTemplate.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
const exportTemplateForm = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplateForm.get = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplateForm.head = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
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
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
export const importMethod = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

importMethod.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{periodeAlokasiId}/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethod.url = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { periodeAlokasiId: args };
    }

    if (Array.isArray(args)) {
        args = {
            periodeAlokasiId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeAlokasiId: args.periodeAlokasiId,
    };

    return (
        importMethod.definition.url
            .replace(
                '{periodeAlokasiId}',
                parsedArgs.periodeAlokasiId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethod.post = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
const importMethodForm = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethodForm.post = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

importMethod.form = importMethodForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
export const updateNonResponse = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateNonResponse.url(options),
    method: 'post',
});

updateNonResponse.definition = {
    methods: ['post'],
    url: '/alokasi/update-non-response',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponse.url = (options?: RouteQueryOptions) => {
    return updateNonResponse.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponse.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateNonResponse.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
const updateNonResponseForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateNonResponse.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponseForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateNonResponse.url(options),
    method: 'post',
});

updateNonResponse.form = updateNonResponseForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
export const submit = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

submit.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/submit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submit.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        submit.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submit.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
const submitForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submitForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

submit.form = submitForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
export const approve = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

approve.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/approve',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approve.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        approve.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approve.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
const approveForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approveForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

approve.form = approveForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
export const reject = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

reject.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/reject',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
reject.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        reject.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
reject.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
const rejectForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
rejectForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

reject.form = rejectForm;
const alokasi = {
    create: Object.assign(create, create),
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    periode: Object.assign(periode, periode),
    store: Object.assign(store, store),
    storeMultiple: Object.assign(storeMultiple, storeMultiple),
    importCreate: Object.assign(importCreate, importCreate),
    importPreview: Object.assign(importPreview, importPreview),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    exportTemplateCreate: Object.assign(
        exportTemplateCreate,
        exportTemplateCreate,
    ),
    exportTemplate: Object.assign(exportTemplate, exportTemplate),
    import: Object.assign(importMethod, importMethod),
    frameSampel: Object.assign(frameSampel, frameSampel),
    updateNonResponse: Object.assign(updateNonResponse, updateNonResponse),
    submit: Object.assign(submit, submit),
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
};

export default alokasi;
