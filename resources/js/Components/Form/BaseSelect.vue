<script setup lang="ts">
import { computed } from 'vue';
import type { SelectOption } from '@/types';

interface Props {
    modelValue?: string | number | null;
    options: SelectOption[];
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    placeholder: 'Pilih...',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', value: string | number | null): void;
}>();

const selectValue = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
        emit('change', value);
    },
});

const selectClasses = computed(() => [
    'block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 focus:border-danger-500 focus:ring-danger-500'
        : 'border-gray-300 text-gray-900 focus:border-primary-500 focus:ring-primary-500',
    props.disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
]);
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>
        <select
            :id="id"
            v-model="selectValue"
            :disabled="disabled"
            :class="selectClasses"
        >
            <option v-if="placeholder" :value="null" disabled>
                {{ placeholder }}
            </option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
