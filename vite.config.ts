import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'http://localhost';
    let hmrHost = 'localhost';
    try {
        hmrHost = new URL(appUrl).hostname;
    } catch (e) {
        // Fallback in case of invalid URL
    }

    return {
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: false,
            hmr: {
                host: hmrHost,
            },
            cors: true,
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/css/filament-admin.css', 'resources/js/app.tsx', 'resources/js/filament-sidebar.js'],
                ssr: 'resources/js/ssr.tsx',
                refresh: true,
            }),
            react(),
            tailwindcss(),
        ],
        esbuild: {
            jsx: 'automatic',
            // Strip console.* and debugger from production bundles so debug logging
            // (and any PII it may carry) never ships to the browser. Dev keeps them.
            drop: mode === 'production' ? ['console', 'debugger'] : [],
        },
        resolve: {
            alias: {
                'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
                '@': resolve(__dirname, 'resources/js'),
            },
        },
    };
});
