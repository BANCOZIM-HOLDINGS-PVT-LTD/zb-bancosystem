import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        // HMR host auto-detection: use VITE_HMR_HOST env var, or default to localhost
        hmr: {
            host: '192.168.1.104',
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
    },
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
            '@': resolve(__dirname, 'resources/js'),
        },
    },
});
