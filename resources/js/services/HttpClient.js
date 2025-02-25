// resources/js/services/HttpClient.js
import axios from 'axios';

const instance = axios.create({
    baseURL: '',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

instance.interceptors.request.use(
    (config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

instance.interceptors.response.use(
    (response) => response.data,
    (error) => {
        const status = error.response?.status;

        if (status === 401) {
            window.location.href = '/login';
        } else if (status === 403) {
            console.error('دسترسی غیرمجاز');
        } else if (status === 404) {
            console.error('منبع درخواستی یافت نشد');
        } else if (status === 422) {
            return Promise.reject(error.response.data);
        } else if (status === 500) {
            console.error('خطای سرور رخ داده است');
        }

        return Promise.reject(error);
    }
);

const HttpClient = {
    get: (url, config = {}) => instance.get(url, config),
    post: (url, data = {}, config = {}) => instance.post(url, data, config),
    put: (url, data = {}, config = {}) => instance.put(url, data, config),
    delete: (url, config = {}) => instance.delete(url, config),
    patch: (url, data = {}, config = {}) => instance.patch(url, data, config)
};

export default HttpClient;
