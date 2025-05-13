import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/web.css',
                'resources/js/web.js',
                'resources/css/admin.css',
                'resources/js/admin.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
            '@admin': path.resolve(__dirname, 'resources/js/admin'),
            '@web': path.resolve(__dirname, 'resources/js/web'),
        }
    },
    // build: {
    //     rollupOptions: {
    //         output: {
    //             manualChunks(id) {
    //                 if (id.includes('node_modules')) {
    //                     return id.toString().split('node_modules/')[1].split('/')[0].toString();
    //                 }
    //             }
    //         }
    //     }
    // }
});
