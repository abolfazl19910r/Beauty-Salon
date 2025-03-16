import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from '@/Components/Admin/AdminDashboard';
import LoyaltyAdmin from '@/Components/Admin/Loyalty/LoyaltyAdmin';
import AnnouncementAdmin from '@/Components/Admin/Announcements/AnnouncementAdmin';
import BlogAdmin from '@/Components/Admin/Blog/BlogAdmin';
import GalleryAdmin from '@/Components/Admin/Gallery/GalleryAdmin';
import ReportDashboard from '@/Components/Admin/Reports/ReportDashboard';
import BookingStats from '@/Components/Admin/BookingStats';

const LoadingComponent = () => (
    <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>
);

const mountComponent = (elementId, Component, customProps = {}) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    console.log(`Mounting ${elementId}...`);

    let props = { ...customProps };
    if (element.dataset && Object.keys(element.dataset).length > 0) {
        props = { ...props, ...element.dataset };

        if (props.routes) {
            try {
                props.routes = JSON.parse(props.routes);
            } catch (e) {
                console.error('Error parsing routes JSON:', e);
            }
        }
    }

    if (window.initialData) {
        props = { ...props, ...window.initialData };
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
        console.log(`${elementId} mounted successfully`);
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

mountComponent('admin-dashboard', AdminDashboard);
mountComponent('reports-panel', ReportDashboard);
mountComponent('admin-loyalty', LoyaltyAdmin);
mountComponent('admin-announcements', AnnouncementAdmin);
mountComponent('admin-blog', BlogAdmin);
mountComponent('admin-gallery', GalleryAdmin);
