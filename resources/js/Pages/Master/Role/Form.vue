<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useUiStore } from '@/stores/ui';
import { FormPage } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox } from '@/Components/Form';
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

const form = ref({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const formErrors = ref<Record<string, string>>({});

const pageTitle = computed(() => {
    return 'Role';
});

const fetchData = async () => {
    if (!props.id) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/roles/${props.id}`);
        const data = response.data.data;
        form.value = {
            code: data.code,
            name: data.name,
            description: data.description || '',
            is_active: data.is_active,
        };
    } catch (error: any) {
        console.error('Failed to fetch role:', error);
        alert('Gagal memuat data role');
        emit('navigate', '/master/role');
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    formErrors.value = {};
    saving.value = true;

    try {
        if (props.mode === 'edit' && props.id) {
            await axios.put(`/api/roles/${props.id}`, form.value);
        } else {
            await axios.post('/api/roles', form.value);
        }
        emit('navigate', '/master/role');
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
    emit('navigate', '/master/role');
};

const handleEdit = () => {
    if (props.id) {
        emit('navigate', `/master/role/${props.id}/edit`);
    }
};

onMounted(() => {
    const titles: Record<FormMode, string> = {
        create: 'Tambah Role',
        edit: 'Edit Role',
        view: 'Detail Role',
    };
    uiStore.setPageTitle(titles[props.mode]);
    uiStore.setPageActions([]);

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
                    v-model="form.code"
                    label="Kode"
                    placeholder="Masukkan kode role"
                    :error="formErrors.code"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama role"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.description"
                        label="Deskripsi"
                        placeholder="Masukkan deskripsi role"
                        :error="formErrors.description"
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
    </FormPage>
</template>
