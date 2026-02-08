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
    title: 'Master COA Mapping',
    entityName: 'coa_mapping',
    entityLabel: 'COA Mapping',
    apiEndpoint: '/api/coa-mappings',
    basePath: '/master/coa-mapping',
    columns: [
        { key: 'module', label: 'Modul', width: '150px', sortable: true },
        { key: 'mapping_key', label: 'Mapping Key', width: '180px', sortable: true },
        { key: 'coa.code', label: 'Kode COA', width: '120px' },
        { key: 'coa.name', label: 'Nama COA' },
        { key: 'description', label: 'Keterangan' },
        { key: 'is_active', label: 'Status', width: '100px', align: 'center', type: 'status' },
    ],
    filters: [
        { key: 'is_active', label: 'Status', type: 'select', options: [
            { value: '', label: 'Semua' },
            { value: '1', label: 'Aktif' },
            { value: '0', label: 'Nonaktif' },
        ]},
        { key: 'module', label: 'Modul', type: 'select', options: [
            { value: '', label: 'Semua' },
            { value: 'Sales', label: 'Sales' },
            { value: 'Purchasing', label: 'Purchasing' },
            { value: 'Inventory', label: 'Inventory' },
            { value: 'Finance', label: 'Finance' },
            { value: 'General', label: 'General' },
        ]},
    ],
    permissionPrefix: 'master.accounting.coa_mapping',
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
