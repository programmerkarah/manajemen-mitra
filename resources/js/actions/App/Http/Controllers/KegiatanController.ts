import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
export const manageRateHonor = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: manageRateHonor.url(args, options),
    method: 'get',
});

manageRateHonor.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}/rate-honor/manage',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageRateHonor.url = (
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
        manageRateHonor.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageRateHonor.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: manageRateHonor.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageRateHonor.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: manageRateHonor.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
const manageRateHonorForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manageRateHonor.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageRateHonorForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manageRateHonor.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::manageRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageRateHonorForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manageRateHonor.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

manageRateHonor.form = manageRateHonorForm;
/**
 * @see \App\Http\Controllers\KegiatanController::bulkUpdateRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
export const bulkUpdateRateHonor = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: bulkUpdateRateHonor.url(args, options),
    method: 'post',
});

bulkUpdateRateHonor.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/rate-honor/bulk',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::bulkUpdateRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulkUpdateRateHonor.url = (
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
        bulkUpdateRateHonor.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::bulkUpdateRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulkUpdateRateHonor.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: bulkUpdateRateHonor.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::bulkUpdateRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
const bulkUpdateRateHonorForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: bulkUpdateRateHonor.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::bulkUpdateRateHonor
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulkUpdateRateHonorForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: bulkUpdateRateHonor.url(args, options),
    method: 'post',
});

bulkUpdateRateHonor.form = bulkUpdateRateHonorForm;
/**
 * @see \App\Http\Controllers\KegiatanController::submit
 * @see app/Http/Controllers/KegiatanController.php:1538
 * @route '/kegiatan/{kegiatan}/submit'
 */
export const submit = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

submit.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/submit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::submit
 * @see app/Http/Controllers/KegiatanController.php:1538
 * @route '/kegiatan/{kegiatan}/submit'
 */
submit.url = (
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
        submit.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::submit
 * @see app/Http/Controllers/KegiatanController.php:1538
 * @route '/kegiatan/{kegiatan}/submit'
 */
submit.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::submit
 * @see app/Http/Controllers/KegiatanController.php:1538
 * @route '/kegiatan/{kegiatan}/submit'
 */
const submitForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::submit
 * @see app/Http/Controllers/KegiatanController.php:1538
 * @route '/kegiatan/{kegiatan}/submit'
 */
submitForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

submit.form = submitForm;
/**
 * @see \App\Http\Controllers\KegiatanController::approve
 * @see app/Http/Controllers/KegiatanController.php:1469
 * @route '/kegiatan/{kegiatan}/approve'
 */
export const approve = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

approve.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/approve',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::approve
 * @see app/Http/Controllers/KegiatanController.php:1469
 * @route '/kegiatan/{kegiatan}/approve'
 */
approve.url = (
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
        approve.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::approve
 * @see app/Http/Controllers/KegiatanController.php:1469
 * @route '/kegiatan/{kegiatan}/approve'
 */
approve.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::approve
 * @see app/Http/Controllers/KegiatanController.php:1469
 * @route '/kegiatan/{kegiatan}/approve'
 */
const approveForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::approve
 * @see app/Http/Controllers/KegiatanController.php:1469
 * @route '/kegiatan/{kegiatan}/approve'
 */
approveForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

approve.form = approveForm;
/**
 * @see \App\Http\Controllers\KegiatanController::reject
 * @see app/Http/Controllers/KegiatanController.php:1501
 * @route '/kegiatan/{kegiatan}/reject'
 */
export const reject = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

reject.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/reject',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::reject
 * @see app/Http/Controllers/KegiatanController.php:1501
 * @route '/kegiatan/{kegiatan}/reject'
 */
reject.url = (
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
        reject.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::reject
 * @see app/Http/Controllers/KegiatanController.php:1501
 * @route '/kegiatan/{kegiatan}/reject'
 */
reject.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::reject
 * @see app/Http/Controllers/KegiatanController.php:1501
 * @route '/kegiatan/{kegiatan}/reject'
 */
const rejectForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::reject
 * @see app/Http/Controllers/KegiatanController.php:1501
 * @route '/kegiatan/{kegiatan}/reject'
 */
rejectForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

reject.form = rejectForm;
/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::create
 * @see app/Http/Controllers/KegiatanController.php:111
 * @route '/kegiatan/create'
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
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/kegiatan',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\KegiatanController::index
 * @see app/Http/Controllers/KegiatanController.php:40
 * @route '/kegiatan'
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
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
export const show = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
show.url = (
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
        show.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
show.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
show.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
const showForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
showForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::show
 * @see app/Http/Controllers/KegiatanController.php:402
 * @route '/kegiatan/{kegiatan}'
 */
showForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
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
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
export const copy = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: copy.url(args, options),
    method: 'get',
});

copy.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}/copy',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
copy.url = (
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
        copy.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
copy.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: copy.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
copy.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: copy.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
const copyForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: copy.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
copyForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: copy.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::copy
 * @see app/Http/Controllers/KegiatanController.php:158
 * @route '/kegiatan/{kegiatan}/copy'
 */
copyForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: copy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

copy.form = copyForm;
/**
 * @see \App\Http\Controllers\KegiatanController::exportFrameSampelTemplate
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
export const exportFrameSampelTemplate = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: exportFrameSampelTemplate.url(options),
    method: 'post',
});

exportFrameSampelTemplate.definition = {
    methods: ['post'],
    url: '/kegiatan/frame-sampel/template',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::exportFrameSampelTemplate
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
exportFrameSampelTemplate.url = (options?: RouteQueryOptions) => {
    return exportFrameSampelTemplate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::exportFrameSampelTemplate
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
exportFrameSampelTemplate.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: exportFrameSampelTemplate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::exportFrameSampelTemplate
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
const exportFrameSampelTemplateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: exportFrameSampelTemplate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::exportFrameSampelTemplate
 * @see app/Http/Controllers/KegiatanController.php:701
 * @route '/kegiatan/frame-sampel/template'
 */
exportFrameSampelTemplateForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: exportFrameSampelTemplate.url(options),
    method: 'post',
});

