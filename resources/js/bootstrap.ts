import axios from 'axios';

// Configure Axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Get CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// Set base URL for API requests
axios.defaults.baseURL = '/api';

export default axios;
