import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const { isMobile, setOpenMobile } = useSidebar();

    const handleNavClick = () => {
        if (isMobile) {
            setOpenMobile(false);
        }
    };

    const isSubItemActive = (
        href: NonNullable<import('@inertiajs/react').InertiaLinkProps['href']>,
    ) => {
        const resolved = resolveUrl(href);
        const url = page.url;
        return (
            url === resolved ||
            url.startsWith(resolved + '/') ||
            url.startsWith(resolved + '?')
        );
    };

    const getInitialOpenItem = () => {
        for (const item of items) {
            if (item.items?.some((sub) => isSubItemActive(sub.href))) {
                return item.title;
            }
        }
        return null;
    };

    const [openItem, setOpenItem] = useState<string | null>(getInitialOpenItem);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu>
                {items.map((item) => {
                    // Check if item has sub-items
                    if (item.items && item.items.length > 0) {
                        const isOpen = openItem === item.title;
                        return (
                            <Collapsible
                                key={item.title}
                                asChild
                                open={isOpen}
                                onOpenChange={(open) =>
                                    setOpenItem(open ? item.title : null)
                                }
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            className="cursor-pointer touch-manipulation"
                                            tooltip={{ children: item.title }}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]:rotate-90" />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent className="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                        <SidebarMenuSub>
                                            {item.items.map((subItem) => (
                                                <SidebarMenuSubItem
                                                    key={subItem.title}
                                                >
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={isSubItemActive(
                                                            subItem.href,
                                                        )}
                                                    >
                                                        <Link
                                                            href={subItem.href}
                                                            prefetch
                                                            onClick={
                                                                handleNavClick
                                                            }
                                                        >
                                                            <span>
                                                                {subItem.title}
                                                            </span>
                                                        </Link>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    }

                    // Regular menu item without sub-items
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={
                                    item.activeWhen
                                        ? item.activeWhen.some((p) =>
                                              page.url.startsWith(p),
                                          )
                                        : page.url.startsWith(
                                              resolveUrl(item.href),
                                          )
                                }
                                tooltip={{ children: item.title }}
                            >
                                <Link
                                    href={item.href}
                                    prefetch
                                    onClick={handleNavClick}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
