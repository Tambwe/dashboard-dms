import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        // Désactiver HMR pour les déploiements réseau
        hmr: process.env.APP_ENV === 'production' ? false : {
            host: '10.223.242.174',
            port: 5173,
        },
    },
});
