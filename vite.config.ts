import { wayfinder } from '@laravel/vite-plugin-wayfinder';
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
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    build: {
        rollupOptions: {
            output: {
                // Manual chunking for better caching
                manualChunks: {
                    // Vendor packages in separate chunk
                    vendor: ['react', 'react-dom', '@inertiajs/react'],
                    // Icon libraries
                    icons: ['lucide-react'],
                },
                // Asset file names with hash for cache busting
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name?.split('.');
                    const ext = info?.[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext || '')) {
                        return `images/[name]-[hash][extname]`;
                    }
                    if (/woff2?|ttf|otf|eot/i.test(ext || '')) {
                        return `fonts/[name]-[hash][extname]`;
                    }
                    return `assets/[name]-[hash][extname]`;
                },
                // Chunk file names with hash
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
            },
        },
        // Minification settings
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: false, // Keep console.log for debugging
                drop_debugger: true,
            },
        },
        // Chunk size warning limit
        chunkSizeWarningLimit: 1000,
        // Source maps for debugging (set to false in production)
        sourcemap: false,
    },
    // Development server configuration
    server: {
        hmr: {
            host: 'localhost',
        },
        // Add headers for development
        headers: {
            'Cache-Control': 'no-cache',
        },
    },
});
