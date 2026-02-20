<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseSelect, BaseTextarea, BaseCheckbox, BaseBrowse } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { SelectOption, BrowseConfig } from '@/types';

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
    title: 'COA Mapping',
    apiEndpoint: '/api/coa-mappings',
    basePath: '/master/coa-mapping',
    auditType: 'coa-mapping',
    auditRelations: ['createdBy', 'updatedBy', 'approvedBy', 'printedBy'],
}, props, emit);

const moduleOptions: SelectOption[] = [
    { value: 'Sales', label: 'Sales' },
    { value: 'Purchasing', label: 'Purchasing' },
    { value: 'Inventory', label: 'Inventory' },
    { value: 'Finance', label: 'Finance' },
    { value: 'General', label: 'General' },
];

const coaBrowseConfig: BrowseConfig = {
    endpoint: '/api/chart-of-accounts',
    title: 'BROWSE CHART OF ACCOUNT',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama Akun' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari akun...',
};

const form = ref({
    module: '' as string,
    mapping_key: '',
    coa_id: null as number | null,
    description: '',
    is_active: true,
});

const coaRowData = ref<any>(null);

onMounted(() => {
    setupPage();
    fetchData((data) => {
        form.value = {
            module: data.module,
            mapping_key: data.mapping_key,
            coa_id: data.coa_id,
            description: data.description || '',
            is_active: data.is_active,
        };
        if (data.coa) {
            coaRowData.value = data.coa;
        }
    });
});

const onSubmit = () => handleSubmit(() => ({ ...form.value }));
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
                <BaseSelect
                    v-model="form.module"
                    :options="moduleOptions"
                    label="Modul"
                    placeholder="Pilih modul"
                    :error="formErrors.module"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.mapping_key"
                    label="Mapping Key"
                    placeholder="Masukkan mapping key"
                    :error="formErrors.mapping_key"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseBrowse
                        v-model="form.coa_id"
                        :config="coaBrowseConfig"
                        :row-data="coaRowData"
                        label="Chart of Account"
                        :error="formErrors.coa_id"
                        :disabled="readonly"
                        required
                        @select="(row: any) => coaRowData = row"
                        @navigate="(route: string) => emit('navigate', route)"
                    />
                </div>

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.description"
                        label="Keterangan"
                        placeholder="Masukkan keterangan"
                        :error="formErrors.description"
                        :disabled="readonly"
                    />
                </div>

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
                loggable-type="coa-mapping"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
