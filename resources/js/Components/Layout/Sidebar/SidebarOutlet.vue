<template>
    <div class="relative h-11" ref="dropdownRef">
        <div class="absolute w-full">
            <div class="bg-white/90 rounded-lg drop-shadow mx-2">
                <a
                    href="#"
                    class="flex flex-row items-center min-h-11 px-2 gap-1.5"
                    @click="selectOutlet"
                >
                    <div
                        class="flex items-center rounded-full text-sm bg-main/20 text-main h-[30px] w-[30px]"
                    >
                        <fa icon="fa-map-marker-alt" class="m-auto" />
                    </div>
                    <div class="flex-1 font-medium text-sm truncate">
                        Semua Outlet
                    </div>
                    <div class="text-[10px] flex flex-col -space-y-0.5">
                        <fa icon="fa-chevron-up" />
                        <fa icon="fa-chevron-down" />
                    </div>
                </a>
                <div
                    class="top-8 w-full rounded-b-lg bg-white overflow-hidden transition-all duration-500 ease-in-out"
                    :class="isOpen ? 'max-h-40' : 'max-h-0'"
                >
                    <div class="text-sm">
                        <ol class="">
                            <li v-for="outlet in outlets">
                                <Link
                                    href="#"
                                    class="hover:bg-neutral-light py-1.5 px-2 block"
                                    >{{ outlet.name }}</Link
                                >
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";
import { onBeforeMount, onMounted, ref } from "vue";

const isOpen = ref(false);
const dropdownRef = ref(null);

const outlets = [
    {
        id: 1,
        name: "Outlet Satu",
    },
    {
        id: 1,
        name: "Outlet Dua",
    },
    {
        id: 1,
        name: "Outlet Outlet 3",
    },
];

const selectOutlet = () => {
    isOpen.value = !isOpen.value;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeMount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>
