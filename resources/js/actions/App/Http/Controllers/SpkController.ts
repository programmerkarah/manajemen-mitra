import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
export const publicPreviewForm = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicPreviewForm.url(options),
    method: 'get',
});

publicPreviewForm.definition = {
    methods: ['get', 'head'],
    url: '/mitra',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
publicPreviewForm.url = (options?: RouteQueryOptions) => {
    return publicPreviewForm.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
publicPreviewForm.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicPreviewForm.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
publicPreviewForm.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: publicPreviewForm.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
const publicPreviewFormForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewForm.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
publicPreviewFormForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewForm.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewForm
 * @see app/Http/Controllers/SpkController.php:2229
 * @route '/mitra'
 */
publicPreviewFormForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewForm.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

publicPreviewForm.form = publicPreviewFormForm;
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewOptions
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
export const publicPreviewOptions = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publicPreviewOptions.url(options),
    method: 'post',
});

publicPreviewOptions.definition = {
    methods: ['post'],
    url: '/mitra/options',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewOptions
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
publicPreviewOptions.url = (options?: RouteQueryOptions) => {
    return publicPreviewOptions.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewOptions
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
publicPreviewOptions.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publicPreviewOptions.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewOptions
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
const publicPreviewOptionsForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publicPreviewOptions.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewOptions
 * @see app/Http/Controllers/SpkController.php:2242
 * @route '/mitra/options'
 */
publicPreviewOptionsForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publicPreviewOptions.url(options),
    method: 'post',
});

publicPreviewOptions.form = publicPreviewOptionsForm;
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewDownload
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
export const publicPreviewDownload = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publicPreviewDownload.url(options),
    method: 'post',
});

publicPreviewDownload.definition = {
    methods: ['post'],
    url: '/mitra',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewDownload
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
publicPreviewDownload.url = (options?: RouteQueryOptions) => {
    return publicPreviewDownload.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewDownload
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
publicPreviewDownload.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publicPreviewDownload.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewDownload
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
const publicPreviewDownloadForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publicPreviewDownload.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewDownload
 * @see app/Http/Controllers/SpkController.php:2296
 * @route '/mitra'
 */
publicPreviewDownloadForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publicPreviewDownload.url(options),
    method: 'post',
});

publicPreviewDownload.form = publicPreviewDownloadForm;
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
export const publicPreviewFile = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicPreviewFile.url(args, options),
    method: 'get',
});

publicPreviewFile.definition = {
    methods: ['get', 'head'],
    url: '/mitra/preview-file/{file}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
publicPreviewFile.url = (
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
        publicPreviewFile.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
publicPreviewFile.get = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicPreviewFile.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
publicPreviewFile.head = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: publicPreviewFile.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
const publicPreviewFileForm = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewFile.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
publicPreviewFileForm.get = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewFile.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SpkController::publicPreviewFile
 * @see app/Http/Controllers/SpkController.php:2812
 * @route '/mitra/preview-file/{file}'
 */
publicPreviewFileForm.head = (
    args: { file: string | number } | [file: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicPreviewFile.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

publicPreviewFile.form = publicPreviewFileForm;
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
 * @see \App\Http\Controllers\SpkController::getPetugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
export const getPetugasNames = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: getPetugasNames.url(options),
    method: 'post',
});

getPetugasNames.definition = {
    methods: ['post'],
    url: '/spk/petugas-names',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::getPetugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
getPetugasNames.url = (options?: RouteQueryOptions) => {
    return getPetugasNames.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SpkController::getPetugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
getPetugasNames.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: getPetugasNames.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::getPetugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
const getPetugasNamesForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: getPetugasNames.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::getPetugasNames
 * @see app/Http/Controllers/SpkController.php:8513
 * @route '/spk/petugas-names'
 */
getPetugasNamesForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: getPetugasNames.url(options),
    method: 'post',
});

getPetugasNames.form = getPetugasNamesForm;
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
 * @see \App\Http\Controllers\SpkController::previewSpk
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
export const previewSpk = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpk.url(args, options),
    method: 'post',
});

