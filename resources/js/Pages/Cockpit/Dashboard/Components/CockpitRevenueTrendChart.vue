<template>
    <div class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs">
        <div class="flex flex-col sm:flex-row justify-between gap-2 items-start sm:items-center">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faChartLine" class="text-main text-xs" />
                    Tren Pendapatan Langganan
                </h3>
                <p class="text-xs text-neutral-500">
                    Akumulasi penerimaan invoice berbayar ({{ periodLabel || 'Periode Ini' }})
                </p>
            </div>
            <div class="text-right">
                <span class="text-xs text-neutral-400">Total Periode:</span>
                <span class="text-sm font-bold text-neutral-800 ml-1">
                    {{ formatIDR(totalPeriodRevenue) }}
                </span>
            </div>
        </div>

        <div class="relative w-full h-[220px] sm:h-[260px]">
            <canvas ref="chartCanvas" class="w-full h-full" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount, computed } from 'vue'
import { Chart } from 'chart.js/auto'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChartLine } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    revenueTrend: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    periodLabel: {
        type: String,
        default: '',
    },
})

const chartCanvas = ref(null)
let chartInstance = null

const totalPeriodRevenue = computed(() => {
    if (!props.revenueTrend?.values || !Array.isArray(props.revenueTrend.values)) return 0
    return props.revenueTrend.values.reduce((acc, val) => acc + Number(val || 0), 0)
})

const renderChart = () => {
    if (!chartCanvas.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const labels = props.revenueTrend?.labels || []
    const data = props.revenueTrend?.values || []

    const ctx = chartCanvas.value.getContext('2d')
    const gradient = ctx.createLinearGradient(0, 0, 0, 240)
    gradient.addColorStop(0, 'rgba(0, 74, 173, 0.22)')
    gradient.addColorStop(1, 'rgba(0, 74, 173, 0.0)')

    chartInstance = new Chart(chartCanvas.value, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Pendapatan',
                    data,
                    fill: true,
                    borderColor: '#004aad',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: labels.length > 20 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#004aad',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#f8fafc',
                    bodyColor: '#f8fafc',
                    padding: 8,
                    cornerRadius: 6,
                    displayColors: false,
                    callbacks: {
                        label: context => `Pendapatan: ${formatIDR(context.parsed.y || 0)}`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)',
                    },
                    ticks: {
                        font: { size: 10 },
                        color: '#64748b',
                        callback: value => {
                            if (value >= 1000000000) return `Rp ${(value / 1000000000).toFixed(1)}M`
                            if (value >= 1000000) return `Rp ${(value / 1000000).toFixed(0)}Jt`
                            if (value >= 1000) return `Rp ${(value / 1000).toFixed(0)}Rb`
                            return `Rp ${value}`
                        },
                    },
                    border: {
                        display: false,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 10 },
                        color: '#64748b',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                    },
                },
            },
        },
    })
}

onMounted(() => {
    renderChart()
})

watch(
    () => props.revenueTrend,
    () => {
        renderChart()
    },
    { deep: true }
)

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy()
    }
})
</script>
