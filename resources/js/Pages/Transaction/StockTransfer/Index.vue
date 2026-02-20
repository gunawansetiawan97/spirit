<script setup lang="ts">
import { ref } from 'vue';
import { DataTable } from '@/Components/Table';
import { useIndexPage } from '@/Composables';
import axios from 'axios';

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const actionLoading = ref<Record<string, boolean>>({});

const handleApprove = async (row: any) => {
    actionLoading.value[`approve-${row.uuid}`] = true;
    try {
        await axios.post(`/api/stock-transfers/${row.uuid}/approve`);
        fetchData();
    } catch (err: any) {
        alert(err.response?.data?.message || 'Gagal melakukan approve.');
    } finally {
        actionLoading.value[`approve-${row.uuid}`] = false;
    }
};

const handleDisapprove = async (row: any) => {
    if (!confirm('Yakin ingin disapprove dokumen ini?')) return;
    actionLoading.value[`disapprove-${row.uuid}`] = true;
    try {
        await axios.post(`/api/stock-transfers/${row.uuid}/disapprove`);
        fetchData();
    } catch (err: any) {
        alert(err.response?.data?.message || 'Gagal melakukan disapprove.');
    } finally {
        actionLoading.value[`disapprove-${row.uuid}`] = false;
    }
};

const {
    data, loading, currentPage, perPage, total, search,
    isDeleting, isExporting, filterValues,
    columns, actions, createButton, deleteDialog,
    searchPlaceholder, filters, exportConfig,
    fetchData, handleSearch, navigateToCreate, navigateToView,
    navigateToEdit, handleDelete, handleFilter, handleFilterReset,
    handleFilterUpdate, handleExportExcel, handleExportPdf,
} = useIndexPage({
    title: 'Transfer Stok',
    entityName: 'stock-transfer',
    entityLabel: 'Transfer Stok',
    apiEndpoint: '/api/stock-transfers',
    basePath: '/transaction/stock-transfer',
    permissionPrefix: 'transaction.stock_transfer',
    columns: [
        { key: 'code',                  label: 'No. Dokumen',  sortable: true,  width: '160px' },
        { key: 'date',                  label: 'Tanggal',      sortable: true,  width: '120px', type: 'date' },
        {
            key: 'from_warehouse',
            label: 'Dari Gudang',
            formatter: (v: any) => v ? `${v.code} - ${v.name}` : '-',
        },
        {
            key: 'to_warehouse',
            label: 'Ke Gudang',
            formatter: (v: any) => v ? `${v.code} - ${v.name}` : '-',
        },
        { key: 'description',           label: 'Keterangan' },
        { key: 'status',                label: 'Status',       width: '100px', align: 'center', type: 'status' },
    ],
    filters: [
        {
            key: 'status', label: 'Status', type: 'select',
            options: [
                { value: '',       label: 'Semua'    },
                { value: 'draft',  label: 'Draft'    },
                { value: 'posted', label: 'Approved' },
            ],
        },
    ],
    searchPlaceholder: 'Cari nomor / keterangan...',
    actions: [
        { type: 'view',   permission: 'transaction.stock_transfer', action: 'view' },
        {
            type: 'custom',
            label: 'Approve',
            color: 'bg-success-600 hover:bg-success-700',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
            show: (row: any) => row.status === 'draft',
            onClick: handleApprove,
        },
        {
            type: 'custom',
            label: 'Disapprove',
            color: 'bg-amber-500 hover:bg-amber-600',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
            show: (row: any) => row.status === 'posted',
            onClick: handleDisapprove,
        },
        { type: 'edit',   permission: 'transaction.stock_transfer', action: 'edit',   show: (row: any) => row.status === 'draft' },
        { type: 'delete', permission: 'transaction.stock_transfer', action: 'delete', show: (row: any) => row.status === 'draft' },
    ],
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
                @action-view="navigateToView"
                @action-edit="navigateToEdit"
                @create="navigateToCreate"
                @delete-confirm="handleDelete"
            />
        </div>
    </div>
</template>
