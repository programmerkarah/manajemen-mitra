type PageModule = {
    default?: unknown;
};

type PageImporter = () => Promise<unknown>;

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

    return module.default ?? module;
}
