import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import preview16e44b from './preview';
import print from './print';
import publicPreview from './public-preview';
/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'post', 'head'],
    url: '/spk',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
 */
indexForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: index.url(options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SpkController::index
 * @see app/Http/Controllers/SpkController.php:46
 * @route '/spk'
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
 * @see \App\Http\Controllers\SpkController::petugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
export const petugasNames = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: petugasNames.url(options),
    method: 'post',
});

petugasNames.definition = {
    methods: ['post'],
    url: '/spk/petugas-names',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::petugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
petugasNames.url = (options?: RouteQueryOptions) => {
    return petugasNames.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::petugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
petugasNames.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: petugasNames.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::petugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
const petugasNamesForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: petugasNames.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::petugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
petugasNamesForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: petugasNames.url(options),
    method: 'post',
});

petugasNames.form = petugasNamesForm;
/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
export const listByMonth = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: listByMonth.url(options),
    method: 'get',
});

listByMonth.definition = {
    methods: ['get', 'head'],
    url: '/spk/list-by-month',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
listByMonth.url = (options?: RouteQueryOptions) => {
    return listByMonth.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
listByMonth.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listByMonth.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
listByMonth.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listByMonth.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
const listByMonthForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
listByMonthForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::listByMonth
 * @see app/Http/Controllers/SpkController.php:337
 * @route '/spk/list-by-month'
 */
listByMonthForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: listByMonth.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

listByMonth.form = listByMonthForm;
/**
 * @see \App\Http\Controllers\SpkController::downloadAll
 * @see app/Http/Controllers/SpkController.php:851
 * @route '/spk/download-all'
 */
export const downloadAll = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: downloadAll.url(options),
    method: 'post',
});

downloadAll.definition = {
    methods: ['post'],
    url: '/spk/download-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::downloadAll
 * @see app/Http/Controllers/SpkController.php:851
 * @route '/spk/download-all'
 */
downloadAll.url = (options?: RouteQueryOptions) => {
    return downloadAll.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::downloadAll
 * @see app/Http/Controllers/SpkController.php:851
 * @route '/spk/download-all'
 */
downloadAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: downloadAll.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadAll
 * @see app/Http/Controllers/SpkController.php:851
 * @route '/spk/download-all'
 */
const downloadAllForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadAll.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadAll
 * @see app/Http/Controllers/SpkController.php:851
 * @route '/spk/download-all'
 */
downloadAllForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadAll.url(options),
    method: 'post',
});

downloadAll.form = downloadAllForm;
/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
export const downloadAllByKegiatan = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadAllByKegiatan.url(args, options),
    method: 'get',
});

