<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
        <Widget
            title="Total Omset Kotor"
            :icon="faMoneyBill1Wave"
            class="widget-main"
            :traction="getTraction(totalSales.now, totalSales.previous)"
            :traction-percentage="getPercentage(totalSales.now, totalSales.previous)"
            :descriptors="descriptors"
        >
            <p class="text-lg font-bold text-neutral-900">{{ formatIDR(totalSales.now) }}</p>
        </Widget>



        <Widget
            title="Total Transaksi"
            :icon="faReceipt"
            class="widget-main"
            :traction="getTraction(totalTransactions.now, totalTransactions.previous)"
            :traction-percentage="getPercentage(totalTransactions.now, totalTransactions.previous)"
            :descriptors="descriptors"
        >
            <p class="text-lg font-bold text-neutral-900">{{ totalTransactions.now }} Transaksi</p>
        </Widget>

        <Widget
            title="Rata Rata per Transaksi"
            :icon="faChartLine"
            class="widget-main"
            :traction="getTraction(averageSales.now, averageSales.previous)"
            :traction-percentage="getPercentage(averageSales.now, averageSales.previous)"
            :descriptors="descriptors"
        >
            <p class="text-lg font-bold text-neutral-900">{{ formatIDR(averageSales.now) }}</p>
        </Widget>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Widget from '@/Components/Widgets/Widget.vue';
import { formatIDR } from '@/Composable/currency-format';
import {
    faChartLine,
    faCoins,
    faMoneyBill1Wave,
    faReceipt,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    totalSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0 }),
    },

    totalTransactions: {
        type: Object,
        default: () => ({ now: 0, previous: 0 }),
    },
    averageSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0 }),
    },
    periodLabel: {
        type: String,
        default: 'bulan ini',
    },
});

const descriptors = computed(() => `vs ${props.periodLabel}`);

const getTraction = (now, previous) => {
    return now >= previous ? 'up' : 'down';
};

const getPercentage = (now, previous) => {
    if (!previous || previous === 0) return 0;
    return Math.abs(Math.round(((now - previous) / previous) * 100));
};
</script>
