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
            <div className="flex h-screen w-full overflow-hidden bg-gradient-to-br from-blue-50/50 via-white/30 to-indigo-50/50 dark:from-neutral-950/50 dark:via-neutral-900/30 dark:to-neutral-950/50 backdrop-blur-sm">
                {/* Sidebar */}
                <AppSidebar />

                {/* Main Content */}
                <div className="flex flex-1 flex-col min-w-0 overflow-hidden">
                    {/* Sticky Header */}
                    <div className="sticky top-0 z-30 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl border-b border-white/20 dark:border-neutral-700/30 transition-all shadow-sm flex-shrink-0">
                        <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    </div>
                    {/* Content - Single Scroll Area */}
                    <AppContent variant="sidebar" className="overflow-y-auto overflow-x-auto flex-1 min-h-0">
                        <div className="flex flex-col gap-4 p-4 md:gap-6 md:p-6 pb-8 md:pb-12 min-w-max">
                            {children}
                        </div>
                    </AppContent>
                    <FlashMessage />
                </div>
            </div>
        </AppShell>
    );
}
