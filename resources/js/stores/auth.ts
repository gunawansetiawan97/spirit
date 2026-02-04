import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    role: {
        id: number;
        uuid: string;
        code: string;
        name: string;
    } | null;
    branch: {
        id: number;
        uuid: string;
        code: string;
        name: string;
    } | null;
}

interface MenuItem {
    id: number;
    code: string;
    name: string;
    icon: string | null;
    route: string | null;
    type: string;
    children?: MenuItem[];
}

interface Permission {
    can_view: boolean;
    can_create: boolean;
    can_edit: boolean;
    can_delete: boolean;
    can_approve: boolean;
    can_print: boolean;
    can_export: boolean;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(localStorage.getItem('token'));
    const menus = ref<MenuItem[]>([]);
    const permissions = ref<Record<string, Permission>>({});
    const isLoading = ref(false);

    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const currentBranch = computed(() => user.value?.branch);
    const currentRole = computed(() => user.value?.role);

    const setAuth = (data: {
        user: User;
        token?: string;
        menus: MenuItem[];
        permissions: Record<string, Permission>;
    }) => {
        user.value = data.user;
        menus.value = data.menus;
        permissions.value = data.permissions;
        if (data.token) {
            token.value = data.token;
            localStorage.setItem('token', data.token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
        }
    };

    const login = async (email: string, password: string) => {
        isLoading.value = true;
        try {
            const response = await axios.post('/api/login', { email, password });
            setAuth(response.data.data);
            return { success: true };
        } catch (error: any) {
            return {
                success: false,
                message: error.response?.data?.message || 'Login gagal',
                errors: error.response?.data?.errors,
            };
        } finally {
            isLoading.value = false;
        }
    };

    const logout = async () => {
        try {
            await axios.post('/api/logout');
        } catch (error) {
            // Ignore error
        } finally {
            user.value = null;
            token.value = null;
            menus.value = [];
            permissions.value = {};
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        }
    };

    const fetchUser = async () => {
        if (!token.value) return false;

        isLoading.value = true;
        try {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            const response = await axios.get('/api/me');
            setAuth(response.data.data);
            return true;
        } catch (error) {
            logout();
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const hasPermission = (permissionCode: string, action: keyof Permission = 'can_view'): boolean => {
        const perm = permissions.value[permissionCode];
        if (!perm) return false;
        return perm[action];
    };

    const can = (action: string, permissionCode: string): boolean => {
        const actionMap: Record<string, keyof Permission> = {
            view: 'can_view',
            create: 'can_create',
            edit: 'can_edit',
            delete: 'can_delete',
            approve: 'can_approve',
            print: 'can_print',
            export: 'can_export',
        };
        return hasPermission(permissionCode, actionMap[action] || 'can_view');
    };

    // Initialize auth on store creation
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    return {
        user,
        token,
        menus,
        permissions,
        isLoading,
        isAuthenticated,
        currentBranch,
        currentRole,
        login,
        logout,
        fetchUser,
        hasPermission,
        can,
    };
});
