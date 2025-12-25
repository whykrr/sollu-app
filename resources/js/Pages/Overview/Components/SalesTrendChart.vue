<template>
    <div class="flex flex-col gap-2 p-4 bg-white rounded-md border">
        <div class="flex flex-row justify-between gap-2 items-start">
            <div>
                <h3 class="text-lg font-semibold">Tren Penjualan</h3>
                <p class="text-sm text-gray-500">
                    Performa penjualan berdasarkan waktu
                </p>
            </div>
            <div>
                <GroupDropdownIconField
                    id="type"
                    v-model="type"
                    :icon="faCalendarDays"
                    class="sm"
                    :options="[
                        { value: 'today', label: 'Hari Ini' },
                        { value: 'week', label: 'Minggu' },
                        { value: 'month', label: 'Bulan', sele },
                        { value: 'year', label: 'Tahun' },
                    ]"
                />
            </div>
        </div>
        <canvas id="chart-trend" height="350" />
    </div>
</template>

<script setup>
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import { formatIDR } from '@/Composable/currency-format';
import { faCalendarDays } from '@fortawesome/free-solid-svg-icons';
import { Chart } from 'chart.js/auto';
import { onMounted, ref } from 'vue';

const type = ref('month');

const props = defineProps({
    trend: {
        label: Array,
        value: Array,
    },
});

const colorPaletteChartLine = ['rgb(0 74 173)', 'rgb(93 224 230)'];
const datasetChart = [];

props.trend.value.forEach((val, i) => {
    datasetChart.push({
        label: val.title,
        data: val.data,
        fill: false,
        borderColor: colorPaletteChartLine[i],
        backgroundColor: colorPaletteChartLine[i],
        tension: 0.3,
    });
});

onMounted(() => {
    let showIDR = true;

    const chart = new Chart(document.getElementById('chart-trend'), {
        type: 'line',
        data: {
            labels: props.trend.label,
            datasets: datasetChart,
        },
        options: {
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
                            return showIDR
                                ? `Total Penjualan: ${formatIDR(value)}`
                                : `Total Penjualan: ${value}`;
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
                    // stacked: true,
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });
});
</script>
