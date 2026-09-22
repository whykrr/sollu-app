<template>
    <div class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 h-full">
        <div>
            <h3 class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faCreditCard" class="text-main text-xs" />
                Ringkasan Metode Pembayaran
            </h3>
            <p class="text-xs text-neutral-500">
                Proporsi penerimaan transaksi berdasarkan jenis pembayaran
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center flex-1">
            <div class="relative min-h-[180px] sm:min-h-[200px] flex items-center justify-center">
                <canvas ref="chartCanvas" class="w-full h-full max-h-[200px]" />
            </div>
            <div class="flex flex-col gap-1.5">
                <div
                    v-for="(method, index) in paymentMethods.label"
                    :key="index"
                    class="flex items-center justify-between p-2 rounded-md bg-neutral-50 border border-neutral-100/80"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <span
                            class="w-2.5 h-2.5 rounded-full shrink-0"
                            :style="{ backgroundColor: getMethodColor(index) }"
                        ></span>
                        <span class="text-xs font-medium text-neutral-700 truncate">{{
                            method
                        }}</span>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-bold text-neutral-900 block">
                            {{ paymentMethods.value?.[index] || 0 }}%
                        </span>
                        <span
                            v-if="paymentMethods.revenue?.[index]"
                            class="text-[10px] text-neutral-500 block"
                        >
                            {{ formatIDR(paymentMethods.revenue[index]) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCreditCard } from '@fortawesome/free-solid-svg-icons'
import { formatIDR } from '@/Composable/currency-format'

const props = defineProps({
    paymentMethods: {
        type: Object,
        default: () => ({
            label: [],
            value: [],
            revenue: [],
        }),
    },
})

const chartCanvas = ref(null)
let chartInstance = null

const colors = ['#10B981', '#004AAD', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899']

const getMethodColor = index => colors[index % colors.length]

const renderChart = () => {
    if (!chartCanvas.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const labels = props.paymentMethods?.label || []
    const data = props.paymentMethods?.value || []

    chartInstance = new Chart(chartCanvas.value, {
        type: 'pie',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: colors.slice(0, Math.max(labels.length, 1)),
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
                    backgroundColor: '#1e293b',
                    titleColor: '#f8fafc',
                    bodyColor: '#f8fafc',
                    padding: 8,
                    cornerRadius: 6,
                    callbacks: {
                        label: function (context) {
                            const index = context.dataIndex
                            const percentage = context.parsed || 0
                            const revenue = props.paymentMethods.revenue?.[index]
                            return revenue
                                ? ` ${context.label}: ${percentage}% (${formatIDR(revenue)})`
                                : ` ${context.label}: ${percentage}%`
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
    () => props.paymentMethods,
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
