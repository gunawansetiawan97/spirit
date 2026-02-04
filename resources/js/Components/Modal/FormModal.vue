<script setup lang="ts">
import { computed } from 'vue';
import BaseModal from './BaseModal.vue';
import { BaseButton } from '../Form';

interface Props {
    modelValue: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    submitText?: string;
    cancelText?: string;
    loading?: boolean;
    submitDisabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    submitText: 'Simpan',
    cancelText: 'Batal',
    loading: false,
    submitDisabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'submit'): void;
    (e: 'cancel'): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const handleSubmit = () => {
    emit('submit');
};

const handleCancel = () => {
    isOpen.value = false;
    emit('cancel');
};
</script>

<template>
    <BaseModal
        v-model="isOpen"
        :title="title"
        :size="size"
        :closable="!loading"
        :close-on-escape="!loading"
        :close-on-overlay="!loading"
    >
        <form @submit.prevent="handleSubmit">
            <slot />
        </form>

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
                    type="submit"
                    variant="primary"
                    :loading="loading"
                    :disabled="submitDisabled"
                    @click="handleSubmit"
                >
                    {{ submitText }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>
