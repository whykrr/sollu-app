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
            <div class="text-2xl font-bold text-web-main font-merriweather">
                {{ data["title"] }}
            </div>
            <div class="text-sm">
                <div class="font-semibold">{{ data["penulis"] }}</div>
                <div>
                    {{ formatedDate(data["created_at"]) }}
                </div>
            </div>
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

const formatedDate = (dateString) => {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0"); // Months are 0-based
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};
</script>
