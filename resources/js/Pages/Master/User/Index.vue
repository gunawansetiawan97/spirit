<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import { DataTable } from '@/Components/Table';
import { BaseButton } from '@/Components/Form';
import { ConfirmDialog } from '@/Components/Modal';
import type { TableColumn, ActionConfig } from '@/types';
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

// Delete dialog
const showDeleteDialog = ref(false);
const isDeleting = ref(false);
const deletingItem = ref<any>(null);

const columns: TableColumn[] = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role.name', label: 'Role' },
    { key: 'branch.name', label: 'Cabang' },
    { key: 'is_active', label: 'Status', width: '100px', align: 'center' },
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

const openDeleteDialog = (row: any) => {
    deletingItem.value = row;
    showDeleteDialog.value = true;
};

const handleDelete = async () => {
    if (!deletingItem.value) return;

    isDeleting.value = true;
    try {
        await axios.delete(`/api/users/${deletingItem.value.uuid}`);
        showDeleteDialog.value = false;
        deletingItem.value = null;
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
        <!-- Table with search -->
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
                @search="handleSearch"
                @update:current-page="currentPage = $event; fetchData()"
                @update:per-page="perPage = $event; currentPage = 1; fetchData()"
                @row-click="navigateToView"
                @action-view="navigateToView"
                @action-edit="navigateToEdit"
                @action-delete="openDeleteDialog"
            >
                <template #toolbar>
                    <BaseButton
                        v-if="authStore.can('create', 'master.user')"
                        variant="primary"
                        @click="navigateToCreate"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah User
                    </BaseButton>
                </template>

                <template #cell-role\.name="{ row }">
                    {{ row.role?.name || '-' }}
                </template>
                <template #cell-branch\.name="{ row }">
                    {{ row.branch?.name || '-' }}
                </template>
                <template #cell-is_active="{ value }">
                    <span
                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                        :class="value ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-800'"
                    >
                        {{ value ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </template>
            </DataTable>
        </div>

        <!-- Delete Dialog -->
        <ConfirmDialog
            v-model="showDeleteDialog"
            title="Hapus User"
            :message="`Apakah Anda yakin ingin menghapus user '${deletingItem?.name}'?`"
            variant="danger"
            confirm-text="Hapus"
            :loading="isDeleting"
            @confirm="handleDelete"
        />
    </div>
</template>
