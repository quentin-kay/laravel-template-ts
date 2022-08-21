import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import checker from 'vite-plugin-checker';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/ts/app.ts',
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
        checker({ vueTsc: true }),
    ],
    resolve: {
        alias: {
            '@': 'resources/ts',
            'ziggy': 'vendor/tightenco/ziggy/dist',
            'ziggy-vue': 'vendor/tightenco/ziggy/dist/vue.m',
        },
    },
});
