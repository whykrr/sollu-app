<template>
    <div class="px-2 relative space-y-2 pb-2">
        <a
            href="#"
            target="_blank"
            class="flex flex-row gap-2 items-center py-0 px-2 cursor-pointer rounded-lg text-neutral-600 hover:text-neutral-900"
        >
            <FontAwesomeIcon :icon="faCircleQuestion" />
            <div class="text-sm">Pusat Bantuan</div>
            <div class="flex-1"></div>
            <div>
                <FontAwesomeIcon
                    :icon="faArrowUpFromBracket"
                    class="text-[15px]"
                />
            </div>
        </a>
        <Link
            href="#"
            class="flex flex-row gap-2 items-center py-0 px-2 cursor-pointer rounded-lg text-neutral-600 hover:text-neutral-900"
        >
            <FontAwesomeIcon :icon="faCog" />
            <div class="text-sm grow">Pengaturan</div>
        </Link>

        <div
            class="flex flex-col gap-1.5 rounded-lg border border-neutral-300 p-2"
        >
            <div>
                <div class="inline-block p-1.5 bg-info/20 rounded-md text-xs">
                    <FontAwesomeIcon :icon="faBolt" class="text-sm" />
                    Uji Coba Gratis
                </div>
            </div>
            <div class="text-xs">
                Masa uji coba gratis anda akan berakhir dalam
                <span class="font-medium">15 hari</span>
            </div>
            <Link href="#" class="btn btn-outline-main btn-sm justify-center"
                >Langganan Sekarang</Link
            >
        </div>
    </div>
</template>

<script setup>
import {
    faArrowUpFromBracket,
    faBolt,
    faCircleQuestion,
    faCog,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link } from "@inertiajs/vue3";
</script>
