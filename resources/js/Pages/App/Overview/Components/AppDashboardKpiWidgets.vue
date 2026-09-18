<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <!-- Total Omset Kotor -->
        <Widget :icon="faMoneyBillWave" title="Total Omset Kotor">
            <span class="font-bold text-neutral-800 text-lg">
                {{ formatIDR(totalSales?.now || 0) }}
            </span>
            <div class="flex items-center gap-1 mt-1 text-xs">
                <span
                    v-if="(totalSales?.growth || 0) >= 0"
                    class="font-semibold text-emerald-600 flex items-center gap-0.5"
                >
                    <FontAwesomeIcon :icon="faArrowTrendUp" class="text-[10px]" />
                    +{{ totalSales?.growth || 0 }}%
                </span>
                <span v-else class="font-semibold text-rose-600 flex items-center gap-0.5">
                    <FontAwesomeIcon :icon="faArrowTrendDown" class="text-[10px]" />
                    {{ totalSales?.growth }}%
                </span>
                <span class="text-neutral-400">vs {{ periodLabel }}</span>
            </div>
        </Widget>

        <!-- Total Transaksi -->
        <Widget :icon="faReceipt" title="Total Transaksi">
            <div class="flex items-baseline gap-1">
                <span class="font-bold text-neutral-800 text-lg">
                    {{ totalTransactions?.now || 0 }}
                </span>
                <span class="text-xs text-neutral-500">Transaksi</span>
            </div>
            <div class="flex items-center gap-1 mt-1 text-xs">
                <span
                    v-if="(totalTransactions?.growth || 0) >= 0"
                    class="font-semibold text-emerald-600 flex items-center gap-0.5"
                >
                    <FontAwesomeIcon :icon="faArrowTrendUp" class="text-[10px]" />
                    +{{ totalTransactions?.growth || 0 }}%
                </span>
                <span v-else class="font-semibold text-rose-600 flex items-center gap-0.5">
                    <FontAwesomeIcon :icon="faArrowTrendDown" class="text-[10px]" />
                    {{ totalTransactions?.growth }}%
                </span>
                <span class="text-neutral-400">vs {{ periodLabel }}</span>
            </div>
        </Widget>

        <!-- Rata-rata per Transaksi (AOV) -->
        <Widget :icon="faChartLine" title="Rata-rata Nilai Transaksi">
            <span class="font-bold text-neutral-800 text-lg">
                {{ formatIDR(averageSales?.now || 0) }}
            </span>
            <div class="flex items-center gap-1 mt-1 text-xs">
                <span
                    v-if="(averageSales?.growth || 0) >= 0"
                    class="font-semibold text-emerald-600 flex items-center gap-0.5"
                >
                    <FontAwesomeIcon :icon="faArrowTrendUp" class="text-[10px]" />
                    +{{ averageSales?.growth || 0 }}%
                </span>
                <span v-else class="font-semibold text-rose-600 flex items-center gap-0.5">
                    <FontAwesomeIcon :icon="faArrowTrendDown" class="text-[10px]" />
                    {{ averageSales?.growth }}%
                </span>
                <span class="text-neutral-400">vs {{ periodLabel }}</span>
            </div>
        </Widget>

        <!-- Peringatan Stok Rendah -->
        <Widget :icon="faBoxesStacked" title="Peringatan Stok Rendah">
            <div class="flex items-baseline gap-1">
                <span
                    class="font-bold text-lg"
                    :class="(lowStockCount || 0) > 0 ? 'text-amber-600' : 'text-neutral-800'"
                >
                    {{ lowStockCount || 0 }}
                </span>
                <span class="text-xs text-neutral-500">Item Bahan/Produk</span>
            </div>
            <div class="text-xs mt-1">
                <span
                    v-if="(lowStockCount || 0) > 0"
                    class="text-amber-700 font-medium flex items-center gap-1"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Perlu pengadaan restock
                </span>
                <span v-else class="text-emerald-600 font-medium"> Stok dalam batas aman </span>
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
    faReceipt,
    faChartLine,
    faBoxesStacked,
    faArrowTrendUp,
    faArrowTrendDown,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    totalSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    totalTransactions: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    averageSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    lowStockCount: {
        type: Number,
        default: 0,
    },
    periodLabel: {
        type: String,
        default: 'periode lalu',
    },
})
</script>
