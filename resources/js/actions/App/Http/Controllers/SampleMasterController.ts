import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/master-sampel',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
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
 * @see \App\Http\Controllers\SampleMasterController::storeFrame
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
export const storeFrame = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeFrame.url(options),
    method: 'post',
});

storeFrame.definition = {
    methods: ['post'],
    url: '/master-sampel/frame',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::storeFrame
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
storeFrame.url = (options?: RouteQueryOptions) => {
    return storeFrame.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::storeFrame
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
storeFrame.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeFrame.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::storeFrame
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
const storeFrameForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeFrame.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::storeFrame
 * @see app/Http/Controllers/SampleMasterController.php:22
 * @route '/master-sampel/frame'
 */
storeFrameForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeFrame.url(options),
    method: 'post',
});

storeFrame.form = storeFrameForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::updateFrame
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
export const updateFrame = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateFrame.url(args, options),
    method: 'put',
});

updateFrame.definition = {
    methods: ['put'],
    url: '/master-sampel/frame/{frame}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::updateFrame
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
updateFrame.url = (
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
        updateFrame.definition.url
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::updateFrame
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
updateFrame.put = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateFrame.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::updateFrame
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
const updateFrameForm = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateFrame.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::updateFrame
 * @see app/Http/Controllers/SampleMasterController.php:39
 * @route '/master-sampel/frame/{frame}'
 */
updateFrameForm.put = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateFrame.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updateFrame.form = updateFrameForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::destroyFrame
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
export const destroyFrame = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyFrame.url(args, options),
    method: 'delete',
});

destroyFrame.definition = {
    methods: ['delete'],
    url: '/master-sampel/frame/{frame}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyFrame
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroyFrame.url = (
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
        destroyFrame.definition.url
            .replace('{frame}', parsedArgs.frame.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyFrame
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroyFrame.delete = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyFrame.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyFrame
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
const destroyFrameForm = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyFrame.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyFrame
 * @see app/Http/Controllers/SampleMasterController.php:56
 * @route '/master-sampel/frame/{frame}'
 */
destroyFrameForm.delete = (
    args:
        | { frame: number | { id: number } }
        | [frame: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyFrame.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroyFrame.form = destroyFrameForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::storeUnit
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
export const storeUnit = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeUnit.url(options),
    method: 'post',
});

storeUnit.definition = {
    methods: ['post'],
    url: '/master-sampel/unit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::storeUnit
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
storeUnit.url = (options?: RouteQueryOptions) => {
    return storeUnit.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::storeUnit
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
storeUnit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeUnit.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::storeUnit
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
const storeUnitForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeUnit.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::storeUnit
 * @see app/Http/Controllers/SampleMasterController.php:63
 * @route '/master-sampel/unit'
 */
storeUnitForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeUnit.url(options),
    method: 'post',
});

storeUnit.form = storeUnitForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::updateUnit
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
export const updateUnit = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateUnit.url(args, options),
    method: 'put',
});

updateUnit.definition = {
    methods: ['put'],
    url: '/master-sampel/unit/{unit}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::updateUnit
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
updateUnit.url = (
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
        updateUnit.definition.url
            .replace('{unit}', parsedArgs.unit.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::updateUnit
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
updateUnit.put = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updateUnit.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::updateUnit
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
const updateUnitForm = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateUnit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::updateUnit
 * @see app/Http/Controllers/SampleMasterController.php:80
 * @route '/master-sampel/unit/{unit}'
 */
updateUnitForm.put = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateUnit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updateUnit.form = updateUnitForm;
/**
 * @see \App\Http\Controllers\SampleMasterController::destroyUnit
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
export const destroyUnit = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyUnit.url(args, options),
    method: 'delete',
});

destroyUnit.definition = {
    methods: ['delete'],
    url: '/master-sampel/unit/{unit}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyUnit
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroyUnit.url = (
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
        destroyUnit.definition.url
            .replace('{unit}', parsedArgs.unit.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyUnit
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroyUnit.delete = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyUnit.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyUnit
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
const destroyUnitForm = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyUnit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::destroyUnit
 * @see app/Http/Controllers/SampleMasterController.php:97
 * @route '/master-sampel/unit/{unit}'
 */
destroyUnitForm.delete = (
    args:
        | { unit: number | { id: number } }
        | [unit: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyUnit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroyUnit.form = destroyUnitForm;
const SampleMasterController = {
    index,
    storeFrame,
    updateFrame,
    destroyFrame,
    storeUnit,
    updateUnit,
    destroyUnit,
};

export default SampleMasterController;
