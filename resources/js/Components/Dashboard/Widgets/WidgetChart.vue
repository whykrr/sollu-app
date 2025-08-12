<template>
    <div class="widget">
        <div class="flex flex-row items-center gap-2 relative">
            <h2 class="font-medium flex-1">{{ title }}</h2>
            <div class="absolute top-0 right-0">
                <div class="widget-icon w-[40px]! h-[40px]!">
                    <FontAwesomeIcon class="text-xl" :icon />
                </div>
            </div>
        </div>
        <div>
            <div class="text-2xl font-bold">
                {{ highlight }}
                <div
                    class="inline-flex text-sm font-medium text-success items-center"
                >
                    <font-awesome-icon :icon="faPlus" class="text-xs mr-0.5" />
                    <span>{{ subHighlight }}</span>
                </div>
            </div>
        </div>
        <canvas :id="'chart' + id" class="canvas -m-[18px]"></canvas>
    </div>
</template>
<script setup>
import { onMounted } from "vue";
import { Chart } from "chart.js/auto";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faArrowUp, faPlus } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    id: String,
    type: String,
    highlight: String,
    subHighlight: String,
    icon: String,
    title: String,
    labels: Array,
    data: Array,
});

const getColorFromClass = (className) => {
    const el = document.getElementById(`chart${props.id}`);
    const color = getComputedStyle(el).color;
    return color;
};

onMounted(() => {
    const borderColor = getColorFromClass(props.color);
    const backgroundColor = getColorFromClass(props.color);

    new Chart(document.getElementById("chart" + props.id), {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: [
                {
                    data: props.data,
                    fill: true,
                    borderColor,
                    backgroundColor,
                    borderWidth: 3,
                    tension: 0.5,
                    pointRadius: 0,
                    borderRadius: 3,
                    spanGaps: true,
                },
            ],
        },
        options: {
            layout: {
                padding: {
                    top: 3,
                    bottom: 0,
                },
            },
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
