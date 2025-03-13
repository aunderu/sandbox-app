import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'Modules/SandBox/Resources/assets/js/infinite-scroll.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@sandbox': path.resolve(__dirname, 'Modules/SandBox/Resources/assets'),
        },
    },
});