downloadAllByKegiatan.definition = {
    methods: ['get', 'head'],
    url: '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
downloadAllByKegiatan.url = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
) => {
    if (Array.isArray(args)) {
        args = {
            periode: args[0],
            kegiatan: args[1],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        periode: args.periode,
        kegiatan: args.kegiatan,
    };

    return (
        downloadAllByKegiatan.definition.url
            .replace('{periode}', parsedArgs.periode.toString())
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
downloadAllByKegiatan.get = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadAllByKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
downloadAllByKegiatan.head = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadAllByKegiatan.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
const downloadAllByKegiatanForm = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAllByKegiatan.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
downloadAllByKegiatanForm.get = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAllByKegiatan.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::downloadAllByKegiatan
 * @see app/Http/Controllers/SpkController.php:1097
 * @route '/spk/periode/{periode}/kegiatan/{kegiatan}/download-all'
 */
downloadAllByKegiatanForm.head = (
    args:
        | { periode: string | number; kegiatan: string | number }
        | [periode: string | number, kegiatan: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadAllByKegiatan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadAllByKegiatan.form = downloadAllByKegiatanForm;
/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
export const showByMonthGet = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showByMonthGet.url(options),
    method: 'get',
});

showByMonthGet.definition = {
    methods: ['get', 'head'],
    url: '/spk/month',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
showByMonthGet.url = (options?: RouteQueryOptions) => {
    return showByMonthGet.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
showByMonthGet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showByMonthGet.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
showByMonthGet.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: showByMonthGet.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
const showByMonthGetForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showByMonthGet.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
showByMonthGetForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showByMonthGet.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::showByMonthGet
 * @see app/Http/Controllers/SpkController.php:421
 * @route '/spk/month'
 */
showByMonthGetForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showByMonthGet.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showByMonthGet.form = showByMonthGetForm;
/**
 * @see \App\Http\Controllers\SpkController::showByMonth
 * @see app/Http/Controllers/SpkController.php:439
 * @route '/spk/month'
 */
export const showByMonth = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: showByMonth.url(options),
    method: 'post',
});

showByMonth.definition = {
    methods: ['post'],
    url: '/spk/month',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::showByMonth
 * @see app/Http/Controllers/SpkController.php:439
 * @route '/spk/month'
 */
showByMonth.url = (options?: RouteQueryOptions) => {
    return showByMonth.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::showByMonth
 * @see app/Http/Controllers/SpkController.php:439
 * @route '/spk/month'
 */
showByMonth.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: showByMonth.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::showByMonth
 * @see app/Http/Controllers/SpkController.php:439
 * @route '/spk/month'
 */
const showByMonthForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: showByMonth.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::showByMonth
 * @see app/Http/Controllers/SpkController.php:439
 * @route '/spk/month'
 */
showByMonthForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: showByMonth.url(options),
    method: 'post',
});

showByMonth.form = showByMonthForm;
/**
 * @see \App\Http\Controllers\SpkController::downloadByKegiatanMonth
 * @see app/Http/Controllers/SpkController.php:1279
 * @route '/spk/month/kegiatan/download'
 */
export const downloadByKegiatanMonth = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: downloadByKegiatanMonth.url(options),
    method: 'post',
});

downloadByKegiatanMonth.definition = {
    methods: ['post'],
    url: '/spk/month/kegiatan/download',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::downloadByKegiatanMonth
 * @see app/Http/Controllers/SpkController.php:1279
 * @route '/spk/month/kegiatan/download'
 */
downloadByKegiatanMonth.url = (options?: RouteQueryOptions) => {
    return downloadByKegiatanMonth.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::downloadByKegiatanMonth
 * @see app/Http/Controllers/SpkController.php:1279
 * @route '/spk/month/kegiatan/download'
 */
downloadByKegiatanMonth.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: downloadByKegiatanMonth.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadByKegiatanMonth
 * @see app/Http/Controllers/SpkController.php:1279
 * @route '/spk/month/kegiatan/download'
 */
const downloadByKegiatanMonthForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadByKegiatanMonth.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::downloadByKegiatanMonth
 * @see app/Http/Controllers/SpkController.php:1279
 * @route '/spk/month/kegiatan/download'
 */
downloadByKegiatanMonthForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: downloadByKegiatanMonth.url(options),
    method: 'post',
});

downloadByKegiatanMonth.form = downloadByKegiatanMonthForm;
/**
 * @see \App\Http\Controllers\SpkController::uploadSigned
 * @see app/Http/Controllers/SpkController.php:1451
 * @route '/spk/{spk}/upload-signed'
 */
export const uploadSigned = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.definition = {
    methods: ['post'],
    url: '/spk/{spk}/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::uploadSigned
 * @see app/Http/Controllers/SpkController.php:1451
 * @route '/spk/{spk}/upload-signed'
 */
uploadSigned.url = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spk: args };
    }

    if (Array.isArray(args)) {
        args = {
            spk: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spk: args.spk,
    };

    return (
        uploadSigned.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::uploadSigned
 * @see app/Http/Controllers/SpkController.php:1451
 * @route '/spk/{spk}/upload-signed'
 */
uploadSigned.post = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::uploadSigned
 * @see app/Http/Controllers/SpkController.php:1451
 * @route '/spk/{spk}/upload-signed'
 */
const uploadSignedForm = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::uploadSigned
 * @see app/Http/Controllers/SpkController.php:1451
 * @route '/spk/{spk}/upload-signed'
 */
uploadSignedForm.post = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(args, options),
    method: 'post',
});