previewSpk.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::previewSpk
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
previewSpk.url = (
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
        previewSpk.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::previewSpk
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
previewSpk.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpk
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
const previewSpkForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpk
 * @see app/Http/Controllers/SpkController.php:5323
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview'
 */
previewSpkForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpk.url(args, options),
    method: 'post',
});

previewSpk.form = previewSpkForm;
/**
 * @see \App\Http\Controllers\SpkController::previewSpkMain
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
export const previewSpkMain = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpkMain.url(args, options),
    method: 'post',
});

previewSpkMain.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::previewSpkMain
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
previewSpkMain.url = (
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
        previewSpkMain.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::previewSpkMain
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
previewSpkMain.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpkMain.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpkMain
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
const previewSpkMainForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpkMain.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpkMain
 * @see app/Http/Controllers/SpkController.php:5724
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main'
 */
previewSpkMainForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpkMain.url(args, options),
    method: 'post',
});

previewSpkMain.form = previewSpkMainForm;
/**
 * @see \App\Http\Controllers\SpkController::previewSpkLampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
export const previewSpkLampiran = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpkLampiran.url(args, options),
    method: 'post',
});

previewSpkLampiran.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::previewSpkLampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
previewSpkLampiran.url = (
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
        previewSpkLampiran.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::previewSpkLampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
previewSpkLampiran.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewSpkLampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpkLampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
const previewSpkLampiranForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpkLampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewSpkLampiran
 * @see app/Http/Controllers/SpkController.php:5880
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran'
 */
previewSpkLampiranForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewSpkLampiran.url(args, options),
    method: 'post',
});

previewSpkLampiran.form = previewSpkLampiranForm;
/**
 * @see \App\Http\Controllers\SpkController::previewAllSpk
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
export const previewAllSpk = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewAllSpk.url(args, options),
    method: 'post',
});

previewAllSpk.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/preview-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::previewAllSpk
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
previewAllSpk.url = (
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
        previewAllSpk.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::previewAllSpk
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
previewAllSpk.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: previewAllSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewAllSpk
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
const previewAllSpkForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewAllSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::previewAllSpk
 * @see app/Http/Controllers/SpkController.php:4782
 * @route '/spk/periode/{periodeHashedId}/preview-all'
 */
previewAllSpkForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: previewAllSpk.url(args, options),
    method: 'post',
});

previewAllSpk.form = previewAllSpkForm;
/**
 * @see \App\Http\Controllers\SpkController::printSelectedMain
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
export const printSelectedMain = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: printSelectedMain.url(args, options),
    method: 'post',
});

printSelectedMain.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/print-selected-main',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::printSelectedMain
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
printSelectedMain.url = (
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
        printSelectedMain.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::printSelectedMain
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
printSelectedMain.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: printSelectedMain.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::printSelectedMain
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
const printSelectedMainForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: printSelectedMain.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::printSelectedMain
 * @see app/Http/Controllers/SpkController.php:4890
 * @route '/spk/periode/{periodeHashedId}/print-selected-main'
 */
printSelectedMainForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: printSelectedMain.url(args, options),
    method: 'post',
});

printSelectedMain.form = printSelectedMainForm;
/**
 * @see \App\Http\Controllers\SpkController::printSelectedLampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
export const printSelectedLampiran = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: printSelectedLampiran.url(args, options),
    method: 'post',
});

printSelectedLampiran.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/print-selected-lampiran',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::printSelectedLampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
printSelectedLampiran.url = (
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
        printSelectedLampiran.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::printSelectedLampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
printSelectedLampiran.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: printSelectedLampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::printSelectedLampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
const printSelectedLampiranForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: printSelectedLampiran.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::printSelectedLampiran
 * @see app/Http/Controllers/SpkController.php:4990
 * @route '/spk/periode/{periodeHashedId}/print-selected-lampiran'
 */
printSelectedLampiranForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: printSelectedLampiran.url(args, options),
    method: 'post',
});

