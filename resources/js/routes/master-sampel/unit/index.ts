import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/master-sampel/unit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
export const update = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/master-sampel/unit/{unit}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
update.url = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { unit: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { unit: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            unit: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        unit: typeof args.unit === 'object' ? args.unit.id : args.unit,
    };

    return (
        update.definition.url
            .replace('{unit}', parsedArgs.unit.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
update.put = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
const updateForm = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
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
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
updateForm.put = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
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
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
export const destroy = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/master-sampel/unit/{unit}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroy.url = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { unit: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { unit: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            unit: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        unit: typeof args.unit === 'object' ? args.unit.id : args.unit,
    };

    return (
        destroy.definition.url
            .replace('{unit}', parsedArgs.unit.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroy.delete = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
const destroyForm = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
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
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroyForm.delete = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
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
const unit = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default unit;
