import React from 'react';
import Sidebar from './Sidebar';
import Header from './Header';
import { Toaster } from 'sonner';

interface AdminLayoutProps {
    children: React.ReactNode;
}

const AdminLayout = ({ children }: AdminLayoutProps) => {
    return (
        <div className="flex min-h-screen bg-gray-50 dark:bg-[#1c2434] transition-colors duration-300">
            {/* Sidebar */}
            <Sidebar />

            <div className="flex-1 flex flex-col min-w-0">
                {/* Header */}
                <Header />

                {/* Main Content */}
                <main className="flex-1 p-6 lg:p-8 overflow-y-auto overflow-x-hidden">
                    <div className="max-w-7xl mx-auto">
                        {children}
                    </div>
                </main>
            </div>

            {/* Global Toaster */}
            <Toaster 
                position="top-center" 
                expand={true} 
                richColors 
                toastOptions={{
                    style: { 
                        borderRadius: '0', 
                        fontFamily: 'Tajawal, sans-serif',
                        fontWeight: 'bold'
                    },
                }}
            />
        </div>
    );
};

export default AdminLayout;
