<template>
    <aside class="hidden sm:flex md:min-w-[30%] lg:min-w-[20%] h-screen z-30">
        <div
            class="flex flex-col justify-between bg-neutral-light w-full border-r shadow"
        >
            <div>
                <div
                    class="flex flex-row justify-between items-center px-4 h-16"
                >
                    <Link :href="route('dashboard.overview')">
                        <img
                            src="storage/img/logo-fit-color.png"
                            class="w-22"
                            alt="Sollu"
                        />
                    </Link>
                    <div class="text-sm cursor-pointer">
                        <FontAwesomeIcon :icon="faChevronLeft" />
                        <FontAwesomeIcon :icon="faChevronLeft" />
                    </div>
                </div>
            </div>
            <SidebarOutlet v-if="page === 'main'" class="mb-2" />
            <SidebarNav class="mb-2" />
            <SidebarFooter />
        </div>
    </aside>
</template>

<script setup>
import SidebarOutlet from "./SidebarOutlet.vue";
import SidebarNav from "./SidebarNav.vue";
import SidebarFooter from "./SidebarFooter.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faChevronLeft } from "@fortawesome/free-solid-svg-icons";
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = computed(() => {
    const url = usePage().url;
    return url.startsWith("/merchant") ? "settings" : "main";
});
</script>
