import React from 'react';
import { createRoot } from 'react-dom/client';
import BookingStats from '@/Components/booking/BookingStats';

/**
 * ⚠️ Cleanup (R-Cleanup-DeadCode): This file previously mounted 6 admin panel components (AdminDashboard, ReportDashboard, LoyaltyAdmin, AnnouncementAdmin, BlogAdmin, GalleryAdmin). All 6 of these have now been converted to Blade — the first 4 already (according to the refactoring document), Announcement and Gallery now. The broken import of BlogAdmin (the file that was removed in the R-AdminBlog phase) caused Vite to reject the entire bundle, causing any admin page that @vite this file (not just the blog page) to fail with an import-analysis error. By removing these imports completely, that risk is gone. The only remaining mount (booking-stats) is for client-side pages, not the admin panel, and is not relevant to this cleanup.
 */

const LoadingComponent = () => (
    <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>
);

const mountComponent = (elementId, Component, customProps = {}) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    let props = { ...customProps };
    if (element.dataset && Object.keys(element.dataset).length > 0) {
        props = { ...props, ...element.dataset };
    }

    try {
        const root = createRoot(element);
        root.render(
            <React.StrictMode>
                <React.Suspense fallback={<LoadingComponent />}>
                    <Component {...props} />
                </React.Suspense>
            </React.StrictMode>
        );
    } catch (error) {
        console.error(`Error mounting ${elementId}:`, error);
        element.innerHTML = `
            <div class="p-4 text-center text-red-600">
                خطا در بارگذاری کامپوننت
                <br/>
                <small class="text-gray-500">${error.message}</small>
            </div>
        `;
    }
};

if (document.getElementById('booking-stats')) {
    const container = document.getElementById('booking-stats');
    let customProps = {};

    if (container.dataset.stats) {
        try {
            customProps.initialStats = JSON.parse(container.dataset.stats);
        } catch (error) {
            console.error('Error parsing booking stats:', error);
        }
    }

    if (container.dataset.date) {
        customProps.date = container.dataset.date;
    }

    mountComponent('booking-stats', BookingStats, customProps);
}
