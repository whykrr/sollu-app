<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta
            v-if="data.meta.keyword"
            name="keywords"
            :content="data.meta.keyword"
        />
        <meta
            v-if="data.meta.description"
            name="description"
            :content="data.meta.description"
        />
    </Head>
    <div class="py-10">
        <div class="px-2 md:px-8 xl:px-16 flex flex-col">
            <div class="flex flex-row items-center gap-4">
                <div
                    class="rounded bg-web-main flex flex-col items-center text-white p-2 w-20"
                >
                    <div class="font-bold border-b border-white">
                        {{ getMonthID(data.tanggal) }}
                    </div>
                    <div>{{ getDay(data.tanggal) }}</div>
                </div>
                <div class="text-2xl font-bold text-web-main font-merriweather">
                    {{ data["title"] }}
                </div>
            </div>
            <div class="text-sm"></div>
            <div class="mt-2">
                <img
                    class="mx-auto"
                    :src="'storage/' + data['gambar']"
                    :alt="data['title']"
                />
            </div>
            <div class="mt-4 ql-editor" v-html="data['isi']"></div>
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
