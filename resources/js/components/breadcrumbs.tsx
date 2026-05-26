import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/react';
import { Fragment } from 'react';

export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb className="max-w-full min-w-0">
                    <BreadcrumbList className="max-w-full min-w-0 flex-nowrap sm:flex-wrap sm:break-words">
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem className="min-w-0 shrink-0 break-words">
                                        {isLast ? (
                                            <BreadcrumbPage className="max-w-[7rem] truncate text-xs break-words sm:max-w-[16rem] sm:text-sm lg:max-w-[20rem]">
                                                {item.title}
                                            </BreadcrumbPage>
                                        ) : item.icon ? (
                                            <BreadcrumbLink
                                                asChild
                                                className="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-sm break-words"
                                            >
                                                <Link href={item.href}>
                                                    <item.icon className="size-3.5 shrink-0" />
                                                    <span className="sr-only sm:not-sr-only sm:inline sm:max-w-[8rem] sm:truncate">
                                                        {item.title}
                                                    </span>
                                                </Link>
                                            </BreadcrumbLink>
                                        ) : (
                                            <BreadcrumbLink
                                                asChild
                                                className="break-words"
                                            >
                                                <Link
                                                    href={item.href}
                                                    className="hidden max-w-[6rem] truncate sm:inline-flex sm:max-w-[10rem]"
                                                >
                                                    {item.title}
                                                </Link>
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
