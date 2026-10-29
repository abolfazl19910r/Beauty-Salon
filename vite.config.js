import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/admin.jsx',
                'resources/js/salon-qr.js',
                // بسته‌ی ۵: تصویر QR برای اینستاگرام (صفحه‌ی «QR و پوستر سالن» مدیر و «QR من» متخصص)
                'resources/js/salon-qr-poster.js',
                // کتابخانه‌هایی که قبلاً از CDN می‌آمدند (۲۰۲۶-۱۰-۱۵)؛ هر کدام فقط در صفحه‌ی خودش
                'resources/js/vendor/sweetalert.js',
                'resources/js/vendor/apexcharts.js',
                'resources/js/vendor/chartjs.js'
            ],
            refresh: true,
        }),
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
            'axios'
        ]
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // کتابخانه‌های سنگین صفحه‌ای در chunk جدا تا روی بقیه‌ی صفحه‌ها بار نشوند
                    for (const lib of ['apexcharts', 'chart.js', 'sweetalert2']) {
                        if (id.includes(`node_modules/${lib}/`)) {
                            return lib.replace('.', '');
                        }
                    }
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        }
    },
    server: {
        // Node 17+ روی ویندوز «localhost» را ::1 می‌کند و نشانی IPv6 در CSP نوشتنی نیست (۲۰۲۶-۱۰-۲۹)؛ برای شبکه‌ی محلی: npm run dev -- --host
        host: '127.0.0.1',
        hmr: {
            overlay: true
        },
        watch: {
            usePolling: true
        }
    }
});
