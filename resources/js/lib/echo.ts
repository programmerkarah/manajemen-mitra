import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Laravel Echo
window.Pusher = Pusher;

// Get CSRF token from meta tag
const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') || '';

// Detect broadcaster type - prioritize Pusher if key exists
const usePusher = !!import.meta.env.VITE_PUSHER_APP_KEY;
const broadcaster = usePusher ? 'pusher' : 'reverb';

console.log('🔌 Initializing Echo with broadcaster:', broadcaster);

if (usePusher) {
    console.log('🔑 Pusher Key:', import.meta.env.VITE_PUSHER_APP_KEY);
    console.log('🌍 Pusher Cluster:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
} else {
    console.log('🔑 Reverb Key:', import.meta.env.VITE_REVERB_APP_KEY);
    console.log('🌍 Reverb Host:', import.meta.env.VITE_REVERB_HOST);
}

// Initialize Laravel Echo with dynamic configuration
const echo = new Echo(
    usePusher
        ? {
              // Pusher configuration (for production/Hostinger)
              broadcaster: 'pusher',
              key: import.meta.env.VITE_PUSHER_APP_KEY || '',
              cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1',
              forceTLS: true,
              authEndpoint: '/broadcasting/auth',
              auth: {
                  headers: {
                      'X-CSRF-TOKEN': csrfToken,
                  },
              },
          }
        : {
              // Reverb configuration (for local development)
              broadcaster: 'reverb',
              key: import.meta.env.VITE_REVERB_APP_KEY,
              wsHost: import.meta.env.VITE_REVERB_HOST,
              wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
              wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
              forceTLS:
                  (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
              enabledTransports: ['ws', 'wss'],
              auth: {
                  headers: {
                      'X-CSRF-TOKEN': csrfToken,
                  },
              },
          },
);

// Debug: Log Pusher connection status
if (usePusher && echo.connector?.pusher) {
    echo.connector.pusher.connection.bind(
        'state_change',
        (states: { previous: string; current: string }) => {
            console.log(
                '📡 Pusher state change:',
                states.previous,
                '→',
                states.current,
            );
        },
    );

    echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Pusher connected successfully');
    });

    echo.connector.pusher.connection.bind('error', (err: unknown) => {
        console.error('❌ Pusher connection error:', err);
    });
}

export default echo;