exportFrameSampelTemplate.form = exportFrameSampelTemplateForm;
/**
 * @see \App\Http\Controllers\KegiatanController::importFrameSampelPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
export const importFrameSampelPreview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importFrameSampelPreview.url(options),
    method: 'post',
});

importFrameSampelPreview.definition = {
    methods: ['post'],
    url: '/kegiatan/frame-sampel/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::importFrameSampelPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importFrameSampelPreview.url = (options?: RouteQueryOptions) => {
    return importFrameSampelPreview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::importFrameSampelPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importFrameSampelPreview.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importFrameSampelPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::importFrameSampelPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
const importFrameSampelPreviewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importFrameSampelPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::importFrameSampelPreview
 * @see app/Http/Controllers/KegiatanController.php:718
 * @route '/kegiatan/frame-sampel/import-preview'
 */
importFrameSampelPreviewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importFrameSampelPreview.url(options),
    method: 'post',
});

importFrameSampelPreview.form = importFrameSampelPreviewForm;
/**
 * @see \App\Http\Controllers\KegiatanController::store
 * @see app/Http/Controllers/KegiatanController.php:343
 * @route '/kegiatan/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/kegiatan/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::store
 * @see app/Http/Controllers/KegiatanController.php:343
 * @route '/kegiatan/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\KegiatanController::store
 * @see app/Http/Controllers/KegiatanController.php:343
 * @route '/kegiatan/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::store
 * @see app/Http/Controllers/KegiatanController.php:343
 * @route '/kegiatan/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::store
 * @see app/Http/Controllers/KegiatanController.php:343
 * @route '/kegiatan/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
export const edit = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
edit.url = (
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
        edit.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
edit.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
edit.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
const editForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
editForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::edit
 * @see app/Http/Controllers/KegiatanController.php:453
 * @route '/kegiatan/{kegiatan}/edit'
 */
editForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
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
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
const updateb9734cd5c2a78d737b907b15b48053bb = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateb9734cd5c2a78d737b907b15b48053bb.url(args, options),
    method: 'put',
});

