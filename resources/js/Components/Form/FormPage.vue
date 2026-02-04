<script setup lang="ts">
import { computed } from 'vue';
import BaseButton from './BaseButton.vue';

type FormMode = 'create' | 'edit' | 'view';

interface Props {
    title: string;
    mode: FormMode;
    loading?: boolean;
    saving?: boolean;
    backRoute?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    saving: false,
    backRoute: '',
});

const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'back'): void;
    (e: 'edit'): void;
}>();

const pageTitle = computed(() => {
    const titles: Record<FormMode, string> = {
        create: `Tambah ${props.title}`,
        edit: `Edit ${props.title}`,
        view: `Detail ${props.title}`,
    };
    return titles[props.mode];
});

const isReadonly = computed(() => props.mode === 'view');
const showSubmit = computed(() => props.mode !== 'view');
const showEdit = computed(() => props.mode === 'view');

const handleBack = () => {
    emit('back');
};

const handleSubmit = () => {
    emit('submit');
};

const handleEdit = () => {
    emit('edit');
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Header with action buttons -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                        @click="handleBack"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-900">{{ pageTitle }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <slot name="header-actions" />

                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="handleBack"
                    >
                        {{ isReadonly ? 'Kembali' : 'Batal' }}
                    </BaseButton>

                    <BaseButton
                        v-if="showEdit"
                        type="button"
                        variant="primary"
                        @click="handleEdit"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </BaseButton>

                    <BaseButton
                        v-if="showSubmit"
                        type="submit"
                        form="form-page"
                        variant="primary"
                        :loading="saving"
                        :disabled="saving"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ mode === 'create' ? 'Simpan' : 'Update' }}
                    </BaseButton>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <svg class="h-8 w-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="ml-2 text-gray-500">Memuat data...</span>
            </div>

            <!-- Form content -->
            <form v-else id="form-page" @submit.prevent="handleSubmit">
                <div class="rounded-lg bg-white p-6 shadow">
                    <slot :readonly="isReadonly" :mode="mode" />
                </div>
            </form>
        </div>
    </div>
</template>
