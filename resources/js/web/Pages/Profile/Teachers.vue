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
            <div>
                <div
                    class="w-[60%] md:w-[40%] xl:w-[20%] border border-web-main/50 rounded p-2 mx-auto"
                >
                    <div>
                        <img
                            :src="'storage/' + data['foto-kepala-sekolah']"
                            alt="Kepala Sekolah"
                        />
                    </div>
                    <div
                        class="flex flex-row justify-between mt-4 items-center"
                    >
                        <div>
                            <div class="font-semibold">Kepala Sekolah</div>
                            <div class="">
                                {{ data["nama-kepala-sekolah"] }}
                            </div>
                        </div>
                        <div>
                            <a
                                target="_blank"
                                :href="
                                    'https://wa.me/' +
                                    data['telepon-kepala-sekolah']
                                "
                                class="rounded-full w-10 h-10 p-2 bg-green-500 text-white flex items-center"
                            >
                                <fa
                                    class="mx-auto"
                                    icon="fa-brands fa-whatsapp"
                                />
                            </a>
                        </div>
                    </div>

                    <div
                        class="flex flex-row gap-4 text-xl justify-between px-10"
                    ></div>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                <div
                    v-for="g in guru"
                    class="border border-web-main/50 rounded p-2 mx-auto"
                >
                    <div>
                        <img
                            v-if="g['foto']"
                            :src="'storage/' + g['foto']"
                            alt="Kepala Sekolah"
                        />
                        <img
                            v-else
                            src="https://fakeimg.pl/600x800"
                            :alt="g['title']"
                        />
                    </div>
                    <div
                        class="flex flex-row justify-between mt-4 items-center text-sm gap-2"
                    >
                        <div>
                            <div class="font-semibold">{{ g["jabatan"] }}</div>
                            <div class="">
                                {{ g["title"] }}
                            </div>
                        </div>
                        <div>
                            <a
                                target="_blank"
                                :href="'https://wa.me/' + g['nomor-telepon']"
                                class="rounded-full w-7 h-7 p-2 bg-green-500 text-white flex items-center"
                            >
                                <fa
                                    class="mx-auto"
                                    icon="fa-brands fa-whatsapp"
                                />
                            </a>
                        </div>
                    </div>

                    <div
                        class="flex flex-row gap-4 text-xl justify-between px-10"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { Link, usePage } from "@inertiajs/vue3";

defineProps({
    data: Object,
    guru: Array,
});

const page = usePage();
const settings = page.props.settings;
</script>
