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
            console.log('useSessionInvalidation: No userId provided');
            return;
        }

        console.log('useSessionInvalidation: Setting up listener for user', userId);

        // Subscribe to private channel for this user
        const channel = echo.private(`session.${userId}`);

        console.log('useSessionInvalidation: Subscribed to channel', `session.${userId}`);

        // Listen for session invalidation event
        channel.listen('.session.invalidated', (event: any) => {
            console.log('🔴 Session invalidated received!', event);
            
            // Force immediate redirect without Inertia (to bypass any caching)
            window.location.href = '/login?message=' + encodeURIComponent('Anda telah login dari perangkat lain. Silakan login kembali.');
        });

        // Debug: log connection status
        channel.subscription.bind('pusher:subscription_succeeded', () => {
            console.log('✅ Channel subscription succeeded');
        });

        channel.subscription.bind('pusher:subscription_error', (error: any) => {
            console.error('❌ Channel subscription error:', error);
        });

        // Cleanup: unsubscribe when component unmounts
        return () => {
            console.log('useSessionInvalidation: Cleaning up listener');
            channel.stopListening('.session.invalidated');
            echo.leave(`session.${userId}`);
        };
    }, [userId]);
}
