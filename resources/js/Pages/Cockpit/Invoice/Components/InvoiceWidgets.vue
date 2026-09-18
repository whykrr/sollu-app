<template>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <WidgetMini :icon="faFileInvoice" title="Total Tagihan" variant="main">
            {{ metrics.total_invoices || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Invoice</span>
        </WidgetMini>
        <WidgetMini
            :icon="faClock"
            title="Menunggu Verifikasi"
            variant="amber"
            value-class="text-amber-600"
        >
            {{ metrics.pending_validation || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Perlu Review</span>
        </WidgetMini>
        <WidgetMini
            :icon="faCheckCircle"
            title="Tagihan Lunas"
            variant="success"
            value-class="text-success"
        >
            {{ metrics.paid_invoices || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Selesai</span>
        </WidgetMini>
        <WidgetMini
            :icon="faTimesCircle"
            title="Ditolak"
            variant="danger"
            value-class="text-danger"
        >
            {{ metrics.rejected_invoices || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Dibatalkan</span>
        </WidgetMini>
    </div>
</template>

<script setup>
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import {
    faFileInvoice,
    faClock,
    faCheckCircle,
    faTimesCircle,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total_invoices: 0,
            pending_validation: 0,
            paid_invoices: 0,
            rejected_invoices: 0,
        }),
    },
})
</script>
