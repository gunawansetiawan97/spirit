<script setup lang="ts">
import { DataTable } from '@/Components/Table';
import { useIndexPage } from '@/Composables';

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const {
    data, loading, currentPage, perPage, total, search,
    isDeleting, isExporting, filterValues, columns, actions,
    createButton, deleteDialog, searchPlaceholder, filters, exportConfig,
    fetchData, handleSearch, navigateToCreate, navigateToView, navigateToEdit,
    handleDelete, handleFilter, handleFilterReset, handleFilterUpdate,
    handleExportExcel, handleExportPdf,
} = useIndexPage({
    title: 'Master Group Akun',
    entityName: 'account_group',
    entityLabel: 'Group Akun',
    apiEndpoint: '/api/account-groups',
    basePath: '/master/account-group',
    columns: [
        { key: 'account_type.code', label: 'Tipe Akun', width: '150px', sortable: false },
        { key: 'group_name', label: 'Nama Group', sortable: true },
        { key: 'normal_balance', label: 'Saldo Normal', width: '120px' },
        { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
    ],
    filters: [
        { key: 'is_active', label: 'Status', type: 'select', options: [
            { value: '', label: 'Semua' },
            { value: '1', label: 'Aktif' },
            { value: '0', label: 'Nonaktif' },
        ]},
    ],
    permissionPrefix: 'master.accounting.account_group',
}, emit);
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
                @create="navigateToCreate"
                @delete-confirm="handleDelete"
            />
        </div>
    </div>
</template>
