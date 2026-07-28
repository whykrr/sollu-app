<template>
    <div class="space-y-2">
        <h4 class="font-semibold text-lg">
            Tren Perubahan Stok (30 Hari Terakhir)
        </h4>

        <div v-if="loading" class="text-center text-gray-500 py-4">
            Memuat grafik...
        </div>
        <div
            v-else-if="!chartData.data.some((d) => d !== 0)"
            class="text-center text-gray-500 py-4"
        >
            Tidak ada pergerakan stok dalam 30 hari terakhir.
        </div>
        <div
            v-else
            class="h-64 flex items-end gap-1 pt-4 pb-2 border-b border-l border-gray-300 relative"
        >
            <!-- Simple CSS Bar Chart -->
            <div
                v-for="(val, idx) in chartData.data"
                :key="idx"
                class="flex-1 transition-colors relative group"
                :class="
                    val > 0
                        ? 'bg-success hover:bg-success-dark'
                        : val < 0
                          ? 'bg-danger hover:bg-danger-dark'
                          : 'bg-transparent'
                "
                :style="{ height: getBarHeight(val) + '%' }"
            >
                <div
                    class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap z-10 pointer-events-none"
                >
                    {{ chartData.labels[idx] }}: {{ val > 0 ? '+' : ''
                    }}{{ val }}
                </div>
            </div>

            <!-- Baseline for zero -->
            <div
                v-if="minVal < 0"
                class="absolute w-full border-t border-dashed border-gray-400 pointer-events-none"
                :style="{ bottom: zeroLinePosition + '%' }"
            ></div>
        </div>

        <div
            v-if="!loading && chartData.data.some((d) => d !== 0)"
            class="flex justify-between text-xs text-gray-500"
        >
            <span>{{ chartData.labels[0] }}</span>
            <span>{{ chartData.labels[chartData.labels.length - 1] }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    item: Object,
});

const loading = ref(true);
const chartData = ref({ labels: [], data: [] });

onMounted(() => {
    fetchChart();
});

const fetchChart = async () => {
    loading.value = true;
    try {
        const response = await axios.get(
            route('inventories.stocks.chart', props.item.id),
        );
        chartData.value = response.data;
    } catch (error) {
        console.error('Failed to load chart', error);
    } finally {
        loading.value = false;
    }
};

const maxVal = computed(() => {
    if (!chartData.value.data.length) return 100;
    const max = Math.max(...chartData.value.data);
    return max > 0 ? max : 100;
});

const minVal = computed(() => {
    if (!chartData.value.data.length) return 0;
    return Math.min(...chartData.value.data, 0);
});

const range = computed(() => {
    return maxVal.value - minVal.value || 100;
});

const zeroLinePosition = computed(() => {
    return (Math.abs(minVal.value) / range.value) * 100;
});

const getBarHeight = (val) => {
    const normalizedVal = Math.abs(val);
    const maxScale =
        Math.max(Math.abs(maxVal.value), Math.abs(minVal.value)) || 1;
    return (normalizedVal / maxScale) * 100;
};
</script>
