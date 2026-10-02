import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
            port: 5173,
            protocol: 'ws',
        },
        headers: {
            'Cache-Control': 'no-cache',
        },
    },
    esbuild: {
        jsx: 'automatic',
        drop: ['debugger'],
    },
    build: {
        target: 'es2022',
        minify: 'esbuild',
        sourcemap: false,
        reportCompressedSize: false,
        chunkSizeWarningLimit: 800,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }

                    if (
                        id.includes('/react/') ||
                        id.includes('/react-dom/') ||
                        id.includes('/scheduler/')
                    ) {
                        return 'vendor-react';
                    }

                    if (id.includes('/@inertiajs/')) {
                        return 'vendor-inertia';
                    }

                    if (
                        id.includes('/@radix-ui/') ||
                        id.includes('/cmdk/') ||
                        id.includes('/input-otp/')
                    ) {
                        return 'vendor-ui';
                    }

                    if (id.includes('/lucide-react/')) {
                        return 'vendor-icons';
                    }

                    if (id.includes('/recharts/')) {
                        return 'vendor-charts';
                    }

                    if (id.includes('/crypto-js/')) {
                        return 'vendor-crypto';
                    }

                    if (
                        id.includes('/laravel-echo/') ||
                        id.includes('/pusher-js/')
                    ) {
                        return 'vendor-realtime';
                    }

                    return undefined;
                },
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name?.split('.');
                    const ext = info?.[info.length - 1];

                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext || '')) {
                        return 'images/[name]-[hash][extname]';
                    }

                    if (/woff2?|ttf|otf|eot/i.test(ext || '')) {
                        return 'fonts/[name]-[hash][extname]';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
            },
        },
    },
});
