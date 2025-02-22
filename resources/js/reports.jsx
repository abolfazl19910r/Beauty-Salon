// resources/js/reports.jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import ReportDashboard from './Components/Admin/Reports/ReportDashboard';

const root = createRoot(document.getElementById('reports-panel'));
root.render(
    <React.StrictMode>
        <ReportDashboard
            baseUrl={window.initialData.baseUrl}
            routes={window.initialData.routes}
        />
    </React.StrictMode>
);
