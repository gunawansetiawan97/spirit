<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import { DataTable } from '@/Components/Table';
import type { TableColumn, ActionConfig, CreateButtonConfig, DeleteDialogConfig } from '@/types';
import axios from 'axios';

const uiStore = useUiStore();

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

// Table state
const data = ref<any[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const perPage = ref(10);
const total = ref(0);
const search = ref('');
const isDeleting = ref(false);

const columns: TableColumn[] = [
    { key: 'code', label: 'Kode', width: '120px', sortable: true },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'description', label: 'Deskripsi' },
    { key: 'users_count', label: 'Jumlah User', width: '120px', align: 'center' },
    { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
];

const actions: ActionConfig[] = [
    { type: 'view', permission: 'master.role', action: 'view' },
    { type: 'permissions', permission: 'master.permission', action: 'edit' },
    { type: 'edit', permission: 'master.role', action: 'edit' },
    { type: 'delete', permission: 'master.role', action: 'delete' },
];

const createButton: CreateButtonConfig = {
    label: 'Tambah Role',
    permission: 'master.role',
    action: 'create',
};

const deleteDialog: DeleteDialogConfig = {
    title: 'Hapus Role',
    message: (row) => `Apakah Anda yakin ingin menghapus role '${row.name}'?`,
};

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/roles', {
            params: {
                page: currentPage.value,
                per_page: perPage.value,
                search: search.value,
            },
        });
        data.value = response.data.data;
        total.value = response.data.meta.total;
    } catch (error) {
        console.error('Failed to fetch roles:', error);
    } finally {
        loading.value = false;
    }
};

const handleSearch = (value: string) => {
    search.value = value;
    currentPage.value = 1;
    fetchData();
};

const navigateToCreate = () => {
    emit('navigate', '/master/role/create');
};

const navigateToView = (row: any) => {
    emit('navigate', `/master/role/${row.uuid}`);
};

const navigateToEdit = (row: any) => {
    emit('navigate', `/master/role/${row.uuid}/edit`);
};

const navigateToPermissions = (row: any) => {
    emit('navigate', `/master/role/${row.uuid}/permissions`);
};

const handleDelete = async (row: any) => {
    isDeleting.value = true;
    try {
        await axios.delete(`/api/roles/${row.uuid}`);
        fetchData();
    } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menghapus role');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(() => {
    uiStore.setPageTitle('Master Role');
    uiStore.setPageActions([]);
    fetchData();
});
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-lg bg-white p-4 shadow">
            <DataTable
                :columns="columns"
                :data="data"
                :loading="loading"
                :searchable="true"
                :search-value="search"
                search-placeholder="Cari role..."
                :paginated="true"
                :current-page="currentPage"
                :per-page="perPage"
                :total="total"
                :actions="actions"
                :create-button="createButton"
                :delete-dialog="deleteDialog"
                :delete-loading="isDeleting"
                @search="handleSearch"
                @update:current-page="currentPage = $event; fetchData()"
                @update:per-page="perPage = $event; currentPage = 1; fetchData()"
                @row-click="navigateToView"
                @action-view="navigateToView"
                @action-edit="navigateToEdit"
                @action-permissions="navigateToPermissions"
                @create="navigateToCreate"
                @delete-confirm="handleDelete"
            />
        </div>
    </div>
</template>
