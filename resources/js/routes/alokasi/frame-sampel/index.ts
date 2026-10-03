import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replace
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
export const replace = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: replace.url(args, options),
    method: 'patch',
});

replace.definition = {
    methods: ['patch'],
    url: '/alokasi/frame-sampel/{frameAllocation}/replace',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replace
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replace.url = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { frameAllocation: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { frameAllocation: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            frameAllocation: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        frameAllocation:
            typeof args.frameAllocation === 'object'
                ? args.frameAllocation.id
                : args.frameAllocation,
    };

    return (
        replace.definition.url
            .replace('{frameAllocation}', parsedArgs.frameAllocation.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replace
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replace.patch = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: replace.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replace
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
const replaceForm = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: replace.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replace
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replaceForm.patch = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: replace.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

replace.form = replaceForm;
const frameSampel = {
    replace: Object.assign(replace, replace),
};

export default frameSampel;
