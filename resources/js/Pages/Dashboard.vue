<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';

const authStore = useAuthStore();
const uiStore = useUiStore();

onMounted(() => {
    uiStore.setPageTitle('Dashboard');
    uiStore.clearPageActions();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Welcome card -->
        <div class="rounded-lg bg-white p-6 shadow">
            <h2 class="text-xl font-semibold text-gray-900">
                Selamat datang, {{ authStore.user?.name }}!
            </h2>
            <p class="mt-1 text-gray-600">
                Anda login sebagai <span class="font-medium">{{ authStore.currentRole?.name }}</span>
                di cabang <span class="font-medium">{{ authStore.currentBranch?.name }}</span>
            </p>
        </div>

        <!-- Stats grid -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-100">
                        <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Penjualan</p>
                        <p class="text-2xl font-semibold text-gray-900">Rp 0</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-100">
                        <svg class="h-6 w-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pesanan Selesai</p>
                        <p class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-100">
                        <svg class="h-6 w-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pesanan Pending</p>
                        <p class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-100">
                        <svg class="h-6 w-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Stok Menipis</p>
                        <p class="text-2xl font-semibold text-gray-900">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="rounded-lg bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900">Aksi Cepat</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <button class="flex items-center rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50">
                    <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="ml-3 text-sm font-medium text-gray-900">Buat Penjualan</span>
                </button>
                <button class="flex items-center rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50">
                    <svg class="h-8 w-8 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="ml-3 text-sm font-medium text-gray-900">Buat Pembelian</span>
                </button>
                <button class="flex items-center rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50">
                    <svg class="h-8 w-8 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="ml-3 text-sm font-medium text-gray-900">Cek Stok</span>
                </button>
                <button class="flex items-center rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50">
                    <svg class="h-8 w-8 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="ml-3 text-sm font-medium text-gray-900">Lihat Laporan</span>
                </button>
            </div>
        </div>
    </div>
</template>
