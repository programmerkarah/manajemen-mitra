import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
export const form = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: form.url(options),
    method: 'get',
});

form.definition = {
    methods: ['get', 'head'],
    url: '/mitra',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
form.url = (options?: RouteQueryOptions) => {
    return form.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
form.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: form.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
form.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: form.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
const formForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: form.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
formForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: form.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::form
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
formForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: form.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

form.form = formForm;
/**
 * @see \App\Http\Controllers\SpkController::options
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
export const options = (
    routeOptions?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: options.url(routeOptions),
    method: 'post',
});

options.definition = {
    methods: ['post'],
    url: '/mitra/options',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::options
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
options.url = (routeOptions?: RouteQueryOptions) => {
    return options.definition.url + queryParams(routeOptions);
};

/**
 * @see \App\Http\Controllers\SpkController::options
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
options.post = (routeOptions?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: options.url(routeOptions),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::options
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
const optionsForm = (
    routeOptions?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: options.url(routeOptions),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::options
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
optionsForm.post = (
    routeOptions?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: options.url(routeOptions),
    method: 'post',
});

options.form = optionsForm;
/**
 * @see \App\Http\Controllers\SpkController::download
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
export const download = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: download.url(options),
    method: 'post',
});

download.definition = {
    methods: ['post'],
    url: '/mitra',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::download
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::download
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
download.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: download.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::download
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
const downloadForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: download.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::download
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
downloadForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: download.url(options),
    method: 'post',
});

download.form = downloadForm;
/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
export const file = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: file.url(args, options),
    method: 'get',
});

file.definition = {
    methods: ['get', 'head'],
    url: '/mitra/preview-file/{file}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
file.url = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { file: args };
    }

    if (Array.isArray(args)) {
        args = {
            file: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        file: args.file,
    };

    return (
        file.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
file.get = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: file.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
file.head = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: file.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
const fileForm = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: file.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
fileForm.get = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: file.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::file
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
fileForm.head = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: file.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

file.form = fileForm;
const publicPreview = {
    form: Object.assign(form, form),
    options: Object.assign(options, options),
    download: Object.assign(download, download),
    file: Object.assign(file, file),
};

export default publicPreview;
