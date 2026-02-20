<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseSelect, BaseCheckbox, BaseBrowse } from '@/Components/Form';
import { useFormPage } from '@/Composables';
import type { SelectOption, BrowseConfig } from '@/types';
import axios from 'axios';

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
    title: 'Chart of Account',
    apiEndpoint: '/api/chart-of-accounts',
    basePath: '/master/coa',
    auditType: 'chart-of-account',
    auditRelations: ['createdBy', 'updatedBy', 'approvedBy', 'printedBy'],
}, props, emit);

const accountGroupOptions = ref<SelectOption[]>([]);

const postingTypeOptions: SelectOption[] = [
    { value: 'Posting', label: 'Posting' },
    { value: 'Header', label: 'Header' },
];

const currencyOptions: SelectOption[] = [
    { value: 'IDR', label: 'IDR' },
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
    { value: 'SGD', label: 'SGD' },
];

const parentBrowseConfig: BrowseConfig = {
    endpoint: '/api/chart-of-accounts',
    title: 'BROWSE PARENT ACCOUNT',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama Akun' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    searchPlaceholder: 'Cari akun...',
};

const form = ref({
    code: '',
    name: '',
    account_group_id: null as number | null,
    posting_type: 'Posting' as string,
    parent_id: null as number | null,
    is_active: true,
    allow_manual_journal: true,
    currency: 'IDR' as string,
    cost_center: false,
});

const parentRowData = ref<any>(null);

const fetchOptions = async () => {
    try {
        const res = await axios.get('/api/account-groups/list');
        accountGroupOptions.value = res.data.data.map((r: any) => ({
            value: r.id,
            label: `${r.account_type?.code ?? ''} - ${r.group_name}`,
        }));
    } catch (error) {
        console.error('Failed to fetch options:', error);
    }
};

onMounted(() => {
    setupPage();
    Promise.all([
        fetchOptions(),
        fetchData((data) => {
            form.value = {
                code: data.code,
                name: data.name,
                account_group_id: data.account_group_id,
                posting_type: data.posting_type,
                parent_id: data.parent_id,
                is_active: data.is_active,
                allow_manual_journal: data.allow_manual_journal,
                currency: data.currency,
                cost_center: data.cost_center,
            };
            if (data.parent) {
                parentRowData.value = data.parent;
            }
        }),
    ]);
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
                <BaseInput
                    v-model="form.code"
                    label="Kode Akun"
                    placeholder="Masukkan kode akun"
                    :error="formErrors.code"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama Akun"
                    placeholder="Masukkan nama akun"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <BaseSelect
                    v-model="form.account_group_id"
                    :options="accountGroupOptions"
                    label="Group Akun"
                    placeholder="Pilih group akun"
                    :error="formErrors.account_group_id"
                    :disabled="readonly"
                    required
                />

                <BaseSelect
                    v-model="form.posting_type"
                    :options="postingTypeOptions"
                    label="Tipe Posting"
                    placeholder="Pilih tipe posting"
                    :error="formErrors.posting_type"
                    :disabled="readonly"
                    required
                />

                <BaseBrowse
                    v-model="form.parent_id"
                    :config="parentBrowseConfig"
                    :row-data="parentRowData"
                    label="Parent Account"
                    :error="formErrors.parent_id"
                    :disabled="readonly"
                    @select="(row: any) => parentRowData = row"
                    @navigate="(route: string) => emit('navigate', route)"
                />

                <BaseSelect
                    v-model="form.currency"
                    :options="currencyOptions"
                    label="Mata Uang"
                    placeholder="Pilih mata uang"
                    :error="formErrors.currency"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2 flex flex-wrap gap-6">
                    <BaseCheckbox
                        v-model="form.allow_manual_journal"
                        label="Izinkan Jurnal Manual"
                        :disabled="readonly"
                    />

                    <BaseCheckbox
                        v-model="form.cost_center"
                        label="Cost Center"
                        :disabled="readonly"
                    />

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
                loggable-type="chart-of-account"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
