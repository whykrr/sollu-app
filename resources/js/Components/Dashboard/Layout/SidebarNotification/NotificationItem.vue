<template>
    <div class="relative bg-white px-3 py-2 hover:bg-neutral-300/30 rounded-lg">
        <span
            v-if="!read"
            class="absolute w-2 h-2 bg-danger top-2 left-2 rounded-full"
        ></span>
        <div class="flex flex-row gap-2">
            <div>
                <div
                    class="flex items-center justify-center rounded-full bg-neutral-300 h-10 w-10"
                >
                    <FontAwesomeIcon :icon="faShop" class="m-auto" />
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-sm">
                    <span class="font-semibold mr-1">
                        {{ title }}
                    </span>
                    <span>
                        {{ message }}
                    </span>
                    <div class="text-xs text-neutral-400">
                        {{ formatDateTime(timestamp) }}
                    </div>
                </div>
                <div>
                    <button class="btn btn-highlight-info btn-xs">
                        Lihat Detail
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { formatDateTime } from "@/helpers/Dashboard/time";
import { faShop } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

defineProps({
    read: {
        type: Boolean,
        default: true,
    },
    title: String,
    message: String,
    timestamp: String,
});
</script>
