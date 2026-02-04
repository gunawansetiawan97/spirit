<script setup lang="ts">
import { DataTable } from '@/Components/Table';
import { useIndexPage } from '@/Composables';
import type { ActionConfig } from '@/types';

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
    isExporting,
    filterValues,
    columns,
    createButton,
    deleteDialog,
    searchPlaceholder,
    filters,
    exportConfig,
    fetchData,
    handleSearch,
    navigateToCreate,
    navigateToView,
    navigateToEdit,
    handleDelete,
    handleFilter,
    handleFilterReset,
    handleFilterUpdate,
    handleExportExcel,
    handleExportPdf,
} = useIndexPage({
    title: 'Master Role',
    entityName: 'role',
    apiEndpoint: '/api/roles',
    basePath: '/master/role',
    columns: [
        { key: 'code', label: 'Kode', width: '120px', sortable: true },
        { key: 'name', label: 'Nama', sortable: true },
        { key: 'description', label: 'Deskripsi' },
        { key: 'users_count', label: 'Jumlah User', width: '120px', align: 'center' },
        { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
    ],
    filters: [
        { key: 'is_active', label: 'Status', type: 'select', options: [
            { value: '', label: 'Semua' },
            { value: '1', label: 'Aktif' },
            { value: '0', label: 'Nonaktif' },
        ]},
    ],
}, emit);

// Custom actions with permissions button
const actions: ActionConfig[] = [
    { type: 'view', permission: 'master.role', action: 'view' },
    { type: 'permissions', permission: 'master.permission', action: 'edit' },
    { type: 'edit', permission: 'master.role', action: 'edit' },
    { type: 'delete', permission: 'master.role', action: 'delete' },
];

const navigateToPermissions = (row: any) => {
    emit('navigate', `/master/role/${row.uuid}/permissions`);
};
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
                :filters="filters"
                :filter-values="filterValues"
                :export-config="exportConfig"
                :export-loading="isExporting"
                @search="handleSearch"
                @update:current-page="currentPage = $event; fetchData()"
                @update:per-page="perPage = $event; currentPage = 1; fetchData()"
                @update:filter-values="handleFilterUpdate"
                @filter="handleFilter"
                @filter-reset="handleFilterReset"
                @export-excel="handleExportExcel"
                @export-pdf="handleExportPdf"
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
