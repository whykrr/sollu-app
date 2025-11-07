<template>
    <div ref="dropdownRef">
        <div
            class="bg-neutral-200/70 hover:p-1 hover:-m-1 rounded-full transition-all duration-150 ease-in-out"
            :class="{ 'p-1 -m-1': showPanel }"
        >
            <a href="#" class="text-slate-700" @click.prevent="togglePanel">
                <div
                    class="rounded-full w-10 h-10 bg-white/90 flex items-center justify-center border"
                >
                    {{ initials }}
                </div>
                <!-- <img
                    :src="
                        'https://ui-avatars.com/api/?name=' +
                        auth.name +
                        '&size=40&background=fff'
                    "
                    alt="Profile"
                    class="rounded-full w-9 h-9"
                /> -->
            </a>
        </div>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="showPanel"
                class="absolute bg-gray-300/50 backdrop-blur-sm rounded-lg w-70 top-[48px] right-0 shadow-lg shadow-neutral-300 p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4">
                        <a href="#" @click.prevent="closePanel">
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>
                    <div class="text-center text-sm font-medium">
                        {{ auth.email }}
                    </div>
                    <div class="m-auto">
                        <div
                            class="rounded-full w-16 h-16 text-2xl bg-white flex items-center justify-center"
                        >
                            {{ initials }}
                        </div>
                        <!-- <img
                            :src="
                                'https://ui-avatars.com/api/?name=' +
                                auth.name +
                                '&size=40&background=fff'
                            "
                            alt="Profile"
                            class="rounded-full w-16 h-16"
                        /> -->
                    </div>
                    <div class="text-center text-xl">
                        {{ auth.name }}
                    </div>
                    <div class="bg-white rounded-xl overflow-hidden">
                        <ol>
                            <li v-for="item in accountLinks">
                                <Link
                                    v-if="item.method == 'delete'"
                                    :href="item.link"
                                    class="flex items-center w-full gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
                                    method="delete"
                                    as="button"
                                >
                                    <FontAwesomeIcon
                                        :icon="item.icon"
                                    ></FontAwesomeIcon>
                                    {{ item.label }}
                                </Link>
                                <Link
                                    v-else
                                    :href="item.link"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
                                >
                                    <FontAwesomeIcon
                                        :icon="item.icon"
                                    ></FontAwesomeIcon>
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
import {
    faClose,
    faKey,
    faRightFromBracket,
    faUser,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, usePage } from "@inertiajs/vue3";
import { method } from "lodash";
import { computed, onBeforeMount, onMounted, ref } from "vue";

const auth = computed(() => usePage().props.auth);
const showPanel = ref(false);
const dropdownRef = ref(null);

const initials = computed(() => {
    const name = auth.value?.name || "";
    return name
        .split(" ")
        .map((word) => word[0])
        .join("")
        .substring(0, 2)
        .toUpperCase();
});

const togglePanel = () => {
    showPanel.value = !showPanel.value;
};

const closePanel = () => {
    showPanel.value = false;
};

const accountLinks = [
    {
        label: "Info Akun",
        icon: faUser,
        link: "#",
        method: "get",
    },
    {
        label: "Ubah kata sandi",
        icon: faKey,
        link: "#",
    },
    {
        label: "Keluar",
        icon: faRightFromBracket,
        link: route("dashboard.logout"),
        method: "delete",
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
