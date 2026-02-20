<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

interface AuditData {
    created_by?: { id: number; name: string } | null;
    updated_by?: { id: number; name: string } | null;
    approved_by?: { id: number; name: string } | null;
    printed_by?: { id: number; name: string } | null;
    created_at?: string | null;
    updated_at?: string | null;
    approved_at?: string | null;
    printed_at?: string | null;
}

interface ActivityLog {
    id: number;
    user: { id: number; name: string } | null;
    action: string;
    changes: { old: Record<string, any>; new: Record<string, any> } | null;
    ip_address: string | null;
    created_at: string;
}

interface Props {
    loggableType: string;
    loggableId: string | number;
    auditData?: AuditData;
}

const props = defineProps<Props>();

const logs = ref<ActivityLog[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const formatDate = (dateStr: string | null | undefined): string => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const actionLabels: Record<string, string> = {
    created: 'membuat record',
    updated: 'mengupdate record',
    deleted: 'menghapus record',
    approved: 'approve record',
    unapproved: 'unapprove record',
    printed: 'mencetak record',
    children_updated: 'mengubah detail record',
};

const actionColors: Record<string, string> = {
    created: 'bg-green-500',
    updated: 'bg-blue-500',
    deleted: 'bg-red-500',
    approved: 'bg-emerald-500',
    unapproved: 'bg-yellow-500',
    printed: 'bg-purple-500',
    children_updated: 'bg-indigo-500',
};

const formatValue = (value: any): string => {
    if (value === null || value === undefined) return '(kosong)';
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
    return String(value);
};

const fetchLogs = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/activity-logs', {
            params: {
                loggable_type: props.loggableType,
                loggable_id: props.loggableId,
                page,
                per_page: 20,
            },
        });
        logs.value = response.data.data;
        currentPage.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
        total.value = response.data.meta.total;
    } catch (error) {
        console.error('Failed to fetch activity logs:', error);
    } finally {
        loading.value = false;
    }
};

watch(() => [props.loggableType, props.loggableId], () => {
    if (props.loggableId) {
        fetchLogs(1);
    }
});

onMounted(() => {
    if (props.loggableId) {
        fetchLogs(1);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Audit Summary -->
        <div>
            <h3 class="mb-4 text-base font-semibold text-gray-900">Informasi Audit</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Created -->
                <div class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Dibuat oleh</p>
                        <p class="text-sm font-semibold text-gray-900">{{ auditData?.created_by?.name || '-' }}</p>
                        <p class="text-xs text-gray-500">{{ formatDate(auditData?.created_at) }}</p>
                    </div>
                </div>

                <!-- Updated -->
                <div class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Diupdate oleh</p>
                        <p class="text-sm font-semibold text-gray-900">{{ auditData?.updated_by?.name || '-' }}</p>
                        <p class="text-xs text-gray-500">{{ formatDate(auditData?.updated_at) }}</p>
                    </div>
                </div>

                <!-- Approved -->
                <div v-if="auditData?.approved_at !== undefined" class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full" :class="auditData?.approved_at ? 'bg-emerald-100' : 'bg-gray-100'">
                        <svg v-if="auditData?.approved_at" class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Status Approval</p>
                        <p class="text-sm font-semibold" :class="auditData?.approved_at ? 'text-emerald-600' : 'text-gray-500'">
                            {{ auditData?.approved_at ? 'Approved' : 'Belum Approved' }}
                        </p>
                        <template v-if="auditData?.approved_at">
                            <p class="text-xs text-gray-600">{{ auditData?.approved_by?.name || '-' }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(auditData?.approved_at) }}</p>
                        </template>
                    </div>
                </div>

                <!-- Printed -->
                <div v-if="auditData?.printed_at !== undefined" class="flex items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full" :class="auditData?.printed_at ? 'bg-purple-100' : 'bg-gray-100'">
                        <svg class="h-4 w-4" :class="auditData?.printed_at ? 'text-purple-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Status Cetak</p>
                        <p class="text-sm font-semibold" :class="auditData?.printed_at ? 'text-purple-600' : 'text-gray-500'">
                            {{ auditData?.printed_at ? 'Sudah Dicetak' : 'Belum Dicetak' }}
                        </p>
                        <template v-if="auditData?.printed_at">
                            <p class="text-xs text-gray-600">{{ auditData?.printed_by?.name || '-' }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(auditData?.printed_at) }}</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div>
            <h3 class="mb-4 text-base font-semibold text-gray-900">
                Riwayat Aktivitas
                <span v-if="total" class="ml-1 text-sm font-normal text-gray-500">({{ total }})</span>
            </h3>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <svg class="h-6 w-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <!-- Empty -->
            <div v-else-if="logs.length === 0" class="py-8 text-center text-sm text-gray-500">
                Belum ada riwayat aktivitas
            </div>

            <!-- Timeline -->
            <div v-else class="relative">
                <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200" />

                <div v-for="(log, index) in logs" :key="log.id" class="relative mb-4 ml-10">
                    <!-- Dot -->
                    <div
                        class="absolute -left-[1.625rem] top-1 h-3 w-3 rounded-full border-2 border-white"
                        :class="actionColors[log.action] || 'bg-gray-400'"
                    />

                    <!-- Content -->
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-900">{{ log.user?.name || 'System' }}</span>
                                <span class="ml-1 text-sm text-gray-600">{{ actionLabels[log.action] || log.action }}</span>
                            </div>
                            <span class="flex-shrink-0 text-xs text-gray-500">{{ formatDate(log.created_at) }}</span>
                        </div>

                        <!-- Changes detail (field updates & children) -->
                        <div v-if="log.changes && (log.action === 'updated' || log.action === 'children_updated')" class="mt-2 space-y-1">
                            <div
                                v-for="(newVal, key) in log.changes.new"
                                :key="String(key)"
                                class="flex items-baseline gap-2 text-xs"
                            >
                                <span class="font-medium text-gray-600">{{ key }}:</span>
                                <span class="text-red-500 line-through">{{ formatValue(log.changes.old[key as string]) }}</span>
                                <svg class="h-3 w-3 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span class="text-green-600">{{ formatValue(newVal) }}</span>
                            </div>
                        </div>

                        <!-- IP Address -->
                        <div v-if="log.ip_address" class="mt-1 text-xs text-gray-400">
                            IP: {{ log.ip_address }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="lastPage > 1" class="mt-4 flex items-center justify-center gap-2">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="currentPage <= 1"
                    @click="fetchLogs(currentPage - 1)"
                >
                    Sebelumnya
                </button>
                <span class="text-sm text-gray-500">{{ currentPage }} / {{ lastPage }}</span>
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="currentPage >= lastPage"
                    @click="fetchLogs(currentPage + 1)"
                >
                    Selanjutnya
                </button>
            </div>
        </div>
    </div>
</template>
