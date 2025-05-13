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
        <div class="px-2 md:px-8 xl:px-16 flex flex-col gap-4">
            <div
                class="text-2xl font-bold text-web-main text-center font-merriweather appear"
            >
                {{ data["title"] }}
            </div>
            <div class="flex flex-col-reverse md:grid md:grid-cols-10 gap-4">
                <div
                    class="ql-editor md:col-span-5 xl:col-span-6"
                    v-html="data['isi']"
                ></div>
                <div class="md:col-span-5 xl:col-span-4">
                    <img
                        class="w-[50%] md:w-full mx-auto rounded"
                        :src="'storage/' + data['gambar']"
                        :alt="data['title']"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { usePage } from "@inertiajs/vue3";

defineProps({
    data: Object,
});

const page = usePage();
const settings = page.props.settings;
</script>
