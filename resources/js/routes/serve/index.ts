import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
export const download = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});

download.definition = {
    methods: ['get', 'head'],
    url: '/serve-download/{filename}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
download.url = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { filename: args };
    }

    if (Array.isArray(args)) {
        args = {
            filename: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        filename: args.filename,
    };

    return (
        download.definition.url
            .replace('{filename}', parsedArgs.filename.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
download.get = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});
/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
download.head = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
});

/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
const downloadForm = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});

/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
downloadForm.get = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});
/**
 * @see routes/web.php:146
 * @route '/serve-download/{filename}'
 */
downloadForm.head = (
    args:
        | { filename: string | number }
        | [filename: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

download.form = downloadForm;
const serve = {
    download: Object.assign(download, download),
};

export default serve;
