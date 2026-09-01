import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
    window.axios.defaults.headers.common.Accept = 'application/json';
}

const remoteBaseUrl = String(import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '');

window.api = axios.create({
    baseURL: `${remoteBaseUrl}/api`,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

window.api.interceptors.request.use((config) => {
    const accessToken = window.localStorage.getItem('appgastos_token');

    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }

    return config;
});

window.api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 401) {
            window.dispatchEvent(new CustomEvent('appgastos:unauthorized'));
        }

        return Promise.reject(error);
    },
);
