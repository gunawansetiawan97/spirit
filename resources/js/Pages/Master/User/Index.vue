<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { DataTable } from '@/Components/Table';
import { useIndexPage } from '@/Composables';
import type { ActionConfig } from '@/types';

const authStore = useAuthStore();

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const {
    data,
    loading,
    currentPage,
    perPage,
    total,
    search,
    isDeleting,
    columns,
    createButton,
    deleteDialog,
    searchPlaceholder,
    fetchData,
    handleSearch,
    navigateToCreate,
    navigateToView,
    navigateToEdit,
    handleDelete,
} = useIndexPage({
    title: 'Master User',
    entityName: 'user',
    apiEndpoint: '/api/users',
    basePath: '/master/user',
    columns: [
        { key: 'name', label: 'Nama', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'role.name', label: 'Role' },
        { key: 'branch.name', label: 'Cabang' },
        { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
    ],
}, emit);

// Custom actions: user cannot delete themselves
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
                :search-placeholder="searchPlaceholder"
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
