<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo, BaseFormRow } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseSelect, BaseCheckbox, BaseGrid, BaseBrowse, BaseImageUpload } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { BrowseConfig } from '@/types';
import type { ExistingImage } from '@/Components/Form/BaseImageUpload.vue';

const props = withDefaults(defineProps<{
    id?: string | number;
    mode: 'create' | 'edit' | 'view';
}>(), { mode: 'create' });

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const {
    loading, saving, formErrors, activeTab, auditData, recordId,
    formTabs, pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Produk',
    apiEndpoint: '/api/products',
    basePath: '/master/product',
    auditType: 'product',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

// Form data
const form = ref({
    code: '',
    name: '',
    product_category_id: null as number | null,
    product_brand_id: null as number | null,
    type: 'stock' as string,
    min_stock: null as number | null,
    max_stock: null as number | null,
    description: '',
    coa_inventory_id: null as number | null,
    coa_cogs_id: null as number | null,
    coa_sales_id: null as number | null,
    is_active: true,
});

const autoCode = ref(false);

// Unit grid
const unitRows = ref<{ unit_id: number | null; conversion: number; is_base_unit: boolean }[]>([]);
const unitRowDataMap = ref<Record<number, any>>({});

// Images (managed by BaseImageUpload)
const existingImages = ref<ExistingImage[]>([]);
const newFiles = ref<File[]>([]);
const deletedImageIds = ref<number[]>([]);

// Browse configs
const categoryBrowseConfig: BrowseConfig = {
    endpoint: '/api/product-categories',
    title: 'BROWSE KATEGORI PRODUK',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    createRoute: '/master/product-category/create',
    searchPlaceholder: 'Cari kategori...',
};

const brandBrowseConfig: BrowseConfig = {
    endpoint: '/api/product-brands',
    title: 'BROWSE MERK PRODUK',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    createRoute: '/master/product-brand/create',
    searchPlaceholder: 'Cari merk...',
};

const unitBrowseConfig: BrowseConfig = {
    endpoint: '/api/units',
    title: 'BROWSE UNIT',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    createRoute: '/master/unit/create',
    searchPlaceholder: 'Cari unit...',
};

const coaBrowseConfig: BrowseConfig = {
    endpoint: '/api/chart-of-accounts',
    title: 'BROWSE CHART OF ACCOUNT',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari akun...',
};

const typeOptions = [
    { value: 'stock', label: 'Stock' },
    { value: 'non-stock', label: 'Non-Stock' },
];

// Row data maps for browse in edit/view mode
const categoryRowData = ref<any>(null);
const brandRowData = ref<any>(null);
const coaInventoryRowData = ref<any>(null);
const coaCogsRowData = ref<any>(null);
const coaSalesRowData = ref<any>(null);

// AUTOCODE
const generateAutoCode = () => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const ts = `${now.getFullYear()}/${pad(now.getMonth() + 1)}/${pad(now.getDate())}/${pad(now.getHours())}/${pad(now.getMinutes())}/${pad(now.getSeconds())}`;
    const rand = Math.floor(Math.random() * 100000);
    return `AUTOCODE/${ts}/${rand}`;
};

// --- Browse inter-communication ---

/** When category is selected, auto-fill COA fields from category's defaults */
const handleCategorySelect = (row: any) => {
    categoryRowData.value = row;

    // Auto-fill COA from category (only if target field is still empty)
    if (row.coa_inventory && !form.value.coa_inventory_id) {
        form.value.coa_inventory_id = row.coa_inventory.id;
        coaInventoryRowData.value = row.coa_inventory;
    }
    if (row.coa_cogs && !form.value.coa_cogs_id) {
        form.value.coa_cogs_id = row.coa_cogs.id;
        coaCogsRowData.value = row.coa_cogs;
    }
    if (row.coa_sales && !form.value.coa_sales_id) {
        form.value.coa_sales_id = row.coa_sales.id;
        coaSalesRowData.value = row.coa_sales;
    }
};

/** When category is cleared, clear related COA fields */
const handleCategoryClear = () => {
    categoryRowData.value = null;
    form.value.coa_inventory_id = null;
    form.value.coa_cogs_id = null;
    form.value.coa_sales_id = null;
    coaInventoryRowData.value = null;
    coaCogsRowData.value = null;
    coaSalesRowData.value = null;
};

