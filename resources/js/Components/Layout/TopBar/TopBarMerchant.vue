<template>
    <div class="relative" ref="dropdownRef">
        <div
            class="bg-neutral-200 hover:p-1 hover:-m-1 rounded-full"
            :class="{ 'p-1 -m-1': showPanel }"
        >
            <a
                href="#"
                class="flex flex-row items-center gap-2 h-9 pl-1 pr-3 bg-white rounded-full drop-shadow"
                @click="togglePanel"
            >
                <img
                    :src="'https://dummyimage.com/35x35'"
                    alt="Merchant"
                    class="rounded-full w-7 h-7"
                />
                <span class="font-medium">{{ auth.merchant }}</span>
            </a>
        </div>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="showPanel"
                class="absolute bg-gray-300/50 backdrop-blur-lg rounded-lg w-96 top-12 -right-12 shadow-lg shadow-neutral-300 p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4">
                        <a href="#" @click="closePanel">
                            <fa icon="close" />
                        </a>
                    </div>
                    <div class="text-center text-lg font-medium">
                        Informasi Bisnis
                    </div>
                    <div
                        class="bg-white rounded-xl overflow-hidden p-2 space-y-2"
                    >
                        <div class="flex flex-row gap-2 items-center">
                            <div>
                                <img
                                    :src="'https://dummyimage.com/200x100'"
                                    alt="Profile"
                                    class="rounded-lg w-auto h-16"
                                />
                            </div>

                            <div class="text-center text-xl">
                                {{ auth.merchant }}
                            </div>
                        </div>
                        <div class="grid grid-flow-row gap-1 text-sm">
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Jenis Usaha</div>
                                <div>Toko Konvensional</div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Langganan</div>
                                <div>Free Trial</div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Masa Berlaku</div>
                                <div>12 Januari 2026</div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Jumlah Outlet</div>
                                <div>2</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden">
                        <ol>
                            <li v-for="item in accountLinks">
                                <Link
                                    :href="item.link"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm"
                                    :method="item.method"
                                >
                                    <fa :icon="item.icon"></fa>
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { method } from "lodash";
import { computed, onBeforeMount, onMounted, ref } from "vue";

const page = usePage();
const auth = computed(() => page.props.auth);
const showPanel = ref(false);
const dropdownRef = ref(null);

const togglePanel = () => {
    showPanel.value = !showPanel.value;
};

const closePanel = () => {
    showPanel.value = false;
};

const accountLinks = [
    {
        label: "Info Bisnis",
        icon: "fa-shop",
        link: "#",
        method: "get",
    },
    {
        label: "Langganan & Tagihan",
        icon: "fa-credit-card",
        link: "#",
        method: "get",
    },
    {
        label: "Pengaturan",
        icon: "fa-cogs",
        link: "#",
        method: "get",
    },
];

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showPanel.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeMount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>
