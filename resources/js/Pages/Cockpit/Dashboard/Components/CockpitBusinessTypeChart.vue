<template>
    <div
        class="flex flex-col justify-between gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs"
    >
        <div>
            <h3 class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faBriefcase" class="text-emerald-600 text-xs" />
                Distribusi Jenis Bisnis
            </h3>
            <p class="text-xs text-neutral-500">Klasifikasi merchant berdasarkan sektor industri</p>
        </div>

        <div class="relative w-full h-[200px] sm:h-[230px]">
            <canvas ref="chartCanvas" class="w-full h-full" />
        </div>

        <div
            class="flex items-center justify-between text-[11px] text-neutral-400 pt-1 border-t border-neutral-100"
        >
            <span>Total Kategori: {{ businessTypeDistribution.labels?.length || 0 }}</span>
            <span>Diurutkan berdasarkan entitas terbanyak</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBriefcase } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    businessTypeDistribution: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
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

    const labels = props.businessTypeDistribution?.labels || []
    const data = props.businessTypeDistribution?.values || []

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    axis: 'y',
                    label: 'Jumlah Merchant',
                    data,
                    backgroundColor: '#059669',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.65,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
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
                        label: context => ` ${context.parsed.x} Merchant`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)',
                    },
                    ticks: {
                        font: { size: 10 },
                        color: '#64748b',
                        precision: 0,
                    },
                    border: {
                        display: false,
                    },
                },
                y: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 10 },
                        color: '#475569',
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
    () => props.businessTypeDistribution,
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
