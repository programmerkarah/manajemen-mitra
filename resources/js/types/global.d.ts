// Global window type for Pusher
declare global {
    interface Window {
        Pusher: typeof import('pusher-js').default;
    }
}

export {};
