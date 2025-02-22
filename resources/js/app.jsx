import React from 'react';
import './bootstrap';
import { lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from './Components/Admin/AdminDashboard.jsx';
import ReportDashboard from './Components/Admin/Reports/ReportDashboard.jsx';
import BookingActions from './Components/BookingActions.jsx';
import TwoFactorAuth from './Components/Auth/TwoFactorAuth';
import SecureForm from './Components/Common/SecureForm';

// Debug logs
console.log('App.jsx initialized');

// Set up global React
window.React = React;
window.ReactDOM = { createRoot };

// Admin Dashboard
if (document.getElementById('admin-dashboard')) {
    console.log('Mounting AdminDashboard...');
    try {
        const root = createRoot(document.getElementById('admin-dashboard'));
        root.render(
            <React.StrictMode>
                <AdminDashboard />
            </React.StrictMode>
        );
        console.log('AdminDashboard mounted successfully');
    } catch (error) {
        console.error('Error mounting AdminDashboard:', error);
    }
}

// Reports Dashboard
const reportsElement = document.getElementById('reports-panel');
if (reportsElement) {
    console.log('Mounting ReportDashboard...');
    try {
        const root = createRoot(reportsElement);

        // Loading component
        const LoadingComponent = () => (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <div className="mt-2 text-gray-600">در حال بارگذاری...</div>
                </div>
            </div>
        );

        root.render(
            <React.StrictMode>
                <Suspense fallback={<LoadingComponent />}>
                    <ReportDashboard
                        baseUrl={window.initialData?.baseUrl}
                        routes={window.initialData?.routes}
                    />
                </Suspense>
            </React.StrictMode>
        );
        console.log('ReportDashboard mounted successfully');
    } catch (error) {
        console.error('Error mounting ReportDashboard:', error);
        // Show error in UI
        reportsElement.innerHTML = `
            <div class="p-4 text-center text-red-600">
                خطا در بارگذاری داشبورد گزارشات
                <br/>
                <small class="text-gray-500">${error.message}</small>
            </div>
        `;
    }
}

// Booking Actions
if (document.getElementById('booking-actions')) {
    console.log('Mounting BookingActions...');
    try {
        const container = document.getElementById('booking-actions');
        const bookingData = JSON.parse(container.dataset.booking);
        const root = createRoot(container);
        root.render(
            <React.StrictMode>
                <BookingActions booking={bookingData} />
            </React.StrictMode>
        );
        console.log('BookingActions mounted successfully');
    } catch (error) {
        console.error('Error mounting BookingActions:', error);
    }
}

// Two Factor Auth
if (document.getElementById('two-factor-auth')) {
    console.log('Mounting TwoFactorAuth...');
    try {
        const root = createRoot(document.getElementById('two-factor-auth'));
        root.render(
            <React.StrictMode>
                <TwoFactorAuth />
            </React.StrictMode>
        );
        console.log('TwoFactorAuth mounted successfully');
    } catch (error) {
        console.error('Error mounting TwoFactorAuth:', error);
    }
}

// Secure Form
if (document.getElementById('secure-form')) {
    console.log('Mounting SecureForm...');
    try {
        const root = createRoot(document.getElementById('secure-form'));
        root.render(
            <React.StrictMode>
                <SecureForm />
            </React.StrictMode>
        );
        console.log('SecureForm mounted successfully');
    } catch (error) {
        console.error('Error mounting SecureForm:', error);
    }
}
