<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta name="keywords" :content="beranda.meta.keyword" />
        <meta name="description" :content="beranda.meta.description" />
    </Head>

    <Slider :poster />
    <div class="w-full relative md:translate-y-[-50%] z-[5] md:mb-[-50px]">
        <div
            class="w-[100%] md:w-[75%] h-[70px] md:h-[100px] bg-web-main mx-auto grid grid-cols-3 drop-shadow-lg md:rounded p-2 md:p-4 text-white"
        >
            <div class="flex flex-col items-center border-r-2 border-white">
                <div class="text-xl md:text-4xl">
                    {{ beranda["sorotan-1"] }}
                </div>
                <div>{{ beranda["deskripsi-sorotan-1"] }}</div>
            </div>
            <div class="flex flex-col items-center border-r-2 border-white">
                <div class="text-xl md:text-4xl">
                    {{ beranda["sorotan-2"] }}
                </div>
                <div>{{ beranda["deskripsi-sorotan-2"] }}</div>
            </div>
            <div class="flex flex-col items-center">
                <div class="text-xl md:text-4xl">
                    {{ beranda["sorotan-3"] }}
                </div>
                <div>{{ beranda["deskripsi-sorotan-3"] }}</div>
            </div>
        </div>
    </div>
    <div class="py-6 flex flex-col gap-10">
        <div
            class="px-2 md:px-8 xl:px-16 text-2xl font-bold text-web-main text-center font-merriweather appear"
        >
            {{ beranda["title"] }}
        </div>
        <div
            class="px-2 md:px-8 xl:px-16 flex flex-col md:grid grid-cols-6 gap-4 md:gap-16"
        >
            <div class="md:col-span-3 xl:col-span-2 appear">
                <img
                    class="w-[50%] md:w-full mx-auto"
                    :src="'storage/' + beranda['foto-kepala-sekolah']"
                    alt="Kepala Sekolah"
                />
            </div>
            <div class="md:col-span-3 xl:col-span-4 appear">
                <div class="font-merriweather font-semibold text-2xl mb-2">
                    Sambutan Kepala Sekolah
                </div>
                <div
                    class="ql-editor"
                    v-html="beranda['sambutan-kepala-sekolah']"
                ></div>
            </div>
        </div>

        <div
            class="px-2 md:px-8 xl:px-16 text-2xl font-bold text-web-main text-center font-merriweather appear"
        >
            Berita Terikini
        </div>
        <div
            class="px-2 md:px-8 xl:px-16 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-8 appear"
        >
            <Link
                :href="route('news.show', { slug: n.slug })"
                v-for="n in berita"
            >
                <div class="flex flex-col gap-1">
                    <img :src="'storage/' + n.gambar" :alt="n.title" />
                    <div class="font-semibold md:text-xl">
                        {{ n.title }}
                    </div>
                    <div
                        class="flex flex-col md:flex-row gap-2 md:justify-between text-gray-400"
                    >
                        <span>{{ n.penulis }}</span>
                        <div>{{ formatedDate(n.created_at) }}</div>
                    </div>
                </div>
            </Link>
        </div>

        <div class="relative">
            <div class="absolute w-full">
                <img
                    :src="'storage/' + beranda['latar-belakang-pengumuman']"
                    alt="Background Pengumuman"
                    class="object-cover brightness-50"
                />
            </div>
            <div
                class="px-2 md:px-8 xl:px-16 text-2xl font-bold text-web-main text-center font-merriweather appear mt-6 mb-6"
            >
                Pengumuman
            </div>

            <div
                class="px-2 md:px-8 xl:px-16 grid md:grid-cols-2 xl:grid-cols-3 gap-8 appear"
            >
                <Link
                    :href="route('announcements.show', { slug: p.slug })"
                    v-for="p in pengumuman"
                >
                    <div
                        class="flex flex-col gap-2 bg-web-main/75 rounded drop-shadow-md p-3"
                    >
                        <img :src="'storage/' + p.gambar" :alt="p.title" />
                        <div class="grid grid-cols-10 gap-4">
                            <div class="col-span-2">
                                <div
                                    class="rounded bg-white text-sm md:text-base flex flex-col items-center text-web-main p-2"
                                >
                                    <div
                                        class="font-bold border-b border-web-main"
                                    >
                                        {{ getMonthID(p.tanggal) }}
                                    </div>
                                    <div>{{ getDay(p.tanggal) }}</div>
                                </div>
                            </div>
                            <div
                                class="col-span-8 font-semibold md:text-xl text-white"
                            >
                                {{ p.title }}
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <div
            class="px-2 md:px-8 xl:px-16 text-2xl font-bold text-web-main text-center font-merriweather appear mt-12"
        >
            Galleri Sekolah
        </div>

        <Gallery :data="galeri" class="appear" />
    </div>
</template>

<script setup>
import { usePage, Head, Link } from "@inertiajs/vue3";
import Slider from "./Components/Slider.vue";
import Gallery from "./Components/Gallery.vue";

defineProps({
    poster: Array,
    beranda: Object,
    berita: Array,
    pengumuman: Array,
    galeri: Array,
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
