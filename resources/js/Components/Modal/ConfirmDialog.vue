<script setup lang="ts">
import { computed } from 'vue';
import BaseModal from './BaseModal.vue';
import { BaseButton } from '../Form';

interface Props {
    modelValue: boolean;
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'info' | 'warning' | 'danger';
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Konfirmasi',
    confirmText: 'Ya',
    cancelText: 'Batal',
    variant: 'info',
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    isOpen.value = false;
    emit('cancel');
};

const iconColor = computed(() => {
    const colors = {
        info: 'text-primary-600 bg-primary-100',
        warning: 'text-warning-600 bg-warning-100',
        danger: 'text-danger-600 bg-danger-100',
    };
    return colors[props.variant];
});

const confirmButtonVariant = computed(() => {
    const variants = {
        info: 'primary',
        warning: 'warning',
        danger: 'danger',
    } as const;
    return variants[props.variant];
});
</script>

<template>
    <BaseModal
        v-model="isOpen"
        size="sm"
        :closable="!loading"
        :close-on-escape="!loading"
        :close-on-overlay="!loading"
    >
        <div class="text-center sm:text-left">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full sm:mx-0" :class="iconColor">
                <!-- Warning Icon -->
                <svg v-if="variant === 'warning'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <!-- Danger Icon -->
                <svg v-else-if="variant === 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <!-- Info Icon -->
                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-3 sm:ml-4 sm:mt-0">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ title }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    {{ message }}
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <BaseButton
                    variant="secondary"
                    :disabled="loading"
                    @click="handleCancel"
                >
                    {{ cancelText }}
                </BaseButton>
                <BaseButton
                    :variant="confirmButtonVariant"
                    :loading="loading"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>