uploadSigned.form = uploadSignedForm;
/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
export const show = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/spk/{spk}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
show.url = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spk: args };
    }

    if (Array.isArray(args)) {
        args = {
            spk: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spk: args.spk,
    };

    return (
        show.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
show.get = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
show.head = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
const showForm = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
showForm.get = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::show
 * @see app/Http/Controllers/SpkController.php:4557
 * @route '/spk/{spk}'
 */
showForm.head = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
export const create = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/spk/periode/{periodeHashedId}/generate',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
create.url = (
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
        create.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
create.get = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
create.head = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
const createForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
createForm.get = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::create
 * @see app/Http/Controllers/SpkController.php:3621
 * @route '/spk/periode/{periodeHashedId}/generate'
 */
createForm.head = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: create.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

create.form = createForm;
/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
export const createAddendum = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createAddendum.url(args, options),
    method: 'get',
});

createAddendum.definition = {
    methods: ['get', 'post', 'head'],
    url: '/spk/periode/{periodeHashedId}/addendum',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendum.url = (
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
        createAddendum.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendum.get = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: createAddendum.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendum.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: createAddendum.url(args, options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendum.head = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: createAddendum.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
const createAddendumForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createAddendum.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendumForm.get = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createAddendum.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendumForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: createAddendum.url(args, options),
    method: 'post',
});
/**
 * @see \App\Http\Controllers\SpkController::createAddendum
 * @see app/Http/Controllers/SpkController.php:3860
 * @route '/spk/periode/{periodeHashedId}/addendum'
 */
createAddendumForm.head = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: createAddendum.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

createAddendum.form = createAddendumForm;
/**
 * @see \App\Http\Controllers\SpkController::preview
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
export const preview = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
});

preview.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::preview
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
preview.url = (
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
        preview.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::preview
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
preview.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::preview
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
const previewForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::preview
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
previewForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(args, options),
    method: 'post',
});

preview.form = previewForm;
/**
 * @see \App\Http\Controllers\SpkController::previewAddendum
 * @see app/Http/Controllers/SpkController.php:4093
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum'
 */
export const previewAddendum = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewAddendum.url(args, options),
    method: 'post',
});

previewAddendum.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::previewAddendum
 * @see app/Http/Controllers/SpkController.php:4093
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum'
 */
previewAddendum.url = (
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
        previewAddendum.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::previewAddendum
 * @see app/Http/Controllers/SpkController.php:4093
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum'
 */
previewAddendum.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewAddendum
 * @see app/Http/Controllers/SpkController.php:4093
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum'
 */
const previewAddendumForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewAddendum
 * @see app/Http/Controllers/SpkController.php:4093
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum'
 */
previewAddendumForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewAddendum.url(args, options),
    method: 'post',
});

previewAddendum.form = previewAddendumForm;
/**
 * @see \App\Http\Controllers\SpkController::generate
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
export const generate = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generate.url(args, options),
    method: 'post',
});

generate.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generate
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generate.url = (
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
        generate.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generate
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generate.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generate
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
const generateForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generate
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generateForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generate.url(args, options),
    method: 'post',
});

generate.form = generateForm;
/**
 * @see \App\Http\Controllers\SpkController::regenerateDocument
 * @see app/Http/Controllers/SpkController.php:1521
 * @route '/spk/{spkHashedId}/regenerate-document'
 */
export const regenerateDocument = (
    args:
        | { spkHashedId: string | number }
        | [spkHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: regenerateDocument.url(args, options),
    method: 'post',
});

