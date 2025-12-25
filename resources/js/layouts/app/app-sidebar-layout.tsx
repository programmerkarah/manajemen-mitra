import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashMessage } from '@/components/flash-message';
import { useSessionInvalidation } from '@/hooks/use-session-invalidation';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth } = usePage().props as any;

    // Listen for session invalidation via WebSocket
    useSessionInvalidation(auth?.user?.id);

    return (
        <AppShell variant="sidebar">
            <div className="flex h-screen w-full bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Sidebar */}
                <AppSidebar />

                {/* Main Content */}
                <div className="flex flex-1 flex-col min-w-0">
                    {/* Sticky Header */}
                    <div className="sticky top-0 z-30 bg-white/80 dark:bg-neutral-900/80 backdrop-blur border-b border-neutral-200 dark:border-neutral-800 transition-all">
                        <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    </div>
                    {/* Content */}
                    <AppContent variant="sidebar" className="overflow-x-hidden">
                        <div className="flex flex-1 flex-col gap-4 p-4 md:gap-6 md:p-6">
                            {children}
                        </div>
                    </AppContent>
                    <FlashMessage />
                </div>
            </div>
        </AppShell>
    );
}
