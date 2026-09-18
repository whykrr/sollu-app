<template>
    <div class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs">
        <div class="flex flex-col sm:flex-row justify-between gap-2 items-start sm:items-center">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faUsersViewfinder" class="text-sky-600 text-xs" />
                    Pertumbuhan Merchant & Outlet Baru
                </h3>
                <p class="text-xs text-neutral-500">
                    Aktivitas pendaftaran bisnis dan cabang baru (6 bulan terakhir)
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1 text-neutral-600 font-medium">
                    <span class="w-2.5 h-2.5 rounded-sm bg-sky-600"></span>
                    Merchant Baru
                </span>
                <span class="inline-flex items-center gap-1 text-neutral-600 font-medium">
                    <span class="w-2.5 h-2.5 rounded-sm bg-teal-500"></span>
                    Outlet Baru
                </span>
            </div>
        </div>

        <div class="relative w-full h-[220px] sm:h-[260px]">
            <canvas ref="chartCanvas" class="w-full h-full" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUsersViewfinder } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    acquisitionTrend: {
        type: Object,
        default: () => ({ labels: [], merchants: [], outlets: [] }),
    },
})

const chartCanvas = ref(null)
let chartInstance = null

const renderChart = () => {
    if (!chartCanvas.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const labels = props.acquisitionTrend?.labels || []
    const merchants = props.acquisitionTrend?.merchants || []
    const outlets = props.acquisitionTrend?.outlets || []

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Merchant Baru',
                    data: merchants,
                    backgroundColor: '#0284c7',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                },
                {
                    label: 'Outlet Baru',
                    data: outlets,
                    backgroundColor: '#14b8a6',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
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
                    callbacks: {
                        label: context => ` ${context.dataset.label}: ${context.parsed.y} entitas`,
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
                        stepSize: 1,
                        precision: 0,
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
    () => props.acquisitionTrend,
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
