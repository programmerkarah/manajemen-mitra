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
