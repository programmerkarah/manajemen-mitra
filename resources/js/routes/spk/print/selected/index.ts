import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
export const main = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: main.url(args, options),
    method: 'post',
});

main.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/print-selected-main',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
main.url = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { periodeHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            periodeHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeHashedId: args.periodeHashedId,
    };

    return (
        main.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
main.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: main.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
const mainForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: main.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
mainForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: main.url(args, options),
    method: 'post',
});

main.form = mainForm;
/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
export const lampiran = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: lampiran.url(args, options),
    method: 'post',
});

lampiran.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/print-selected-lampiran',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
lampiran.url = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { periodeHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            periodeHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeHashedId: args.periodeHashedId,
    };

    return (
        lampiran.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
lampiran.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: lampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
const lampiranForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: lampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
lampiranForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: lampiran.url(args, options),
    method: 'post',
});

lampiran.form = lampiranForm;
const selected = {
    main: Object.assign(main, main),
    lampiran: Object.assign(lampiran, lampiran),
};

export default selected;
