export type StaticHttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

export interface StaticRouteDefinition {
    url: string;
    method: StaticHttpMethod;
}

export interface StaticFormDefinition {
    action: string;
    method: StaticHttpMethod;
}

export type StaticRoute = (() => StaticRouteDefinition) & {
    url: () => string;
    form: () => StaticFormDefinition;
};

export function defineStaticRoute(
    url: string,
    method: StaticHttpMethod = 'get',
): StaticRoute {
    const route = (() => ({ url, method })) as StaticRoute;

    route.url = () => url;
    route.form = () => ({ action: url, method });

    return route;
}
