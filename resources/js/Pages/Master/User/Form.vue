<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useUiStore } from '@/stores/ui';
import { FormPage } from '@/Components/Form';
import { BaseInput, BaseSelect, BaseCheckbox, BaseBrowse } from '@/Components/Form';
import type { SelectOption, BrowseConfig } from '@/types';
import axios from 'axios';

type FormMode = 'create' | 'edit' | 'view';

interface Props {
    id?: string | number;
    mode: FormMode;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const uiStore = useUiStore();

const loading = ref(false);
const saving = ref(false);

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
    branch_id: null as number | null,
    is_active: true,
});

const branchRowData = ref<any>(null);

const formErrors = ref<Record<string, string>>({});

const pageTitle = computed(() => {
    return 'User';
});

const fetchOptions = async () => {
    try {
        const rolesRes = await axios.get('/api/roles/list');
        roleOptions.value = rolesRes.data.data.map((r: any) => ({ value: r.id, label: r.name }));
    } catch (error) {
        console.error('Failed to fetch options:', error);
    }
};

const fetchData = async () => {
    if (!props.id) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/users/${props.id}`);
        const data = response.data.data;
        form.value = {
            name: data.name,
            email: data.email,
            password: '',
            role_id: data.role?.id || null,
            branch_id: data.branch?.id || null,
            is_active: data.is_active,
        };
        branchRowData.value = data.branch || null;
    } catch (error: any) {
        console.error('Failed to fetch user:', error);
        alert('Gagal memuat data user');
        emit('navigate', '/master/user');
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    formErrors.value = {};
    saving.value = true;

    try {
        const payload = { ...form.value };
        // Remove password if editing and password is empty
        if (props.mode === 'edit' && !payload.password) {
            delete (payload as any).password;
        }

        if (props.mode === 'edit' && props.id) {
            await axios.put(`/api/users/${props.id}`, payload);
        } else {
            await axios.post('/api/users', payload);
        }
        emit('navigate', '/master/user');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            formErrors.value = Object.fromEntries(
                Object.entries(error.response.data.errors).map(([key, value]) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ])
            ) as Record<string, string>;
        } else {
            alert(error.response?.data?.message || 'Gagal menyimpan data');
        }
    } finally {
        saving.value = false;
    }
};

const handleBack = () => {
    emit('navigate', '/master/user');
};

const handleEdit = () => {
    if (props.id) {
        emit('navigate', `/master/user/${props.id}/edit`);
    }
};

onMounted(async () => {
    const titles: Record<FormMode, string> = {
        create: 'Tambah User',
        edit: 'Edit User',
        view: 'Detail User',
    };
    uiStore.setPageTitle(titles[props.mode]);
    uiStore.setPageActions([]);

    await fetchOptions();

    if (props.id && (props.mode === 'edit' || props.mode === 'view')) {
        fetchData();
    }
});
</script>

<template>
    <FormPage
        :title="pageTitle"
        :mode="mode"
        :loading="loading"
        :saving="saving"
        @submit="handleSubmit"
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

                <BaseBrowse
                    v-model="form.branch_id"
                    :config="branchBrowseConfig"
                    :row-data="branchRowData"
                    label="Cabang"
                    placeholder="Pilih cabang..."
                    :error="formErrors.branch_id"
                    :disabled="readonly"
                    required
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
    </FormPage>
</template>
