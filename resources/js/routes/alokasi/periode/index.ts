import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
import revisi8e9161 from './revisi';
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
export const show = (
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
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
show.url = (
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
        show.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
show.get = (
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
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
show.head = (
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
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const showForm = (
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
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showForm.get = (
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
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::show
 * @see app/Http/Controllers/AlokasiPetugasController.php:2121
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
showForm.head = (
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
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
export const exportMonitoringSkgb = (
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
    url: exportMonitoringSkgb.url(args, options),
    method: 'get',
});

exportMonitoringSkgb.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgb.url = (
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
        exportMonitoringSkgb.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgb.get = (
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
    url: exportMonitoringSkgb.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgb.head = (
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
    url: exportMonitoringSkgb.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
const exportMonitoringSkgbForm = (
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
    action: exportMonitoringSkgb.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbForm.get = (
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
    action: exportMonitoringSkgb.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::exportMonitoringSkgb
 * @see app/Http/Controllers/AlokasiPetugasController.php:2475
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/export-monitoring-skgb'
 */
exportMonitoringSkgbForm.head = (
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
    action: exportMonitoringSkgb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

exportMonitoringSkgb.form = exportMonitoringSkgbForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
export const submit = (
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
    url: submit.url(args, options),
    method: 'post',
});

submit.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submit.url = (
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
        submit.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submit.post = (
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
    url: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
const submitForm = (
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
    action: submit.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::submit
 * @see app/Http/Controllers/AlokasiPetugasController.php:2072
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit'
 */
submitForm.post = (
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
    action: submit.url(args, options),
    method: 'post',
});

submit.form = submitForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
export const edit = (
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
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
edit.url = (
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
        edit.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
edit.get = (
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
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
edit.head = (
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
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
const editForm = (
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
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editForm.get = (
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
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::edit
 * @see app/Http/Controllers/AlokasiPetugasController.php:3009
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit'
 */
editForm.head = (
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
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
export const update = (
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
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
update.url = (
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
        update.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
update.put = (
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
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const updateForm = (
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
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::update
 * @see app/Http/Controllers/AlokasiPetugasController.php:3556
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
updateForm.put = (
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
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update.form = updateForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
export const destroy = (
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
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroy.url = (
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
        destroy.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroy.delete = (
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
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::destroy
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
const destroyForm = (
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
 * @see app/Http/Controllers/AlokasiPetugasController.php:4149
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}'
 */
destroyForm.delete = (
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
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
export const kembalikanDraft = (
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
    url: kembalikanDraft.url(args, options),
    method: 'post',
});

kembalikanDraft.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanDraft.url = (
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
        kembalikanDraft.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanDraft.post = (
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
    url: kembalikanDraft.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
const kembalikanDraftForm = (
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
    action: kembalikanDraft.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::kembalikanDraft
 * @see app/Http/Controllers/AlokasiPetugasController.php:4234
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/kembalikan-draft'
 */
kembalikanDraftForm.post = (
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
    action: kembalikanDraft.url(args, options),
    method: 'post',
});

kembalikanDraft.form = kembalikanDraftForm;
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisi
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
export const revisi = (
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
    url: revisi.url(args, options),
    method: 'post',
});

revisi.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisi
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisi.url = (
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
        revisi.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisi
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisi.post = (
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
    url: revisi.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisi
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
const revisiForm = (
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
    action: revisi.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::revisi
 * @see app/Http/Controllers/AlokasiPetugasController.php:4318
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi'
 */
revisiForm.post = (
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
    action: revisi.url(args, options),
    method: 'post',
});

revisi.form = revisiForm;
const periode = {
    show: Object.assign(show, show),
    exportMonitoringSkgb: Object.assign(
        exportMonitoringSkgb,
        exportMonitoringSkgb,
    ),
    submit: Object.assign(submit, submit),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    kembalikanDraft: Object.assign(kembalikanDraft, kembalikanDraft),
    revisi: Object.assign(revisi, revisi8e9161),
};

export default periode;
