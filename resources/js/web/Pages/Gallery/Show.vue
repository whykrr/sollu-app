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
            <div class="mt-4 flex flex-col md:grid grid-cols-10 gap-4">
                <div class="col-span-7">
                    <img
                        class="w-full"
                        :src="'storage/' + data['poster']"
                        :alt="data['title']"
                    />
                </div>
                <div class="col-span-3 flex flex-col gap-4">
                    <img
                        class="w-full"
                        :src="'storage/' + data['gambar-1']"
                        :alt="data['title']"
                    />
                    <img
                        class="w-full"
                        :src="'storage/' + data['gambar-2']"
                        :alt="data['title']"
                    />
                    <img
                        v-if="data['gambar-3']"
                        class="w-full"
                        :src="'storage/' + data['gambar-3']"
                        :alt="data['title']"
                    />
                </div>
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
