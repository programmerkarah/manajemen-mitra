import {
    applyUrlDefaults,
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkan
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
export const batalkan = (
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
    url: batalkan.url(args, options),
    method: 'post',
});

batalkan.definition = {
    methods: ['post'],
    url: '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkan
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkan.url = (
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
        batalkan.definition.url
            .replace('{kegiatan}', parsedArgs.kegiatan.toString())
            .replace('{tahun}', parsedArgs.tahun.toString())
            .replace('{bulan}', parsedArgs.bulan.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkan
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkan.post = (
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
    url: batalkan.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkan
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
const batalkanForm = (
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
    action: batalkan.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlokasiPetugasController::batalkan
 * @see app/Http/Controllers/AlokasiPetugasController.php:4368
 * @route '/alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi/batal'
 */
batalkanForm.post = (
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
    action: batalkan.url(args, options),
    method: 'post',
});

batalkan.form = batalkanForm;
const revisi = {
    batalkan: Object.assign(batalkan, batalkan),
};

export default revisi;
