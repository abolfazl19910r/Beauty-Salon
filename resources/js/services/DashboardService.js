// resources/js/services/DashboardService.js
import HttpClient from './HttpClient';

class DashboardService {
    /**
     * @returns {Promise<Object>}
     */
    static async getDashboardData() {
        try {
            return await HttpClient.get('/admin/dashboard');
        } catch (error) {
            console.error('خطا در دریافت اطلاعات داشبورد:', error);
            throw error;
        }
    }

    /**
     * @param {Object} params
     * @param {string} params.start_date
     * @param {string} params.end_date
     * @returns {Promise<Object>}
     */
    static async getDailyRevenue(params = {}) {
        try {
            return await HttpClient.get('/admin/dashboard/daily-revenue', { params });
        } catch (error) {
            console.error('خطا در دریافت اطلاعات درآمد روزانه:', error);
            throw error;
        }
    }

    /**
     * @returns {Promise<Object>}
     */
    static async getPopularServices() {
        try {
            return await HttpClient.get('/admin/reports/popular-services');
        } catch (error) {
            console.error('خطا در دریافت اطلاعات خدمات محبوب:', error);
            throw error;
        }
    }

    /**
     * @returns {Promise<Object>}
     */
    static async getActiveSpecialists() {
        try {
            return await HttpClient.get('/admin/reports/active-specialists');
        } catch (error) {
            console.error('خطا در دریافت اطلاعات متخصصین فعال:', error);
            throw error;
        }
    }
}

export default DashboardService;
