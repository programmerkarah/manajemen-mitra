import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import echo from '../lib/echo';

/**
 * Hook to listen for session invalidation events via WebSocket.
 * When user logs in from another device, this will automatically
 * redirect them to the login page.
 */
export function useSessionInvalidation(userId: number | null | undefined) {
    useEffect(() => {
        if (!userId) {
            return;
        }

        // Subscribe to private channel for this user
        const channel = echo.private(`session.${userId}`);

        // Listen for session invalidation event
        channel.listen('.session.invalidated', (event: any) => {
            console.log('Session invalidated:', event);

            // Show a message and redirect to login
            router.visit('/login', {
                method: 'get',
                data: {
                    message:
                        'Anda telah login dari perangkat lain. Silakan login kembali.',
                },
                onSuccess: () => {
                    // Force reload to clear all state
                    window.location.href = '/login';
                },
            });
        });

        // Cleanup: unsubscribe when component unmounts
        return () => {
            channel.stopListening('.session.invalidated');
            echo.leave(`session.${userId}`);
        };
    }, [userId]);
}
