import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Branch {
    id: number;
    code: string;
    name: string;
}

interface PageAction {
    key: string;
    label: string;
    icon?: string;
    variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning';
    permission?: string;
    action?: string;
}

export const useUiStore = defineStore('ui', () => {
    const sidebarCollapsed = ref(false);
    const sidebarMobileOpen = ref(false);
    const currentRoute = ref(window.location.pathname || '/dashboard');
    const currentBranch = ref<Branch | null>(null);
    const availableBranches = ref<Branch[]>([]);
    const pageTitle = ref('');
    const pageActions = ref<PageAction[]>([]);
    const isLoadingBranches = ref(false);
    const formIsOpen = ref(false);

    const toggleSidebar = () => {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    };

    const toggleMobileSidebar = () => {
        sidebarMobileOpen.value = !sidebarMobileOpen.value;
    };

    const closeMobileSidebar = () => {
        sidebarMobileOpen.value = false;
    };

    const fetchBranches = async () => {
        isLoadingBranches.value = true;
        try {
            const response = await axios.get('/api/branches/all');
            availableBranches.value = response.data.data;
        } catch (error) {
            console.error('Failed to fetch branches:', error);
        } finally {
            isLoadingBranches.value = false;
        }
    };

    const switchBranch = async (branchId: number) => {
        try {
            const response = await axios.post('/api/switch-branch', { branch_id: branchId });
            currentBranch.value = response.data.data.branch;
            localStorage.setItem('currentBranchId', String(branchId));
            axios.defaults.headers.common['X-Branch-ID'] = String(branchId);
            return true;
        } catch (error) {
            console.error('Failed to switch branch:', error);
            return false;
        }
    };

    const setCurrentBranch = (branch: Branch | null) => {
        currentBranch.value = branch;
        if (branch) {
            localStorage.setItem('currentBranchId', String(branch.id));
            axios.defaults.headers.common['X-Branch-ID'] = String(branch.id);
        } else {
            delete axios.defaults.headers.common['X-Branch-ID'];
        }
    };

    const setPageTitle = (title: string) => {
        pageTitle.value = title;
        document.title = title ? `${title} - Spirit ERP` : 'Spirit ERP';
    };

    const setCurrentRoute = (route: string) => {
        currentRoute.value = route;
    };

    const setPageActions = (actions: PageAction[]) => {
        pageActions.value = actions;
    };

    const clearPageActions = () => {
        pageActions.value = [];
    };

    const setFormOpen = (open: boolean) => {
        formIsOpen.value = open;
    };

    // Initialize from localStorage
    const savedBranchId = localStorage.getItem('currentBranchId');
    if (savedBranchId) {
        // Will be set after branches are loaded
    }

    return {
        sidebarCollapsed,
        sidebarMobileOpen,
        currentRoute,
        currentBranch,
        availableBranches,
        pageTitle,
        pageActions,
        isLoadingBranches,
        formIsOpen,
        toggleSidebar,
        toggleMobileSidebar,
        closeMobileSidebar,
        fetchBranches,
        switchBranch,
        setCurrentBranch,
        setCurrentRoute,
        setPageTitle,
        setFormOpen,
        setPageActions,
        clearPageActions,
    };
});
