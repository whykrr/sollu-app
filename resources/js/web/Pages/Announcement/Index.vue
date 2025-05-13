<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta name="keywords" content="Pengumuman" />
    </Head>
    <div class="py-10">
        <div class="px-2 md:px-8 xl:px-16 flex flex-col gap-4">
            <div
                class="text-2xl font-bold text-web-main text-center font-merriweather appear"
            >
                Pengumuman
            </div>
            <div
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 md:gap-4 xl:gap-8"
            >
                <Link
                    :href="route('announcements.show', { slug: d.slug })"
                    v-for="d in data"
                >
                    <div
                        class="flex flex-col gap-2 bg-web-main/75 rounded drop-shadow-md p-3"
                    >
                        <img :src="'storage/' + d.gambar" :alt="d.title" />
                        <div class="grid grid-cols-10 gap-4">
                            <div class="col-span-2">
                                <div
                                    class="rounded bg-white flex flex-col items-center text-web-main p-2"
                                >
                                    <div
                                        class="font-bold border-b border-web-main"
                                    >
                                        {{ getMonthID(d.tanggal) }}
                                    </div>
                                    <div>{{ getDay(d.tanggal) }}</div>
                                </div>
                            </div>
                            <div
                                class="col-span-8 font-semibold text-xl text-white"
                            >
                                {{ d.title }}
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
<script setup>
import { Link, usePage } from "@inertiajs/vue3";

defineProps({
    data: Array,
});

const page = usePage();
const settings = page.props.settings;

const getMonthID = (dateString) => {
    const date = new Date(dateString);
    const monthsID = [
        "JAN",
        "FEB",
        "MAR",
        "APR",
        "MEI",
        "JUN",
        "JUL",
        "AGU",
        "SEP",
        "OKT",
        "NOV",
        "DES",
    ];
    return monthsID[date.getMonth()];
};
const getDay = (dateString) => {
    const date = new Date(dateString);
    return String(date.getDate()).padStart(2, "0");
};
</script>
