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
import { lazy, Suspense, type PropsWithChildren } from 'react';

const DeadlineBypassRequestModal = lazy(() =>
    import('@/components/deadline-bypass-request-modal').then((module) => ({
        default: module.DeadlineBypassRequestModal,
    })),
);

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth, flash, ssoSync, sessionConfig } =
        usePage<SharedData>().props;
    const hasDeadlineBypassRequest = Boolean(flash?.deadline_blocked);

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
            <div className="flex h-dvh w-full overflow-hidden bg-background">
                <AppSidebar />
                <div className="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                    <div className="sticky top-0 z-30 flex-shrink-0 border-b border-border bg-card/95 shadow-sm backdrop-blur">
                        <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    </div>
                    <AppContent
                        variant="sidebar"
                        scroll-region=""
                        className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto"
                    >
                        <div className="mx-auto flex w-full max-w-[1600px] min-w-0 flex-col gap-4 p-3 pb-8 sm:p-4 md:gap-5 md:p-6 md:pb-10">
                            {children}
                        </div>
                    </AppContent>
                    <FlashMessage />
                    {hasDeadlineBypassRequest && (
                        <Suspense fallback={null}>
                            <DeadlineBypassRequestModal />
                        </Suspense>
                    )}
                </div>
            </div>
        </AppShell>
    );
}
