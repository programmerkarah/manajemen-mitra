import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
export const manage = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
});

manage.definition = {
    methods: ['get', 'head'],
    url: '/kegiatan/{kegiatan}/rate-honor/manage',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manage.url = (
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
        manage.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manage.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: manage.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manage.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: manage.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
const manageForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manage.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageForm.get = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manage.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\KegiatanController::manage
 * @see app/Http/Controllers/KegiatanController.php:1287
 * @route '/kegiatan/{kegiatan}/rate-honor/manage'
 */
manageForm.head = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: manage.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

manage.form = manageForm;
/**
 * @see \App\Http\Controllers\KegiatanController::bulk
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
export const bulk = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: bulk.url(args, options),
    method: 'post',
});

bulk.definition = {
    methods: ['post'],
    url: '/kegiatan/{kegiatan}/rate-honor/bulk',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\KegiatanController::bulk
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulk.url = (
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
        bulk.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\KegiatanController::bulk
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulk.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: bulk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::bulk
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
const bulkForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: bulk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\KegiatanController::bulk
 * @see app/Http/Controllers/KegiatanController.php:1312
 * @route '/kegiatan/{kegiatan}/rate-honor/bulk'
 */
bulkForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: bulk.url(args, options),
    method: 'post',
});

bulk.form = bulkForm;
const rateHonor = {
    manage: Object.assign(manage, manage),
    bulk: Object.assign(bulk, bulk),
};

export default rateHonor;
