import { buildNavItems } from '@/lib/nav-items';
import { resolveUrl } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Augments the given breadcrumb list with a role-aware parent group prefix
 * derived from the sidebar navigation structure.
 *
 * Example (admin at /kegiatan):
 *   input:  [{ title: 'Kegiatan', href: '/kegiatan' }]
 *   output: [{ title: 'Master', href: '#' }, { title: 'Kegiatan', href: '/kegiatan' }]
 *
 * Example (ketua_tim at /kegiatan):
 *   output: [{ title: 'Administrasi', href: '#' }, { title: 'Kegiatan', href: '/kegiatan' }]
 */
export function useSidebarBreadcrumbs(
    breadcrumbs: BreadcrumbItem[],
): BreadcrumbItem[] {
    const { auth } = usePage<SharedData>().props;
    const page = usePage();
    const activeRoleName = auth.activeRole?.name;

    const currentUrl =
        typeof page.url === 'string' ? page.url : String(page.url);

    const navItems = buildNavItems(activeRoleName);

    for (const item of navItems) {
        if (!item.items || item.items.length === 0) {
            continue;
        }

        for (const sub of item.items) {
            const subUrl = resolveUrl(sub.href);

            // Skip trivial hrefs (e.g. '#') to avoid false positives
            if (!subUrl || subUrl === '#' || subUrl.length <= 1) {
                continue;
            }

            // Match exactly or as a path prefix (e.g. /kegiatan/abc123)
            const isMatch =
                currentUrl === subUrl || currentUrl.startsWith(subUrl + '/');

            if (isMatch) {
                // Avoid duplicating the group label when already present
                if (breadcrumbs[0]?.title === item.title) {
                    return breadcrumbs;
                }

                const groupBreadcrumb: BreadcrumbItem = {
                    title: item.title,
                    href: '#',
                    icon: item.icon ?? null,
                };

                return [groupBreadcrumb, ...breadcrumbs];
            }
        }
    }

    // No matching group found — return unchanged
    return breadcrumbs;
}
