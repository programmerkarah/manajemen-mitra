import { useEffect } from 'react';

/**
 * Loads the websocket client only for authenticated sessions.
 * Keeping Echo/Pusher out of the application shell significantly reduces
 * the initial bundle for guest pages and the synchronous layout chunk.
 */
export function useSessionInvalidation(userId: number | null | undefined) {
    useEffect(() => {
        if (!userId) {
            return;
        }

        let disposed = false;
        let cleanup: (() => void) | undefined;

        void import('../lib/echo').then(({ default: echo }) => {
            if (disposed) {
                return;
            }

            const channelName = `session.${userId}`;
            const channel = echo.private(channelName);

            channel.listen(
                '.session.invalidated',
                () => {
                    window.location.href =
                        '/login?message=' +
                        encodeURIComponent(
                            'Anda telah login dari perangkat lain. Silakan login kembali.',
                        );
                },
            );

            cleanup = () => {
                channel.stopListening('.session.invalidated');
                echo.leave(channelName);
            };
        });

        return () => {
            disposed = true;
            cleanup?.();
        };
    }, [userId]);
}
