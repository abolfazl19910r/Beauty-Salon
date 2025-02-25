import React from 'react';
import {
    LayoutDashboard,
    Bell,
    Newspaper,
    Image,
    Gift,
    Calendar
} from 'lucide-react';

const Sidebar = () => {
    const menuItems = [
        { title: 'داشبورد', path: '/admin/dashboard', icon: LayoutDashboard },
        { title: 'اعلانات', path: '/admin/announcements', icon: Bell },
        { title: 'وبلاگ', path: '/admin/blog', icon: Newspaper },
        { title: 'گالری', path: '/admin/gallery', icon: Image },
        { title: 'امتیازات', path: '/admin/loyalty', icon: Gift },
        { title: 'زمانبندی', path: '/admin/schedule', icon: Calendar },
    ];

    return (
        <div className="w-64 bg-white shadow-lg h-screen fixed right-0 top-0 py-4">
            {menuItems.map((item, index) => {
                const IconComponent = item.icon;
                return (
                    <a
                        key={index}
                        href={item.path}
                        className="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                    >
                        <IconComponent className="ml-2" size={20} />
                        <span className="text-sm font-medium">{item.title}</span>
                    </a>
                );
            })}
        </div>
    );
};

export default Sidebar;
