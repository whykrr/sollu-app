<template>
    <aside
        class="sidebar"
        :class="{
            minimize: appStore.sidebar.minimize,
            show: appStore.sidebar.show,
        }"
        ref="sidebarRef"
    >
        <div class="sidebar-container">
            <div>
                <div
                    class="flex justify-between items-center px-2 min-h-16 relative"
                >
                    <Link :href="route('dashboard.overview')">
                        <img
                            src="storage/img/logo-fit-color.png"
                            class="w-22"
                            alt="Sollu"
                        />
                    </Link>
                    <div
                        @click="appStore.hide"
                        class="block sm:hidden text-sm cursor-pointer"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </div>

                    <Transition name="spin" mode="out-in">
                        <div
                            v-if="!appStore.sidebar.minimize"
                            @click="appStore.minimize()"
                            class="hidden sm:block text-nowrap -space-x-1 p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150"
                        >
                            <FontAwesomeIcon :icon="faChevronLeft" />
                            <FontAwesomeIcon :icon="faChevronLeft" />
                        </div>
                        <div
                            @click="appStore.maximize()"
                            v-else
                            class="hidden sm:block p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150 group-[lock]:"
                        >
                            <FontAwesomeIcon :icon="faLock" />
                        </div>
                    </Transition>
                </div>
            </div>
            <SidebarOutlet />
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
import {
    faBars,
    faChevronLeft,
    faClose,
    faLock,
} from "@fortawesome/free-solid-svg-icons";
import { computed, onBeforeMount, onMounted, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useAppStore } from "@/store/Dashboard/app";

const page = computed(() => {
    const url = usePage().url;
    return url.startsWith("/merchant") ? "settings" : "main";
});

const appStore = useAppStore();

// const handleClickOutside = (event) => {
//     if (sidebarRef.value && !sidebarRef.value.contains(event.target)) {
//         appStore.hide();
//     }
// };

// onMounted(() => {
//     document.addEventListener("click", handleClickOutside);
// });

// onBeforeMount(() => {
//     document.removeEventListener("click", handleClickOutside);
// });

router.on("finish", () => appStore.hide());
</script>
