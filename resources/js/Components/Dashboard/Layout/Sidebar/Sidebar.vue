<template>
    <aside class="hidden sm:flex w-60 md:w-72 h-screen z-30">
        <div
            class="flex flex-col justify-between bg-neutral-light w-full border-r shadow"
        >
            <div>
                <div
                    class="flex flex-row justify-between items-center px-4 h-16"
                >
                    <img
                        src="storage/img/logo-fit-color.png"
                        class="w-22"
                        alt="Sollu"
                    />
                    <div class="text-sm cursor-pointer">
                        <fa icon="fa-chevron-left" />
                        <fa icon="fa-chevron-left" />
                    </div>
                </div>
            </div>
            <SidebarOutlet class="mb-2" />
            <SidebarNav class="mb-2" />
            <SidebarFooter />
        </div>
    </aside>
</template>

<script setup>
import SidebarOutlet from "./SidebarOutlet.vue";
import SidebarNav from "./SidebarNav.vue";
import SidebarFooter from "./SidebarFooter.vue";
</script>
