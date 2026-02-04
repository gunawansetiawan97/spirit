<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import { DataTable } from '@/Components/Table';
import type { TableColumn, ActionConfig, CreateButtonConfig, DeleteDialogConfig } from '@/types';
import axios from 'axios';

const uiStore = useUiStore();
const authStore = useAuthStore();

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
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role.name', label: 'Role' },
    { key: 'branch.name', label: 'Cabang' },
    { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
];

const actions = computed<ActionConfig[]>(() => [
    { type: 'view', permission: 'master.user', action: 'view' },
    { type: 'edit', permission: 'master.user', action: 'edit' },
    {
        type: 'delete',
        permission: 'master.user',
        action: 'delete',
        show: (row: any) => row.uuid !== authStore.user?.uuid,
    },
]);

const createButton: CreateButtonConfig = {
    label: 'Tambah User',
    permission: 'master.user',
    action: 'create',
};

const deleteDialog: DeleteDialogConfig = {
    title: 'Hapus User',
    message: (row) => `Apakah Anda yakin ingin menghapus user '${row.name}'?`,
};

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/users', {
            params: {
                page: currentPage.value,
                per_page: perPage.value,
                search: search.value,
            },
        });
        data.value = response.data.data;
        total.value = response.data.meta.total;
    } catch (error) {
        console.error('Failed to fetch users:', error);
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
    emit('navigate', '/master/user/create');
};

const navigateToView = (row: any) => {
    emit('navigate', `/master/user/${row.uuid}`);
};

const navigateToEdit = (row: any) => {
    emit('navigate', `/master/user/${row.uuid}/edit`);
};

const handleDelete = async (row: any) => {
    isDeleting.value = true;
    try {
        await axios.delete(`/api/users/${row.uuid}`);
        fetchData();
    } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menghapus user');
    } finally {
        isDeleting.value = false;
    }
};

onMounted(() => {
    uiStore.setPageTitle('Master User');
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
                search-placeholder="Cari user..."
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
                @create="navigateToCreate"
                @delete-confirm="handleDelete"
            />
        </div>
    </div>
</template>
