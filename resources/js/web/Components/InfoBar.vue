<template>
    <div
        class="min-h-[30px] bg-gray-200 flex flex-col md:flex-row gap-2 md:gap-0 font-normal md:justify-between p-2 px-2 md:px-8 xl:px-16 md:py-0 text-sm"
    >
        <div
            class="flex flex-col md:flex-row gap-1 md:gap-4 text-web-main items-center justify-center md:justify-start"
        >
            <div>
                <fa icon="fa-phone" />
                <a
                    :href="'https://wa.me/' + socialMedia.whatsapp"
                    target="_blank"
                    class="ml-2"
                    >+{{ socialMedia.whatsapp }}</a
                >
            </div>
            <div>
                <fa icon="fa-envelope" />
                <a :href="'mailto:info@sman2-tegineneng.sch.id'" class="ml-2"
                    >info@sman2-tegineneng.sch.id</a
                >
            </div>
        </div>
        <div
            class="flex flex-row gap-4 items-center justify-center md:justify-end text-lg"
        >
            <a :href="socialMedia.facebook">
                <fa icon="fa-brands fa-facebook" class="text-blue-500" />
            </a>
            <a :href="socialMedia.instagram">
                <fa icon="fa-brands fa-instagram" />
            </a>
            <a :href="socialMedia.tiktok">
                <fa icon="fa-brands fa-tiktok" />
            </a>
            <a :href="socialMedia.youtube">
                <fa icon="fa-brands fa-youtube" class="text-red-500" />
            </a>
        </div>
    </div>
</template>
<script setup>
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const socialMedia = page.props.socialMedia;
</script>
