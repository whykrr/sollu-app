<template>
    <swiper
        class="w-full"
        :modules="modules"
        :slides-per-view="1"
        :space-between="0"
        :auto-height="true"
        navigation
        :pagination="{ clickable: true }"
        :normalize-slide-index="true"
        :autoplay="true"
        :loop="true"
    >
        <swiper-slide v-for="p in poster">
            <img class="w-full" :src="'storage/' + p.gambar" :alt="p.title" />
        </swiper-slide>
    </swiper>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Autoplay } from "swiper/modules";

defineProps({
    poster: Array,
});

import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";

const modules = [Navigation, Pagination, Autoplay];
</script>

<style scoped>
.swiper {
    position: relative;
    z-index: 1; /* Ensures Swiper has a lower z-index than its overlays */
}

.swiper-slide {
    z-index: 2; /* Ensures slides are visible */
}

.swiper-button-next,
.swiper-button-prev,
.swiper-pagination {
    z-index: 10; /* Ensures navigation buttons and pagination are above slides */
}

.swiper-button-next,
.swiper-button-prev {
    color: rgb(255 255 255 / 0.5); /* Replace with your desired color */
}

/* Optional: Add hover effect */
.swiper-button-next:hover,
.swiper-button-prev:hover {
    color: rgb(255 255 255); /* Lighter or different shade on hover */
}

/* Optional: Adjust size if needed */
.swiper-button-next,
.swiper-button-prev {
    font-size: 16px; /* Adjust icon size */
}

.swiper-pagination-bullet {
    width: 6px;
    height: 6px;
    background-color: rgb(255 255 255 / 0.5); /* Default color */
    opacity: 1; /* Make them fully visible */
    margin: 0 5px; /* Add spacing between dots */
    border-radius: 50%; /* Make them circular */
}

/* Active dot styling */
.swiper-pagination-bullet-active {
    background-color: rgb(255 255 255); /* Highlight color */
    transform: scale(1.2); /* Slightly enlarge active dot */
    transition: transform 0.3s, background-color 0.3s; /* Smooth transition */
}
</style>
