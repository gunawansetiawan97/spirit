import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';
import type { TablePagination, SortConfig } from '@/types';

interface UseTableOptions {
    endpoint: string;
    defaultPerPage?: number;
    defaultSort?: SortConfig;
    autoFetch?: boolean;
}

interface FetchParams {
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    search?: string;
    [key: string]: any;
}

export function useTable<T = any>(options: UseTableOptions) {
    const { endpoint, defaultPerPage = 10, defaultSort, autoFetch = true } = options;

    const data = ref<T[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const selectedRows = ref<T[]>([]);

    const pagination = reactive<TablePagination>({
        currentPage: 1,
        perPage: defaultPerPage,
        total: 0,
        lastPage: 1,
    });

    const sort = ref<SortConfig | null>(defaultSort || null);
    const filters = reactive<Record<string, any>>({});
    const search = ref('');

    // Build query params
    const queryParams = computed<FetchParams>(() => {
        const params: FetchParams = {
            page: pagination.currentPage,
            per_page: pagination.perPage,
        };

        if (sort.value) {
            params.sort_by = sort.value.key;
            params.sort_direction = sort.value.direction;
        }

        if (search.value) {
            params.search = search.value;
        }

        // Add filters
        Object.keys(filters).forEach((key) => {
            if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                params[key] = filters[key];
            }
        });

        return params;
    });

    // Fetch data from API
    const fetchData = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(endpoint, {
                params: queryParams.value,
            });

            // Handle Laravel pagination response
            if (response.data.data) {
                data.value = response.data.data;
                pagination.total = response.data.meta?.total || response.data.total || 0;
                pagination.lastPage = response.data.meta?.last_page || response.data.last_page || 1;
                pagination.currentPage = response.data.meta?.current_page || response.data.current_page || 1;
                pagination.perPage = response.data.meta?.per_page || response.data.per_page || defaultPerPage;
            } else {
                // Handle simple array response
                data.value = response.data;
                pagination.total = response.data.length;
                pagination.lastPage = 1;
            }
        } catch (err: any) {
            error.value = err.response?.data?.message || err.message || 'Gagal memuat data';
            data.value = [];
        } finally {
            loading.value = false;
        }
    };

    // Go to specific page
    const goToPage = (page: number) => {
        if (page >= 1 && page <= pagination.lastPage) {
            pagination.currentPage = page;
            fetchData();
        }
    };

    // Change per page
    const setPerPage = (perPage: number) => {
        pagination.perPage = perPage;
        pagination.currentPage = 1;
        fetchData();
    };

    // Set sort
    const setSort = (newSort: SortConfig) => {
        sort.value = newSort;
        pagination.currentPage = 1;
        fetchData();
    };

    // Set filter
    const setFilter = (key: string, value: any) => {
        filters[key] = value;
    };

    // Set multiple filters
    const setFilters = (newFilters: Record<string, any>) => {
        Object.keys(newFilters).forEach((key) => {
            filters[key] = newFilters[key];
        });
    };

    // Apply filters and fetch
    const applyFilters = () => {
        pagination.currentPage = 1;
        fetchData();
    };

    // Reset filters
    const resetFilters = () => {
        Object.keys(filters).forEach((key) => {
            filters[key] = '';
        });
        search.value = '';
        pagination.currentPage = 1;
        fetchData();
    };

    // Set search
    const setSearch = (value: string) => {
        search.value = value;
        pagination.currentPage = 1;
        fetchData();
    };

    // Refresh data
    const refresh = () => {
        fetchData();
    };

    // Select/deselect row
    const toggleRowSelection = (row: T) => {
        const index = selectedRows.value.indexOf(row);
        if (index === -1) {
            selectedRows.value.push(row);
        } else {
            selectedRows.value.splice(index, 1);
        }
    };

    // Select all rows
    const selectAll = () => {
        selectedRows.value = [...data.value];
    };

    // Deselect all rows
    const deselectAll = () => {
        selectedRows.value = [];
    };

    // Check if row is selected
    const isSelected = (row: T) => {
        return selectedRows.value.includes(row);
    };

    // Auto fetch on mount
    if (autoFetch) {
        fetchData();
    }

    return {
        // State
        data,
        loading,
        error,
        pagination,
        sort,
        filters,
        search,
        selectedRows,
        queryParams,

        // Methods
        fetchData,
        refresh,
        goToPage,
        setPerPage,
        setSort,
        setFilter,
        setFilters,
        applyFilters,
        resetFilters,
        setSearch,
        toggleRowSelection,
        selectAll,
        deselectAll,
        isSelected,
    };
}
