<template>
    <div class="p-4 bg-white rounded-md border border-neutral-200 flex flex-col gap-3 h-full shadow-xs">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-neutral-800 flex items-center gap-2">
                    <FontAwesomeIcon :icon="faLayerGroup" class="text-main" />
                    Pendapatan per Kategori
                </h3>
                <p class="text-xs text-neutral-500">
                    Distribusi penjualan berdasarkan kategori produk
                </p>
            </div>
        </div>
        <div class="relative flex-1 min-h-[240px] flex items-center justify-center">
            <canvas id="chart-category-sales" class="w-full max-h-[260px]"></canvas>
        </div>
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { Chart } from 'chart.js/auto';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faLayerGroup } from '@fortawesome/free-solid-svg-icons';
import { formatIDR } from '@/Composable/currency-format';

const props = defineProps({
    categorySales: {
        type: Object,
        default: () => ({
            label: [],
            value: [],
        }),
    },
});

let chartInstance = null;

const renderChart = () => {
    const ctx = document.getElementById('chart-category-sales');
    if (!ctx) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    const colors = [
        '#004AAD',
        '#5DE0E6',
        '#F59E0B',
        '#10B981',
        '#8B5CF6',
        '#EC4899',
    ];

    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: props.categorySales.label || [],
            datasets: [
                {
                    data: props.categorySales.value || [],
                    backgroundColor: colors.slice(0, props.categorySales.label?.length || 5),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        font: {
                            size: 11,
                            family: 'Inter, sans-serif',
                        },
                        padding: 12,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed;
                            return ` ${context.label}: ${formatIDR(value)}`;
                        },
                    },
                },
            },
            cutout: '68%',
        },
    });
};

onMounted(() => {
    renderChart();
});

watch(() => props.categorySales, () => {
    renderChart();
}, { deep: true });
</script>
