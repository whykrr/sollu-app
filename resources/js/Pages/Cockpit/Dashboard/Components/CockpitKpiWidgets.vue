<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <!-- Pendapatan Langganan (MRR / Periode) -->
        <Widget :icon="faMoneyBillWave" title="Pendapatan Langganan">
            <span class="font-bold text-neutral-800 text-lg">
                {{ formatIDR(metrics.current_revenue || 0) }}
            </span>
            <div class="flex items-center gap-1 mt-1 text-xs">
                <span
                    v-if="(metrics.revenue_growth_percent || 0) >= 0"
                    class="font-semibold text-emerald-600 flex items-center gap-0.5"
                >
                    <FontAwesomeIcon :icon="faArrowTrendUp" class="text-[10px]" />
                    +{{ metrics.revenue_growth_percent || 0 }}%
                </span>
                <span v-else class="font-semibold text-rose-600 flex items-center gap-0.5">
                    <FontAwesomeIcon :icon="faArrowTrendDown" class="text-[10px]" />
                    {{ metrics.revenue_growth_percent }}%
                </span>
                <span class="text-neutral-400">vs periode lalu</span>
            </div>
        </Widget>

        <!-- Merchant Aktif & Trial -->
        <Widget :icon="faStore" title="Merchant Aktif">
            <div class="flex items-baseline gap-1">
                <span class="font-bold text-emerald-700 text-lg">
                    {{ metrics.active_merchants || 0 }}
                </span>
                <span class="text-xs text-neutral-500">Bisnis</span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500">
                <span class="inline-flex items-center gap-1 text-amber-600 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    {{ metrics.trial_merchants || 0 }} Trial
                </span>
                <span class="text-neutral-300">•</span>
                <span class="text-neutral-400">
                    {{ metrics.suspended_merchants || 0 }} Nonaktif
                </span>
            </div>
        </Widget>

        <!-- Total Cabang Outlet -->
        <Widget :icon="faBuilding" title="Total Cabang Outlet">
            <div class="flex items-baseline gap-1">
                <span class="font-bold text-sky-700 text-lg">
                    {{ metrics.total_outlets || 0 }}
                </span>
                <span class="text-xs text-neutral-500">Cabang</span>
            </div>
            <div class="text-xs text-neutral-400 mt-1">
                Dari {{ metrics.total_merchants || 0 }} total bisnis terdaftar
            </div>
        </Widget>

        <!-- Antrean Verifikasi Pembayaran -->
        <Widget :icon="faClockRotateLeft" title="Verifikasi Pembayaran">
            <div class="flex items-baseline gap-1">
                <span
                    class="font-bold text-lg"
                    :class="
                        (metrics.pending_invoices_count || 0) > 0
                            ? 'text-amber-600'
                            : 'text-neutral-800'
                    "
                >
                    {{ metrics.pending_invoices_count || 0 }}
                </span>
                <span class="text-xs text-neutral-500">Invoice</span>
            </div>
            <div class="text-xs mt-1">
                <span
                    v-if="(metrics.pending_invoices_count || 0) > 0"
                    class="text-amber-700 font-medium flex items-center gap-1"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Perlu validasi manual
                </span>
                <span v-else class="text-emerald-600 font-medium">
                    Semua transaksi telah diverifikasi
                </span>
            </div>
        </Widget>
    </div>
</template>

<script setup>
import Widget from '@/Components/Widgets/Widget.vue'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faMoneyBillWave,
    faStore,
    faBuilding,
    faClockRotateLeft,
    faArrowTrendUp,
    faArrowTrendDown,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            current_revenue: 0,
            previous_revenue: 0,
            revenue_growth_percent: 0,
            total_merchants: 0,
            active_merchants: 0,
            suspended_merchants: 0,
            active_subscribers: 0,
            trial_merchants: 0,
            total_outlets: 0,
            pending_invoices_count: 0,
        }),
    },
})
</script>
