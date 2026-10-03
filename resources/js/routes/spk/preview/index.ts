import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
export const main = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: main.url(args, options),
    method: 'post',
});

main.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
main.url = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            periodeHashedId: args[0],
            petugasHashedId: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeHashedId: args.periodeHashedId,
        petugasHashedId: args.petugasHashedId,
    };

    return (
        main.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
main.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: main.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
const mainForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: main.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::main
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
mainForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: main.url(args, options),
    method: 'post',
});

main.form = mainForm;
/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
export const lampiran = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: lampiran.url(args, options),
    method: 'post',
});

lampiran.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
lampiran.url = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            periodeHashedId: args[0],
            petugasHashedId: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeHashedId: args.periodeHashedId,
        petugasHashedId: args.petugasHashedId,
    };

    return (
        lampiran.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
lampiran.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: lampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
const lampiranForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: lampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::lampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
lampiranForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: lampiran.url(args, options),
    method: 'post',
});

lampiran.form = lampiranForm;
/**
 * @see \App\Http\Controllers\SpkController::all
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
export const all = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: all.url(args, options),
    method: 'post',
});

all.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/preview-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::all
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
all.url = (
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
        all.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::all
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
all.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: all.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::all
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
const allForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: all.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::all
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
allForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: all.url(args, options),
    method: 'post',
});

all.form = allForm;
const preview = {
    main: Object.assign(main, main),
    lampiran: Object.assign(lampiran, lampiran),
    all: Object.assign(all, all),
};

export default preview;