updateb9734cd5c2a78d737b907b15b48053bb.definition = {
    methods: ['put', 'patch'],
    url: '/kegiatan/{kegiatan}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
updateb9734cd5c2a78d737b907b15b48053bb.url = (
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
        updateb9734cd5c2a78d737b907b15b48053bb.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
updateb9734cd5c2a78d737b907b15b48053bb.put = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateb9734cd5c2a78d737b907b15b48053bb.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
updateb9734cd5c2a78d737b907b15b48053bb.patch = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updateb9734cd5c2a78d737b907b15b48053bb.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
const updateb9734cd5c2a78d737b907b15b48053bbForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateb9734cd5c2a78d737b907b15b48053bb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
updateb9734cd5c2a78d737b907b15b48053bbForm.put = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateb9734cd5c2a78d737b907b15b48053bb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}/edit'
 */
updateb9734cd5c2a78d737b907b15b48053bbForm.patch = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateb9734cd5c2a78d737b907b15b48053bb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updateb9734cd5c2a78d737b907b15b48053bb.form =
    updateb9734cd5c2a78d737b907b15b48053bbForm;
/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
const update4b3696143f6d49310525b7a964fefec8 = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update4b3696143f6d49310525b7a964fefec8.url(args, options),
    method: 'put',
});

update4b3696143f6d49310525b7a964fefec8.definition = {
    methods: ['put'],
    url: '/kegiatan/{kegiatan}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8.url = (
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
        update4b3696143f6d49310525b7a964fefec8.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8.put = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update4b3696143f6d49310525b7a964fefec8.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
const update4b3696143f6d49310525b7a964fefec8Form = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update4b3696143f6d49310525b7a964fefec8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8Form.put = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update4b3696143f6d49310525b7a964fefec8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update4b3696143f6d49310525b7a964fefec8.form =
    update4b3696143f6d49310525b7a964fefec8Form;
/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
const update4b3696143f6d49310525b7a964fefec8 = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update4b3696143f6d49310525b7a964fefec8.url(args, options),
    method: 'patch',
});

update4b3696143f6d49310525b7a964fefec8.definition = {
    methods: ['patch'],
    url: '/kegiatan/{kegiatan}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8.url = (
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
        update4b3696143f6d49310525b7a964fefec8.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8.patch = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update4b3696143f6d49310525b7a964fefec8.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
const update4b3696143f6d49310525b7a964fefec8Form = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update4b3696143f6d49310525b7a964fefec8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::update
 * @see app/Http/Controllers/KegiatanController.php:506
 * @route '/kegiatan/{kegiatan}'
 */
update4b3696143f6d49310525b7a964fefec8Form.patch = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update4b3696143f6d49310525b7a964fefec8.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update4b3696143f6d49310525b7a964fefec8.form =
    update4b3696143f6d49310525b7a964fefec8Form;

export const update = {
    '/kegiatan/{kegiatan}/edit': updateb9734cd5c2a78d737b907b15b48053bb,
    '/kegiatan/{kegiatan}': update4b3696143f6d49310525b7a964fefec8,
    '/kegiatan/{kegiatan}': update4b3696143f6d49310525b7a964fefec8,
};

/**
 * @see \App\Http\Controllers\KegiatanController::destroy
 * @see app/Http/Controllers/KegiatanController.php:1255
 * @route '/kegiatan/{kegiatan}'
 */
export const destroy = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/kegiatan/{kegiatan}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\KegiatanController::destroy
 * @see app/Http/Controllers/KegiatanController.php:1255
 * @route '/kegiatan/{kegiatan}'
 */
destroy.url = (
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
        destroy.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::destroy
 * @see app/Http/Controllers/KegiatanController.php:1255
 * @route '/kegiatan/{kegiatan}'
 */
destroy.delete = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\KegiatanController::destroy
 * @see app/Http/Controllers/KegiatanController.php:1255
 * @route '/kegiatan/{kegiatan}'
 */
const destroyForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
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
 * @see \App\Http\Controllers\KegiatanController::destroy
 * @see app/Http/Controllers/KegiatanController.php:1255
 * @route '/kegiatan/{kegiatan}'
 */
destroyForm.delete = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
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
const KegiatanController = {
    manageRateHonor,
    bulkUpdateRateHonor,
    submit,
    approve,
    reject,
    create,
    index,
    show,
    copy,
    exportFrameSampelTemplate,
    importFrameSampelPreview,
    store,
    edit,
    update,
    destroy,
};

export default KegiatanController;
