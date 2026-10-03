import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
const index27acacc5484f59d4dc6c7e50de40ef4a = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: index27acacc5484f59d4dc6c7e50de40ef4a.url(options),
    method: 'get',
});

index27acacc5484f59d4dc6c7e50de40ef4a.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index27acacc5484f59d4dc6c7e50de40ef4a.url = (options?: RouteQueryOptions) => {
    return (
        index27acacc5484f59d4dc6c7e50de40ef4a.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index27acacc5484f59d4dc6c7e50de40ef4a.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: index27acacc5484f59d4dc6c7e50de40ef4a.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index27acacc5484f59d4dc6c7e50de40ef4a.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: index27acacc5484f59d4dc6c7e50de40ef4a.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
const index27acacc5484f59d4dc6c7e50de40ef4aForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index27acacc5484f59d4dc6c7e50de40ef4a.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index27acacc5484f59d4dc6c7e50de40ef4aForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index27acacc5484f59d4dc6c7e50de40ef4a.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa'
 */
index27acacc5484f59d4dc6c7e50de40ef4aForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index27acacc5484f59d4dc6c7e50de40ef4a.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

index27acacc5484f59d4dc6c7e50de40ef4a.form =
    index27acacc5484f59d4dc6c7e50de40ef4aForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
const index872feb02b5e2c444fa1794cc69a7e71b = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: index872feb02b5e2c444fa1794cc69a7e71b.url(options),
    method: 'post',
});

index872feb02b5e2c444fa1794cc69a7e71b.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
index872feb02b5e2c444fa1794cc69a7e71b.url = (options?: RouteQueryOptions) => {
    return (
        index872feb02b5e2c444fa1794cc69a7e71b.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
index872feb02b5e2c444fa1794cc69a7e71b.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: index872feb02b5e2c444fa1794cc69a7e71b.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
const index872feb02b5e2c444fa1794cc69a7e71bForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index872feb02b5e2c444fa1794cc69a7e71b.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::index
 * @see app/Http/Controllers/PengajuanPulsaController.php:52
 * @route '/pengajuan-pulsa/filter'
 */
index872feb02b5e2c444fa1794cc69a7e71bForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index872feb02b5e2c444fa1794cc69a7e71b.url(options),
    method: 'post',
});

index872feb02b5e2c444fa1794cc69a7e71b.form =
    index872feb02b5e2c444fa1794cc69a7e71bForm;

export const index = {
    '/pengajuan-pulsa': index27acacc5484f59d4dc6c7e50de40ef4a,
    '/pengajuan-pulsa/filter': index872feb02b5e2c444fa1794cc69a7e71b,
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const create6e0964a797aa34627dfee4f63efbb28e = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'get',
});

create6e0964a797aa34627dfee4f63efbb28e.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28e.url = (options?: RouteQueryOptions) => {
    return (
        create6e0964a797aa34627dfee4f63efbb28e.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28e.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28e.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const create6e0964a797aa34627dfee4f63efbb28eForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28eForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28eForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create6e0964a797aa34627dfee4f63efbb28e.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

create6e0964a797aa34627dfee4f63efbb28e.form =
    create6e0964a797aa34627dfee4f63efbb28eForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const create6e0964a797aa34627dfee4f63efbb28e = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'post',
});

create6e0964a797aa34627dfee4f63efbb28e.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/create',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28e.url = (options?: RouteQueryOptions) => {
    return (
        create6e0964a797aa34627dfee4f63efbb28e.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28e.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
const create6e0964a797aa34627dfee4f63efbb28eForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create'
 */
create6e0964a797aa34627dfee4f63efbb28eForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: create6e0964a797aa34627dfee4f63efbb28e.url(options),
    method: 'post',
});

create6e0964a797aa34627dfee4f63efbb28e.form =
    create6e0964a797aa34627dfee4f63efbb28eForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
const create446765b51d0482f087859e63ae0dffa1 = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: create446765b51d0482f087859e63ae0dffa1.url(options),
    method: 'post',
});

create446765b51d0482f087859e63ae0dffa1.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/create/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
create446765b51d0482f087859e63ae0dffa1.url = (options?: RouteQueryOptions) => {
    return (
        create446765b51d0482f087859e63ae0dffa1.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
create446765b51d0482f087859e63ae0dffa1.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: create446765b51d0482f087859e63ae0dffa1.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
const create446765b51d0482f087859e63ae0dffa1Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: create446765b51d0482f087859e63ae0dffa1.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::create
 * @see app/Http/Controllers/PengajuanPulsaController.php:98
 * @route '/pengajuan-pulsa/create/filter'
 */
create446765b51d0482f087859e63ae0dffa1Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: create446765b51d0482f087859e63ae0dffa1.url(options),
    method: 'post',
});

