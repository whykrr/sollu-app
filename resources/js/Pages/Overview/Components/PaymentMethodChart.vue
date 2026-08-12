<template>
    <div class="p-4 bg-white rounded-md border border-neutral-200 flex flex-col gap-3 h-full shadow-xs">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-neutral-800 flex items-center gap-2">
                    <FontAwesomeIcon :icon="faCreditCard" class="text-main" />
                    Ringkasan Metode Pembayaran
                </h3>
                <p class="text-xs text-neutral-500">
                    Proporsi penerimaan uang berdasarkan jenis pembayaran
                </p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center flex-1">
            <div class="relative min-h-[200px] flex items-center justify-center">
                <canvas id="chart-payment-method" class="w-full max-h-[220px]"></canvas>
            </div>
            <div class="flex flex-col gap-2">
                <div
                    v-for="(method, index) in paymentMethods.label"
                    :key="index"
                    class="flex items-center justify-between p-2 rounded-md bg-neutral-50 border border-neutral-100"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full shrink-0"
                            :style="{ backgroundColor: getMethodColor(index) }"
                        ></span>
                        <span class="text-xs font-medium text-neutral-700">{{ method }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-neutral-900 block">
                            {{ paymentMethods.value?.[index] }}%
                        </span>
                        <span class="text-[10px] text-neutral-500 block" v-if="paymentMethods.revenue?.[index]">
                            {{ formatIDR(paymentMethods.revenue[index]) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { Chart } from 'chart.js/auto';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCreditCard } from '@fortawesome/free-solid-svg-icons';
import { formatIDR } from '@/Composable/currency-format';

const props = defineProps({
    paymentMethods: {
        type: Object,
        default: () => ({
            label: [],
            value: [],
            revenue: [],
        }),
    },
});

const colors = ['#10B981', '#004AAD', '#3B82F6', '#F59E0B'];

const getMethodColor = (index) => colors[index % colors.length];

let chartInstance = null;

const renderChart = () => {
    const ctx = document.getElementById('chart-payment-method');
    if (!ctx) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: props.paymentMethods.label || [],
            datasets: [
                {
                    data: props.paymentMethods.value || [],
                    backgroundColor: colors.slice(0, props.paymentMethods.label?.length || 4),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const index = context.dataIndex;
                            const percentage = context.parsed;
                            const revenue = props.paymentMethods.revenue?.[index];
                            return revenue
                                ? ` ${context.label}: ${percentage}% (${formatIDR(revenue)})`
                                : ` ${context.label}: ${percentage}%`;
                        },
                    },
                },
            },
        },
    });
};

onMounted(() => {
    renderChart();
});

watch(() => props.paymentMethods, () => {
    renderChart();
}, { deep: true });
</script>
