<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { FormPage, BaseFormRow, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseGrid, BaseBrowse, BaseDatePicker } from '@/Components/Form';
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
    title: 'Transfer Stok',
    apiEndpoint: '/api/stock-transfers',
    basePath: '/transaction/stock-transfer',
    auditType: 'stock_transfer',
    auditRelations: ['createdBy', 'updatedBy', 'approvedBy', 'deletedBy'],
}, props, emit);

// --- Header form ---
const form = ref({
    code: '',
    status: 'draft' as 'draft' | 'posted',
    date: new Date().toISOString().substring(0, 10),
    from_warehouse_id: null as number | null,
    to_warehouse_id: null as number | null,
    description: '',
});

const fromWarehouseRowData = ref<any>(null);
const toWarehouseRowData   = ref<any>(null);

const autoCode = ref(false);

const generateAutoCode = () => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const ts = `${now.getFullYear()}/${pad(now.getMonth() + 1)}/${pad(now.getDate())}/${pad(now.getHours())}/${pad(now.getMinutes())}/${pad(now.getSeconds())}`;
    return `AUTOCODE/${ts}/${Math.floor(Math.random() * 100000)}`;
};

const recordUuid = ref('');
const approving  = ref(false);
const approveError = ref('');

// --- Detail rows ---
interface DetailRow {
    product_id: number | null;
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

const rows = ref<DetailRow[]>([]);

const newRow = (): DetailRow => ({
    product_id: null, batch_number: '', unit_id: null,
    qty: 1, unit_cost: 0, description: '',
    _productData: null, _units: [], _batchOptions: [],
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

// --- Event handlers ---
const handleProductSelect = (row: DetailRow, productData: any) => {
    row._productData = productData;
    row._units = (productData.units || []).map((u: any) => ({
        value: u.unit?.id ?? u.unit_id,
        label: `${u.unit?.code ?? ''} - ${u.unit?.name ?? ''}`,
    }));
    row.unit_id = null;
    row.batch_number = '';
    row._batchOptions = [];
};

const loadBatches = async (row: DetailRow) => {
    if (!row.product_id || !form.value.from_warehouse_id) return;
    try {
        const res = await axios.get('/api/stock-ledgers/batches', {
            params: { product_id: row.product_id, warehouse_id: form.value.from_warehouse_id },
        });
        row._batchOptions = (res.data.data || []).map((b: any) => ({
            value: b.batch_number ?? '',
            label: `${b.batch_number ?? '(tanpa batch)'} — saldo: ${Number(b.available_qty).toFixed(2)}`,
        }));
    } catch {
        row._batchOptions = [];
    }
};

const mapDetailRow = (d: any): DetailRow => {
    const units = (d.product?.units || []).map((u: any) => ({
        value: u.unit?.id ?? u.unit_id,
        label: `${u.unit?.code ?? ''} - ${u.unit?.name ?? ''}`,
    }));
    return {
        product_id: d.product?.id || null,
        batch_number: d.batch_number || '',
        unit_id: d.unit?.id || null,
        qty: Number(d.qty),
        unit_cost: Number(d.unit_cost),
        description: d.description || '',
        _productData: d.product || null,
        _units: units,
        _batchOptions: [],
    };
};

// --- Fetch data for edit/view ---
onMounted(() => {
    setupPage();
    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }

    fetchData((data) => {
        form.value = {
            code: data.code || '',
            status: data.status || 'draft',
            date: data.date ? String(data.date).substring(0, 10) : new Date().toISOString().substring(0, 10),
            from_warehouse_id: data.from_warehouse?.id || null,
            to_warehouse_id:   data.to_warehouse?.id   || null,
            description: data.description || '',
        };
        recordUuid.value = data.uuid || '';
        if (data.from_warehouse) fromWarehouseRowData.value = data.from_warehouse;
        if (data.to_warehouse)   toWarehouseRowData.value   = data.to_warehouse;

        rows.value = (data.details || []).map(mapDetailRow);

        // Pre-load batch options for all rows that already have a product selected
        rows.value.forEach(row => loadBatches(row));
    });
});

// --- Approve / Disapprove ---
const handleApprove = async () => {
    if (!recordUuid.value) return;
    approveError.value = '';
    approving.value = true;
    try {
        await axios.post(`/api/stock-transfers/${recordUuid.value}/approve`);
        fetchData((data) => {
            form.value.code   = data.code   || '';
            form.value.status = data.status || 'posted';
        });
    } catch (err: any) {
        approveError.value = err.response?.data?.message || 'Gagal melakukan approve.';
    } finally {
        approving.value = false;
    }
};

const handleDisapprove = async () => {
    if (!recordUuid.value) return;
    approveError.value = '';
    approving.value = true;
    try {
        await axios.post(`/api/stock-transfers/${recordUuid.value}/disapprove`);
        fetchData((data) => {
            form.value.code   = data.code   || '';
            form.value.status = data.status || 'draft';
        });
    } catch (err: any) {
        approveError.value = err.response?.data?.message || 'Gagal melakukan disapprove.';
    } finally {
        approving.value = false;
    }
};

// --- Print ---
const showPrint = ref(false);

const formatPrintDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const [y, m, d] = dateStr.split('-');
    return `${parseInt(d)} ${months[parseInt(m) - 1]} ${y}`;
};

const getUnitLabel = (row: DetailRow) => row._units.find(u => u.value === row.unit_id)?.label ?? '-';
const getProductLabel = (row: DetailRow) =>
    row._productData ? `${row._productData.code} - ${row._productData.name}` : '-';
const fmtQty = (n: number) => Number(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
const fmtCurrency = (n: number) => Number(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const handlePrint = () => {
    showPrint.value = true;
    nextTick(() => {
        window.print();
        window.addEventListener('afterprint', () => { showPrint.value = false; }, { once: true });
    });
};

// --- Submit ---
const onSubmit = () => handleSubmit(() => ({
    code: form.value.code,
    date: form.value.date,
    from_warehouse_id: form.value.from_warehouse_id,
    to_warehouse_id:   form.value.to_warehouse_id,
    description: form.value.description,
    details: rows.value
        .filter(r => r.product_id && r.unit_id && r.qty > 0)
        .map(r => ({
            product_id:   r.product_id,
            batch_number: r.batch_number || null,
            unit_id:      r.unit_id,
            qty:          r.qty,
            unit_cost:    r.unit_cost,
            description:  r.description || null,
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
        :allow-edit="form.status !== 'posted'"
        v-model:active-tab="activeTab"
        @submit="onSubmit"
        @back="handleBack"
        @edit="handleEdit"
    >
        <!-- Approve / Disapprove / Print buttons (view mode only) -->
        <template v-if="mode === 'view'" #header-actions>
            <div class="flex items-center gap-2">
                <button
                    v-if="form.status === 'draft'"
                    type="button"
                    :disabled="approving"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-success-700 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="handleApprove"
                >
                    <svg v-if="!approving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    {{ approving ? 'Memproses...' : 'Approve' }}
                </button>
                <button
                    v-if="form.status === 'posted'"
                    type="button"
                    :disabled="approving"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="handleDisapprove"
                >
                    <svg v-if="!approving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    {{ approving ? 'Memproses...' : 'Disapprove' }}
                </button>
                <button
                    v-if="form.status === 'posted'"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-gray-700"
                    @click="handlePrint"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
            </div>
        </template>

        <template #default="{ readonly }">
            <div class="space-y-4">

                <!-- Error alert -->
                <div
                    v-if="approveError"
                    class="flex items-start gap-3 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700"
                >
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <span>{{ approveError }}</span>
                </div>

                <!-- Header fields -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <!-- Nomor & Status -->
                    <BaseFormRow label="Nomor" :help-text="autoCode ? 'Nomor otomatis' : ''">
                        <div class="flex items-center gap-2">
                            <BaseInput :model-value="form.code" disabled class="flex-1" />
                            <span
                                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="form.status === 'posted'
                                    ? 'bg-success-100 text-success-700'
                                    : 'bg-amber-100 text-amber-700'"
                            >
                                {{ form.status === 'posted' ? 'Approved' : 'Draft' }}
                            </span>
                        </div>
                    </BaseFormRow>

                    <BaseFormRow label="Tanggal" required :error="formErrors.date">
                        <BaseDatePicker v-model="form.date" :disabled="readonly" />
                    </BaseFormRow>

                    <BaseFormRow label="Dari Gudang" required :error="formErrors.from_warehouse_id">
                        <BaseBrowse
                            v-model="form.from_warehouse_id"
                            :config="warehouseBrowseConfig"
                            placeholder="Pilih gudang asal..."
                            :disabled="readonly"
                            :row-data="fromWarehouseRowData"
                            @select="(row: any) => fromWarehouseRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Ke Gudang" required :error="formErrors.to_warehouse_id">
                        <BaseBrowse
                            v-model="form.to_warehouse_id"
                            :config="warehouseBrowseConfig"
                            placeholder="Pilih gudang tujuan..."
                            :disabled="readonly"
                            :row-data="toWarehouseRowData"
                            @select="(row: any) => toWarehouseRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Keterangan" class="col-span-2" :error="formErrors.description">
                        <BaseInput v-model="form.description" placeholder="Keterangan transfer" :disabled="readonly" />
                    </BaseFormRow>
                </div>

                <!-- Detail rows -->
                <BaseGrid
                    v-model="rows"
                    :columns="[
                        { key: 'product_id',   label: 'Produk',     width: '220px' },
                        { key: 'batch_number', label: 'Batch',      width: '150px' },
                        { key: 'unit_id',      label: 'Satuan',     width: '130px' },
                        { key: 'qty',          label: 'Qty',        width: '90px'  },
                        { key: 'unit_cost',    label: 'Harga/Unit', width: '110px' },
                        { key: 'description',  label: 'Keterangan' },
                    ]"
                    title="Barang yang Ditransfer"
                    :disabled="readonly"
                    :new-row="newRow"
                    :error="formErrors.details"
                    :required-keys="['product_id', 'unit_id', 'qty']"
                >
                    <template #cell-product_id="{ row, disabled }">
                        <BaseBrowse
                            :modelValue="row.product_id"
                            :config="productBrowseConfig"
                            placeholder="Cari produk..."
                            :row-data="row._productData"
                            :disabled="disabled"
                            @update:modelValue="(val: any) => row.product_id = val"
                            @select="(pd: any) => { row.product_id = pd.id; handleProductSelect(row, pd); }"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </template>

                    <template #cell-batch_number="{ row, disabled }">
                        <select
                            v-model="row.batch_number"
                            class="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                            @focus="loadBatches(row)"
                        >
                            <option value="">— pilih batch —</option>
                            <option v-for="opt in row._batchOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </template>

                    <template #cell-unit_id="{ row, disabled }">
                        <select
                            v-model="row.unit_id"
                            class="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled || !row.product_id"
                        >
                            <option :value="null">— pilih satuan —</option>
                            <option v-for="u in row._units" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </template>

                    <template #cell-qty="{ row, disabled }">
                        <input v-model.number="row.qty" type="number" step="0.0001" min="0.0001"
                            class="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled" />
                    </template>

                    <template #cell-unit_cost="{ row, disabled }">
                        <input v-model.number="row.unit_cost" type="number" step="0.01" min="0"
                            class="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled" />
                    </template>

                    <template #cell-description="{ row, disabled }">
                        <input v-model="row.description" type="text" placeholder="Keterangan baris"
                            class="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled" />
                    </template>
                </BaseGrid>

            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="stock_transfer"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>

    <!-- ===== PRINT OVERLAY ===== -->
    <teleport to="body">
        <div v-if="showPrint" id="spirit-print-root" class="fixed inset-0 z-[9999] overflow-auto bg-white p-10 text-gray-900">

            <button
                class="no-print mb-6 inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50"
                @click="showPrint = false"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Tutup
            </button>

            <div class="mb-6 border-b-2 border-gray-800 pb-4 text-center">
                <h1 class="text-xl font-bold uppercase tracking-wide">Transfer Stok</h1>
            </div>

            <table class="mb-6 w-full text-sm">
                <tbody>
                    <tr>
                        <td class="w-40 py-0.5 font-medium text-gray-600">No. Dokumen</td>
                        <td class="w-4 text-center">:</td>
                        <td class="font-semibold">{{ form.code }}</td>
                        <td class="w-40 py-0.5 font-medium text-gray-600">Tanggal</td>
                        <td class="w-4 text-center">:</td>
                        <td>{{ formatPrintDate(form.date) }}</td>
                    </tr>
                    <tr>
                        <td class="py-0.5 font-medium text-gray-600">Dari Gudang</td>
                        <td class="text-center">:</td>
                        <td>{{ fromWarehouseRowData ? `${fromWarehouseRowData.code} - ${fromWarehouseRowData.name}` : '-' }}</td>
                        <td class="py-0.5 font-medium text-gray-600">Ke Gudang</td>
                        <td class="text-center">:</td>
                        <td>{{ toWarehouseRowData ? `${toWarehouseRowData.code} - ${toWarehouseRowData.name}` : '-' }}</td>
                    </tr>
                    <tr v-if="form.description">
                        <td class="py-0.5 font-medium text-gray-600">Keterangan</td>
                        <td class="text-center">:</td>
                        <td colspan="3">{{ form.description }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="mb-6 w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-3 py-1.5 text-center">No.</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-left">Produk</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-left">Batch</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-left">Satuan</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-right">Qty</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-right">Harga/Unit</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-right">Nilai</th>
                        <th class="border border-gray-300 px-3 py-1.5 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in rows" :key="i">
                        <td class="border border-gray-300 px-3 py-1 text-center">{{ i + 1 }}</td>
                        <td class="border border-gray-300 px-3 py-1">{{ getProductLabel(row) }}</td>
                        <td class="border border-gray-300 px-3 py-1">{{ row.batch_number || '-' }}</td>
                        <td class="border border-gray-300 px-3 py-1">{{ getUnitLabel(row) }}</td>
                        <td class="border border-gray-300 px-3 py-1 text-right">{{ fmtQty(row.qty) }}</td>
                        <td class="border border-gray-300 px-3 py-1 text-right">{{ fmtCurrency(row.unit_cost) }}</td>
                        <td class="border border-gray-300 px-3 py-1 text-right">{{ fmtCurrency(row.qty * row.unit_cost) }}</td>
                        <td class="border border-gray-300 px-3 py-1">{{ row.description || '' }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-10 grid grid-cols-3 gap-8 text-sm">
                <div class="text-center">
                    <p class="mb-16">Dibuat oleh,</p>
                    <div class="border-t border-gray-400 pt-1">(.........................)</div>
                </div>
                <div class="text-center">
                    <p class="mb-16">Diperiksa oleh,</p>
                    <div class="border-t border-gray-400 pt-1">(.........................)</div>
                </div>
                <div class="text-center">
                    <p class="mb-16">Disetujui oleh,</p>
                    <div class="border-t border-gray-400 pt-1">(.........................)</div>
                </div>
            </div>

        </div>
    </teleport>
</template>

<style>
@media print {
    body > *:not(#spirit-print-root) { display: none !important; }
    #spirit-print-root { position: fixed !important; inset: 0 !important; overflow: visible !important; }
    .no-print { display: none !important; }
}
</style>
