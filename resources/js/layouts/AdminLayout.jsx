import React from 'react';
import Sidebar from '@/components/admin/Sidebar';
import Header from '@/components/admin/Header';

const AdminLayout = ({ children }) => {
    return (
        <>
            <meta name="csrf-token" content={document.querySelector('meta[name="csrf-token"]')?.content || ''} />
            <div className="flex h-screen bg-gray-100">
                <Sidebar />
                <div className="flex-1 flex flex-col overflow-hidden">
                    <Header />
                    <main className="flex-1 overflow-y-auto p-4">
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
};

export default AdminLayout;
