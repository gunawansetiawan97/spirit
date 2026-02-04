import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import { exportToExcel, exportToPdf } from '@/utils/exportUtils';
import type { TableColumn, ActionConfig, CreateButtonConfig, DeleteDialogConfig, FilterField, ExportConfig } from '@/types';

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

    // Filters
    filters?: FilterField[];

    // Export
    exportConfig?: ExportConfig;

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
        filters = [],
        searchPlaceholder = `Cari ${entityLabel}...`,
        permissionPrefix = `master.${entityName}`,
    } = config;

    // Export config with defaults
    const exportConfig: ExportConfig | undefined = config.exportConfig ?? (
        permissionPrefix ? {
            filename: entityName,
            title: title,
            permission: permissionPrefix,
        } : undefined
    );

    // Table state
    const data = ref<any[]>([]);
    const loading = ref(false);
    const currentPage = ref(1);
    const perPage = ref(10);
    const total = ref(0);
    const search = ref('');
    const isDeleting = ref(false);
    const isExporting = ref(false);

    // Filter state
    const filterValues = reactive<Record<string, any>>(
        filters.reduce((acc, field) => ({ ...acc, [field.key]: '' }), {})
    );

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

    // Build filter params (exclude empty values)
    const getFilterParams = (): Record<string, any> => {
        const params: Record<string, any> = {};
        for (const key in filterValues) {
            if (filterValues[key] !== '' && filterValues[key] != null) {
                params[key] = filterValues[key];
            }
        }
        return params;
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
                    ...getFilterParams(),
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

    // Filter handlers
    const handleFilter = () => {
        currentPage.value = 1;
        fetchData();
    };

    const handleFilterReset = () => {
        for (const key in filterValues) {
            filterValues[key] = '';
        }
        currentPage.value = 1;
        fetchData();
    };

    const handleFilterUpdate = (values: Record<string, any>) => {
        Object.assign(filterValues, values);
    };

    // Export handlers
    const handleExportExcel = async () => {
        isExporting.value = true;
        try {
            const response = await axios.get(apiEndpoint, {
                params: {
                    per_page: -1,
                    search: search.value,
                    ...getFilterParams(),
                },
            });
            const allData = response.data.data;
            const filename = exportConfig?.filename || entityName;
            exportToExcel(allData, columns, filename);
        } catch (error) {
            console.error(`Failed to export ${entityName}s to Excel:`, error);
        } finally {
            isExporting.value = false;
        }
    };

    const handleExportPdf = async () => {
        isExporting.value = true;
        try {
            const response = await axios.get(apiEndpoint, {
                params: {
                    per_page: -1,
                    search: search.value,
                    ...getFilterParams(),
                },
            });
            const allData = response.data.data;
            const filename = exportConfig?.filename || entityName;
            const pdfTitle = exportConfig?.title || title;
            exportToPdf(allData, columns, filename, pdfTitle);
        } catch (error) {
            console.error(`Failed to export ${entityName}s to PDF:`, error);
        } finally {
            isExporting.value = false;
        }
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
        isExporting,
        filterValues,

        // Config
        columns,
        actions,
        createButton,
        deleteDialog,
        searchPlaceholder,
        filters,
        exportConfig,

        // Methods
        fetchData,
        handleSearch,
        handlePageChange,
        handlePerPageChange,
        navigateToCreate,
        navigateToView,
        navigateToEdit,
        handleDelete,
        handleFilter,
        handleFilterReset,
        handleFilterUpdate,
        handleExportExcel,
        handleExportPdf,
    };
}
