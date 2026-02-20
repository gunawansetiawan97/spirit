<script setup lang="ts">
import { useIndexPage } from '@/Composables';

const emit = defineEmits<{ (e: 'navigate', route: string): void }>();

const { loading, rows, meta, filters, sort, handleSearch, handlePageChange, handleSort, handleRowClick, handleCreate } =
    useIndexPage({
        apiEndpoint: '/api/warehouses',
        basePath: '/master/warehouse',
        searchFields: ['code', 'name'],
    }, emit);
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex items-center justify-between gap-4">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Cari kode / nama..."
                class="w-64 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                @input="handleSearch"
            />
            <button
                type="button"
                class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                @click="handleCreate"
            >
                + Tambah Gudang
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading">
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Memuat...</td>
                    </tr>
                    <tr v-else-if="rows.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
                    </tr>
                    <tr
                        v-for="row in rows"
                        v-else
                        :key="row.id"
                        class="cursor-pointer hover:bg-gray-50"
                        @click="handleRowClick(row)"
                    >
                        <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ row.code }}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-700">{{ row.name }}</td>
                        <td class="px-4 py-2.5 text-sm">
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            >
                                {{ row.is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
            <span>{{ meta.total }} data</span>
            <div class="flex gap-2">
                <button :disabled="meta.current_page <= 1" class="rounded border px-3 py-1 disabled:opacity-40" @click="handlePageChange(meta.current_page - 1)">‹</button>
                <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button :disabled="meta.current_page >= meta.last_page" class="rounded border px-3 py-1 disabled:opacity-40" @click="handlePageChange(meta.current_page + 1)">›</button>
            </div>
        </div>
    </div>
</template>
