import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/spk/petugas-pengganti',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::index
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:35
 * @route '/spk/petugas-pengganti'
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
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
export const createPkppContract = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createPkppContract.url(args, options),
    method: 'get',
});

createPkppContract.definition = {
    methods: ['get', 'head'],
    url: '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createPkppContract.url = (
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
        createPkppContract.definition.url
            .replace('{replacement}', parsedArgs.replacement.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createPkppContract.get = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createPkppContract.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createPkppContract.head = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: createPkppContract.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
const createPkppContractForm = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createPkppContract.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createPkppContractForm.get = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createPkppContract.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::createPkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:73
 * @route '/spk/petugas-pengganti/{replacement}/pkpp-contracts/create'
 */
createPkppContractForm.head = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createPkppContract.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

createPkppContract.form = createPkppContractForm;
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storeReplacement
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
export const storeReplacement = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeReplacement.url(options),
    method: 'post',
});

storeReplacement.definition = {
    methods: ['post'],
    url: '/sensus-ekonomi/replacements',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storeReplacement
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
storeReplacement.url = (options?: RouteQueryOptions) => {
    return storeReplacement.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storeReplacement
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
storeReplacement.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeReplacement.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storeReplacement
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
const storeReplacementForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeReplacement.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storeReplacement
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:137
 * @route '/sensus-ekonomi/replacements'
 */
storeReplacementForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeReplacement.url(options),
    method: 'post',
});

storeReplacement.form = storeReplacementForm;
/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storePkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
export const storePkppContract = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storePkppContract.url(args, options),
    method: 'post',
});

storePkppContract.definition = {
    methods: ['post'],
    url: '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storePkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
storePkppContract.url = (
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
        storePkppContract.definition.url
            .replace('{replacement}', parsedArgs.replacement.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storePkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
storePkppContract.post = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storePkppContract.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storePkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
const storePkppContractForm = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storePkppContract.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SensusEkonomiReplacementController::storePkppContract
 * @see app/Http/Controllers/SensusEkonomiReplacementController.php:233
 * @route '/sensus-ekonomi/replacements/{replacement}/pkpp-contracts'
 */
storePkppContractForm.post = (
    args:
        | { replacement: number | { id: number } }
        | [replacement: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storePkppContract.url(args, options),
    method: 'post',
});

storePkppContract.form = storePkppContractForm;
const SensusEkonomiReplacementController = {
    index,
    createPkppContract,
    storeReplacement,
    storePkppContract,
};

export default SensusEkonomiReplacementController;
