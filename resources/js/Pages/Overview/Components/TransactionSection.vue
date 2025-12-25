<template>
    <div class="flex flex-col md:grid md:grid-cols-1 lg:grid-cols-3 gap-2 mb-2">
        <Widget
            title="Total Penjualan"
            :icon="faMoneyBill1Wave"
            class="widget-main"
            :traction="getTraction(totalSales.now, totalSales.previous)"
            :traction-percentage="
                getPercentage(totalSales.now, totalSales.previous)
            "
            :descriptors="getSubtitle(filter)"
        >
            <p class="text-md">{{ formatIDR(totalSales.now) }}</p>
        </Widget>

        <Widget
            title="Total Transaksi"
            :icon="faReceipt"
            :traction="
                getTraction(totalTransactions.now, totalTransactions.previous)
            "
            :traction-percentage="
                getPercentage(totalTransactions.now, totalTransactions.previous)
            "
            :descriptors="getSubtitle(filter)"
            class="widget-main"
        >
            <p class="text-md">{{ totalTransactions.now }} Transaksi</p>
        </Widget>

        <Widget
            title="Rata Rata per Transaksi"
            :icon="faChartLine"
            :traction="getTraction(averageSales.now, averageSales.previous)"
            :traction-percentage="
                getPercentage(averageSales.now, averageSales.previous)
            "
            :descriptors="getSubtitle(filter)"
            class="widget-main"
        >
            <p class="text-md">{{ formatIDR(averageSales.now) }}</p>
        </Widget>
    </div>
</template>
<script setup>
import Widget from '@/Components/Widgets/Widget.vue';
import { formatIDR } from '@/Composable/currency-format';

import {
    faChartLine,
    faMoneyBill1Wave,
    faReceipt,
} from '@fortawesome/free-solid-svg-icons';

defineProps({
    totalSales: {
        type: Number,
        default: {
            now: 0,
            previous: 0,
        },
    },
    totalTransactions: {
        type: Object,
        default: {
            now: 0,
            previous: 0,
        },
    },
    averageSales: {
        type: Object,
        default: {
            now: 0,
            previous: 0,
        },
    },
    filter: {
        type: String,
        default: 'month',
    },
});

const getTraction = (now, previous) => {
    if (now > previous) {
        return 'up';
    } else {
        return 'down';
    }
};

const getPercentage = (now, previous) => {
    if (previous === 0) {
        return 0;
    }
    return Math.round(((now - previous) / previous) * 100);
};

const getSubtitle = (filter) => {
    switch (filter) {
        case 'day':
            return 'dari kemarin';
        case 'week':
            return 'dari minggu lalu';
        case 'month':
            return 'dari bulan lalu';
        case 'year':
            return 'dari tahun lalu';
        default:
            return 'dari bulan lalu';
    }
};
</script>
