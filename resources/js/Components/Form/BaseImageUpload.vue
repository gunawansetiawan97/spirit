<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue';

export interface ExistingImage {
    id: number;
    image_path: string;
    sort_order?: number;
}

interface Props {
    /** Existing images from server */
    existingImages?: ExistingImage[];
    /** New File objects to upload */
    newFiles?: File[];
    /** IDs of removed existing images */
    deletedIds?: number[];
    /** Label */
    label?: string;
    /** Disabled state (view mode) */
    disabled?: boolean;
    /** Error message */
    error?: string;
    /** URL prefix for existing images (default: '/storage/') */
    pathPrefix?: string;
    /** Accept file types (default: 'image/*') */
    accept?: string;
}

const props = withDefaults(defineProps<Props>(), {
    existingImages: () => [],
    newFiles: () => [],
    deletedIds: () => [],
    pathPrefix: '/storage/',
    accept: 'image/*',
});

const emit = defineEmits<{
    (e: 'update:existingImages', value: ExistingImage[]): void;
    (e: 'update:newFiles', value: File[]): void;
    (e: 'update:deletedIds', value: number[]): void;
}>();

// Internal preview URLs for new files
const previewUrls = ref<string[]>([]);

// Build initial preview URLs for any pre-existing newFiles
const syncPreviews = () => {
    // Clean up old URLs
    previewUrls.value.forEach(url => URL.revokeObjectURL(url));
    previewUrls.value = props.newFiles.map(file => URL.createObjectURL(file));
};

// Cleanup on unmount
onBeforeUnmount(() => {
    previewUrls.value.forEach(url => URL.revokeObjectURL(url));
});

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files) return;

    const files = Array.from(input.files);
    const updatedFiles = [...props.newFiles, ...files];
    const newUrls = files.map(f => URL.createObjectURL(f));
    previewUrls.value = [...previewUrls.value, ...newUrls];

    emit('update:newFiles', updatedFiles);
    input.value = '';
};

const removeExisting = (image: ExistingImage) => {
    emit('update:deletedIds', [...props.deletedIds, image.id]);
    emit('update:existingImages', props.existingImages.filter(i => i.id !== image.id));
};

const removeNew = (index: number) => {
    URL.revokeObjectURL(previewUrls.value[index]);
    previewUrls.value.splice(index, 1);

    const updatedFiles = [...props.newFiles];
    updatedFiles.splice(index, 1);
    emit('update:newFiles', updatedFiles);
};

const getImageUrl = (image: ExistingImage): string => {
    return `${props.pathPrefix}${image.image_path}`;
};
</script>

<template>
    <div>
        <label v-if="label" class="mb-2 block text-sm font-medium text-gray-700">
            {{ label }}
        </label>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            <!-- Existing images -->
            <div
                v-for="image in existingImages"
                :key="'existing-' + image.id"
                class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
            >
                <img
                    :src="getImageUrl(image)"
                    class="h-full w-full object-cover"
                    alt="Image"
                    loading="lazy"
                />
                <button
                    v-if="!disabled"
                    type="button"
                    class="absolute right-1 top-1 rounded-full bg-red-500 p-1 text-white opacity-0 shadow transition-opacity group-hover:opacity-100"
                    @click="removeExisting(image)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- New image previews -->
            <div
                v-for="(url, index) in previewUrls"
                :key="'new-' + index"
                class="group relative aspect-square overflow-hidden rounded-lg border border-dashed border-primary-300 bg-gray-50"
            >
                <img
                    :src="url"
                    class="h-full w-full object-cover"
                    alt="New image"
                />
                <button
                    v-if="!disabled"
                    type="button"
                    class="absolute right-1 top-1 rounded-full bg-red-500 p-1 text-white opacity-0 shadow transition-opacity group-hover:opacity-100"
                    @click="removeNew(index)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Add button -->
            <label
                v-if="!disabled"
                class="flex aspect-square cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:border-primary-400 hover:bg-primary-50"
            >
                <div class="text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="mt-1 block text-xs text-gray-500">Tambah</span>
                </div>
                <input
                    type="file"
                    :accept="accept"
                    multiple
                    class="hidden"
                    @change="handleFileSelect"
                />
            </label>

            <!-- Empty state -->
            <div
                v-if="disabled && existingImages.length === 0 && previewUrls.length === 0"
                class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-gray-200"
            >
                <span class="text-xs text-gray-400">Tidak ada gambar</span>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
