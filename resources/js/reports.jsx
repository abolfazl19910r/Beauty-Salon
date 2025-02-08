// resources/js/reports.jsx
import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import ReportDashboard from './Components/Admin/Reports/ReportDashboard';

const container = document.getElementById('reports-app');
if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <ReportDashboard />
        </React.StrictMode>
    );
}
