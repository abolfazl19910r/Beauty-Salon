// resources/js/admin.jsx

import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminDashboard from './Components/Admin/AdminDashboard';
import { LoyaltyAdmin, AnnouncementAdmin } from './components/admin/LoyaltyAdmin';
import { BlogAdmin, GalleryAdmin } from './components/admin/BlogAdmin';

// Mount admin components
if (document.getElementById('admin-dashboard')) {
    createRoot(document.getElementById('admin-dashboard')).render(<AdminDashboard />);
}

if (document.getElementById('admin-loyalty')) {
    createRoot(document.getElementById('admin-loyalty')).render(<LoyaltyAdmin />);
}

if (document.getElementById('admin-announcements')) {
    createRoot(document.getElementById('admin-announcements')).render(<AnnouncementAdmin />);
}

if (document.getElementById('admin-blog')) {
    createRoot(document.getElementById('admin-blog')).render(<BlogAdmin />);
}

if (document.getElementById('admin-gallery')) {
    createRoot(document.getElementById('admin-gallery')).render(<GalleryAdmin />);
}