printSelectedLampiran.form = printSelectedLampiranForm;
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
 * @see \App\Http\Controllers\SpkController::generateSpk
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
export const generateSpk = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateSpk.url(args, options),
    method: 'post',
});

generateSpk.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateSpk
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generateSpk.url = (
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
        generateSpk.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateSpk
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generateSpk.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateSpk
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
const generateSpkForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateSpk
 * @see app/Http/Controllers/SpkController.php:6045
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate'
 */
generateSpkForm.post = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateSpk.url(args, options),
    method: 'post',
});

generateSpk.form = generateSpkForm;
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
 * @see \App\Http\Controllers\SpkController::cancelByPeriodeAndPetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
export const cancelByPeriodeAndPetugas = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelByPeriodeAndPetugas.url(args, options),
    method: 'delete',
});

cancelByPeriodeAndPetugas.definition = {
    methods: ['delete'],
    url: '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodeAndPetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodeAndPetugas.url = (
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
        cancelByPeriodeAndPetugas.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace('{petugasHashedId}', parsedArgs.petugasHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodeAndPetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodeAndPetugas.delete = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: cancelByPeriodeAndPetugas.url(args, options),
    method: 'delete',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodeAndPetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
const cancelByPeriodeAndPetugasForm = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelByPeriodeAndPetugas.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::cancelByPeriodeAndPetugas
 * @see app/Http/Controllers/SpkController.php:1698
 * @route '/spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/cancel'
 */
cancelByPeriodeAndPetugasForm.delete = (
    args:
        | { periodeHashedId: string | number; petugasHashedId: string | number }
        | [periodeHashedId: string | number, petugasHashedId: string | number],
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: cancelByPeriodeAndPetugas.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

cancelByPeriodeAndPetugas.form = cancelByPeriodeAndPetugasForm;
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
 * @see \App\Http\Controllers\SpkController::generateBatchAddendum
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
export const generateBatchAddendum = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatchAddendum.url(args, options),
    method: 'post',
});

generateBatchAddendum.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/generate-addendum-batch',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateBatchAddendum
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateBatchAddendum.url = (
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
        generateBatchAddendum.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateBatchAddendum
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateBatchAddendum.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateBatchAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateBatchAddendum
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
const generateBatchAddendumForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatchAddendum.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateBatchAddendum
 * @see app/Http/Controllers/SpkController.php:4307
 * @route '/spk/periode/{periodeHashedId}/generate-addendum-batch'
 */
generateBatchAddendumForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateBatchAddendum.url(args, options),
    method: 'post',
});

generateBatchAddendum.form = generateBatchAddendumForm;
/**
 * @see \App\Http\Controllers\SpkController::generateAllSpk
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
export const generateAllSpk = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAllSpk.url(args, options),
    method: 'post',
});

generateAllSpk.definition = {
    methods: ['post'],
    url: '/spk/periode/{periodeHashedId}/generate-all',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SpkController::generateAllSpk
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAllSpk.url = (
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
        generateAllSpk.definition.url
            .replace('{periodeHashedId}', parsedArgs.periodeHashedId.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::generateAllSpk
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAllSpk.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: generateAllSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAllSpk
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
const generateAllSpkForm = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAllSpk.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\SpkController::generateAllSpk
 * @see app/Http/Controllers/SpkController.php:7544
 * @route '/spk/periode/{periodeHashedId}/generate-all'
 */
generateAllSpkForm.post = (
    args:
        | { periodeHashedId: string | number }
        | [periodeHashedId: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: generateAllSpk.url(args, options),
    method: 'post',
});

generateAllSpk.form = generateAllSpkForm;
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
 * @route '/spk/{spk}/edit'
 */
const update0d3c861f7b195e59748ef019b6804fa7 = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update0d3c861f7b195e59748ef019b6804fa7.url(args, options),
    method: 'put',
});

