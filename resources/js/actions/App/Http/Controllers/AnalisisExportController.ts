import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
export const umum = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: umum.url(options),
    method: 'get',
});

umum.definition = {
    methods: ['get', 'head'],
    url: '/analisis/umum/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
umum.url = (options?: RouteQueryOptions) => {
    return umum.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
umum.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: umum.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
umum.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: umum.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
const umumForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: umum.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
 */
umumForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: umum.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::umum
 * @see app/Http/Controllers/AnalisisExportController.php:24
 * @route '/analisis/umum/export-pdf'
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
/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
export const petugas = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: petugas.url(options),
    method: 'get',
});

petugas.definition = {
    methods: ['get', 'head'],
    url: '/analisis/petugas/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
petugas.url = (options?: RouteQueryOptions) => {
    return petugas.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
petugas.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: petugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
petugas.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: petugas.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
const petugasForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugas.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
 */
petugasForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugas.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::petugas
 * @see app/Http/Controllers/AnalisisExportController.php:205
 * @route '/analisis/petugas/export-pdf'
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
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
export const petugasOrganik = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: petugasOrganik.url(options),
    method: 'get',
});

petugasOrganik.definition = {
    methods: ['get', 'head'],
    url: '/analisis/petugas-organik/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
petugasOrganik.url = (options?: RouteQueryOptions) => {
    return petugasOrganik.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
petugasOrganik.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: petugasOrganik.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
petugasOrganik.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: petugasOrganik.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
const petugasOrganikForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugasOrganik.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
 */
petugasOrganikForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: petugasOrganik.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::petugasOrganik
 * @see app/Http/Controllers/AnalisisExportController.php:605
 * @route '/analisis/petugas-organik/export-pdf'
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
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
export const pulsa = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pulsa.url(options),
    method: 'get',
});

pulsa.definition = {
    methods: ['get', 'head'],
    url: '/analisis/pulsa/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
pulsa.url = (options?: RouteQueryOptions) => {
    return pulsa.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
pulsa.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pulsa.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
pulsa.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pulsa.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
const pulsaForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: pulsa.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
 */
pulsaForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pulsa.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::pulsa
 * @see app/Http/Controllers/AnalisisExportController.php:749
 * @route '/analisis/pulsa/export-pdf'
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
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
export const dokumen = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: dokumen.url(options),
    method: 'get',
});

dokumen.definition = {
    methods: ['get', 'head'],
    url: '/analisis/dokumen/export-pdf',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
dokumen.url = (options?: RouteQueryOptions) => {
    return dokumen.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
dokumen.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dokumen.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
dokumen.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dokumen.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
const dokumenForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dokumen.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
 */
dokumenForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: dokumen.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AnalisisExportController::dokumen
 * @see app/Http/Controllers/AnalisisExportController.php:865
 * @route '/analisis/dokumen/export-pdf'
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
const AnalisisExportController = {
    umum,
    petugas,
    petugasOrganik,
    pulsa,
    dokumen,
};

export default AnalisisExportController;