regenerateDocument.definition = {
    methods: ['post'],
    url: '/spk/{spkHashedId}/regenerate-document',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::regenerateDocument
 * @see app/Http/Controllers/SpkController.php:1521
 * @route '/spk/{spkHashedId}/regenerate-document'
 */
regenerateDocument.url = (
    args:
        | { spkHashedId: string | number }
        | [spkHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spkHashedId: args };
    }

    if (Array.isArray(args)) {
        args = {
            spkHashedId: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spkHashedId: args.spkHashedId,
    };

    return (
        regenerateDocument.definition.url
            .replace('{spkHashedId}', parsedArgs.spkHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::regenerateDocument
 * @see app/Http/Controllers/SpkController.php:1521
 * @route '/spk/{spkHashedId}/regenerate-document'
 */
regenerateDocument.post = (
    args:
        | { spkHashedId: string | number }
        | [spkHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: regenerateDocument.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::regenerateDocument
 * @see app/Http/Controllers/SpkController.php:1521
 * @route '/spk/{spkHashedId}/regenerate-document'
 */
const regenerateDocumentForm = (
    args:
        | { spkHashedId: string | number }
        | [spkHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: regenerateDocument.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::regenerateDocument
 * @see app/Http/Controllers/SpkController.php:1521
 * @route '/spk/{spkHashedId}/regenerate-document'
 */
regenerateDocumentForm.post = (
    args:
        | { spkHashedId: string | number }
        | [spkHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: regenerateDocument.url(args, options),
    method: 'post',
});

regenerateDocument.form = regenerateDocumentForm;
/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodePetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
export const cancelByPeriodePetugas = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelByPeriodePetugas.url(args, options),
    method: 'delete',
});

cancelByPeriodePetugas.definition = {
    methods: ['delete'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodePetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodePetugas.url = (
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
        cancelByPeriodePetugas.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodePetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodePetugas.delete = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelByPeriodePetugas.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodePetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
const cancelByPeriodePetugasForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelByPeriodePetugas.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodePetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodePetugasForm.delete = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelByPeriodePetugas.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

cancelByPeriodePetugas.form = cancelByPeriodePetugasForm;
/**
 * @see \App\Http\Controllers\SpkController::cancelAllByPeriode
 * @see app/Http/Controllers/SpkController.php:1750
 * @route '/spk/periode/{periodeHashedId}/cancel-all'
 */
export const cancelAllByPeriode = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelAllByPeriode.url(args, options),
    method: 'delete',
});

cancelAllByPeriode.definition = {
    methods: ['delete'],
    url: '/spk/periode/{periodeHashedId}/cancel-all',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SpkController::cancelAllByPeriode
 * @see app/Http/Controllers/SpkController.php:1750
 * @route '/spk/periode/{periodeHashedId}/cancel-all'
 */
cancelAllByPeriode.url = (
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
        cancelAllByPeriode.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::cancelAllByPeriode
 * @see app/Http/Controllers/SpkController.php:1750
 * @route '/spk/periode/{periodeHashedId}/cancel-all'
 */
cancelAllByPeriode.delete = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelAllByPeriode.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelAllByPeriode
 * @see app/Http/Controllers/SpkController.php:1750
 * @route '/spk/periode/{periodeHashedId}/cancel-all'
 */
const cancelAllByPeriodeForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelAllByPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelAllByPeriode
 * @see app/Http/Controllers/SpkController.php:1750
 * @route '/spk/periode/{periodeHashedId}/cancel-all'
 */
cancelAllByPeriodeForm.delete = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelAllByPeriode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

cancelAllByPeriode.form = cancelAllByPeriodeForm;
/**
 * @see \App\Http\Controllers\SpkController::generateAddendum
 * @see app/Http/Controllers/SpkController.php:4239
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum'
 */
export const generateAddendum = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAddendum.url(args, options),
    method: 'post',
});

generateAddendum.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateAddendum
 * @see app/Http/Controllers/SpkController.php:4239
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum'
 */
generateAddendum.url = (
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
        generateAddendum.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateAddendum
 * @see app/Http/Controllers/SpkController.php:4239
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum'
 */
generateAddendum.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAddendum
 * @see app/Http/Controllers/SpkController.php:4239
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum'
 */
const generateAddendumForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAddendum
 * @see app/Http/Controllers/SpkController.php:4239
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum'
 */
generateAddendumForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAddendum.url(args, options),
    method: 'post',
});

generateAddendum.form = generateAddendumForm;
/**
 * @see \App\Http\Controllers\SpkController::generateAddendumBatch
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
export const generateAddendumBatch = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAddendumBatch.url(args, options),
    method: 'post',
});

generateAddendumBatch.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/generate-addendum-batch',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateAddendumBatch
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateAddendumBatch.url = (
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
        generateAddendumBatch.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateAddendumBatch
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateAddendumBatch.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAddendumBatch.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAddendumBatch
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
const generateAddendumBatchForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAddendumBatch.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAddendumBatch
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateAddendumBatchForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAddendumBatch.url(args, options),
    method: 'post',
});

generateAddendumBatch.form = generateAddendumBatchForm;
/**
 * @see \App\Http\Controllers\SpkController::generateAll
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
export const generateAll = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAll.url(args, options),
    method: 'post',
});

generateAll.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/generate-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateAll
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAll.url = (
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
        generateAll.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateAll
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAll.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAll.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAll
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
const generateAllForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAll.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAll
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAllForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAll.url(args, options),
    method: 'post',
});

generateAll.form = generateAllForm;
/**
 * @see \App\Http\Controllers\SpkController::store
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/spk',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::store
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::store
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::store
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::store
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
export const edit = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/spk/{spk}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
edit.url = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spk: args };
    }

    if (Array.isArray(args)) {
        args = {
            spk: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spk: args.spk,
    };

    return (
        edit.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
edit.get = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
edit.head = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
const editForm = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
editForm.get = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::edit
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
editForm.head = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
export const update = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put'],
    url: '/spk/{spk}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
update.url = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spk: args };
    }

    if (Array.isArray(args)) {
        args = {
            spk: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spk: args.spk,
    };

    return (
        update.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
update.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const updateForm = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
updateForm.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
 * @see \App\Http\Controllers\SpkController::destroy
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
export const destroy = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/spk/{spk}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SpkController::destroy
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
destroy.url = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spk: args };
    }

    if (Array.isArray(args)) {
        args = {
            spk: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        spk: args.spk,
    };

    return (
        destroy.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::destroy
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
destroy.delete = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SpkController::destroy
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const destroyForm = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
 * @see \App\Http\Controllers\SpkController::destroy
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
destroyForm.delete = (
    args: { spk: string | number } | [spk: string | number] | string | number,
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
const spk = {
    publicPreview: Object.assign(publicPreview, publicPreview),
    index: Object.assign(index, index),
    petugasNames: Object.assign(petugasNames, petugasNames),
    listByMonth: Object.assign(listByMonth, listByMonth),
    downloadAll: Object.assign(downloadAll, downloadAll),
    downloadAllByKegiatan: Object.assign(
        downloadAllByKegiatan,
        downloadAllByKegiatan,
    ),
    showByMonthGet: Object.assign(showByMonthGet, showByMonthGet),
    showByMonth: Object.assign(showByMonth, showByMonth),
    downloadByKegiatanMonth: Object.assign(
        downloadByKegiatanMonth,
        downloadByKegiatanMonth,
    ),
    uploadSigned: Object.assign(uploadSigned, uploadSigned),
    show: Object.assign(show, show),
    create: Object.assign(create, create),
    createAddendum: Object.assign(createAddendum, createAddendum),
    preview: Object.assign(preview, preview16e44b),
    print: Object.assign(print, print),
    previewAddendum: Object.assign(previewAddendum, previewAddendum),
    generate: Object.assign(generate, generate),
    regenerateDocument: Object.assign(regenerateDocument, regenerateDocument),
    cancelByPeriodePetugas: Object.assign(
        cancelByPeriodePetugas,
        cancelByPeriodePetugas,
    ),
    cancelAllByPeriode: Object.assign(cancelAllByPeriode, cancelAllByPeriode),
    generateAddendum: Object.assign(generateAddendum, generateAddendum),
    generateAddendumBatch: Object.assign(
        generateAddendumBatch,
        generateAddendumBatch,
    ),
    generateAll: Object.assign(generateAll, generateAll),
    store: Object.assign(store, store),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
};

export default spk;