update0d3c861f7b195e59748ef019b6804fa7.definition = {
    methods: ['put', 'patch'],
    url: '/spk/{spk}/edit',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
update0d3c861f7b195e59748ef019b6804fa7.url = (
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
        update0d3c861f7b195e59748ef019b6804fa7.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
update0d3c861f7b195e59748ef019b6804fa7.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update0d3c861f7b195e59748ef019b6804fa7.url(args, options),
    method: 'put',
});
/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
update0d3c861f7b195e59748ef019b6804fa7.patch = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update0d3c861f7b195e59748ef019b6804fa7.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}/edit'
 */
const update0d3c861f7b195e59748ef019b6804fa7Form = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update0d3c861f7b195e59748ef019b6804fa7.url(args, {
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
 * @route '/spk/{spk}/edit'
 */
update0d3c861f7b195e59748ef019b6804fa7Form.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update0d3c861f7b195e59748ef019b6804fa7.url(args, {
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
 * @route '/spk/{spk}/edit'
 */
update0d3c861f7b195e59748ef019b6804fa7Form.patch = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update0d3c861f7b195e59748ef019b6804fa7.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update0d3c861f7b195e59748ef019b6804fa7.form =
    update0d3c861f7b195e59748ef019b6804fa7Form;
/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const updatee2c20fc1302b04bdc0400ab56ab093a3 = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, options),
    method: 'put',
});

updatee2c20fc1302b04bdc0400ab56ab093a3.definition = {
    methods: ['put'],
    url: '/spk/{spk}',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
updatee2c20fc1302b04bdc0400ab56ab093a3.url = (
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
        updatee2c20fc1302b04bdc0400ab56ab093a3.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
updatee2c20fc1302b04bdc0400ab56ab093a3.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const updatee2c20fc1302b04bdc0400ab56ab093a3Form = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, {
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
updatee2c20fc1302b04bdc0400ab56ab093a3Form.put = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatee2c20fc1302b04bdc0400ab56ab093a3.form =
    updatee2c20fc1302b04bdc0400ab56ab093a3Form;
/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const updatee2c20fc1302b04bdc0400ab56ab093a3 = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, options),
    method: 'patch',
});

updatee2c20fc1302b04bdc0400ab56ab093a3.definition = {
    methods: ['patch'],
    url: '/spk/{spk}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
updatee2c20fc1302b04bdc0400ab56ab093a3.url = (
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
        updatee2c20fc1302b04bdc0400ab56ab093a3.definition.url
            .replace('{spk}', parsedArgs.spk.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
updatee2c20fc1302b04bdc0400ab56ab093a3.patch = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\SpkController::update
 * @see app/Http/Controllers/SpkController.php:0
 * @route '/spk/{spk}'
 */
const updatee2c20fc1302b04bdc0400ab56ab093a3Form = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
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
updatee2c20fc1302b04bdc0400ab56ab093a3Form.patch = (
    args: { spk: string | number } | [spk: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updatee2c20fc1302b04bdc0400ab56ab093a3.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updatee2c20fc1302b04bdc0400ab56ab093a3.form =
    updatee2c20fc1302b04bdc0400ab56ab093a3Form;

export const update = {
    '/spk/{spk}/edit': update0d3c861f7b195e59748ef019b6804fa7,
    '/spk/{spk}': updatee2c20fc1302b04bdc0400ab56ab093a3,
    '/spk/{spk}': updatee2c20fc1302b04bdc0400ab56ab093a3,
};

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
const SpkController = {
    publicPreviewForm,
    publicPreviewOptions,
    publicPreviewDownload,
    publicPreviewFile,
    index,
    getPetugasNames,
    listByMonth,
    downloadAll,
    downloadAllByKegiatan,
    showByMonthGet,
    showByMonth,
    downloadByKegiatanMonth,
    uploadSigned,
    show,
    create,
    createAddendum,
    previewSpk,
    previewSpkMain,
    previewSpkLampiran,
    previewAllSpk,
    printSelectedMain,
    printSelectedLampiran,
    previewAddendum,
    generateSpk,
    regenerateDocument,
    cancelByPeriodeAndPetugas,
    cancelAllByPeriode,
    generateAddendum,
    generateBatchAddendum,
    generateAllSpk,
    store,
    edit,
    update,
    destroy,
};

export default SpkController;
