<template>
    <div class="p-2 relative">
        <button
            type="button"
            class="bg-linear-to-r from-main to-secondary rounded-md p-2 cursor-pointer w-full"
            @click="toggleAccountDropdown"
        >
            <div class="flex flex-row gap-2 items-center text-white text-sm">
                <div>
                    <img
                        src="https://dummyimage.com/35x35'"
                        alt="Profile"
                        class="rounded-full w-[35px] h-[35px]"
                    />
                </div>
                <div class="grow text-left">
                    {{ auth.name }}
                </div>
                <FontAwesomeIcon
                    :icon="faChevronUp"
                    class="transition-transform duration-200"
                    :class="{ 'rotate-180': accountDropdown }"
                />
            </div>
        </button>
        <div
            v-if="accountDropdown"
            class="fixed inset-0"
            @click="toggleAccountDropdown"
        />
        <div
            class="dropdown-account"
            :class="{
                show: accountDropdown,
            }"
        >
            <div
                class="flex flex-row gap-2 pb-2 items-center border-b border-gray-200 mb-0.5"
            >
                <div>
                    <img
                        :src="
                            'https://ui-avatars.com/api/?name=' +
                            auth.name +
                            '&size=40'
                        "
                        alt="Profile"
                        class="rounded-full w-[50px] h-[50px]"
                    />
                </div>
                <div class="grow">
                    <div class="font-semibold">
                        {{ auth.name }}
                    </div>
                    <div class="text-sm">
                        {{ auth.role[0] }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-0.5 text-sm">
                <Link href="#" class="sidebar-item rounded-lg px-2 py-1">
                    <FontAwesomeIcon :icon="faUser"></FontAwesomeIcon>
                    Akun Saya
                </Link>
                <Link
                    :href="route('dashboard.logout')"
                    class="sidebar-item rounded-lg px-2 py-1"
                    method="delete"
                    as="button"
                >
                    <FontAwesomeIcon
                        :icon="faRightFromBracket"
                    ></FontAwesomeIcon>
                    Keluar
                </Link>
            </div>
        </div>
    </div>
</template>
<script setup>
import {
    faChevronUp,
    faRightFromBracket,
    faUser,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const page = usePage();
const auth = computed(() => page.props.auth);

const accountDropdown = ref(false);

const toggleAccountDropdown = () =>
    (accountDropdown.value = !accountDropdown.value);

watch(
    () => page.url,
    () => {
        accountDropdown.value = false;
    }
);
</script>
