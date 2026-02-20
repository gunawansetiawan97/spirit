<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, BaseFormRow, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox, BaseBrowse } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { BrowseConfig } from '@/types';

const props = withDefaults(defineProps<{
    id?: string | number;
    mode: 'create' | 'edit' | 'view';
}>(), { mode: 'create' });

const emit = defineEmits<{ (e: 'navigate', route: string): void }>();

const {
    loading, saving, formErrors, activeTab, auditData, recordId,
    formTabs, pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Supplier',
    apiEndpoint: '/api/suppliers',
    basePath: '/purchasing/supplier',
    auditType: 'supplier',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

const form = ref({
    code:         '',
    name:         '',
    phone:        '',
    address:      '',
    coa_id:       null as number | null,
    coa_dp_id:    null as number | null,
    city:         '',
    npwp_no:      '',
    npwp_name:    '',
    npwp_address: '',
    nik:          '',
    is_active:    true,
});

const autoCode    = ref(false);
const coaRowData  = ref<any>(null);
const coaDpRowData = ref<any>(null);

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

const generateAutoCode = () => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const ts = `${now.getFullYear()}/${pad(now.getMonth() + 1)}/${pad(now.getDate())}/${pad(now.getHours())}/${pad(now.getMinutes())}/${pad(now.getSeconds())}`;
    return `AUTOCODE/${ts}/${Math.floor(Math.random() * 100000)}`;
};

onMounted(() => {
    setupPage();
    fetchData((data) => {
        form.value = {
            code:         data.code         || '',
            name:         data.name         || '',
            phone:        data.phone        || '',
            address:      data.address      || '',
            coa_id:       data.coa?.id      || null,
            coa_dp_id:    data.coa_dp?.id   || null,
            city:         data.city         || '',
            npwp_no:      data.npwp_no      || '',
            npwp_name:    data.npwp_name    || '',
            npwp_address: data.npwp_address || '',
            nik:          data.nik          || '',
            is_active:    data.is_active,
        };
        if (data.coa)    coaRowData.value   = data.coa;
        if (data.coa_dp) coaDpRowData.value = data.coa_dp;
    });

    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }
});

const onSubmit = () => handleSubmit(() => ({
    code:         autoCode.value ? undefined : form.value.code,
    name:         form.value.name,
    phone:        form.value.phone        || null,
    address:      form.value.address      || null,
    coa_id:       form.value.coa_id       || null,
    coa_dp_id:    form.value.coa_dp_id    || null,
    city:         form.value.city         || null,
    npwp_no:      form.value.npwp_no      || null,
    npwp_name:    form.value.npwp_name    || null,
    npwp_address: form.value.npwp_address || null,
    nik:          form.value.nik          || null,
    is_active:    form.value.is_active,
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
            <div class="grid grid-cols-2 gap-x-6 gap-y-2">

                <!-- Baris 1: Kode & Nama -->
                <BaseFormRow label="Kode" :error="formErrors.code" :help-text="autoCode ? 'Nomor otomatis' : ''">
                    <BaseInput v-model="form.code" :disabled="readonly || autoCode || mode === 'edit'" />
                </BaseFormRow>

                <BaseFormRow label="Nama" required :error="formErrors.name">
                    <BaseInput v-model="form.name" placeholder="Nama supplier" :disabled="readonly" />
                </BaseFormRow>

                <!-- Baris 2: Telepon & Kota -->
                <BaseFormRow label="No. Telepon" :error="formErrors.phone">
                    <BaseInput v-model="form.phone" placeholder="Nomor telepon" :disabled="readonly" />
                </BaseFormRow>

                <BaseFormRow label="Kota" :error="formErrors.city">
                    <BaseInput v-model="form.city" placeholder="Kota" :disabled="readonly" />
                </BaseFormRow>

                <!-- Baris 3: Alamat -->
                <BaseFormRow label="Alamat" class="col-span-2" :error="formErrors.address">
                    <BaseTextarea v-model="form.address" placeholder="Alamat lengkap" :disabled="readonly" :rows="2" />
                </BaseFormRow>

                <!-- Baris 4: COA -->
                <BaseFormRow label="COA Hutang" :error="formErrors.coa_id">
                    <BaseBrowse
                        v-model="form.coa_id"
                        :config="coaBrowseConfig"
                        placeholder="Pilih akun hutang..."
                        :disabled="readonly"
                        :row-data="coaRowData"
                        @select="(row: any) => coaRowData = row"
                        @navigate="(route: string) => emit('navigate', route)"
                    />
                </BaseFormRow>

                <BaseFormRow label="COA Uang Muka" :error="formErrors.coa_dp_id">
                    <BaseBrowse
                        v-model="form.coa_dp_id"
                        :config="coaBrowseConfig"
                        placeholder="Pilih akun uang muka..."
                        :disabled="readonly"
                        :row-data="coaDpRowData"
                        @select="(row: any) => coaDpRowData = row"
                        @navigate="(route: string) => emit('navigate', route)"
                    />
                </BaseFormRow>

                <!-- Baris 5: NPWP & NIK -->
                <BaseFormRow label="No. NPWP" :error="formErrors.npwp_no">
                    <BaseInput v-model="form.npwp_no" placeholder="Nomor NPWP" :disabled="readonly" />
                </BaseFormRow>

                <BaseFormRow label="NIK" :error="formErrors.nik">
                    <BaseInput v-model="form.nik" placeholder="NIK KTP" :disabled="readonly" />
                </BaseFormRow>

                <!-- Baris 6: Nama NPWP & Status -->
                <BaseFormRow label="Nama NPWP" :error="formErrors.npwp_name">
                    <BaseInput v-model="form.npwp_name" placeholder="Nama sesuai NPWP" :disabled="readonly" />
                </BaseFormRow>

                <BaseFormRow label="Status">
                    <div class="flex h-[34px] items-center">
                        <BaseCheckbox v-model="form.is_active" label="Aktif" :disabled="readonly" />
                    </div>
                </BaseFormRow>

                <!-- Baris 7: Alamat NPWP -->
                <BaseFormRow label="Alamat NPWP" class="col-span-2" :error="formErrors.npwp_address">
                    <BaseTextarea v-model="form.npwp_address" placeholder="Alamat sesuai NPWP" :disabled="readonly" :rows="2" />
                </BaseFormRow>

            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="supplier"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
