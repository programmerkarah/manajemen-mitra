import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
export const create = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
const createForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
 */
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::create
 * @see app/Http/Controllers/AlokasiPetugasController.php:1288
 * @route '/alokasi/create'
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
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/alokasi',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::index
 * @see app/Http/Controllers/AlokasiPetugasController.php:48
 * @route '/alokasi'
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
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
export const show = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        show.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
show.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
const showForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
showForm.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:1834
 * @route '/alokasi/{alokasi}'
 */
showForm.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

show.form = showForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
export const showPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showPeriode.url(args, options),
    method: 'get',
});

showPeriode.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        showPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showPeriode.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showPeriode.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showPeriode.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: showPeriode.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const showPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showPeriode.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showPeriodeForm.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showPeriode.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::showPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showPeriodeForm.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showPeriode.form = showPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
export const exportMonitoringSkgbPdf = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportMonitoringSkgbPdf.url(args, options),
    method: 'get',
});

exportMonitoringSkgbPdf.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbPdf.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        exportMonitoringSkgbPdf.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbPdf.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportMonitoringSkgbPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbPdf.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportMonitoringSkgbPdf.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
const exportMonitoringSkgbPdfForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMonitoringSkgbPdf.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbPdfForm.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMonitoringSkgbPdf.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgbPdf
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbPdfForm.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportMonitoringSkgbPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportMonitoringSkgbPdf.form = exportMonitoringSkgbPdfForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/alokasi/store',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::store
 * @see app/Http/Controllers/AlokasiPetugasController.php:1753
 * @route '/alokasi/store'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
export const storeMultiple = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeMultiple.url(args, options),
    method: 'post',
});

storeMultiple.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/store-multiple',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultiple.url = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { kegiatan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
    };

    return (
        storeMultiple.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultiple.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeMultiple.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
const storeMultipleForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeMultiple.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::storeMultiple
 * @see app/Http/Controllers/AlokasiPetugasController.php:670
 * @route '/alokasi/kegiatan/{kegiatan}/store-multiple'
 */
storeMultipleForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeMultiple.url(args, options),
    method: 'post',
});

storeMultiple.form = storeMultipleForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
export const importCreate = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importCreate.url(args, options),
    method: 'post',
});

importCreate.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreate.url = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { kegiatan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
    };

    return (
        importCreate.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreate.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importCreate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
const importCreateForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importCreate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:5123
 * @route '/alokasi/kegiatan/{kegiatan}/import'
 */
importCreateForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importCreate.url(args, options),
    method: 'post',
});

importCreate.form = importCreateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
export const importPreview = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(args, options),
    method: 'post',
});

importPreview.definition = {
    methods: ['post'],
    url: '/alokasi/kegiatan/{kegiatan}/import-preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreview.url = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { kegiatan: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { kegiatan: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan:
            typeof args.kegiatan === 'object'
                ? args.kegiatan.id
                : args.kegiatan,
    };

    return (
        importPreview.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreview.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importPreview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
const importPreviewForm = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importPreview
 * @see app/Http/Controllers/AlokasiPetugasController.php:4890
 * @route '/alokasi/kegiatan/{kegiatan}/import-preview'
 */
importPreviewForm.post = (
    args:
        | { kegiatan: number | { id: number } }
        | [kegiatan: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importPreview.url(args, options),
    method: 'post',
});

importPreview.form = importPreviewForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
export const edit = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/{alokasi}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        edit.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
edit.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
const editForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
editForm.get = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1852
 * @route '/alokasi/{alokasi}/edit'
 */
editForm.head = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

edit.form = editForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
const update318bce536d1768b158945a1619ea4d13 = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update318bce536d1768b158945a1619ea4d13.url(args, options),
    method: 'put',
});

update318bce536d1768b158945a1619ea4d13.definition = {
    methods: ['put', 'patch'],
    url: '/alokasi/{alokasi}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
update318bce536d1768b158945a1619ea4d13.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        update318bce536d1768b158945a1619ea4d13.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
update318bce536d1768b158945a1619ea4d13.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update318bce536d1768b158945a1619ea4d13.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
update318bce536d1768b158945a1619ea4d13.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update318bce536d1768b158945a1619ea4d13.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
const update318bce536d1768b158945a1619ea4d13Form = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update318bce536d1768b158945a1619ea4d13.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
update318bce536d1768b158945a1619ea4d13Form.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update318bce536d1768b158945a1619ea4d13.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}/edit'
 */
