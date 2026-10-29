import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    base: './',
    root: './',

    build: {
        outDir: 'assets',
        emptyOutDir: false,
        // Sem "watch" aqui: senão o "npm run build" fica vigiando e nunca termina.
        // Para vigiar durante o desenvolvimento, use "npm run watch" (vite build --watch).
        rollupOptions: {
            input: path.resolve(__dirname, 'src/js/main.js'),
            output: {
                entryFileNames: 'js/bundle.js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/style.css';
                    }
                    return '[name][extname]';
                },
            },
        },
    },
});
