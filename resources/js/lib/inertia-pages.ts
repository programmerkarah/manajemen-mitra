import { formatPersonName, normalizePersonNames } from '@/lib/person-name';
import { createElement, type ComponentType } from 'react';

type PageModule = {
    default?: unknown;
};

type PageImporter = () => Promise<unknown>;

type InertiaPageComponent = ComponentType<Record<string, unknown>> & {
    layout?: unknown;
};

const normalizedPageComponents = new WeakMap<object, InertiaPageComponent>();

function withNormalizedPersonNames(component: InertiaPageComponent): InertiaPageComponent {
    const cached = normalizedPageComponents.get(component as object);
    if (cached) return cached;

    const NormalizedPage = ((props: Record<string, unknown>) =>
        createElement(component, normalizePersonNames(props))) as InertiaPageComponent;

    NormalizedPage.displayName = `NormalizedPersonNames(${component.displayName || component.name || 'Page'})`;
    NormalizedPage.layout = component.layout;

    normalizedPageComponents.set(component as object, NormalizedPage);

    return NormalizedPage;
}

export async function resolveInertiaPage(
    name: string,
    pages: Record<string, PageImporter>,
): Promise<unknown> {
    const path = `./pages/${name}.tsx`;
    const importer = pages[path];

    if (!importer) {
        throw new Error(`Inertia page not found: ${path}`);
    }

    const module = (await importer()) as PageModule;
    const resolved = (module.default ?? module) as InertiaPageComponent;

    if (typeof resolved !== 'function') {
        return resolved;
    }

    return withNormalizedPersonNames(resolved);
}

// Re-export the display formatter from the global page-normalization boundary.
// This keeps page-specific code from re-implementing ucwords differently.
export { formatPersonName };
