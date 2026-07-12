import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder'; 

export default defineConfig(({ command }) => ({
    // Expose the project root to the client so SourceLinks can open files in VSCode during development
    define: command === 'serve' ? { __PROJECT_ROOT__: JSON.stringify(process.cwd()) } : {},
    build: {
        minify: false,
    },
    plugins: [
        // 2. 将 wayfinder 注册进插件列表（可以开启 formVariants: true 来获得更强的表单类型推导）
        wayfinder({ formVariants: true }), 
        // wayfinder(),
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
}));
