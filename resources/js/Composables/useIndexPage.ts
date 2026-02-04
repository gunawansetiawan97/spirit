import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import type { TableColumn, ActionConfig, CreateButtonConfig, DeleteDialogConfig } from '@/types';

export interface IndexPageConfig {
    // Entity info
    title: string;
    entityName: string;
    entityLabel?: string;

    // API
    apiEndpoint: string;

    // Navigation
    basePath: string;

    // Table columns
    columns: TableColumn[];

    // Actions (optional - defaults based on entityName)
    actions?: ActionConfig[];

    // Search
    searchPlaceholder?: string;

    // Permissions (optional - defaults based on entityName)
    permissionPrefix?: string;
}

type NavigateEmit = (event: 'navigate', route: string) => void;

export function useIndexPage(config: IndexPageConfig, emit: NavigateEmit) {
    const uiStore = useUiStore();
    const authStore = useAuthStore();

    const {
        title,
        entityName,
        entityLabel = entityName,
        apiEndpoint,
        basePath,
        columns,
        searchPlaceholder = `Cari ${entityLabel}...`,
        permissionPrefix = `master.${entityName}`,
    } = config;

    // Table state
    const data = ref<any[]>([]);
    const loading = ref(false);
    const currentPage = ref(1);
    const perPage = ref(10);
    const total = ref(0);
    const search = ref('');
    const isDeleting = ref(false);

    // Default actions based on entityName
    const defaultActions = computed<ActionConfig[]>(() => [
        { type: 'view', permission: permissionPrefix, action: 'view' },
        { type: 'edit', permission: permissionPrefix, action: 'edit' },
        { type: 'delete', permission: permissionPrefix, action: 'delete' },
    ]);

    const actions = computed(() => config.actions || defaultActions.value);

    // Create button config
    const createButton: CreateButtonConfig = {
        label: `Tambah ${entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)}`,
        permission: permissionPrefix,
        action: 'create',
    };

    // Delete dialog config
    const deleteDialog: DeleteDialogConfig = {
        title: `Hapus ${entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)}`,
        message: (row) => `Apakah Anda yakin ingin menghapus ${entityLabel} '${row.name}'?`,
    };

    // Fetch data
    const fetchData = async () => {
        loading.value = true;
        try {
            const response = await axios.get(apiEndpoint, {
                params: {
                    page: currentPage.value,
                    per_page: perPage.value,
                    search: search.value,
                },
            });
            data.value = response.data.data;
            total.value = response.data.meta.total;
        } catch (error) {
            console.error(`Failed to fetch ${entityName}s:`, error);
        } finally {
            loading.value = false;
        }
    };

    // Event handlers
    const handleSearch = (value: string) => {
        search.value = value;
        currentPage.value = 1;
        fetchData();
    };

    const handlePageChange = (page: number) => {
        currentPage.value = page;
        fetchData();
    };

    const handlePerPageChange = (value: number) => {
        perPage.value = value;
        currentPage.value = 1;
        fetchData();
    };

    // Navigation
    const navigateToCreate = () => {
        emit('navigate', `${basePath}/create`);
    };

    const navigateToView = (row: any) => {
        emit('navigate', `${basePath}/${row.uuid}`);
    };

    const navigateToEdit = (row: any) => {
        emit('navigate', `${basePath}/${row.uuid}/edit`);
    };

    // Delete
    const handleDelete = async (row: any) => {
        isDeleting.value = true;
        try {
            await axios.delete(`${apiEndpoint}/${row.uuid}`);
            fetchData();
        } catch (error: any) {
            alert(error.response?.data?.message || `Gagal menghapus ${entityLabel}`);
        } finally {
            isDeleting.value = false;
        }
    };

    // Initialize
    onMounted(() => {
        uiStore.setPageTitle(title);
        uiStore.setPageActions([]);
        fetchData();
    });

    return {
        // State
        data,
        loading,
        currentPage,
        perPage,
        total,
        search,
        isDeleting,

        // Config
        columns,
        actions,
        createButton,
        deleteDialog,
        searchPlaceholder,

        // Methods
        fetchData,
        handleSearch,
        handlePageChange,
        handlePerPageChange,
        navigateToCreate,
        navigateToView,
        navigateToEdit,
        handleDelete,
    };
}
