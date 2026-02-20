<script setup lang="ts">
import { ref, onMounted, computed, markRaw, defineAsyncComponent } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { AppLayout } from '@/Components/Layout';
import Login from '@/Pages/Auth/Login.vue';
import Dashboard from '@/Pages/Dashboard.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();

const isInitialized = ref(false);
const currentRoute = ref('/dashboard');

// Route patterns with parameter matching
interface RouteConfig {
    pattern: RegExp;
    component: any;
    getProps?: (params: Record<string, string>) => Record<string, any>;
}

const UUID = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';

// Generate standard CRUD routes (index, create, view, edit) for a module
function crudRoutes(path: string, index: () => Promise<any>, form: () => Promise<any>): RouteConfig[] {
    const I = defineAsyncComponent(index);
    const F = defineAsyncComponent(form);
    return [
        { pattern: new RegExp(`^${path}$`), component: I },
        { pattern: new RegExp(`^${path}/create$`), component: F, getProps: () => ({ mode: 'create' }) },
        { pattern: new RegExp(`^${path}/(${UUID})$`), component: F, getProps: (p) => ({ id: p.id, mode: 'view' }) },
        { pattern: new RegExp(`^${path}/(${UUID})/edit$`), component: F, getProps: (p) => ({ id: p.id, mode: 'edit' }) },
    ];
}

const RolePermission = defineAsyncComponent(() => import('@/Pages/Master/Role/Permission.vue'));

const routePatterns: RouteConfig[] = [
    { pattern: /^\/dashboard$/, component: Dashboard },
    ...crudRoutes('/master/role', () => import('@/Pages/Master/Role/Index.vue'), () => import('@/Pages/Master/Role/Form.vue')),
    { pattern: new RegExp(`^/master/role/(${UUID})/permissions$`), component: RolePermission, getProps: (p) => ({ roleId: p.id }) },
    ...crudRoutes('/master/user', () => import('@/Pages/Master/User/Index.vue'), () => import('@/Pages/Master/User/Form.vue')),
    ...crudRoutes('/master/branch', () => import('@/Pages/Master/Branch/Index.vue'), () => import('@/Pages/Master/Branch/Form.vue')),
    ...crudRoutes('/master/account-type', () => import('@/Pages/Master/AccountType/Index.vue'), () => import('@/Pages/Master/AccountType/Form.vue')),
    ...crudRoutes('/master/account-group', () => import('@/Pages/Master/AccountGroup/Index.vue'), () => import('@/Pages/Master/AccountGroup/Form.vue')),
    ...crudRoutes('/master/coa', () => import('@/Pages/Master/Coa/Index.vue'), () => import('@/Pages/Master/Coa/Form.vue')),
    ...crudRoutes('/master/coa-mapping', () => import('@/Pages/Master/CoaMapping/Index.vue'), () => import('@/Pages/Master/CoaMapping/Form.vue')),
    ...crudRoutes('/master/unit', () => import('@/Pages/Master/Unit/Index.vue'), () => import('@/Pages/Master/Unit/Form.vue')),
    ...crudRoutes('/master/product-category', () => import('@/Pages/Master/ProductCategory/Index.vue'), () => import('@/Pages/Master/ProductCategory/Form.vue')),
    ...crudRoutes('/master/product-brand', () => import('@/Pages/Master/ProductBrand/Index.vue'), () => import('@/Pages/Master/ProductBrand/Form.vue')),
    ...crudRoutes('/master/product', () => import('@/Pages/Master/Product/Index.vue'), () => import('@/Pages/Master/Product/Form.vue')),
    ...crudRoutes('/master/warehouse', () => import('@/Pages/Master/Warehouse/Index.vue'), () => import('@/Pages/Master/Warehouse/Form.vue')),
    ...crudRoutes('/master/adjustment-type', () => import('@/Pages/Master/AdjustmentType/Index.vue'), () => import('@/Pages/Master/AdjustmentType/Form.vue')),
    ...crudRoutes('/transaction/stock-adjustment', () => import('@/Pages/Transaction/StockAdjustment/Index.vue'), () => import('@/Pages/Transaction/StockAdjustment/Form.vue')),
    ...crudRoutes('/transaction/stock-transfer', () => import('@/Pages/Transaction/StockTransfer/Index.vue'), () => import('@/Pages/Transaction/StockTransfer/Form.vue')),
    ...crudRoutes('/purchasing/supplier', () => import('@/Pages/Purchasing/Supplier/Index.vue'), () => import('@/Pages/Purchasing/Supplier/Form.vue')),
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
        currentRoute.value = route;
        uiStore.setCurrentRoute(route);
        window.history.pushState({}, '', route);
        /*
    if (isDetailRoute(route)) {
        window.open(route, '_blank');
    } else {
        currentRoute.value = route;
        window.history.pushState({}, '', route);
    }
    */
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
        const path = window.location.pathname || '/dashboard';
        currentRoute.value = path;
        uiStore.setCurrentRoute(path);
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
