<template>
    <div class="relative" ref="dropdownRef">
        <a
            href="#"
            class="flex flex-row items-center px-2 gap-1 h-8 min-w-8 text-slate-700 bg-white rounded-lg drop-shadow-sm shadow-neutral-100"
            @click="togglePanel"
        >
            <div>
                <div class="text-base font-medium">
                    {{ auth.name }}
                </div>
            </div>
            <img
                :src="
                    'https://ui-avatars.com/api/?name=' + auth.name + '&size=40'
                "
                alt="Profile"
                class="rounded-full w-6 h-6"
            />
        </a>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="showPanel"
                class="absolute bg-gray-300/50 backdrop-blur-lg rounded-lg w-70 top-10 right-0 shadow-lg shadow-neutral-300 p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4">
                        <a href="#" @click="closePanel">
                            <fa icon="close" />
                        </a>
                    </div>
                    <div class="text-center text-sm font-medium">
                        {{ auth.email }}
                    </div>
                    <div class="m-auto">
                        <img
                            :src="
                                'https://ui-avatars.com/api/?name=' +
                                auth.name +
                                '&size=40&background=fff'
                            "
                            alt="Profile"
                            class="rounded-full w-16 h-16"
                        />
                    </div>
                    <div class="text-center text-xl">
                        {{ auth.name }}
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
        label: "Info Akun",
        icon: "fa-user",
        link: "#",
        method: "get",
    },
    {
        label: "Ubah kata sandi",
        icon: "fa-key",
        link: "#",
        method: "get",
    },
    {
        label: "Keluar",
        icon: "fa-right-from-bracket",
        link: route("logout"),
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
