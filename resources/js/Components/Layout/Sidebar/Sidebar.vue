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
            <SidebarFooter />
        </div>
    </aside>
</template>

<script setup>
import SidebarOutlet from "./SidebarOutlet.vue";
import SidebarNav from "./SidebarNav.vue";
import SidebarFooter from "./SidebarFooter.vue";
</script>
