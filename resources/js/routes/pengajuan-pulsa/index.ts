import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import create4f58d6 from './create';
import detail30d213 from './detail';
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
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
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
export const filter = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

filter.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
filter.url = (options?: RouteQueryOptions) => {
    return filter.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
filter.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
const filterForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::filter
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
filterForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: filter.url(options),
    method: 'post',
});

filter.form = filterForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
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
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
export const template = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});

template.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/template',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
template.url = (options?: RouteQueryOptions) => {
    return template.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
template.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
template.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: template.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
const templateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
templateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::template
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
templateForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: template.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

template.form = templateForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::importPreview
 * @see app/Http/Controllers/PengajuanPulsaController.php:465
 * @route '/pengajuan-pulsa/import-preview'
 */
export const importPreview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

importPreview.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::importPreview
 * @see app/Http/Controllers/PengajuanPulsaController.php:465
 * @route '/pengajuan-pulsa/import-preview'
 */
importPreview.url = (options?: RouteQueryOptions) => {
    return importPreview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::importPreview
 * @see app/Http/Controllers/PengajuanPulsaController.php:465
 * @route '/pengajuan-pulsa/import-preview'
 */
importPreview.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::importPreview
 * @see app/Http/Controllers/PengajuanPulsaController.php:465
 * @route '/pengajuan-pulsa/import-preview'
 */
const importPreviewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::importPreview
 * @see app/Http/Controllers/PengajuanPulsaController.php:465
 * @route '/pengajuan-pulsa/import-preview'
 */
importPreviewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(options),
    method: 'post',
});

importPreview.form = importPreviewForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
export const detail = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: detail.url(options),
    method: 'get',
});

detail.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/detail',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail.url = (options?: RouteQueryOptions) => {
    return detail.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: detail.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: detail.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
const detailForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detail.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detailForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: detail.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detailForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detail.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

detail.form = detailForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::store
 * @see app/Http/Controllers/PengajuanPulsaController.php:863
 * @route '/pengajuan-pulsa'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::store
 * @see app/Http/Controllers/PengajuanPulsaController.php:863
 * @route '/pengajuan-pulsa'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::store
 * @see app/Http/Controllers/PengajuanPulsaController.php:863
 * @route '/pengajuan-pulsa'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::store
 * @see app/Http/Controllers/PengajuanPulsaController.php:863
 * @route '/pengajuan-pulsa'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::store
 * @see app/Http/Controllers/PengajuanPulsaController.php:863
 * @route '/pengajuan-pulsa'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::resubmit
 * @see app/Http/Controllers/PengajuanPulsaController.php:1119
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/resubmit'
 */
export const resubmit = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: resubmit.url(args, options),
    method: 'post',
});

resubmit.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/{pengajuanPulsa}/resubmit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::resubmit
 * @see app/Http/Controllers/PengajuanPulsaController.php:1119
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/resubmit'
 */
resubmit.url = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { pengajuanPulsa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { pengajuanPulsa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            pengajuanPulsa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        pengajuanPulsa:
            typeof args.pengajuanPulsa === 'object'
                ? args.pengajuanPulsa.id
                : args.pengajuanPulsa,
    };

    return (
        resubmit.definition.url
            .replace('{pengajuanPulsa}', parsedArgs.pengajuanPulsa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::resubmit
 * @see app/Http/Controllers/PengajuanPulsaController.php:1119
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/resubmit'
 */
resubmit.post = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: resubmit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::resubmit
 * @see app/Http/Controllers/PengajuanPulsaController.php:1119
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/resubmit'
 */
const resubmitForm = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: resubmit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::resubmit
 * @see app/Http/Controllers/PengajuanPulsaController.php:1119
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/resubmit'
 */
resubmitForm.post = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: resubmit.url(args, options),
    method: 'post',
});

resubmit.form = resubmitForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::review
 * @see app/Http/Controllers/PengajuanPulsaController.php:1204
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/review'
 */
export const review = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
});

review.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/{pengajuanPulsa}/review',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::review
 * @see app/Http/Controllers/PengajuanPulsaController.php:1204
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/review'
 */
review.url = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { pengajuanPulsa: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { pengajuanPulsa: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            pengajuanPulsa: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        pengajuanPulsa:
            typeof args.pengajuanPulsa === 'object'
                ? args.pengajuanPulsa.id
                : args.pengajuanPulsa,
    };

    return (
        review.definition.url
            .replace('{pengajuanPulsa}', parsedArgs.pengajuanPulsa.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::review
 * @see app/Http/Controllers/PengajuanPulsaController.php:1204
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/review'
 */
review.post = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::review
 * @see app/Http/Controllers/PengajuanPulsaController.php:1204
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/review'
 */
const reviewForm = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::review
 * @see app/Http/Controllers/PengajuanPulsaController.php:1204
 * @route '/pengajuan-pulsa/{pengajuanPulsa}/review'
 */
reviewForm.post = (
    args:
        | { pengajuanPulsa: number | { id: number } }
        | [pengajuanPulsa: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
});

review.form = reviewForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::reviewAll
 * @see app/Http/Controllers/PengajuanPulsaController.php:1278
 * @route '/pengajuan-pulsa/review-all'
 */
export const reviewAll = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reviewAll.url(options),
    method: 'post',
});

reviewAll.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/review-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::reviewAll
 * @see app/Http/Controllers/PengajuanPulsaController.php:1278
 * @route '/pengajuan-pulsa/review-all'
 */
reviewAll.url = (options?: RouteQueryOptions) => {
    return reviewAll.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::reviewAll
 * @see app/Http/Controllers/PengajuanPulsaController.php:1278
 * @route '/pengajuan-pulsa/review-all'
 */
reviewAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reviewAll.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::reviewAll
 * @see app/Http/Controllers/PengajuanPulsaController.php:1278
 * @route '/pengajuan-pulsa/review-all'
 */
const reviewAllForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reviewAll.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::reviewAll
 * @see app/Http/Controllers/PengajuanPulsaController.php:1278
 * @route '/pengajuan-pulsa/review-all'
 */
reviewAllForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reviewAll.url(options),
    method: 'post',
});

reviewAll.form = reviewAllForm;
const pengajuanPulsa = {
    index: Object.assign(index, index),
    filter: Object.assign(filter, filter),
    create: Object.assign(create, create4f58d6),
    template: Object.assign(template, template),
    importPreview: Object.assign(importPreview, importPreview),
    detail: Object.assign(detail, detail30d213),
    store: Object.assign(store, store),
    resubmit: Object.assign(resubmit, resubmit),
    review: Object.assign(review, review),
    reviewAll: Object.assign(reviewAll, reviewAll),
};

export default pengajuanPulsa;
