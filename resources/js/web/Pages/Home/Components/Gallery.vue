<template>
    <swiper
        class="w-full"
        :effect="'coverflow'"
        :grab-cursor="true"
        :centered-slides="true"
        :slides-per-view="'auto'"
        :coverflow-effect="{
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: true,
        }"
        :pagination="true"
        :modules="modules"
        :loop="true"
    >
        <swiper-slide v-for="d in data">
            <img :src="'storage/' + d.poster" :alt="d.title" />
            <div data-swiper-parallax="-300">
                <div class="absolute mb-2">
                    <div class="text-lg font-semibold my-2">{{ d.title }}</div>
                    <Link
                        :href="route('galleries.show', { slug: d.slug })"
                        class="inline border border-web-main px-2 py-1 rounded text-web-main hover:bg-web-main hover:text-white"
                    >
                        Lihat Selengkapnya
                    </Link>
                </div>
            </div>
        </swiper-slide>
    </swiper>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, EffectCoverflow } from "swiper/modules";

import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/effect-coverflow";
import { Link } from "@inertiajs/vue3";

const modules = [Navigation, EffectCoverflow];

defineProps({
    data: Array,
});
</script>

<style scoped>
.swiper {
    width: 100%;
    padding-bottom: 120px;
}

.swiper-slide {
    background-position: center;
    background-size: cover;
    width: 50%;
    height: fit-content;
}

.swiper-slide img {
    display: block;
    width: 100%;
}
</style>
