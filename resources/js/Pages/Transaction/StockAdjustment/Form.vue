<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, BaseFormRow, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseSelect, BaseGrid, BaseBrowse } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { BrowseConfig } from '@/types';
import axios from 'axios';

const props = withDefaults(defineProps<{
    id?: string | number;
    mode: 'create' | 'edit' | 'view';
}>(), { mode: 'create' });

const emit = defineEmits<{ (e: 'navigate', route: string): void }>();

const {
    loading, saving, formErrors, activeTab, auditData, recordId,
    formTabs, pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Penyesuaian Stok',
    apiEndpoint: '/api/stock-adjustments',
    basePath: '/transaction/stock-adjustment',
    auditType: 'stock_adjustment',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

// --- Header form ---
const form = ref({
    date: new Date().toISOString().substring(0, 10),
    warehouse_id: null as number | null,
    adjustment_type_id: null as number | null,
    description: '',
});

const warehouseRowData = ref<any>(null);
const adjustmentTypeRowData = ref<any>(null);

// --- Detail rows ---
// Each row stores UI state via _prefix fields (not sent to backend)
interface DetailRow {
    product_id: number | null;
    direction: 'in' | 'out';
    batch_number: string;
    unit_id: number | null;
    qty: number;
    unit_cost: number;
    description: string;
    // UI state
    _productData: any;
    _units: { value: number; label: string }[];
    _batchOptions: { value: string; label: string }[];
}

const detailRows = ref<DetailRow[]>([]);

const newDetailRow = (): DetailRow => ({
    product_id: null,
    direction: 'in',
    batch_number: '',
    unit_id: null,
    qty: 1,
    unit_cost: 0,
    description: '',
    _productData: null,
    _units: [],
    _batchOptions: [],
});

// --- Browse configs ---
const warehouseBrowseConfig: BrowseConfig = {
    endpoint: '/api/warehouses',
    title: 'BROWSE GUDANG',
    columns: [
        { key: 'code', label: 'Kode', width: '100px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari gudang...',
};

const adjustmentTypeBrowseConfig: BrowseConfig = {
    endpoint: '/api/adjustment-types',
    title: 'BROWSE TIPE PENYESUAIAN',
    columns: [
        { key: 'code', label: 'Kode', width: '100px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari tipe...',
    createRoute: '/master/adjustment-type/create',
};

const productBrowseConfig: BrowseConfig = {
    endpoint: '/api/products',
    title: 'BROWSE PRODUK',
    columns: [
        { key: 'code', label: 'Kode', width: '100px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari produk...',
    createRoute: '/master/product/create',
};

const directionOptions = [
    { value: 'in', label: 'Masuk' },
    { value: 'out', label: 'Keluar' },
];

// --- Event handlers ---

/** When product is selected in a detail row, populate unit options */
const handleProductSelect = (row: DetailRow, productData: any) => {
    row._productData = productData;
    row._units = (productData.units || []).map((u: any) => ({
        value: u.unit?.id ?? u.unit_id,
        label: `${u.unit?.code ?? ''} - ${u.unit?.name ?? ''}`,
    }));
    // Reset dependent fields
    row.unit_id = null;
    row.batch_number = '';
    row._batchOptions = [];
};

/** When product or direction changes for a "keluar" row, load available batches */
const loadBatches = async (row: DetailRow) => {
    if (row.direction !== 'out' || !row.product_id || !form.value.warehouse_id) return;

    try {
        const res = await axios.get('/api/stock-ledgers/batches', {
            params: { product_id: row.product_id, warehouse_id: form.value.warehouse_id },
        });
        row._batchOptions = (res.data.data || []).map((b: any) => ({
            value: b.batch_number ?? '',
            label: `${b.batch_number ?? '(tanpa batch)'} — saldo: ${Number(b.available_qty).toFixed(2)}`,
        }));
    } catch {
        row._batchOptions = [];
    }
};

const handleDirectionChange = (row: DetailRow) => {
    row.batch_number = '';
    loadBatches(row);
};

// --- Fetch data for edit/view ---
onMounted(() => {
    setupPage();
    fetchData((data) => {
        form.value = {
            date: data.date,
            warehouse_id: data.warehouse?.id || null,
            adjustment_type_id: data.adjustment_type?.id || null,
            description: data.description || '',
        };
        if (data.warehouse) warehouseRowData.value = data.warehouse;
        if (data.adjustment_type) adjustmentTypeRowData.value = data.adjustment_type;

        detailRows.value = (data.details || []).map((d: any) => {
            const units = (d.product?.units || []).map((u: any) => ({
                value: u.unit?.id ?? u.unit_id,
                label: `${u.unit?.code ?? ''} - ${u.unit?.name ?? ''}`,
            }));
            return {
                product_id: d.product?.id || null,
                direction: d.direction,
                batch_number: d.batch_number || '',
                unit_id: d.unit?.id || null,
                qty: Number(d.qty),
                unit_cost: Number(d.unit_cost),
                description: d.description || '',
                _productData: d.product || null,
                _units: units,
                _batchOptions: [],
            };
        });
    });
});

// --- Submit ---
const onSubmit = () => handleSubmit(() => ({
    date: form.value.date,
    warehouse_id: form.value.warehouse_id,
    adjustment_type_id: form.value.adjustment_type_id,
    description: form.value.description,
    details: detailRows.value
        .filter(r => r.product_id && r.unit_id && r.qty > 0)
        .map(r => ({
            product_id: r.product_id,
            direction: r.direction,
            batch_number: r.batch_number || null,
            unit_id: r.unit_id,
            qty: r.qty,
            unit_cost: r.unit_cost,
            description: r.description || null,
        })),
}));
</script>

<template>
    <FormPage
        :title="pageTitle"
        :mode="mode"
        :loading="loading"
        :saving="saving"
        :tabs="formTabs"
        v-model:active-tab="activeTab"
        @submit="onSubmit"
        @back="handleBack"
        @edit="handleEdit"
    >
        <template #default="{ readonly }">
            <div class="space-y-4">

                <!-- Header -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <BaseFormRow label="Tanggal" required :error="formErrors.date">
                        <BaseInput v-model="form.date" type="text" placeholder="YYYY-MM-DD" :disabled="readonly" />
                    </BaseFormRow>

                    <BaseFormRow label="Gudang" required :error="formErrors.warehouse_id">
                        <BaseBrowse
                            v-model="form.warehouse_id"
                            :config="warehouseBrowseConfig"
                            placeholder="Pilih gudang..."
                            :disabled="readonly"
                            :row-data="warehouseRowData"
                            @select="(row: any) => warehouseRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Tipe" required :error="formErrors.adjustment_type_id">
                        <BaseBrowse
                            v-model="form.adjustment_type_id"
                            :config="adjustmentTypeBrowseConfig"
                            placeholder="Pilih tipe penyesuaian..."
                            :disabled="readonly"
                            :row-data="adjustmentTypeRowData"
                            @select="(row: any) => adjustmentTypeRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Keterangan" :error="formErrors.description">
                        <BaseInput v-model="form.description" placeholder="Keterangan penyesuaian" :disabled="readonly" />
                    </BaseFormRow>
                </div>

                <!-- Detail Grid -->
                <BaseGrid
                    v-model="detailRows"
                    :columns="[
                        { key: 'product_id',   label: 'Produk',    width: '220px' },
                        { key: 'direction',    label: 'Jenis',     width: '110px' },
                        { key: 'batch_number', label: 'No. Batch', width: '180px' },
                        { key: 'unit_id',      label: 'Satuan',    width: '140px' },
                        { key: 'qty',          label: 'Qty',       width: '90px'  },
                        { key: 'unit_cost',    label: 'Harga/Unit', width: '110px' },
                        { key: 'description',  label: 'Keterangan' },
                    ]"
                    title="Daftar Barang"
                    :disabled="readonly"
                    :new-row="newDetailRow"
                    :error="formErrors.details"
                    :required-keys="['product_id', 'unit_id', 'qty']"
                >
                    <!-- Produk -->
                    <template #cell-product_id="{ row, disabled }">
                        <BaseBrowse
                            :modelValue="row.product_id"
                            :config="productBrowseConfig"
                            placeholder="Cari produk..."
                            :row-data="row._productData"
                            :disabled="disabled"
                            @update:modelValue="(val: any) => row.product_id = val"
                            @select="(productData: any) => { row.product_id = productData.id; handleProductSelect(row, productData); }"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </template>

                    <!-- Jenis (Masuk/Keluar) -->
                    <template #cell-direction="{ row, disabled }">
                        <BaseSelect
                            :modelValue="row.direction"
                            :options="directionOptions"
                            :disabled="disabled"
                            @update:modelValue="(val: any) => { row.direction = val; handleDirectionChange(row); }"
                        />
                    </template>

                    <!-- Batch Number: input jika Masuk, select jika Keluar -->
                    <template #cell-batch_number="{ row, disabled }">
                        <!-- Masuk: free text -->
                        <input
                            v-if="row.direction === 'in'"
                            v-model="row.batch_number"
                            type="text"
                            placeholder="No. Batch (opsional)"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                        />
                        <!-- Keluar: select dari stok tersedia -->
                        <select
                            v-else
                            v-model="row.batch_number"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                            @focus="loadBatches(row)"
                        >
                            <option value="">— pilih batch —</option>
                            <option v-for="opt in row._batchOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </template>

                    <!-- Satuan: dari unit produk yang dipilih -->
                    <template #cell-unit_id="{ row, disabled }">
                        <select
                            v-model="row.unit_id"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled || !row.product_id"
                        >
                            <option :value="null">— pilih satuan —</option>
                            <option v-for="u in row._units" :key="u.value" :value="u.value">
                                {{ u.label }}
                            </option>
                        </select>
                    </template>

                    <!-- Qty -->
                    <template #cell-qty="{ row, disabled }">
                        <input
                            v-model.number="row.qty"
                            type="number"
                            step="0.0001"
                            min="0.0001"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                        />
                    </template>

                    <!-- Harga/Unit -->
                    <template #cell-unit_cost="{ row, disabled }">
                        <input
                            v-model.number="row.unit_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                        />
                    </template>

                    <!-- Keterangan -->
                    <template #cell-description="{ row, disabled }">
                        <input
                            v-model="row.description"
                            type="text"
                            placeholder="Keterangan baris"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                        />
                    </template>
                </BaseGrid>

            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="stock_adjustment"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
