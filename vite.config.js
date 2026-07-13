import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Only assets actually loaded by a layout: dashboard.css
            // (layouts/app) and login.css (layouts/guest).
            input: [
                'resources/css/login.css',
                'resources/css/dashboard.css',
            ],
            refresh: true,
        }),
    ],
});
