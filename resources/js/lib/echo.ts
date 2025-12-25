import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Laravel Echo
window.Pusher = Pusher;

// Get CSRF token from meta tag
const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
    '';

// Initialize Laravel Echo with Reverb configuration
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // CSRF token for authentication
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    },
});

export default echo;
