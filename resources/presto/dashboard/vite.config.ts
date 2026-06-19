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
          if (info.name && /\.(woff2?|ttf|eot)$/.test(info.name)) {
            return 'fonts/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
        manualChunks(id) {
          if (id.includes('node_modules/solid-js')) {
            return 'vendor-solid';
          }
          if (id.includes('node_modules/lucide-solid')) {
            return 'vendor-lucide';
          }
          if (id.includes('node_modules')) {
            return 'vendor';
          }
          if (id.includes('src/pages/')) {
            const match = id.match(/src\/pages\/(.+)\.tsx$/);
            if (match) {
              return `page-${match[1].toLowerCase()}`;
            }
          }
        },
      },
    },
  },
  resolve: {
    conditions: ['development', 'browser'],
  },
});
