<script setup lang="ts">
import { computed } from 'vue';
import type { SelectOption } from '@/types';

interface Props {
    modelValue?: string | number | null;
    options: SelectOption[];
    name: string;
    label?: string;
    disabled?: boolean;
    error?: string;
    inline?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    inline: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', value: string | number | null): void;
}>();

const selectedValue = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
        emit('change', value);
    },
});

const containerClasses = computed(() =>
    props.inline ? 'flex flex-wrap gap-4' : 'space-y-2'
);
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="mb-2 block text-sm font-medium text-gray-700">
            {{ label }}
        </label>
        <div :class="containerClasses">
            <div
                v-for="option in options"
                :key="option.value"
                class="flex items-center"
            >
                <input
                    :id="`${name}-${option.value}`"
                    v-model="selectedValue"
                    type="radio"
                    :name="name"
                    :value="option.value"
                    :disabled="disabled || option.disabled"
                    class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <label
                    :for="`${name}-${option.value}`"
                    class="ml-2 text-sm font-medium text-gray-700"
                    :class="{ 'cursor-not-allowed opacity-50': disabled || option.disabled }"
                >
                    {{ option.label }}
                </label>
            </div>
        </div>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
