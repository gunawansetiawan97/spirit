<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox, BaseBrowse } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { BrowseConfig } from '@/types';

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
    title: 'Kategori Produk',
    apiEndpoint: '/api/product-categories',
    basePath: '/master/product-category',
    auditType: 'product-category',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

const form = ref({
    code: '',
    name: '',
    description: '',
    coa_inventory_id: null as number | null,
    coa_cogs_id: null as number | null,
    coa_sales_id: null as number | null,
    is_active: true,
});

const autoCode = ref(false);

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

const coaInventoryRowData = ref<any>(null);
const coaCogsRowData = ref<any>(null);
const coaSalesRowData = ref<any>(null);

const generateAutoCode = () => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const ts = `${now.getFullYear()}/${pad(now.getMonth() + 1)}/${pad(now.getDate())}/${pad(now.getHours())}/${pad(now.getMinutes())}/${pad(now.getSeconds())}`;
    const rand = Math.floor(Math.random() * 100000);
    return `AUTOCODE/${ts}/${rand}`;
};

onMounted(async () => {
    setupPage();
    await fetchData((data) => {
        form.value = {
            code: data.code,
            name: data.name,
            description: data.description || '',
            coa_inventory_id: data.coa_inventory?.id || null,
            coa_cogs_id: data.coa_cogs?.id || null,
            coa_sales_id: data.coa_sales?.id || null,
            is_active: data.is_active,
        };
        if (data.coa_inventory) coaInventoryRowData.value = data.coa_inventory;
        if (data.coa_cogs) coaCogsRowData.value = data.coa_cogs;
        if (data.coa_sales) coaSalesRowData.value = data.coa_sales;
    });

    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }
});

const onSubmit = () => handleSubmit(() => ({
    ...form.value,
    code: autoCode.value ? null : form.value.code,
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <BaseInput
                    v-model="form.code"
                    label="Kode"
                    :help-text="autoCode ? 'Kode otomatis (nomor final bisa berbeda)' : ''"
                    :error="formErrors.code"
                    :disabled="readonly || autoCode || mode === 'edit'"
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama kategori"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.description"
                        label="Deskripsi"
                        placeholder="Masukkan deskripsi kategori"
                        :error="formErrors.description"
                        :disabled="readonly"
                        :rows="3"
                    />
                </div>

                <BaseBrowse
                    v-model="form.coa_inventory_id"
                    :config="coaBrowseConfig"
                    label="COA Persediaan"
                    placeholder="Cari akun..."
                    :error="formErrors.coa_inventory_id"
                    :disabled="readonly"
                    :row-data="coaInventoryRowData"
                    @select="(row: any) => coaInventoryRowData = row"
                    @navigate="(route: string) => emit('navigate', route)"
                />

                <BaseBrowse
                    v-model="form.coa_cogs_id"
                    :config="coaBrowseConfig"
                    label="COA HPP"
                    placeholder="Cari akun..."
                    :error="formErrors.coa_cogs_id"
                    :disabled="readonly"
                    :row-data="coaCogsRowData"
                    @select="(row: any) => coaCogsRowData = row"
                    @navigate="(route: string) => emit('navigate', route)"
                />

                <BaseBrowse
                    v-model="form.coa_sales_id"
                    :config="coaBrowseConfig"
                    label="COA Penjualan"
                    placeholder="Cari akun..."
                    :error="formErrors.coa_sales_id"
                    :disabled="readonly"
                    :row-data="coaSalesRowData"
                    @select="(row: any) => coaSalesRowData = row"
                    @navigate="(route: string) => emit('navigate', route)"
                />

                <div class="md:col-span-2">
                    <BaseCheckbox
                        v-model="form.is_active"
                        label="Status Aktif"
                        :disabled="readonly"
                    />
                </div>
            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="product-category"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
