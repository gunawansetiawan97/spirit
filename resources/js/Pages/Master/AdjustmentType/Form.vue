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
    title: 'Tipe Penyesuaian',
    apiEndpoint: '/api/adjustment-types',
    basePath: '/master/adjustment-type',
    auditType: 'adjustment_type',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

const form = ref({
    code: '',
    name: '',
    coa_id: null as number | null,
    description: '',
    is_active: true,
});

const autoCode = ref(false);
const coaRowData = ref<any>(null);

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
            code: data.code,
            name: data.name,
            coa_id: data.coa?.id || null,
            description: data.description || '',
            is_active: data.is_active,
        };
        if (data.coa) coaRowData.value = data.coa;
    });

    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }
});

const onSubmit = () => handleSubmit(() => ({
    code: autoCode.value ? undefined : form.value.code,
    name: form.value.name,
    coa_id: form.value.coa_id || '',
    description: form.value.description,
    is_active: form.value.is_active,
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
                <BaseFormRow label="Kode" :error="formErrors.code" :help-text="autoCode ? 'Nomor otomatis' : ''">
                    <BaseInput v-model="form.code" :disabled="readonly || autoCode || mode === 'edit'" />
                </BaseFormRow>

                <BaseFormRow label="Nama" required :error="formErrors.name">
                    <BaseInput v-model="form.name" placeholder="Nama tipe penyesuaian" :disabled="readonly" />
                </BaseFormRow>

                <BaseFormRow label="Akun COA" :error="formErrors.coa_id">
                    <BaseBrowse
                        v-model="form.coa_id"
                        :config="coaBrowseConfig"
                        placeholder="Cari akun..."
                        :disabled="readonly"
                        :row-data="coaRowData"
                        @select="(row: any) => coaRowData = row"
                        @navigate="(route: string) => emit('navigate', route)"
                    />
                </BaseFormRow>

                <BaseFormRow label="Status">
                    <div class="flex h-[34px] items-center">
                        <BaseCheckbox v-model="form.is_active" label="Aktif" :disabled="readonly" />
                    </div>
                </BaseFormRow>

                <BaseFormRow label="Deskripsi" class="col-span-2" :error="formErrors.description">
                    <BaseTextarea v-model="form.description" placeholder="Keterangan tambahan" :disabled="readonly" :rows="2" />
                </BaseFormRow>
            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="adjustment_type"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