update318bce536d1768b158945a1619ea4d13Form.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update318bce536d1768b158945a1619ea4d13.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update318bce536d1768b158945a1619ea4d13.form =
    update318bce536d1768b158945a1619ea4d13Form;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
const update36702e7929cb01ccd56304afc548c67c = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update36702e7929cb01ccd56304afc548c67c.url(args, options),
    method: 'put',
});

update36702e7929cb01ccd56304afc548c67c.definition = {
    methods: ['put', 'patch'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update36702e7929cb01ccd56304afc548c67c.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        update36702e7929cb01ccd56304afc548c67c.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update36702e7929cb01ccd56304afc548c67c.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update36702e7929cb01ccd56304afc548c67c.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update36702e7929cb01ccd56304afc548c67c.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update36702e7929cb01ccd56304afc548c67c.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
const update36702e7929cb01ccd56304afc548c67cForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update36702e7929cb01ccd56304afc548c67c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update36702e7929cb01ccd56304afc548c67cForm.put = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update36702e7929cb01ccd56304afc548c67c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:1878
 * @route '/alokasi/{alokasi}'
 */
update36702e7929cb01ccd56304afc548c67cForm.patch = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update36702e7929cb01ccd56304afc548c67c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update36702e7929cb01ccd56304afc548c67c.form =
    update36702e7929cb01ccd56304afc548c67cForm;

export const update = {
    '/alokasi/{alokasi}/edit': update318bce536d1768b158945a1619ea4d13,
    '/alokasi/{alokasi}': update36702e7929cb01ccd56304afc548c67c,
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
export const destroy = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/alokasi/{alokasi}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroy.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        destroy.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroy.delete = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
const destroyForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:1957
 * @route '/alokasi/{alokasi}'
 */
destroyForm.delete = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroy.form = destroyForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submitPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
export const submitPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submitPeriode.url(args, options),
    method: 'post',
});

submitPeriode.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submitPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submitPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        submitPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submitPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submitPeriode.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submitPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submitPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
const submitPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submitPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submitPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submitPeriodeForm.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submitPeriode.url(args, options),
    method: 'post',
});

submitPeriode.form = submitPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
export const editPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: editPeriode.url(args, options),
    method: 'get',
});

editPeriode.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        editPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editPeriode.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: editPeriode.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editPeriode.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: editPeriode.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
const editPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: editPeriode.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editPeriodeForm.get = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: editPeriode.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::editPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editPeriodeForm.head = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: editPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

editPeriode.form = editPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
const updatePeriodef3e40388a28cc396996e515b85a40c9e = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, options),
    method: 'put',
});

updatePeriodef3e40388a28cc396996e515b85a40c9e.definition = {
    methods: ['put', 'patch'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
updatePeriodef3e40388a28cc396996e515b85a40c9e.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        updatePeriodef3e40388a28cc396996e515b85a40c9e.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
updatePeriodef3e40388a28cc396996e515b85a40c9e.put = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
updatePeriodef3e40388a28cc396996e515b85a40c9e.patch = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
const updatePeriodef3e40388a28cc396996e515b85a40c9eForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
updatePeriodef3e40388a28cc396996e515b85a40c9eForm.put = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
updatePeriodef3e40388a28cc396996e515b85a40c9eForm.patch = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatePeriodef3e40388a28cc396996e515b85a40c9e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatePeriodef3e40388a28cc396996e515b85a40c9e.form =
    updatePeriodef3e40388a28cc396996e515b85a40c9eForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const updatePeriode161b234a92cd598ce6093bf21edc7bae = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatePeriode161b234a92cd598ce6093bf21edc7bae.url(args, options),
    method: 'put',
});

