import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { Users, Calendar, DollarSign, Scissors, ChevronUp, ChevronDown } from 'lucide-react';

const AdminDashboard = () => {
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];

    const [stats, setStats] = useState({
        todayAppointments: 0,
        totalRevenue: 0,
        totalUsers: 0,
        totalSpecialists: 0,
        revenueChange: 0,
        usersChange: 0
    });

    const [revenueData, setRevenueData] = useState([]);
    const [servicesData, setServicesData] = useState([]);
    const [specialistsData, setSpecialistsData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchDashboardData = async () => {
            try {
                const response = await fetch('/api/admin/dashboard');
                const data = await response.json();

                const formattedRevenueData = data.dailyRevenue.map(item => ({
                    date: new Date(item.date).toLocaleDateString('fa-IR'),
                    total: parseInt(item.total)
                }));

                const formattedServicesData = data.popularServices.map(service => ({
                    name: service.name,
                    bookings_count: service.bookings_count
                }));

                const formattedSpecialistsData = data.activeSpecialists.map(specialist => ({
                    name: specialist.name,
                    bookings_count: specialist.bookings_count
                }));

                setStats({
                    todayAppointments: data.stats.todayBookings,
                    totalRevenue: data.stats.totalRevenue,
                    totalUsers: data.stats.totalUsers,
                    totalSpecialists: data.stats.totalSpecialists,
                    revenueChange: data.stats.revenueChange || 0,
                    usersChange: data.stats.usersChange || 0
                });

                setRevenueData(formattedRevenueData);
                setServicesData(formattedServicesData);
                setSpecialistsData(formattedSpecialistsData);
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchDashboardData();
    }, []);

    if (loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                در حال بارگذاری...
            </div>
        );
    }

    const StatCard = ({ title, value, icon: Icon, change }) => (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-gray-500">{title}</p>
                        <h3 className="text-2xl font-bold mt-1">{value}</h3>
                        {change !== undefined && (
                            <div className="flex items-center mt-2">
                                {change >= 0 ? (
                                    <ChevronUp className="w-4 h-4 text-green-500" />
                                ) : (
                                    <ChevronDown className="w-4 h-4 text-red-500" />
                                )}
                                <span className={`text-sm ${change >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                  {Math.abs(change)}%
                </span>
                            </div>
                        )}
                    </div>
                    <div className="p-4 bg-blue-50 rounded-full">
                        <Icon className="w-6 h-6 text-blue-500" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );

    return (
        <div className="p-6 space-y-6">
            <h1 className="text-2xl font-bold mb-6">داشبورد مدیریت</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    title="نوبت‌های امروز"
                    value={stats.todayAppointments}
                    icon={Calendar}
                />
                <StatCard
                    title="درآمد کل"
                    value={`${new Intl.NumberFormat('fa-IR').format(stats.totalRevenue)} تومان`}
                    icon={DollarSign}
                    change={stats.revenueChange}
                />
                <StatCard
                    title="کاربران"
                    value={stats.totalUsers}
                    icon={Users}
                    change={stats.usersChange}
                />
                <StatCard
                    title="متخصصین"
                    value={stats.totalSpecialists}
                    icon={Scissors}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>نمودار درآمد روزانه (۷ روز گذشته)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-80">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={revenueData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="total" name="درآمد" stroke="#8884d8" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>خدمات محبوب</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={servicesData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Bar dataKey="bookings_count" name="تعداد نوبت" fill="#0088FE" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>متخصصین فعال</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={specialistsData}
                                        dataKey="bookings_count"
                                        nameKey="name"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={80}
                                        label
                                    >
                                        {specialistsData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default AdminDashboard;



// import React, { useState, useEffect } from 'react';
// import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
// import { Users, Calendar, DollarSign, Scissors } from 'lucide-react';
//
// const AdminDashboard = () => {
//     const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];
//     const [stats, setStats] = useState({
//         totalBookings: 0,
//         todayBookings: 0,
//         totalServices: 0,
//         totalSpecialists: 0,
//         totalUsers: 0,
//         totalRevenue: 0
//     });
//     const [revenueData, setRevenueData] = useState([]);
//     const [servicesData, setServicesData] = useState([]);
//     const [specialistsData, setSpecialistsData] = useState([]);
//     const [loading, setLoading] = useState(true);
//
//     useEffect(() => {
//         const fetchData = async () => {
//             try {
//                 const response = await fetch('/api/admin/dashboard');
//                 const data = await response.json();
//
//                 const formattedRevenueData = data.dailyRevenue.map(item => ({
//                     date: new Date(item.date).toLocaleDateString('fa-IR'),
//                     total: parseInt(item.total)
//                 }));
//
//                 const formattedServicesData = data.popularServices.map(service => ({
//                     name: service.name,
//                     bookings_count: service.bookings_count
//                 }));
//
//                 const formattedSpecialistsData = data.activeSpecialists.map(specialist => ({
//                     name: specialist.name,
//                     bookings_count: specialist.bookings_count
//                 }));
//
//                 setStats(data.stats);
//                 setRevenueData(formattedRevenueData);
//                 setServicesData(formattedServicesData);
//                 setSpecialistsData(formattedSpecialistsData);
//             } catch (error) {
//                 console.error('Error fetching dashboard data:', error);
//             } finally {
//                 setLoading(false);
//             }
//         };
//
//         fetchData();
//     }, []);
//
//     if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;
//
//     return (
//         <div className="p-6 space-y-6">
//             <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
//                 <div className="bg-white rounded-lg shadow p-6">
//                     <div className="flex items-center justify-between">
//                         <div>
//                             <p className="text-sm text-gray-500">نوبت‌های امروز</p>
//                             <h3 className="text-2xl font-bold">{stats.todayBookings}</h3>
//                         </div>
//                         <Calendar className="text-blue-500" size={24} />
//                     </div>
//                 </div>
//
//                 <div className="bg-white rounded-lg shadow p-6">
//                     <div className="flex items-center justify-between">
//                         <div>
//                             <p className="text-sm text-gray-500">درآمد کل</p>
//                             <h3 className="text-2xl font-bold">
//                                 {new Intl.NumberFormat('fa-IR').format(stats.totalRevenue)} تومان
//                             </h3>
//                         </div>
//                         <DollarSign className="text-green-500" size={24} />
//                     </div>
//                 </div>
//
//                 <div className="bg-white rounded-lg shadow p-6">
//                     <div className="flex items-center justify-between">
//                         <div>
//                             <p className="text-sm text-gray-500">کاربران</p>
//                             <h3 className="text-2xl font-bold">{stats.totalUsers}</h3>
//                         </div>
//                         <Users className="text-purple-500" size={24} />
//                     </div>
//                 </div>
//
//                 <div className="bg-white rounded-lg shadow p-6">
//                     <div className="flex items-center justify-between">
//                         <div>
//                             <p className="text-sm text-gray-500">متخصصین</p>
//                             <h3 className="text-2xl font-bold">{stats.totalSpecialists}</h3>
//                         </div>
//                         <Scissors className="text-red-500" size={24} />
//                     </div>
//                 </div>
//             </div>
//
//             <div className="bg-white rounded-lg shadow">
//                 <div className="p-6 border-b">
//                     <h2 className="text-xl font-bold">نمودار درآمد روزانه (۷ روز گذشته)</h2>
//                 </div>
//                 <div className="p-6">
//                     <div className="h-96">
//                         <ResponsiveContainer width="100%" height="100%">
//                             <LineChart data={revenueData}>
//                                 <CartesianGrid strokeDasharray="3 3" />
//                                 <XAxis dataKey="date" />
//                                 <YAxis />
//                                 <Tooltip />
//                                 <Legend />
//                                 <Line type="monotone" dataKey="total" name="درآمد" stroke="#8884d8" />
//                             </LineChart>
//                         </ResponsiveContainer>
//                     </div>
//                 </div>
//             </div>
//
//             <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
//                 <div className="bg-white rounded-lg shadow">
//                     <div className="p-6 border-b">
//                         <h2 className="text-xl font-bold">محبوب‌ترین خدمات</h2>
//                     </div>
//                     <div className="p-6">
//                         <div className="h-80">
//                             <ResponsiveContainer width="100%" height="100%">
//                                 <BarChart data={servicesData}>
//                                     <CartesianGrid strokeDasharray="3 3" />
//                                     <XAxis dataKey="name" />
//                                     <YAxis />
//                                     <Tooltip />
//                                     <Legend />
//                                     <Bar dataKey="bookings_count" name="تعداد نوبت" fill="#0088FE" />
//                                 </BarChart>
//                             </ResponsiveContainer>
//                         </div>
//                     </div>
//                 </div>
//
//                 <div className="bg-white rounded-lg shadow">
//                     <div className="p-6 border-b">
//                         <h2 className="text-xl font-bold">متخصصین فعال</h2>
//                     </div>
//                     <div className="p-6">
//                         <div className="h-80">
//                             <ResponsiveContainer width="100%" height="100%">
//                                 <PieChart>
//                                     <Pie
//                                         data={specialistsData}
//                                         dataKey="bookings_count"
//                                         nameKey="name"
//                                         cx="50%"
//                                         cy="50%"
//                                         outerRadius={80}
//                                         label
//                                     >
//                                         {specialistsData.map((entry, index) => (
//                                             <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
//                                         ))}
//                                     </Pie>
//                                     <Tooltip />
//                                     <Legend />
//                                 </PieChart>
//                             </ResponsiveContainer>
//                         </div>
//                     </div>
//                 </div>
//             </div>
//         </div>
//     );
// };
//
// export default AdminDashboard;
