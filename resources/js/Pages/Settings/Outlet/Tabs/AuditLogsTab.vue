<template>
    <div class="flex flex-col gap-4 p-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Catatan Aktivitas (Audit Logs)</h3>
            <p class="text-sm text-slate-500 mb-4">Riwayat perubahan dan aktivitas pada outlet ini (50 aktivitas terakhir).</p>
        </div>

        <div v-if="!outlet?.audit_logs || outlet.audit_logs.length === 0" class="text-center py-12 text-slate-500">
            Belum ada aktivitas tercatat.
        </div>

        <div v-else class="relative pl-4 border-l-2 border-slate-200 space-y-6 mt-4">
            <div v-for="log in outlet.audit_logs" :key="log.id" class="relative">
                <!-- Timeline dot -->
                <div class="absolute -left-[25px] mt-1.5 w-4 h-4 rounded-full bg-white border-2 border-main flex items-center justify-center shadow-sm"></div>
                
                <div class="bg-white border rounded-lg p-4 shadow-sm ml-2">
                    <div class="flex justify-between items-start gap-4 mb-2">
                        <div class="font-semibold text-slate-800 capitalize">{{ formatAction(log.action) }}</div>
                        <div class="text-xs text-slate-400 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</div>
                    </div>
                    
                    <div class="text-sm text-slate-600 mb-2">
                        Oleh: <span class="font-medium text-slate-700">{{ log.user?.name || 'Sistem' }}</span>
                    </div>

                    <!-- Optional metadata details -->
                    <div v-if="log.metadata" class="text-xs bg-slate-50 p-2 rounded border font-mono text-slate-600 overflow-x-auto">
                        <pre>{{ formatMetadata(log.metadata) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { formatDateTime } from '@/Composable/time';

defineProps({
    outlet: Object,
});

const formatAction = (action) => {
    if (!action) return '-';
    return action.replace(/_/g, ' ');
};

const formatMetadata = (metadata) => {
    if (!metadata) return '';
    try {
        return JSON.stringify(metadata, null, 2);
    } catch (e) {
        return metadata;
    }
};
</script>
