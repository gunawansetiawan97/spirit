<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    currentPage: number;
    perPage: number;
    total: number;
    perPageOptions?: number[];
}

const props = withDefaults(defineProps<Props>(), {
    perPageOptions: () => [10, 25, 50, 100],
});

const emit = defineEmits<{
    (e: 'update:currentPage', value: number): void;
    (e: 'update:perPage', value: number): void;
}>();

const lastPage = computed(() => Math.ceil(props.total / props.perPage));

const from = computed(() => {
    if (props.total === 0) return 0;
    return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => {
    const end = props.currentPage * props.perPage;
    return end > props.total ? props.total : end;
});

const pages = computed(() => {
    const range: (number | string)[] = [];
    const delta = 2;
    const left = props.currentPage - delta;
    const right = props.currentPage + delta + 1;

    let prev: number | null = null;

    for (let i = 1; i <= lastPage.value; i++) {
        if (i === 1 || i === lastPage.value || (i >= left && i < right)) {
            if (prev !== null && i - prev > 1) {
                range.push('...');
            }
            range.push(i);
            prev = i;
        }
    }

    return range;
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= lastPage.value) {
        emit('update:currentPage', page);
    }
};

const updatePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:perPage', Number(target.value));
    emit('update:currentPage', 1);
};
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <!-- Per page selector -->
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <span>Tampilkan</span>
            <select
                :value="perPage"
                class="rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                @change="updatePerPage"
            >
                <option v-for="option in perPageOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
            <span>data</span>
        </div>

        <!-- Info -->
        <div class="text-sm text-gray-600">
            <template v-if="total > 0">
                Menampilkan {{ from }} - {{ to }} dari {{ total }} data
            </template>
            <template v-else>
                Tidak ada data
            </template>
        </div>

        <!-- Pagination buttons -->
        <nav v-if="lastPage > 1" class="flex items-center gap-1">
            <!-- Previous -->
            <button
                type="button"
                :disabled="currentPage === 1"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="goToPage(currentPage - 1)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Page numbers -->
            <template v-for="page in pages" :key="page">
                <span v-if="page === '...'" class="px-2 text-gray-500">...</span>
                <button
                    v-else
                    type="button"
                    class="min-w-[2.5rem] rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        page === currentPage
                            ? 'border-primary-500 bg-primary-500 text-white'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    "
                    @click="goToPage(page as number)"
                >
                    {{ page }}
                </button>
            </template>

            <!-- Next -->
            <button
                type="button"
                :disabled="currentPage === lastPage"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="goToPage(currentPage + 1)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </nav>
    </div>
</template>
