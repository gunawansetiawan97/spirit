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
    title: 'Group Akun',
    apiEndpoint: '/api/account-groups',
    basePath: '/master/account-group',
    auditType: 'account-group',
    auditRelations: ['createdBy', 'updatedBy', 'approvedBy', 'printedBy'],
}, props, emit);

const accountTypeOptions = ref<SelectOption[]>([]);

const normalBalanceOptions: SelectOption[] = [
    { value: 'Debit', label: 'Debit' },
    { value: 'Credit', label: 'Credit' },
];

const form = ref({
    account_type_id: null as number | null,
    group_name: '',
    normal_balance: '' as string,
    is_active: true,
});

const fetchOptions = async () => {
    try {
        const res = await axios.get('/api/account-types/list');
        accountTypeOptions.value = res.data.data.map((r: any) => ({ value: r.id, label: r.code }));
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
                account_type_id: data.account_type_id,
                group_name: data.group_name,
                normal_balance: data.normal_balance,
                is_active: data.is_active,
            };
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
                <BaseSelect
                    v-model="form.account_type_id"
                    :options="accountTypeOptions"
                    label="Tipe Akun"
                    placeholder="Pilih tipe akun"
                    :error="formErrors.account_type_id"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.group_name"
                    label="Nama Group"
                    placeholder="Masukkan nama group"
                    :error="formErrors.group_name"
                    :disabled="readonly"
                    required
                />

                <BaseSelect
                    v-model="form.normal_balance"
                    :options="normalBalanceOptions"
                    label="Saldo Normal"
                    placeholder="Pilih saldo normal"
                    :error="formErrors.normal_balance"
                    :disabled="readonly"
                    required
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
                loggable-type="account-group"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
