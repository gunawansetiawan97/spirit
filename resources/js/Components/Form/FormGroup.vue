<script setup lang="ts">
interface Props {
    label?: string;
    error?: string;
    required?: boolean;
    helpText?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-4">
        <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>
        <slot />
        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
