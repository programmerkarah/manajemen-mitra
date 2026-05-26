import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashMessage } from '@/components/flash-message';
import { useSessionInvalidation } from '@/hooks/use-session-invalidation';
import { useSessionKeepAlive } from '@/hooks/use-session-keepalive';
import { useSsoScrollRestore } from '@/hooks/use-sso-scroll-restore';
import { useSsoSessionSync } from '@/hooks/use-sso-session-sync';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth, ssoSync, sessionConfig } = usePage<SharedData>().props;

    // Listen for session invalidation via WebSocket
    useSessionInvalidation(auth?.user?.id);

    useSsoSessionSync({
        enabled: Boolean(ssoSync?.enabled),
        userId: auth?.user?.id,
        focusCooldownSeconds: Number(ssoSync?.focusCooldownSeconds ?? 120),
        intervalSeconds: Number(ssoSync?.intervalSeconds ?? 600),
    });

    useSessionKeepAlive({
        enabled: Boolean(auth?.user?.id),
        intervalSeconds: Number(sessionConfig?.keepAliveIntervalSeconds ?? 300),
    });

    useSsoScrollRestore();

    return (
        <AppShell variant="sidebar">
            <div className="flex h-dvh w-full overflow-hidden bg-gradient-to-br from-blue-50/50 via-white/30 to-indigo-50/50 dark:from-neutral-950/50 dark:via-neutral-900/30 dark:to-neutral-950/50">
                {/* Sidebar */}
                <AppSidebar />

                {/* Main Content */}
                <div className="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                    {/* Sticky Header */}
                    <div className="sticky top-0 z-30 flex-shrink-0 border-b border-white/20 bg-white/60 shadow-sm transition-all dark:border-neutral-700/30 dark:bg-neutral-900/60">
                        <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    </div>
                    {/* Content - Single Scroll Area */}
                    <AppContent
                        variant="sidebar"
                        scroll-region=""
                        className="min-h-0 flex-1 overflow-x-auto overflow-y-auto"
                    >
                        <div className="flex w-full min-w-0 flex-col gap-4 p-4 pb-8 md:gap-6 md:p-6 md:pb-12">
                            {children}
                        </div>
                    </AppContent>
                    <FlashMessage />
                </div>
            </div>
        </AppShell>
    );
}