updatePeriode161b234a92cd598ce6093bf21edc7bae.definition = {
    methods: ['put'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
updatePeriode161b234a92cd598ce6093bf21edc7bae.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        updatePeriode161b234a92cd598ce6093bf21edc7bae.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
updatePeriode161b234a92cd598ce6093bf21edc7bae.put = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatePeriode161b234a92cd598ce6093bf21edc7bae.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const updatePeriode161b234a92cd598ce6093bf21edc7baeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatePeriode161b234a92cd598ce6093bf21edc7bae.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updatePeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
updatePeriode161b234a92cd598ce6093bf21edc7baeForm.put = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatePeriode161b234a92cd598ce6093bf21edc7bae.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatePeriode161b234a92cd598ce6093bf21edc7bae.form =
    updatePeriode161b234a92cd598ce6093bf21edc7baeForm;

export const updatePeriode = {
    '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit':
        updatePeriodef3e40388a28cc396996e515b85a40c9e,
    '/alokasi/periode/{kegiatan}/{tahun}/{bulan}':
        updatePeriode161b234a92cd598ce6093bf21edc7bae,
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroyPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
export const destroyPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyPeriode.url(args, options),
    method: 'delete',
});

destroyPeriode.definition = {
    methods: ['delete'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroyPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroyPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        destroyPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroyPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroyPeriode.delete = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroyPeriode.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroyPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const destroyPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroyPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroyPeriodeForm.delete = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: destroyPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

destroyPeriode.form = destroyPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
export const exportTemplateCreate = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'get',
});

exportTemplateCreate.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/export/{type}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.url = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { type: args };
    }

    if (Array.isArray(args)) {
        args = {
            type: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        type: args.type,
    };

    return (
        exportTemplateCreate.definition.url
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.get = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreate.head = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportTemplateCreate.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
const exportTemplateCreateForm = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreateForm.get = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplateCreate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4788
 * @route '/alokasi/periode/export/{type}'
 */
exportTemplateCreateForm.head = (
    args: { type: string | number } | [type: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplateCreate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportTemplateCreate.form = exportTemplateCreateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
export const exportTemplate = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});

exportTemplate.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{periodeAlokasiHash}/export/{type}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.url = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            periodeAlokasiHash: args[0],
            type: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeAlokasiHash: args.periodeAlokasiHash,
        type: args.type,
    };

    return (
        exportTemplate.definition.url
            .replace(
                '{periodeAlokasiHash}',
                parsedArgs.periodeAlokasiHash.toString(),
            )
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.get = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplate.head = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: exportTemplate.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
const exportTemplateForm = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplateForm.get = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportTemplate
 * @see app/Http/Controllers/AlokasiPetugasController.php:4810
 * @route '/alokasi/periode/{periodeAlokasiHash}/export/{type}'
 */
exportTemplateForm.head = (
    args:
        | { periodeAlokasiHash: string | number; type: string | number }
        | [periodeAlokasiHash: string | number, type: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: exportTemplate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportTemplate.form = exportTemplateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
export const importMethod = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

importMethod.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{periodeAlokasiId}/import',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethod.url = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { periodeAlokasiId: args };
    }

    if (Array.isArray(args)) {
        args = {
            periodeAlokasiId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periodeAlokasiId: args.periodeAlokasiId,
    };

    return (
        importMethod.definition.url
            .replace(
                '{periodeAlokasiId}',
                parsedArgs.periodeAlokasiId.toString(),
            )
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethod.post = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
const importMethodForm = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::importMethod
 * @see app/Http/Controllers/AlokasiPetugasController.php:4836
 * @route '/alokasi/periode/{periodeAlokasiId}/import'
 */
importMethodForm.post = (
    args:
        | { periodeAlokasiId: string | number }
        | [periodeAlokasiId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
});

importMethod.form = importMethodForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replaceFrameSampel
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
export const replaceFrameSampel = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: replaceFrameSampel.url(args, options),
    method: 'patch',
});

replaceFrameSampel.definition = {
    methods: ['patch'],
    url: '/alokasi/frame-sampel/{frameAllocation}/replace',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replaceFrameSampel
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replaceFrameSampel.url = (
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
        replaceFrameSampel.definition.url
            .replace('{frameAllocation}', parsedArgs.frameAllocation.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replaceFrameSampel
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replaceFrameSampel.patch = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: replaceFrameSampel.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replaceFrameSampel
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
const replaceFrameSampelForm = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: replaceFrameSampel.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::replaceFrameSampel
 * @see app/Http/Controllers/AlokasiPetugasController.php:2480
 * @route '/alokasi/frame-sampel/{frameAllocation}/replace'
 */
replaceFrameSampelForm.patch = (
    args:
        | { frameAllocation: number | { id: number } }
        | [frameAllocation: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: replaceFrameSampel.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

replaceFrameSampel.form = replaceFrameSampelForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanKeDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
export const kembalikanKeDraft = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: kembalikanKeDraft.url(args, options),
    method: 'post',
});

kembalikanKeDraft.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanKeDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanKeDraft.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        kembalikanKeDraft.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanKeDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanKeDraft.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: kembalikanKeDraft.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanKeDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
const kembalikanKeDraftForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: kembalikanKeDraft.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanKeDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanKeDraftForm.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: kembalikanKeDraft.url(args, options),
    method: 'post',
});

kembalikanKeDraft.form = kembalikanKeDraftForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
export const revisiPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revisiPeriode.url(args, options),
    method: 'post',
});

revisiPeriode.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisiPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        revisiPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisiPeriode.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: revisiPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
const revisiPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revisiPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisiPeriodeForm.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revisiPeriode.url(args, options),
    method: 'post',
});

