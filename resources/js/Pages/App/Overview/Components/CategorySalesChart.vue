<template>
    <div class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 h-full">
        <div>
            <h3 class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faLayerGroup" class="text-main text-xs" />
                Pendapatan per Kategori
            </h3>
            <p class="text-xs text-neutral-500">
                Distribusi omset penjualan 5 kategori produk teratas
            </p>
        </div>

        <div
            class="relative flex-1 min-h-[200px] sm:min-h-[220px] flex items-center justify-center"
        >
            <canvas ref="chartCanvas" class="w-full h-full max-h-[230px]" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faLayerGroup } from '@fortawesome/free-solid-svg-icons'
import { formatIDR } from '@/Composable/currency-format'

const props = defineProps({
    categorySales: {
        type: Object,
        default: () => ({
            label: [],
            value: [],
        }),
    },
})

const chartCanvas = ref(null)
let chartInstance = null

const colors = ['#004AAD', '#5DE0E6', '#F59E0B', '#10B981', '#8B5CF6', '#EC4899']

const renderChart = () => {
    if (!chartCanvas.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const labels = props.categorySales?.label || []
    const data = props.categorySales?.value || []

    const isMobile = typeof window !== 'undefined' && window.innerWidth < 640

    chartInstance = new Chart(chartCanvas.value, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: colors.slice(0, Math.max(labels.length, 1)),
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
                    position: isMobile ? 'bottom' : 'right',
                    labels: {
                        boxWidth: 8,
                        usePointStyle: true,
                        font: {
                            size: 11,
                            family: 'Inter, sans-serif',
                        },
                        padding: isMobile ? 6 : 10,
                    },
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#f8fafc',
                    bodyColor: '#f8fafc',
                    padding: 8,
                    cornerRadius: 6,
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed || 0
                            return ` ${context.label}: ${formatIDR(value)}`
                        },
                    },
                },
            },
            cutout: '68%',
        },
    })
}

onMounted(() => {
    renderChart()
})

watch(
    () => props.categorySales,
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
