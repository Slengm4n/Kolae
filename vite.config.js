import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    base: './',
    root: './',

    build: {
        outDir: 'assets',
        emptyOutDir: false,
        watch: {
            exclude: ['assets/**', 'node_modules/**'],
        },
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