revisiPeriode.form = revisiPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkanRevisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
export const batalkanRevisiPeriode = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: batalkanRevisiPeriode.url(args, options),
    method: 'post',
});

batalkanRevisiPeriode.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkanRevisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkanRevisiPeriode.url = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            kegiatan: args[0],
            tahun: args[1],
            bulan: args[2],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        kegiatan: args.kegiatan,
        tahun: args.tahun,
        bulan: args.bulan,
    };

    return (
        batalkanRevisiPeriode.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkanRevisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkanRevisiPeriode.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: batalkanRevisiPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkanRevisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
const batalkanRevisiPeriodeForm = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: batalkanRevisiPeriode.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkanRevisiPeriode
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkanRevisiPeriodeForm.post = (
    args:
        | {
              kegiatan: string | number;
              tahun: string | number;
              bulan: string | number;
          }
        | [
              kegiatan: string | number,
              tahun: string | number,
              bulan: string | number,
          ],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: batalkanRevisiPeriode.url(args, options),
    method: 'post',
});

batalkanRevisiPeriode.form = batalkanRevisiPeriodeForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
export const updateNonResponse = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateNonResponse.url(options),
    method: 'post',
});

updateNonResponse.definition = {
    methods: ['post'],
    url: '/alokasi/update-non-response',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponse.url = (options?: RouteQueryOptions) => {
    return updateNonResponse.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponse.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: updateNonResponse.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
const updateNonResponseForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateNonResponse.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::updateNonResponse
 * @see app/Http/Controllers/AlokasiPetugasController.php:4728
 * @route '/alokasi/update-non-response'
 */
updateNonResponseForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateNonResponse.url(options),
    method: 'post',
});

updateNonResponse.form = updateNonResponseForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
export const submit = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

submit.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/submit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submit.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        submit.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submit.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
const submitForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:1968
 * @route '/alokasi/{alokasi}/submit'
 */
submitForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
});

submit.form = submitForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
export const approve = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

approve.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/approve',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approve.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        approve.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approve.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
const approveForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::approve
 * @see app/Http/Controllers/AlokasiPetugasController.php:1985
 * @route '/alokasi/{alokasi}/approve'
 */
approveForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
});

approve.form = approveForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
export const reject = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

reject.definition = {
    methods: ['post'],
    url: '/alokasi/{alokasi}/reject',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
reject.url = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alokasi: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alokasi: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            alokasi: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        alokasi:
            typeof args.alokasi === 'object' ? args.alokasi.id : args.alokasi,
    };

    return (
        reject.definition.url
            .replace('{alokasi}', parsedArgs.alokasi.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
reject.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
const rejectForm = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::reject
 * @see app/Http/Controllers/AlokasiPetugasController.php:2013
 * @route '/alokasi/{alokasi}/reject'
 */
rejectForm.post = (
    args:
        | { alokasi: number | { id: number } }
        | [alokasi: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
});

reject.form = rejectForm;
const AlokasiPetugasController = {
    create,
    index,
    show,
    showPeriode,
    exportMonitoringSkgbPdf,
    store,
    storeMultiple,
    importCreate,
    importPreview,
    edit,
    update,
    destroy,
    submitPeriode,
    editPeriode,
    updatePeriode,
    destroyPeriode,
    exportTemplateCreate,
    exportTemplate,
    importMethod,
    replaceFrameSampel,
    kembalikanKeDraft,
    revisiPeriode,
    batalkanRevisiPeriode,
    updateNonResponse,
    submit,
    approve,
    reject,
    import: importMethod,
};

export default AlokasiPetugasController;
