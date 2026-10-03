import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
import frame from './frame';
import unit from './unit';
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/master-sampel',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\SampleMasterController::index
 * @see app/Http/Controllers/SampleMasterController.php:14
 * @route '/master-sampel'
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
const masterSampel = {
    index: Object.assign(index, index),
    frame: Object.assign(frame, frame),
    unit: Object.assign(unit, unit),
};

export default masterSampel;
