import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import dokumenA65b6a from './dokumen';
import petugas996823 from './petugas';
import petugasOrganik34e06f from './petugas-organik';
import pulsa88577c from './pulsa';
import umum8346ef from './umum';
/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
export const petugas = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: petugas.url(options),
    method: 'get',
});

petugas.definition = {
    methods: ['get', 'head'],
    url: '/analisis/petugas',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
petugas.url = (options?: RouteQueryOptions) => {
    return petugas.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
petugas.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: petugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
petugas.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: petugas.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
const petugasForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugas.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
petugasForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::petugas
 * @see app/Http/Controllers/AnalisisController.php:23
 * @route '/analisis/petugas'
 */
petugasForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugas.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

petugas.form = petugasForm;
/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
export const petugasOrganik = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: petugasOrganik.url(options),
    method: 'get',
});

petugasOrganik.definition = {
    methods: ['get', 'head'],
    url: '/analisis/petugas-organik',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
petugasOrganik.url = (options?: RouteQueryOptions) => {
    return petugasOrganik.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
petugasOrganik.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: petugasOrganik.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
petugasOrganik.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: petugasOrganik.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
const petugasOrganikForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugasOrganik.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
petugasOrganikForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugasOrganik.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::petugasOrganik
 * @see app/Http/Controllers/AnalisisController.php:362
 * @route '/analisis/petugas-organik'
 */
petugasOrganikForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugasOrganik.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

petugasOrganik.form = petugasOrganikForm;
/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
export const pulsa = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pulsa.url(options),
    method: 'get',
});

pulsa.definition = {
    methods: ['get', 'head'],
    url: '/analisis/pulsa',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
pulsa.url = (options?: RouteQueryOptions) => {
    return pulsa.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
pulsa.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pulsa.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
pulsa.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pulsa.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
const pulsaForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: pulsa.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
pulsaForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pulsa.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::pulsa
 * @see app/Http/Controllers/AnalisisController.php:483
 * @route '/analisis/pulsa'
 */
pulsaForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pulsa.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

pulsa.form = pulsaForm;
/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
export const dokumen = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: dokumen.url(options),
    method: 'get',
});

dokumen.definition = {
    methods: ['get', 'head'],
    url: '/analisis/dokumen',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
dokumen.url = (options?: RouteQueryOptions) => {
    return dokumen.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
dokumen.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dokumen.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
dokumen.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dokumen.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
const dokumenForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dokumen.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
dokumenForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dokumen.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::dokumen
 * @see app/Http/Controllers/AnalisisController.php:567
 * @route '/analisis/dokumen'
 */
dokumenForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dokumen.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

dokumen.form = dokumenForm;
/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
export const umum = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: umum.url(options),
    method: 'get',
});

umum.definition = {
    methods: ['get', 'head'],
    url: '/analisis/umum',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
umum.url = (options?: RouteQueryOptions) => {
    return umum.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
umum.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: umum.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
umum.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: umum.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
const umumForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: umum.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
umumForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: umum.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisController::umum
 * @see app/Http/Controllers/AnalisisController.php:709
 * @route '/analisis/umum'
 */
umumForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: umum.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

umum.form = umumForm;
const analisis = {
    petugas: Object.assign(petugas, petugas996823),
    petugasOrganik: Object.assign(petugasOrganik, petugasOrganik34e06f),
    pulsa: Object.assign(pulsa, pulsa88577c),
    dokumen: Object.assign(dokumen, dokumenA65b6a),
    umum: Object.assign(umum, umum8346ef),
};

export default analisis;
