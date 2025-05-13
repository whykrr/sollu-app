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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="font-merriweather font-semibold text-2xl mb-2">
                        Visi
                    </div>
                    <div class="ql-editor" v-html="data['visi']"></div>
                </div>
                <div>
                    <div class="font-merriweather font-semibold text-2xl mb-2">
                        Misi
                    </div>
                    <div class="ql-editor" v-html="data['misi']"></div>
                </div>
            </div>
            <div class="mt-8">
                <div
                    class="text-center font-merriweather font-semibold text-2xl mb-2"
                >
                    Tujuan
                </div>
                <div class="ql-editor" v-html="data['tujuan-sekolah']"></div>
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
