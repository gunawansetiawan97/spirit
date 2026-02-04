<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox } from '@/Components/Form';
import { useFormPage } from '@/Composables';

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
    title: 'Cabang',
    apiEndpoint: '/api/branches',
    basePath: '/master/branch',
    auditType: 'branch',
    auditRelations: ['createdBy', 'updatedBy', 'approvedBy', 'printedBy'],
}, props, emit);

const form = ref({
    code: '',
    name: '',
    address: '',
    phone: '',
    is_active: true,
});

onMounted(async () => {
    setupPage();
    await fetchData((data) => {
        form.value = {
            code: data.code,
            name: data.name,
            address: data.address || '',
            phone: data.phone || '',
            is_active: data.is_active,
        };
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
                <BaseInput
                    v-model="form.code"
                    label="Kode"
                    placeholder="Masukkan kode cabang"
                    :error="formErrors.code"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama cabang"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.phone"
                    label="Telepon"
                    placeholder="Masukkan nomor telepon"
                    :error="formErrors.phone"
                    :disabled="readonly"
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.address"
                        label="Alamat"
                        placeholder="Masukkan alamat cabang"
                        :error="formErrors.address"
                        :disabled="readonly"
                        :rows="3"
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
                loggable-type="branch"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
