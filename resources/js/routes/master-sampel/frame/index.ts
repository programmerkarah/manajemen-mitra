import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/master-sampel/frame',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::store
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
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
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
export const update = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/master-sampel/frame/{frame}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
update.url = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { frame: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { frame: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            frame: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        frame: typeof args.frame === 'object' ? args.frame.id : args.frame,
    };

    return (
        update.definition.url
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
update.put = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::update
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
const updateForm = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
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
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
updateForm.put = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
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
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
export const destroy = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/master-sampel/frame/{frame}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroy.url = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { frame: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { frame: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            frame: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        frame: typeof args.frame === 'object' ? args.frame.id : args.frame,
    };

    return (
        destroy.definition.url
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroy.delete = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroy
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
const destroyForm = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
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
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroyForm.delete = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
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
const frame = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default frame;
