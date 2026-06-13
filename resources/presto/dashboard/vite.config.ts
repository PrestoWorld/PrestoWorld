import { defineConfig } from 'vite';
import solidPlugin from 'vite-plugin-solid';

export default defineConfig({
    plugins: [solidPlugin()],
    base: '/assets/admin/spa/',
    build: {
        outDir: '../../../public/assets/admin/spa',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                entryFileNames: 'js/admin-spa.js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (info) => {
                    if (info.name && info.name.endsWith('.css')) {
                        return 'css/admin-spa.css';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    resolve: {
        conditions: ['development', 'browser'],
    },
});
