import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/reports.jsx',
                'resources/js/loyalty.jsx',
                'resources/js/blog.jsx',
                'resources/js/notifications.jsx'
            ],
            refresh: true,
        }),
        react()
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@components': '/resources/js/Components',
            '@layouts': '/resources/js/Layouts',
            '@pages': '/resources/js/Pages',
            '@utils': '/resources/js/Utils'
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // React و پکیج‌های اصلی
                    react: ['react', 'react-dom'],

                    // کتابخانه‌های چارت و گراف
                    charts: ['recharts'],

                    // کتابخانه‌های Utility
                    vendor: ['axios', 'lodash', 'date-fns', 'date-fns-jalali'],

                    // کامپوننت‌های UI
                    ui: ['@headlessui/react', '@heroicons/react', 'lucide-react'],

                    // کتابخانه‌های فرم و اعتبارسنجی
                    forms: ['react-hook-form', '@hookform/resolvers', 'yup'],

                    // کتابخانه‌های مربوط به ویرایشگر
                    editor: ['@tiptap/react', '@tiptap/starter-kit', '@tiptap/extension-image'],

                    // کتابخانه‌های مربوط به نوتیفیکیشن
                    notifications: ['react-hot-toast', 'socket.io-client']
                }
            }
        },
        chunkSizeWarningLimit: 1500,
        sourcemap: true,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            }
        }
    },
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            'recharts',
            'axios',
            'lodash',
            '@headlessui/react',
            'lucide-react'
        ]
    },
    server: {
        hmr: {
            overlay: true
        }
    }
});
