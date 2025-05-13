<template>
    <div class="grid grid-cols-2 gap-4">
        <Card title="Chart Line">
            <canvas id="chart-line"></canvas>
        </Card>
        <Card title="Chart Bar">
            <canvas id="chart-bar"></canvas>
        </Card>
        <Card title="Chart Doughnut">
            <canvas id="chart-doughnut"></canvas>
        </Card>
        <Card title="Chart Pie">
            <div class="bg-secondary"></div>
            <canvas id="chart-pie"></canvas>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
import { Chart } from "chart.js/auto";
import { onMounted } from "vue";

const labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"];
const data = {
    labels: labels,
    datasets: [
        {
            label: "My First Dataset",
            data: [65, 59, 80, 81, 56, 55, 40],
            fill: false,
            borderColor: "rgb(0 74 173)",
            backgroundColor: "rgb(0 74 173)",
            tension: 0.3,
        },
        {
            label: "My Sec Dataset",
            data: [65, 2, 56, 90, 12, 67, 88],
            fill: false,
            borderColor: "rgb(93 224 230)",
            backgroundColor: "rgb(93 224 230)",
            tension: 0.3,
        },
    ],
};
const dataBar = {
    labels: labels,
    datasets: [
        {
            label: "My First Dataset",
            data: [65, 59, 80, 81, 56, 55, 40],
            fill: false,
            backgroundColor: "rgb(0 74 173)",
            tension: 0.1,
        },
    ],
};

onMounted(() => {
    new Chart(document.getElementById("chart-line"), {
        type: "line",
        data: data,
        options: {
            plugins: {
                legend: {
                    display: false,
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
    new Chart(document.getElementById("chart-bar"), {
        type: "bar",
        data: dataBar,
    });
    new Chart(document.getElementById("chart-doughnut"), {
        type: "doughnut",
        data: dataBar,
    });
    new Chart(document.getElementById("chart-pie"), {
        type: "pie",
        data: dataBar,
    });
});
</script>
