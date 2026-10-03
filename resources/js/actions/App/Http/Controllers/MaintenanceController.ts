import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
const showDown1ceab25e6eb26d98efac3218399ca311 = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'get',
});

showDown1ceab25e6eb26d98efac3218399ca311.definition = {
    methods: ['get', 'head'],
    url: '/mt',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
showDown1ceab25e6eb26d98efac3218399ca311.url = (
    options?: RouteQueryOptions,
) => {
    return (
        showDown1ceab25e6eb26d98efac3218399ca311.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
showDown1ceab25e6eb26d98efac3218399ca311.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
showDown1ceab25e6eb26d98efac3218399ca311.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: showDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
const showDown1ceab25e6eb26d98efac3218399ca311Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
showDown1ceab25e6eb26d98efac3218399ca311Form.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/mt'
 */
showDown1ceab25e6eb26d98efac3218399ca311Form.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown1ceab25e6eb26d98efac3218399ca311.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showDown1ceab25e6eb26d98efac3218399ca311.form =
    showDown1ceab25e6eb26d98efac3218399ca311Form;
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
const showDown18b688792014c8916cd3a3f035042beb = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'get',
});

showDown18b688792014c8916cd3a3f035042beb.definition = {
    methods: ['get', 'head'],
    url: '/maintenance',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
showDown18b688792014c8916cd3a3f035042beb.url = (
    options?: RouteQueryOptions,
) => {
    return (
        showDown18b688792014c8916cd3a3f035042beb.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
showDown18b688792014c8916cd3a3f035042beb.get = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
showDown18b688792014c8916cd3a3f035042beb.head = (
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: showDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
const showDown18b688792014c8916cd3a3f035042bebForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
showDown18b688792014c8916cd3a3f035042bebForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showDown
 * @see app/Http/Controllers/MaintenanceController.php:161
 * @route '/maintenance'
 */
showDown18b688792014c8916cd3a3f035042bebForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showDown18b688792014c8916cd3a3f035042beb.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showDown18b688792014c8916cd3a3f035042beb.form =
    showDown18b688792014c8916cd3a3f035042bebForm;

export const showDown = {
    '/mt': showDown1ceab25e6eb26d98efac3218399ca311,
    '/maintenance': showDown18b688792014c8916cd3a3f035042beb,
};

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/mt'
 */
const processDown1ceab25e6eb26d98efac3218399ca311 = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'post',
});

processDown1ceab25e6eb26d98efac3218399ca311.definition = {
    methods: ['post'],
    url: '/mt',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/mt'
 */
processDown1ceab25e6eb26d98efac3218399ca311.url = (
    options?: RouteQueryOptions,
) => {
    return (
        processDown1ceab25e6eb26d98efac3218399ca311.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/mt'
 */
processDown1ceab25e6eb26d98efac3218399ca311.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/mt'
 */
const processDown1ceab25e6eb26d98efac3218399ca311Form = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/mt'
 */
processDown1ceab25e6eb26d98efac3218399ca311Form.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processDown1ceab25e6eb26d98efac3218399ca311.url(options),
    method: 'post',
});

processDown1ceab25e6eb26d98efac3218399ca311.form =
    processDown1ceab25e6eb26d98efac3218399ca311Form;
/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/maintenance'
 */
const processDown18b688792014c8916cd3a3f035042beb = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'post',
});

processDown18b688792014c8916cd3a3f035042beb.definition = {
    methods: ['post'],
    url: '/maintenance',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/maintenance'
 */
processDown18b688792014c8916cd3a3f035042beb.url = (
    options?: RouteQueryOptions,
) => {
    return (
        processDown18b688792014c8916cd3a3f035042beb.definition.url +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/maintenance'
 */
processDown18b688792014c8916cd3a3f035042beb.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/maintenance'
 */
const processDown18b688792014c8916cd3a3f035042bebForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processDown
 * @see app/Http/Controllers/MaintenanceController.php:223
 * @route '/maintenance'
 */
processDown18b688792014c8916cd3a3f035042bebForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processDown18b688792014c8916cd3a3f035042beb.url(options),
    method: 'post',
});

processDown18b688792014c8916cd3a3f035042beb.form =
    processDown18b688792014c8916cd3a3f035042bebForm;

export const processDown = {
    '/mt': processDown1ceab25e6eb26d98efac3218399ca311,
    '/maintenance': processDown18b688792014c8916cd3a3f035042beb,
};

/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
export const showBypass = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showBypass.url(options),
    method: 'get',
});

showBypass.definition = {
    methods: ['get', 'head'],
    url: '/bypass',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
showBypass.url = (options?: RouteQueryOptions) => {
    return showBypass.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
showBypass.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showBypass.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
showBypass.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showBypass.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
const showBypassForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showBypass.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
showBypassForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showBypass.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showBypass
 * @see app/Http/Controllers/MaintenanceController.php:17
 * @route '/bypass'
 */
showBypassForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showBypass.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showBypass.form = showBypassForm;
/**
 * @see \App\Http\Controllers\MaintenanceController::processBypass
 * @see app/Http/Controllers/MaintenanceController.php:46
 * @route '/bypass'
 */
export const processBypass = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processBypass.url(options),
    method: 'post',
});

processBypass.definition = {
    methods: ['post'],
    url: '/bypass',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::processBypass
 * @see app/Http/Controllers/MaintenanceController.php:46
 * @route '/bypass'
 */
processBypass.url = (options?: RouteQueryOptions) => {
    return processBypass.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::processBypass
 * @see app/Http/Controllers/MaintenanceController.php:46
 * @route '/bypass'
 */
processBypass.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processBypass
 * @see app/Http/Controllers/MaintenanceController.php:46
 * @route '/bypass'
 */
const processBypassForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processBypass.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processBypass
 * @see app/Http/Controllers/MaintenanceController.php:46
 * @route '/bypass'
 */
processBypassForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processBypass.url(options),
    method: 'post',
});

processBypass.form = processBypassForm;
/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
export const showUp = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: showUp.url(options),
    method: 'get',
});

showUp.definition = {
    methods: ['get', 'head'],
    url: '/up',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
showUp.url = (options?: RouteQueryOptions) => {
    return showUp.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
showUp.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showUp.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
showUp.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showUp.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
const showUpForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showUp.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
showUpForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showUp.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\MaintenanceController::showUp
 * @see app/Http/Controllers/MaintenanceController.php:76
 * @route '/up'
 */
showUpForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: showUp.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

showUp.form = showUpForm;
/**
 * @see \App\Http\Controllers\MaintenanceController::processUp
 * @see app/Http/Controllers/MaintenanceController.php:118
 * @route '/up'
 */
export const processUp = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: processUp.url(options),
    method: 'post',
});

processUp.definition = {
    methods: ['post'],
    url: '/up',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\MaintenanceController::processUp
 * @see app/Http/Controllers/MaintenanceController.php:118
 * @route '/up'
 */
processUp.url = (options?: RouteQueryOptions) => {
    return processUp.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MaintenanceController::processUp
 * @see app/Http/Controllers/MaintenanceController.php:118
 * @route '/up'
 */
processUp.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processUp.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processUp
 * @see app/Http/Controllers/MaintenanceController.php:118
 * @route '/up'
 */
const processUpForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processUp.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\MaintenanceController::processUp
 * @see app/Http/Controllers/MaintenanceController.php:118
 * @route '/up'
 */
processUpForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: processUp.url(options),
    method: 'post',
});

processUp.form = processUpForm;
const MaintenanceController = {
    showDown,
    processDown,
    showBypass,
    processBypass,
    showUp,
    processUp,
};

export default MaintenanceController;
