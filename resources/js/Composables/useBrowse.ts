import { ref } from 'vue';
import axios from 'axios';
import type { BrowseConfig } from '@/types';

export function useBrowse(config: BrowseConfig) {
    const data = ref<any[]>([]);
    const loading = ref(false);
    const search = ref('');
    const currentPage = ref(1);
    const perPage = ref(config.perPage ?? 10);
    const total = ref(0);
    const lastPage = ref(1);

    const resolvedRow = ref<any>(null);
    const resolving = ref(false);

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;

    const fetchData = async () => {
        loading.value = true;
        try {
            const response = await axios.get(config.endpoint, {
                params: {
                    page: currentPage.value,
                    per_page: perPage.value,
                    search: search.value || undefined,
                },
            });
            data.value = response.data.data;
            total.value = response.data.meta.total;
            lastPage.value = response.data.meta.last_page;
            currentPage.value = response.data.meta.current_page;
        } catch (error) {
            console.error('Browse fetch failed:', error);
            data.value = [];
        } finally {
            loading.value = false;
        }
    };

    const handleSearch = (value: string) => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            search.value = value;
            currentPage.value = 1;
            fetchData();
        }, 300);
    };

    const handlePageChange = (page: number) => {
        currentPage.value = page;
        fetchData();
    };

    const resolveValue = async (id: string | number) => {
        resolving.value = true;
        try {
            const endpoint = config.showEndpoint ?? `${config.endpoint}/${id}`;
            const response = await axios.get(endpoint);
            resolvedRow.value = response.data.data;
        } catch (error) {
            console.error('Browse resolve failed:', error);
            resolvedRow.value = null;
        } finally {
            resolving.value = false;
        }
    };

    const resetState = () => {
        search.value = '';
        currentPage.value = 1;
        data.value = [];
        total.value = 0;
    };

    return {
        data,
        loading,
        search,
        currentPage,
        perPage,
        total,
        lastPage,
        resolvedRow,
        resolving,
        fetchData,
        handleSearch,
        handlePageChange,
        resolveValue,
        resetState,
    };
}
