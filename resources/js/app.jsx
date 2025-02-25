import React from 'react';
import './bootstrap';
import { createRoot } from 'react-dom/client';

// User Components
import BookingActions from './Components/BookingActions';
import TwoFactorAuth from './Components/Auth/TwoFactorAuth';
import SecureForm from './Components/Common/SecureForm';

// Loading component
const LoadingComponent = () => (
    <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <div className="mt-2 text-gray-600">در حال بارگذاری...</div>
        </div>
    </div>
);

// Mount component helper
const mountComponent = (elementId, Component, props = {}) => {
    const element = document.getElementById(elementId);
    if (element) {
        console.log(`Mounting ${elementId}...`);
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
    }
};

// Mount user components
if (document.getElementById('booking-actions')) {
    const container = document.getElementById('booking-actions');
    const bookingData = JSON.parse(container.dataset.booking || '{}');
    mountComponent('booking-actions', BookingActions, { booking: bookingData });
}

mountComponent('two-factor-auth', TwoFactorAuth);
mountComponent('secure-form', SecureForm);
