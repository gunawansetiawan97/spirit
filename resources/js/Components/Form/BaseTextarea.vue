<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
    disabled?: boolean;
    readonly?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
    rows?: number;
    maxlength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    rows: 3,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'blur', event: FocusEvent): void;
}>();

const textareaValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const textareaClasses = computed(() => [
    'block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors resize-y',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 placeholder-danger-300 focus:border-danger-500 focus:ring-danger-500'
        : 'border-gray-300 text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500',
    props.disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
]);

const characterCount = computed(() => {
    if (!props.maxlength) return null;
    return `${props.modelValue?.length || 0}/${props.maxlength}`;
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>
        <textarea
            :id="id"
            v-model="textareaValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :readonly="readonly"
            :rows="rows"
            :maxlength="maxlength"
            :class="textareaClasses"
            @blur="emit('blur', $event)"
        />
        <div class="mt-1 flex justify-between">
            <p v-if="helpText && !error" class="text-sm text-gray-500">
                {{ helpText }}
            </p>
            <p v-if="error" class="text-sm text-danger-500">
                {{ error }}
            </p>
            <span v-if="characterCount" class="text-sm text-gray-400">
                {{ characterCount }}
            </span>
        </div>
    </div>
</template>
