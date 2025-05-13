<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta name="keywords" content="Galeri Kegiatan" />
    </Head>
    <div class="py-10">
        <div class="px-2 md:px-8 xl:px-16 flex flex-col gap-4">
            <div
                class="text-2xl font-bold text-web-main text-center font-merriweather appear"
            >
                Galeri Kegiatan
            </div>
            <div
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 md:gap-4 xl:gap-8"
            >
                <Link
                    :href="route('galleries.show', { slug: d.slug })"
                    v-for="d in data"
                >
                    <div
                        class="flex flex-col gap-2 border border-gray-200 rounded drop-shadow-md p-3"
                    >
                        <img :src="'storage/' + d.poster" :alt="d.title" />
                        <div class="col-span-8 font-semibold text-xl">
                            {{ d.title }}
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
</script>
