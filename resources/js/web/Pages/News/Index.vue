<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta name="keywords" content="Berita" />
    </Head>
    <div class="py-10">
        <div class="px-2 md:px-8 xl:px-16 flex flex-col gap-4">
            <div
                class="text-2xl font-bold text-web-main text-center font-merriweather appear"
            >
                Berita
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-8">
                <Link
                    :href="route('news.show', { slug: d.slug })"
                    v-for="d in data"
                >
                    <div class="flex flex-col gap-1">
                        <img :src="'storage/' + d.gambar" :alt="d.title" />
                        <div class="font-semibold text-xl">
                            {{ d.title }}
                        </div>
                        <div
                            class="flex flex-col md:flex-row justify-between text-gray-400"
                        >
                            <span>{{ d.penulis }}</span>
                            <div>{{ formatedDate(d.created_at) }}</div>
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

const formatedDate = (dateString) => {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0"); // Months are 0-based
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};
</script>
