import './bootstrap';
import { createRoot } from 'react-dom/client';
import React from 'react';

const LoadingComponent = () => (
    <div className="flex items-center justify-center p-4">
        <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
    </div>
);

const mountComponent = (elementId, importFn, getProps = () => ({})) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    importFn().then(({ default: Component }) => {
        const root = createRoot(element);
        root.render(
            <React.StrictMode>
                <React.Suspense fallback={<LoadingComponent />}>
                    <Component {...getProps(element)} />
                </React.Suspense>
            </React.StrictMode>
        );
    }).catch(err => {
        console.error(`Error mounting ${elementId}:`, err);
    });
};

mountComponent(
    'announcement-banner',
    () => import('./Components/Announcement/AnnouncementBanner')
);
