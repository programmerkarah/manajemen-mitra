import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from './../../wayfinder';
/**
 * @see routes/web.php:207
 * @route '/session/heartbeat'
 */
export const heartbeat = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: heartbeat.url(options),
    method: 'post',
});

heartbeat.definition = {
    methods: ['post'],
    url: '/session/heartbeat',
} satisfies RouteDefinition<['post']>;

/**
 * @see routes/web.php:207
 * @route '/session/heartbeat'
 */
heartbeat.url = (options?: RouteQueryOptions) => {
    return heartbeat.definition.url + queryParams(options);
};

/**
 * @see routes/web.php:207
 * @route '/session/heartbeat'
 */
heartbeat.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: heartbeat.url(options),
    method: 'post',
});

/**
 * @see routes/web.php:207
 * @route '/session/heartbeat'
 */
const heartbeatForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: heartbeat.url(options),
    method: 'post',
});

/**
 * @see routes/web.php:207
 * @route '/session/heartbeat'
 */
heartbeatForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: heartbeat.url(options),
    method: 'post',
});

heartbeat.form = heartbeatForm;
const session = {
    heartbeat: Object.assign(heartbeat, heartbeat),
};

export default session;
