import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/admin.jsx'
            ],
            refresh: true,
        }),
        react({
            include: '**/*.jsx'
        })
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@components': path.resolve(__dirname, './resources/js/Components'),
            '@layouts': path.resolve(__dirname, './resources/js/Layouts'),
            '@utils': path.resolve(__dirname, './resources/js/Utils'),
            '@lib': path.resolve(__dirname, './resources/js/lib')
        }
    },
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            '@headlessui/react',
            '@heroicons/react',
            'lucide-react',
            'recharts',
            'axios',
            'clsx',
            'tailwind-merge'
        ]
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        }
    },
    server: {
        hmr: {
            overlay: true
        },
        watch: {
            usePolling: true
        }
    }
});
