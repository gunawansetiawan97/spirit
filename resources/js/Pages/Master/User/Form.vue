<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseSelect, BaseCheckbox, BaseBrowseMulti } from '@/Components/Form';
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
    title: 'User',
    apiEndpoint: '/api/users',
    basePath: '/master/user',
    auditType: 'user',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

// Options
const roleOptions = ref<SelectOption[]>([]);

const branchBrowseConfig: BrowseConfig = {
    endpoint: '/api/branches',
    title: 'BROWSE CABANG',
    columns: [
        { key: 'code', label: 'Kode', width: '120px' },
        { key: 'name', label: 'Nama' },
        { key: 'address', label: 'Alamat' },
        { key: 'phone', label: 'Telepon', width: '150px' },
    ],
    displayFormat: '{code} - {name}',
    dropdownFormat: '{code} - {name}',
    createRoute: '/master/branch/create',
    searchPlaceholder: 'Cari cabang...',
};

const form = ref({
    name: '',
    email: '',
    password: '',
    role_id: null as number | null,
    branch_ids: [] as number[],
    is_active: true,
});

const branchRowsData = ref<any[]>([]);

const fetchOptions = async () => {
    try {
        const rolesRes = await axios.get('/api/roles/list');
        roleOptions.value = rolesRes.data.data.map((r: any) => ({ value: r.id, label: r.name }));
    } catch (error) {
        console.error('Failed to fetch options:', error);
    }
};

onMounted(() => {
    setupPage();
    // Run both API calls in parallel
    Promise.all([
        fetchOptions(),
        fetchData((data) => {
            form.value = {
                name: data.name,
                email: data.email,
                password: '',
                role_id: data.role?.id || null,
                branch_ids: data.branches?.map((b: any) => b.id) || [],
                is_active: data.is_active,
            };
            branchRowsData.value = data.branches || [];
        }),
    ]);
});

const onSubmit = () => handleSubmit(() => {
    const payload = { ...form.value };
    if (props.mode === 'edit' && !payload.password) {
        delete (payload as any).password;
    }
    return payload;
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.email"
                    type="email"
                    label="Email"
                    placeholder="Masukkan email"
                    :error="formErrors.email"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-if="mode !== 'view'"
                    v-model="form.password"
                    type="password"
                    :label="mode === 'edit' ? 'Password (kosongkan jika tidak diubah)' : 'Password'"
                    placeholder="Masukkan password"
                    :error="formErrors.password"
                    :disabled="readonly"
                    :required="mode === 'create'"
                />

                <BaseSelect
                    v-model="form.role_id"
                    :options="roleOptions"
                    label="Role"
                    placeholder="Pilih role"
                    :error="formErrors.role_id"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseBrowseMulti
                        v-model="form.branch_ids"
                        :config="branchBrowseConfig"
                        :rows-data="branchRowsData"
                        label="Cabang"
                        placeholder="Pilih cabang..."
                        :error="formErrors.branch_ids"
                        :disabled="readonly"
                        required
                        @navigate="(route: string) => emit('navigate', route)"
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
                loggable-type="user"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
