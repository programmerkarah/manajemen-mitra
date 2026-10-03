import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import { defineConfig, type Plugin } from 'vite';

const require = createRequire(import.meta.url);



function stripClientDirectivesFromVendorModules(): Plugin {
    return {
        name: 'simantik-strip-vendor-use-client-directives',
        apply: 'build',
        enforce: 'pre',
        transform(code, id) {
            if (!id.includes('/node_modules/')) return null;
            if (!/^[\s\n\r]*(['"])use client\1;?/.test(code)) return null;

            // SIMANTIK is a client-side Inertia/Vite application, not an RSC
            // bundle. "use client" is therefore inert metadata here. Remove it
            // before Rollup parses the module so MODULE_LEVEL_DIRECTIVE is
            // never produced in the first place.
            const transformed = code.replace(
                /^[\s\n\r]*(['"])use client\1;?[\s\n\r]*/,
                '',
            );

            return {
                code: transformed,
                map: null,
            };
        },
    };
}

function buildModuleProfiler(): Plugin {
    const counts = new Map<string, number>();

    return {
        name: 'simantik-build-module-profiler',
        apply: 'build',
        moduleParsed({ id }) {
            if (!process.env.SIMANTIK_BUILD_PROFILE) return;

            let bucket = 'app';
            const marker = '/node_modules/';
            const markerIndex = id.lastIndexOf(marker);

            if (markerIndex >= 0) {
                const relative = id.slice(markerIndex + marker.length);
                const parts = relative.split('/');
                bucket = parts[0]?.startsWith('@')
                    ? parts.slice(0, 2).join('/')
                    : parts[0] || 'node_modules';
            }

            counts.set(bucket, (counts.get(bucket) ?? 0) + 1);
        },
        buildEnd() {
            if (!process.env.SIMANTIK_BUILD_PROFILE) return;

            const rows = [...counts.entries()]
                .sort((a, b) => b[1] - a[1])
                .slice(0, 20)
                .map(([name, count]) => `${name}: ${count}`)
                .join('\n');

            console.log(`\n[SIMANTIK build profile]\n${rows}\n`);
        },
    };
}

function toPascalCase(slug: string): string {
    return slug
        .split('-')
        .filter(Boolean)
        .map((part) =>
            /^\d+$/.test(part)
                ? part
                : part.charAt(0).toUpperCase() + part.slice(1),
        )
        .join('');
}

function lucidePerIconResolver(): Plugin {
    const lucideEntry = require.resolve('lucide-react');
    const lucideRoot = dirname(dirname(dirname(lucideEntry)));
    const iconsDir = join(lucideRoot, 'dist', 'esm', 'icons');
    const barrelPath = join(lucideRoot, 'dist', 'esm', 'lucide-react.js');
    const iconFiles = new Set(readdirSync(iconsDir));
    const compactFileMap = new Map<string, string>();
    const exportAliasMap = new Map<string, string>();

    for (const file of iconFiles) {
        if (!file.endsWith('.js')) continue;
        const slug = file.slice(0, -3);
        compactFileMap.set(slug.replaceAll('-', ''), slug);
    }

    if (existsSync(barrelPath)) {
        const barrel = readFileSync(barrelPath, 'utf8');
        const reExportPattern =
            /export\s*\{([^}]+)\}\s*from\s*['"]\.\/icons\/([^'"]+)\.js['"];?/g;

        for (const match of barrel.matchAll(reExportPattern)) {
            const aliases = match[1];
            const targetSlug = match[2];

            for (const alias of aliases.matchAll(
                /default\s+as\s+([A-Za-z0-9_$]+)/g,
            )) {
                exportAliasMap.set(alias[1], targetSlug);
            }
        }
    }

    return {
        name: 'simantik-lucide-per-icon-resolver',
        enforce: 'pre',
        resolveId(source) {
            const prefix = 'lucide-react/icons/';
            if (!source.startsWith(prefix)) return null;

            const requestedSlug = source.slice(prefix.length);
            const directFile = `${requestedSlug}.js`;

            if (iconFiles.has(directFile)) {
                return join(iconsDir, directFile);
            }

            const exportName = toPascalCase(requestedSlug);
            const aliasTarget = exportAliasMap.get(exportName);

            if (aliasTarget && iconFiles.has(`${aliasTarget}.js`)) {
                return join(iconsDir, `${aliasTarget}.js`);
            }

            const compactTarget = compactFileMap.get(
                requestedSlug.replaceAll('-', ''),
            );

            if (compactTarget) {
                return join(iconsDir, `${compactTarget}.js`);
            }

            this.error(
                `Unknown Lucide icon import "${source}". No matching icon module or export alias was found.`,
            );
        },
    };
}

export default defineConfig(({ command }) => ({
    cacheDir: '.cache/vite',
    resolve: {
        dedupe: ['react', 'react-dom'],
    },
    optimizeDeps: {
        exclude: ['lucide-react'],
    },
    plugins: [
        stripClientDirectivesFromVendorModules(),
        buildModuleProfiler(),
        lucidePerIconResolver(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: command === 'serve',
        }),
        ...(command === 'serve' ? [react()] : []),
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
        cssMinify: 'esbuild',
        sourcemap: false,
        reportCompressedSize: false,
        chunkSizeWarningLimit: 800,
        modulePreload: {
            polyfill: false,
        },
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined;

                    if (
                        id.includes('/react/') ||
                        id.includes('/react-dom/') ||
                        id.includes('/scheduler/') ||
                        id.includes('/@inertiajs/')
                    ) {
                        return 'vendor-react';
                    }

                    if (id.includes('/@radix-ui/')) {
                        return 'vendor-radix';
                    }

                    if (
                        id.includes('/recharts/') ||
                        id.includes('/d3-') ||
                        id.includes('/victory-vendor/')
                    ) {
                        return 'vendor-charts';
                    }

                    if (id.includes('/crypto-js/')) {
                        return 'vendor-crypto';
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
}));
