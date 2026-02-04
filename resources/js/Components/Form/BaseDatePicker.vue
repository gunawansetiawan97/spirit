<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
    min?: string;
    max?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'change', value: string): void;
}>();

const dateValue = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
        emit('change', value);
    },
});

const inputClasses = computed(() => [
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
        <input
            :id="id"
            v-model="dateValue"
            type="date"
            :placeholder="placeholder"
            :disabled="disabled"
            :min="min"
            :max="max"
            :class="inputClasses"
        />
        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
