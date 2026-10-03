import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
export const preview = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
});

preview.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/preview',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
const previewForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::preview
 * @see app/Http/Controllers/BastController.php:4981
 * @route '/berita-acara/lampiran-action/preview'
 */
previewForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
});

preview.form = previewForm;
/**
 * @see \App\Http\Controllers\BastController::download
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
export const download = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: download.url(options),
    method: 'post',
});

download.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/download',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::download
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::download
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
download.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: download.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::download
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
const downloadForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: download.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::download
 * @see app/Http/Controllers/BastController.php:5249
 * @route '/berita-acara/lampiran-action/download'
 */
downloadForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: download.url(options),
    method: 'post',
});

download.form = downloadForm;
/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
export const uploadSigned = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadSigned.url(options),
    method: 'post',
});

uploadSigned.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/upload-signed',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadSigned.url = (options?: RouteQueryOptions) => {
    return uploadSigned.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadSigned.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadSigned.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
const uploadSignedForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadSigned
 * @see app/Http/Controllers/BastController.php:5360
 * @route '/berita-acara/lampiran-action/upload-signed'
 */
uploadSignedForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadSigned.url(options),
    method: 'post',
});

uploadSigned.form = uploadSignedForm;
/**
 * @see \App\Http\Controllers\BastController::uploadFasihScreenshot
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
export const uploadFasihScreenshot = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadFasihScreenshot.url(options),
    method: 'post',
});

uploadFasihScreenshot.definition = {
    methods: ['post'],
    url: '/berita-acara/lampiran-action/upload-fasih-screenshot',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\BastController::uploadFasihScreenshot
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadFasihScreenshot.url = (options?: RouteQueryOptions) => {
    return uploadFasihScreenshot.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\BastController::uploadFasihScreenshot
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadFasihScreenshot.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: uploadFasihScreenshot.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadFasihScreenshot
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
const uploadFasihScreenshotForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadFasihScreenshot.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\BastController::uploadFasihScreenshot
 * @see app/Http/Controllers/BastController.php:5390
 * @route '/berita-acara/lampiran-action/upload-fasih-screenshot'
 */
uploadFasihScreenshotForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: uploadFasihScreenshot.url(options),
    method: 'post',
});

uploadFasihScreenshot.form = uploadFasihScreenshotForm;
const lampiran = {
    preview: Object.assign(preview, preview),
    download: Object.assign(download, download),
    uploadSigned: Object.assign(uploadSigned, uploadSigned),
    uploadFasihScreenshot: Object.assign(
        uploadFasihScreenshot,
        uploadFasihScreenshot,
    ),
};

export default lampiran;
