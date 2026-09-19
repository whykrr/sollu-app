<template>
    <div class="space-y-2">
        <div
            v-if="!chart || !chart.data || !chart.data.some(d => d !== 0)"
            class="text-center text-neutral-400 py-6 text-sm"
        >
            Tidak ada pergerakan stok dalam 30 hari terakhir.
        </div>
        <div v-else class="relative h-56 w-full pt-1">
            <canvas id="chart-stock-tab" />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { Chart } from 'chart.js/auto'

const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    chart: {
        type: Object,
        default: () => ({ labels: [], data: [] }),
    },
})

let chartInstance = null

onMounted(() => {
    if (props.chart && props.chart.data && props.chart.data.some(d => d !== 0)) {
        const ctx = document.getElementById('chart-stock-tab')
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
                        },
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
                                    let value = context.parsed.y
                                    return `Pergerakan: ${value > 0 ? '+' : ''}${value}`
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
                                display: false,
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
            })
        }
    }
})

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy()
    }
})
</script>
