<template>
    <aside class="hidden sm:flex w-60 md:w-72 h-screen z-30">
        <div
            class="flex flex-col justify-between gap-3 shadow bg-neutral-light w-full"
        >
            <div>
                <div
                    class="flex flex-row justify-between items-center px-2.5 pt-3"
                >
                    <img
                        src="storage/img/logo-fit-color.png"
                        class="w-22"
                        alt="Sollu"
                    />
                    <div class="pt-2 text-sm">
                        <fa icon="fa-bars" />
                    </div>
                </div>
            </div>
            <SidebarOutlet />
            <SidebarNav />
            <AccountInfo />
        </div>
    </aside>
</template>

<script setup>
import SidebarItem from "@/Components/Layout/Sidebar/SidebarItem.vue";
import SidebarItemExpand from "@/Components/Layout/Sidebar/SidebarItemExpand.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch, watchEffect } from "vue";
import AccountInfo from "@/Components/UI/AccountInfo.vue";
import SidebarIcon from "./SidebarNav.vue";
import SidebarOutlet from "./SidebarOutlet.vue";
import SidebarNav from "./SidebarNav.vue";
</script>
