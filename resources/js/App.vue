<script setup lang="ts">
import { ref, onMounted, computed, markRaw, defineAsyncComponent } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { AppLayout } from '@/Components/Layout';
import Login from '@/Pages/Auth/Login.vue';
import Dashboard from '@/Pages/Dashboard.vue';

// Master pages - lazy loaded
const RoleIndex = defineAsyncComponent(() => import('@/Pages/Master/Role/Index.vue'));
const RoleForm = defineAsyncComponent(() => import('@/Pages/Master/Role/Form.vue'));
const RolePermission = defineAsyncComponent(() => import('@/Pages/Master/Role/Permission.vue'));
const UserIndex = defineAsyncComponent(() => import('@/Pages/Master/User/Index.vue'));
const UserForm = defineAsyncComponent(() => import('@/Pages/Master/User/Form.vue'));
const BranchIndex = defineAsyncComponent(() => import('@/Pages/Master/Branch/Index.vue'));
const BranchForm = defineAsyncComponent(() => import('@/Pages/Master/Branch/Form.vue'));

const authStore = useAuthStore();

const isInitialized = ref(false);
const currentRoute = ref('/dashboard');

// Route patterns with parameter matching
interface RouteConfig {
    pattern: RegExp;
    component: any;
    getProps?: (params: Record<string, string>) => Record<string, any>;
}

// UUID pattern: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
const UUID_PATTERN = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';

const routePatterns: RouteConfig[] = [
    { pattern: /^\/dashboard$/, component: Dashboard },
    { pattern: /^\/master\/role$/, component: RoleIndex },
    { pattern: /^\/master\/role\/create$/, component: RoleForm, getProps: () => ({ mode: 'create' }) },
    { pattern: new RegExp(`^/master/role/(${UUID_PATTERN})$`), component: RoleForm, getProps: (params) => ({ id: params.id, mode: 'view' }) },
    { pattern: new RegExp(`^/master/role/(${UUID_PATTERN})/edit$`), component: RoleForm, getProps: (params) => ({ id: params.id, mode: 'edit' }) },
    { pattern: new RegExp(`^/master/role/(${UUID_PATTERN})/permissions$`), component: RolePermission, getProps: (params) => ({ roleId: params.id }) },
    { pattern: /^\/master\/user$/, component: UserIndex },
    { pattern: /^\/master\/user\/create$/, component: UserForm, getProps: () => ({ mode: 'create' }) },
    { pattern: new RegExp(`^/master/user/(${UUID_PATTERN})$`), component: UserForm, getProps: (params) => ({ id: params.id, mode: 'view' }) },
    { pattern: new RegExp(`^/master/user/(${UUID_PATTERN})/edit$`), component: UserForm, getProps: (params) => ({ id: params.id, mode: 'edit' }) },
    { pattern: /^\/master\/branch$/, component: BranchIndex },
    { pattern: /^\/master\/branch\/create$/, component: BranchForm, getProps: () => ({ mode: 'create' }) },
    { pattern: new RegExp(`^/master/branch/(${UUID_PATTERN})$`), component: BranchForm, getProps: (params) => ({ id: params.id, mode: 'view' }) },
    { pattern: new RegExp(`^/master/branch/(${UUID_PATTERN})/edit$`), component: BranchForm, getProps: (params) => ({ id: params.id, mode: 'edit' }) },
];

const matchRoute = (path: string): { component: any; props: Record<string, any> } => {
    for (const route of routePatterns) {
        const match = path.match(route.pattern);
        if (match) {
            const params: Record<string, string> = {};
            if (match[1]) {
                params.id = match[1];
            }
            const props = route.getProps ? route.getProps(params) : {};
            return { component: markRaw(route.component), props };
        }
    }
    return { component: markRaw(Dashboard), props: {} };
};

const currentPage = computed(() => {
    const { component } = matchRoute(currentRoute.value);
    return component;
});

const currentPageProps = computed(() => {
    const { props } = matchRoute(currentRoute.value);
    return props;
});

// Check if route is a detail page (create/view/edit) vs index page
const isDetailRoute = (route: string): boolean => {
    return /\/(create|edit|permissions)$/.test(route) ||
        /\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/.test(route);
};

const handleNavigate = (route: string) => {
    if (isDetailRoute(route)) {
        window.open(route, '_blank');
    } else {
        currentRoute.value = route;
        window.history.pushState({}, '', route);
    }
};

const handleAction = (action: string) => {
    console.log('Action:', action);
};

const handleLoginSuccess = () => {
    currentRoute.value = '/dashboard';
};

const handleLogout = () => {
    currentRoute.value = '/dashboard';
};

onMounted(async () => {
    // Check if user is already logged in
    if (authStore.token) {
        await authStore.fetchUser();
    }
    isInitialized.value = true;

    // Handle browser back/forward
    window.addEventListener('popstate', () => {
        currentRoute.value = window.location.pathname || '/dashboard';
    });

    // Set initial route
    const path = window.location.pathname;
    if (path && path !== '/') {
        currentRoute.value = path;
    }
});
</script>

<template>
    <!-- Loading state -->
    <div v-if="!isInitialized" class="flex min-h-screen items-center justify-center bg-gray-100">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <p class="mt-4 text-gray-600">Loading...</p>
        </div>
    </div>

    <!-- Login page -->
    <Login v-else-if="!authStore.isAuthenticated" @success="handleLoginSuccess" />

    <!-- Main app -->
    <AppLayout
        v-else
        @navigate="handleNavigate"
        @action="handleAction"
        @logout="handleLogout"
    >
        <component
            :is="currentPage"
            v-bind="currentPageProps"
            :key="currentRoute"
            @navigate="handleNavigate"
        />
    </AppLayout>
</template>
