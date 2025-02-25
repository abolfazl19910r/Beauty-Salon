import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from '@/Components/Admin/AdminDashboard';
import LoyaltyAdmin from '@/Components/Admin/Loyalty/LoyaltyAdmin';
import AnnouncementAdmin from '@/Components/Admin/Announcements/AnnouncementAdmin';
import BlogAdmin from '@/Components/Admin/Blog/BlogAdmin';
import GalleryAdmin from '@/Components/Admin/Gallery/GalleryAdmin';
import ReportDashboard from '@/Components/Admin/Reports/ReportDashboard';

// Loading component
const LoadingComponent = () => (
    <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
    </div>
);

// Mount component helper
const mountComponent = (elementId, Component) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    console.log(`Mounting ${elementId}...`);

    // Get props from data attributes or window.initialData
    let props = {};
    if (element.dataset && Object.keys(element.dataset).length > 0) {
        props = { ...element.dataset };

        // Parse JSON attributes if they exist
        if (props.routes) {
            try {
                props.routes = JSON.parse(props.routes);
            } catch (e) {
                console.error('Error parsing routes JSON:', e);
            }
        }
    }

    // Use window.initialData if available
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

// Mount admin components
mountComponent('admin-dashboard', AdminDashboard);
mountComponent('reports-panel', ReportDashboard);
mountComponent('admin-loyalty', LoyaltyAdmin);
mountComponent('admin-announcements', AnnouncementAdmin);
mountComponent('admin-blog', BlogAdmin);
mountComponent('admin-gallery', GalleryAdmin);