// Fetch data
onMounted(() => {
    setupPage();
    fetchData((data) => {
        form.value = {
            code: data.code,
            name: data.name,
            product_category_id: data.category?.id || null,
            product_brand_id: data.brand?.id || null,
            type: data.type,
            min_stock: data.min_stock,
            max_stock: data.max_stock,
            description: data.description || '',
            coa_inventory_id: data.coa_inventory?.id || null,
            coa_cogs_id: data.coa_cogs?.id || null,
            coa_sales_id: data.coa_sales?.id || null,
            is_active: data.is_active,
        };

        // Browse row data
        if (data.category) categoryRowData.value = data.category;
        if (data.brand) brandRowData.value = data.brand;
        if (data.coa_inventory) coaInventoryRowData.value = data.coa_inventory;
        if (data.coa_cogs) coaCogsRowData.value = data.coa_cogs;
        if (data.coa_sales) coaSalesRowData.value = data.coa_sales;

        // Units
        unitRows.value = data.units?.map((u: any) => ({
            unit_id: u.unit?.id || null,
            conversion: u.conversion,
            is_base_unit: u.is_base_unit,
        })) || [];
        data.units?.forEach((u: any) => {
            if (u.unit) unitRowDataMap.value[u.unit.id] = u.unit;
        });

        // Images
        existingImages.value = data.images || [];
    });

    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }
});