create446765b51d0482f087859e63ae0dffa1.form =
    create446765b51d0482f087859e63ae0dffa1Form;

export const create = {
    '/pengajuan-pulsa/create': create6e0964a797aa34627dfee4f63efbb28e,
    '/pengajuan-pulsa/create': create6e0964a797aa34627dfee4f63efbb28e,
    '/pengajuan-pulsa/create/filter': create446765b51d0482f087859e63ae0dffa1,
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
export const downloadTemplate = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});

downloadTemplate.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/template',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
downloadTemplate.url = (options?: RouteQueryOptions) => {
    return downloadTemplate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
downloadTemplate.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
downloadTemplate.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadTemplate.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
const downloadTemplateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
downloadTemplateForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::downloadTemplate
 * @see app/Http/Controllers/PengajuanPulsaController.php:440
 * @route '/pengajuan-pulsa/template'
 */
downloadTemplateForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadTemplate.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadTemplate.form = downloadTemplateForm;
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
const detail3f2443307d9177c05bfd6578373af3e3 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'get',
});

detail3f2443307d9177c05bfd6578373af3e3.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/detail',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3.url = (options?: RouteQueryOptions) => {
    return (
        detail3f2443307d9177c05bfd6578373af3e3.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
const detail3f2443307d9177c05bfd6578373af3e3Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detail3f2443307d9177c05bfd6578373af3e3.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

detail3f2443307d9177c05bfd6578373af3e3.form =
    detail3f2443307d9177c05bfd6578373af3e3Form;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const detailb662612c6a7e109ce8e3d32f179ebd6c = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'get',
});

detailb662612c6a7e109ce8e3d32f179ebd6c.definition = {
    methods: ['get', 'head'],
    url: '/pengajuan-pulsa/detail/filter',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6c.url = (options?: RouteQueryOptions) => {
    return (
        detailb662612c6a7e109ce8e3d32f179ebd6c.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6c.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6c.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const detailb662612c6a7e109ce8e3d32f179ebd6cForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6cForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6cForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: detailb662612c6a7e109ce8e3d32f179ebd6c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

detailb662612c6a7e109ce8e3d32f179ebd6c.form =
    detailb662612c6a7e109ce8e3d32f179ebd6cForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const detailb662612c6a7e109ce8e3d32f179ebd6c = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'post',
});

detailb662612c6a7e109ce8e3d32f179ebd6c.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/detail/filter',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6c.url = (options?: RouteQueryOptions) => {
    return (
        detailb662612c6a7e109ce8e3d32f179ebd6c.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6c.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
const detailb662612c6a7e109ce8e3d32f179ebd6cForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail/filter'
 */
detailb662612c6a7e109ce8e3d32f179ebd6cForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: detailb662612c6a7e109ce8e3d32f179ebd6c.url(options),
    method: 'post',
});

detailb662612c6a7e109ce8e3d32f179ebd6c.form =
    detailb662612c6a7e109ce8e3d32f179ebd6cForm;
/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
const detail3f2443307d9177c05bfd6578373af3e3 = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'post',
});

detail3f2443307d9177c05bfd6578373af3e3.definition = {
    methods: ['post'],
    url: '/pengajuan-pulsa/detail',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3.url = (options?: RouteQueryOptions) => {
    return (
        detail3f2443307d9177c05bfd6578373af3e3.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
const detail3f2443307d9177c05bfd6578373af3e3Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PengajuanPulsaController::detail
 * @see app/Http/Controllers/PengajuanPulsaController.php:1015
 * @route '/pengajuan-pulsa/detail'
 */
detail3f2443307d9177c05bfd6578373af3e3Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: detail3f2443307d9177c05bfd6578373af3e3.url(options),
    method: 'post',
});

detail3f2443307d9177c05bfd6578373af3e3.form =
    detail3f2443307d9177c05bfd6578373af3e3Form;

export const detail = {
    '/pengajuan-pulsa/detail': detail3f2443307d9177c05bfd6578373af3e3,
    '/pengajuan-pulsa/detail/filter': detailb662612c6a7e109ce8e3d32f179ebd6c,
    '/pengajuan-pulsa/detail/filter': detailb662612c6a7e109ce8e3d32f179ebd6c,
    '/pengajuan-pulsa/detail': detail3f2443307d9177c05bfd6578373af3e3,
};

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
const PengajuanPulsaController = {
    index,
    create,
    downloadTemplate,
    importPreview,
    detail,
    store,
    resubmit,
    review,
    reviewAll,
};

export default PengajuanPulsaController;
