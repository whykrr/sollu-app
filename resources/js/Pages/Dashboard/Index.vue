<template>
    <CardTransparent>
        <div
            class="alert alert-warning mb-2"
            v-if="auth.email_verified_at === null"
        >
            <strong>Verifikasi Email</strong>
            <div class="flex justify-between items-center">
                <span class="text-sm"
                    >Cek email Anda untuk verifikasi sebelum menggunakan
                    fitur.</span
                >
                <Link
                    as="button"
                    method="post"
                    :href="route('dashboard.verification.send')"
                    class="btn btn-highlight-success btn-sm"
                >
                    <fa icon="fa-rotate-right left-0" />
                    Kirim Ulang
                </Link>
            </div>
        </div>

        <div
            class="flex flex-col md:grid md:grid-cols-1 lg:grid-cols-3 gap-2 mb-4"
        >
            <Widget
                title="Total Kunjungan"
                icon="fa-users"
                class="bg-main dark:bg-main-light"
            >
                <p class="text-md">{{ visits }}</p>
            </Widget>

            <Widget
                title="Total Pengunjung"
                icon="fa-person"
                class="bg-clay dark:bg-clay-light"
            >
                <p class="text-md">{{ visitorThisMonth.visitors }}</p>
            </Widget>

            <Widget
                title="Pesan Belum Dibaca"
                icon="fa-envelope"
                class="bg-turquoise dark:bg-turquoise-light"
            >
                <p class="text-md">{{ messageUnread }}</p>
            </Widget>
        </div>

        <div class="grid grid-flow-row lg:grid-cols-4 gap-4 mb-4">
            <div class="col-span-4 lg:col-span-2">
                <Card title="Pengunjung Halaman" class="shadow-md">
                    <canvas id="chart-page"></canvas>
                </Card>
            </div>
            <div class="col-span-4 lg:col-span-2">
                <Card title="Pengunjung Per Bulan" class="shadow-md">
                    <canvas id="chart-visitor"></canvas>
                </Card>
            </div>
        </div>

        <div class="flex flex-col">
            <Card title="Halaman Top 10" class="shadow-md">
                <ListView :data="pageMostVisits" />
            </Card>
        </div>
    </CardTransparent>
</template>

<script setup>
import Card from "@/Components/Cards/Card.vue";
import ListView from "@/Pages/Dashboard/Components/ListView.vue";
import Widget from "@/Components/Widgets/Widget.vue";
import { onMounted, ref } from "vue";
import { Chart } from "chart.js/auto";
import CardTransparent from "@/Components/Cards/CardTransparent.vue";
import { Link, usePage } from "@inertiajs/vue3";

import { usePageStore } from "@/store/page";
const pageStore = usePageStore();
pageStore.title = "Overview";

const auth = usePage().props.auth;

const props = defineProps({
    visits: Number,
    visitorThisMonth: Object,
    messageUnread: Number,
    visitorPerMonthPerPage: {
        label: Array,
        value: Array,
    },
    visitorPerMonth: Object,
    pageMostVisits: Object,
});

const colorPaletteChartLine = [
    "rgb(0 74 173)",
    "rgb(93 224 230)",
    "rgb(106 13 173)",
    "rgb(255 111 145)",
    "rgb(123 75 58)",
];
const datasetChart = [];

props.visitorPerMonthPerPage.value.forEach((val, i) => {
    datasetChart.push({
        label: val.url,
        data: val.value,
        fill: false,
        borderColor: colorPaletteChartLine[i],
        backgroundColor: colorPaletteChartLine[i],
        tension: 0.3,
    });
});

const labelChart2 = "Pengunjung";

onMounted(() => {
    new Chart(document.getElementById("chart-page"), {
        type: "line",
        data: {
            labels: props.visitorPerMonthPerPage.label,
            datasets: datasetChart,
        },
    });
    new Chart(document.getElementById("chart-visitor"), {
        type: "line",
        data: {
            labels: props.visitorPerMonth.label,
            datasets: [
                {
                    label: labelChart2,
                    data: props.visitorPerMonth.value,
                    fill: false,
                    borderColor: "rgb(0 74 173)",
                    backgroundColor: "rgb(0 74 173)",
                    tension: 0.3,
                },
            ],
        },
    });
});
</script>
