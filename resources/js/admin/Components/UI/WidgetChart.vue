<template>
    <div class="widget-chart">
        <div>
            <div class="text-2xl font-bold">
                {{ highlight }}
                <span class="text-xs font-thin">({{ subHighlight }})</span>
            </div>
            <div class="font-semibold">{{ title }}</div>
        </div>
        <canvas :id="'chart' + id" class="canvas"></canvas>
    </div>
</template>
<script setup>
import { onMounted } from "vue";
import { Chart } from "chart.js/auto";

const prop = defineProps({
    id: String,
    type: String,
    highlight: String,
    subHighlight: String,
    title: String,
    labels: Array,
    data: Array,
});

onMounted(() => {
    new Chart(document.getElementById("chart" + prop.id), {
        type: prop.type,
        data: {
            labels: prop.labels,
            datasets: [
                {
                    data: prop.data,
                    fill: false,
                    borderColor: "rgb(255,255,255,0.5)",
                    backgroundColor: "rgb(255,255,255,0.5)",
                    borderWidth: 2,
                    tension: 0.1,
                    borderRadius: 5,
                },
            ],
        },
        options: {
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    displayColors: false,
                },
            },
            scales: {
                x: {
                    display: false,
                },
                y: {
                    display: false,
                },
            },
        },
    });
});
</script>
