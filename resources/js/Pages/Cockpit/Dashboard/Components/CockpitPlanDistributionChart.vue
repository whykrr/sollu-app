<template>
    <div
        class="flex flex-col justify-between gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs"
    >
        <div>
            <h3 class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faLayerGroup" class="text-indigo-600 text-xs" />
                Distribusi Paket Langganan
            </h3>
            <p class="text-xs text-neutral-500">Pangsa paket merchant aktif & uji coba</p>
        </div>

        <div class="relative w-full h-[200px] sm:h-[230px] flex items-center justify-center">
            <canvas ref="chartCanvas" class="w-full h-full" />
        </div>

        <div
            class="flex flex-wrap items-center justify-center gap-2 pt-1 border-t border-neutral-100"
        >
            <div
                v-for="(label, idx) in planDistribution.labels"
                :key="label"
                class="inline-flex items-center gap-1 text-[11px] text-neutral-600"
            >
                <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :style="{ backgroundColor: palette[idx % palette.length] }"
                ></span>
                <span class="font-medium">{{ label }}:</span>
                <span class="font-bold text-neutral-800">{{
                    planDistribution.values[idx] || 0
                }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faLayerGroup } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    planDistribution: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
})

const chartCanvas = ref(null)
let chartInstance = null

const palette = [
    '#4f46e5', // Indigo
    '#0284c7', // Sky
    '#0d9488', // Teal
    '#f59e0b', // Amber
    '#8b5cf6', // Violet
    '#ec4899', // Pink
]

const renderChart = () => {
    if (!chartCanvas.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const labels = props.planDistribution?.labels || []
    const data = props.planDistribution?.values || []

    const backgroundColors = labels.map((_, i) => palette[i % palette.length])

    chartInstance = new Chart(chartCanvas.value, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: backgroundColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
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
                        label: context => {
                            const total = data.reduce((a, b) => a + Number(b || 0), 0)
                            const val = context.parsed || 0
                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0
                            return ` ${context.label}: ${val} (${pct}%)`
                        },
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
    () => props.planDistribution,
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
