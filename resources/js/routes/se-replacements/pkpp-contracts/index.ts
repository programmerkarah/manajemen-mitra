import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
export const create = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
create.url = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { replacement: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { replacement: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            replacement: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        replacement:
            typeof args.replacement === 'object'
                ? args.replacement.id
                : args.replacement,
    };

    return (
        create.definition.url
            .replace('{replacement}', parsedArgs.replacement.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
create.get = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
create.head = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
const createForm = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createForm.get = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::create
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createForm.head = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
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
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
export const store = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
store.url = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { replacement: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { replacement: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            replacement: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        replacement:
            typeof args.replacement === 'object'
                ? args.replacement.id
                : args.replacement,
    };

    return (
        store.definition.url
            .replace('{replacement}', parsedArgs.replacement.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
store.post = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
const storeForm = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::store
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
storeForm.post = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

store.form = storeForm;
const pkppContracts = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
};

export default pkppContracts;
