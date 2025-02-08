import './bootstrap';
import { lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import { createApp } from 'vue'
import AdminDashboard from './Components/Admin/AdminDashboard.jsx';
const ReportsPanel = lazy(() => import('./Components/Admin/Reports/ReportsPanel.jsx'));
import MonthlyReport from './Components/Admin/Reports/MonthlyReport.jsx';
import SpecialistReports from './Components/Admin/Reports/SpecialistReports.jsx';
import SatisfactionReport from './Components/Admin/Reports/SatisfactionReport.jsx';
import FinancialReports from './Components/Admin/Reports/FinancialReports.jsx';
import CustomerReports from './Components/Admin/Reports/CustomerReports.jsx';
import BookingActions from './Components/BookingActions.jsx';
import TwoFactorAuth from './Components/Auth/TwoFactorAuth';
import SecureForm from './Components/Common/SecureForm';

window.React = React;
window.ReactDOM = { createRoot };

if (document.getElementById('admin-dashboard')) {
    const root = createRoot(document.getElementById('admin-dashboard'));
    root.render(<AdminDashboard />);
}

if (document.getElementById('reports-panel')) {
    const root = createRoot(document.getElementById('reports-panel'));
    root.render(<ReportsPanel />);
}

if (document.getElementById('monthly-report')) {
    const root = createRoot(document.getElementById('monthly-report'));
    root.render(<MonthlyReport />);
}

if (document.getElementById('specialist-reports')) {
    const root = createRoot(document.getElementById('specialist-reports'));
    root.render(<SpecialistReports />);
}

if (document.getElementById('satisfaction-report')) {
    const root = createRoot(document.getElementById('satisfaction-report'));
    root.render(<SatisfactionReport />);
}

if (document.getElementById('financial-reports')) {
    const root = createRoot(document.getElementById('financial-reports'));
    root.render(<FinancialReports />);
}

if (document.getElementById('customer-reports')) {
    const root = createRoot(document.getElementById('customer-reports'));
    root.render(<CustomerReports />);
}

if (document.getElementById('reports-root')) {
    const root = createRoot(document.getElementById('reports-root'));
    root.render(React.createElement(ReportsPanel));
}

if (document.getElementById('booking-actions')) {
    const container = document.getElementById('booking-actions');
    const bookingData = JSON.parse(container.dataset.booking);
    const root = createRoot(container);
    root.render(<BookingActions booking={bookingData} />);
}

if (document.getElementById('two-factor-auth')) {
    const root = createRoot(document.getElementById('two-factor-auth'));
    root.render(React.createElement(TwoFactorAuth));
}

if (document.getElementById('SecureForm')) {
    const root = createRoot(document.getElementById('secure-form'));
    root.render(React.createElement(SecureForm));
}