// Submit
const onSubmit = () => handleSubmit(() => {
    const formData = new FormData();

    // Basic fields
    const code = autoCode.value ? '' : form.value.code;
    if (code) formData.append('code', code);
    formData.append('name', form.value.name);
    formData.append('type', form.value.type);
    formData.append('is_active', form.value.is_active ? '1' : '0');

    formData.append('product_category_id', form.value.product_category_id ? String(form.value.product_category_id) : '');
    formData.append('product_brand_id', form.value.product_brand_id ? String(form.value.product_brand_id) : '');
    if (form.value.min_stock != null) formData.append('min_stock', String(form.value.min_stock));
    if (form.value.max_stock != null) formData.append('max_stock', String(form.value.max_stock));
    formData.append('description', form.value.description || '');
    formData.append('coa_inventory_id', form.value.coa_inventory_id ? String(form.value.coa_inventory_id) : '');
    formData.append('coa_cogs_id', form.value.coa_cogs_id ? String(form.value.coa_cogs_id) : '');
    formData.append('coa_sales_id', form.value.coa_sales_id ? String(form.value.coa_sales_id) : '');

    // Units
    unitRows.value.forEach((row, i) => {
        if (row.unit_id) {
            formData.append(`units[${i}][unit_id]`, String(row.unit_id));
            formData.append(`units[${i}][conversion]`, String(row.conversion));
            formData.append(`units[${i}][is_base_unit]`, row.is_base_unit ? '1' : '0');
        }
    });

    // New images
    newFiles.value.forEach((file) => {
        formData.append('images[]', file);
    });

    // Deleted images
    deletedImageIds.value.forEach((id) => {
        formData.append('deleted_images[]', String(id));
    });

    return formData;
});
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

                <!-- Basic Info: 2 kolom, label kiri -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <BaseFormRow label="Kode" :error="formErrors.code" :help-text="autoCode ? 'Nomor otomatis' : ''">
                        <BaseInput
                            v-model="form.code"
                            :disabled="readonly || autoCode || mode === 'edit'"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Nama" required :error="formErrors.name">
                        <BaseInput
                            v-model="form.name"
                            placeholder="Nama produk"
                            :disabled="readonly"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Kategori" :error="formErrors.product_category_id">
                        <BaseBrowse
                            v-model="form.product_category_id"
                            :config="categoryBrowseConfig"
                            placeholder="Cari kategori..."
                            :disabled="readonly"
                            :row-data="categoryRowData"
                            @select="handleCategorySelect"
                            @clear="handleCategoryClear"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Merk" :error="formErrors.product_brand_id">
                        <BaseBrowse
                            v-model="form.product_brand_id"
                            :config="brandBrowseConfig"
                            placeholder="Cari merk..."
                            :disabled="readonly"
                            :row-data="brandRowData"
                            @select="(row: any) => brandRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Tipe" required :error="formErrors.type">
                        <BaseSelect
                            v-model="form.type"
                            :options="typeOptions"
                            :disabled="readonly"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Status">
                        <div class="flex h-[34px] items-center">
                            <BaseCheckbox
                                v-model="form.is_active"
                                label="Aktif"
                                :disabled="readonly"
                            />
                        </div>
                    </BaseFormRow>

                    <BaseFormRow label="Stok Min" :error="formErrors.min_stock">
                        <BaseInput
                            v-model="form.min_stock"
                            type="number"
                            placeholder="0"
                            :disabled="readonly"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Stok Maks" :error="formErrors.max_stock">
                        <BaseInput
                            v-model="form.max_stock"
                            type="number"
                            placeholder="0"
                            :disabled="readonly"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="Deskripsi" class="col-span-2" :error="formErrors.description">
                        <BaseTextarea
                            v-model="form.description"
                            placeholder="Deskripsi produk"
                            :disabled="readonly"
                            :rows="2"
                        />
                    </BaseFormRow>
                </div>

                <!-- Satuan -->
                <BaseGrid
                    v-model="unitRows"
                    :columns="[
                        { key: 'unit_id', label: 'Unit', width: '300px' },
                        { key: 'conversion', label: 'Konversi', width: '150px' },
                        { key: 'is_base_unit', label: 'Unit Dasar', width: '100px' },
                    ]"
                    title="Satuan"
                    :disabled="readonly"
                    :new-row="() => ({ unit_id: null, conversion: 1, is_base_unit: false })"
                    :error="formErrors.units"
                    :unique-keys="['unit_id']"
                    :required-keys="['unit_id']"
                >
                    <template #cell-unit_id="{ row, disabled }">
                        <BaseBrowse
                            :modelValue="row.unit_id"
                            @update:modelValue="(val: any) => row.unit_id = val"
                            :config="unitBrowseConfig"
                            :row-data="unitRowDataMap[row.unit_id]"
                            :disabled="disabled"
                            @select="(rowData: any) => unitRowDataMap[rowData.id] = rowData"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </template>
                    <template #cell-conversion="{ row, disabled }">
                        <input
                            v-model.number="row.conversion"
                            type="number"
                            step="0.0001"
                            min="0.0001"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            :disabled="disabled"
                        />
                    </template>
                    <template #cell-is_base_unit="{ row, disabled }">
                        <div class="flex justify-center">
                            <input
                                v-model="row.is_base_unit"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                :disabled="disabled"
                            />
                        </div>
                    </template>
                </BaseGrid>

                <!-- COA: 3 kolom, label kiri -->
                <div class="grid grid-cols-3 gap-x-6 gap-y-2">
                    <BaseFormRow label="COA Persediaan" :error="formErrors.coa_inventory_id">
                        <BaseBrowse
                            v-model="form.coa_inventory_id"
                            :config="coaBrowseConfig"
                            placeholder="Cari akun..."
                            :disabled="readonly"
                            :row-data="coaInventoryRowData"
                            @select="(row: any) => coaInventoryRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="COA HPP" :error="formErrors.coa_cogs_id">
                        <BaseBrowse
                            v-model="form.coa_cogs_id"
                            :config="coaBrowseConfig"
                            placeholder="Cari akun..."
                            :disabled="readonly"
                            :row-data="coaCogsRowData"
                            @select="(row: any) => coaCogsRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>

                    <BaseFormRow label="COA Penjualan" :error="formErrors.coa_sales_id">
                        <BaseBrowse
                            v-model="form.coa_sales_id"
                            :config="coaBrowseConfig"
                            placeholder="Cari akun..."
                            :disabled="readonly"
                            :row-data="coaSalesRowData"
                            @select="(row: any) => coaSalesRowData = row"
                            @navigate="(route: string) => emit('navigate', route)"
                        />
                    </BaseFormRow>
                </div>

                <!-- Gambar -->
                <BaseFormRow label="Gambar" :error="formErrors.images">
                    <BaseImageUpload
                        v-model:existing-images="existingImages"
                        v-model:new-files="newFiles"
                        v-model:deleted-ids="deletedImageIds"
                        :disabled="readonly"
                    />
                </BaseFormRow>

            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="product"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
