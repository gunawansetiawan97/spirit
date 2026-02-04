<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';

interface Props {
    modelValue: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    closable?: boolean;
    closeOnEscape?: boolean;
    closeOnOverlay?: boolean;
    persistent?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    closable: true,
    closeOnEscape: true,
    closeOnOverlay: true,
    persistent: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'close'): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const close = () => {
    if (props.closable && !props.persistent) {
        isOpen.value = false;
        emit('close');
    }
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.closeOnEscape && isOpen.value) {
        close();
    }
};

const handleOverlayClick = () => {
    if (props.closeOnOverlay) {
        close();
    }
};

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        full: 'max-w-full mx-4',
    };
    return sizes[props.size];
});

watch(isOpen, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <!-- Overlay -->
                <div
                    class="fixed inset-0 bg-black/50 transition-opacity"
                    @click="handleOverlayClick"
                />

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="isOpen"
                            class="relative w-full transform rounded-lg bg-white shadow-xl transition-all"
                            :class="sizeClasses"
                            @click.stop
                        >
                            <!-- Header -->
                            <div v-if="title || closable || $slots.header" class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                                <slot name="header">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ title }}
                                    </h3>
                                </slot>
                                <button
                                    v-if="closable"
                                    type="button"
                                    class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    @click="close"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4">
                                <slot />
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="border-t border-gray-200 px-6 py-4">
                                <slot name="footer" />
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
