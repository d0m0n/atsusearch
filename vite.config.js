import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        port: 5173,
        strictPort: true,
        host: 'localhost'
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/search.js',
                'resources/js/search_simple.js',
                'resources/js/history.js'
            ],
            refresh: true,
        }),
        vue()
    ],
});
