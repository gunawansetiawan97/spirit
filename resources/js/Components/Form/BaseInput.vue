<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: string | number;
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
    placeholder?: string;
    disabled?: boolean;
    readonly?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    modelValue: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'blur', event: FocusEvent): void;
    (e: 'focus', event: FocusEvent): void;
}>();

const inputValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const inputClasses = computed(() => [
    'block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 placeholder-danger-300 focus:border-danger-500 focus:ring-danger-500'
        : 'border-gray-300 text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500',
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
            v-model="inputValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :readonly="readonly"
            :class="inputClasses"
            @blur="emit('blur', $event)"
            @focus="emit('focus', $event)"
        />
        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
