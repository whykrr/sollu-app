<template>
    <div class="space-y-2">
        <h4 class="font-semibold text-lg">
            Tren Perubahan Stok (30 Hari Terakhir)
        </h4>

        <div
            v-if="!chart || !chart.data || !chart.data.some((d) => d !== 0)"
            class="text-center text-gray-500 py-4"
        >
            Tidak ada pergerakan stok dalam 30 hari terakhir.
        </div>
        <div v-else class="relative h-64 w-full">
            <canvas id="chart-stock-tab" />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Chart } from 'chart.js/auto';

const props = defineProps({
    item: Object,
    chart: {
        type: Object,
        default: () => ({ labels: [], data: [] }),
    }
});

let chartInstance = null;

onMounted(() => {
    if (props.chart && props.chart.data && props.chart.data.some((d) => d !== 0)) {
        const ctx = document.getElementById('chart-stock-tab');
        if (ctx) {
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: props.chart.labels,
                    datasets: [
                        {
                            label: 'Perubahan Stok',
                            data: props.chart.data,
                            fill: false,
                            borderColor: 'rgb(0 74 173)',
                            backgroundColor: 'rgb(0 74 173)',
                            tension: 0.3,
                        }
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    datasets: {
                        line: {
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let value = context.parsed.y;
                                    return `Pergerakan: ${value > 0 ? '+' : ''}${value}`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            grid: {
                                display: true,
                            },
                            ticks: {
                                display: false, // hide y-axis labels like SalesTrendChart
                            },
                            border: {
                                display: false,
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });
        }
    }
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>
