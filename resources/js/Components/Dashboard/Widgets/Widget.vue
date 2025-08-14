<template>
    <div class="widget">
        <div class="flex flex-row items-center gap-2">
            <div>
                <div class="widget-icon">
                    <FontAwesomeIcon :icon="faBell" />
                </div>
            </div>
            <h2 class="font-medium">{{ title }}</h2>
        </div>
        <div class="text-gray-8 font-bold">
            <slot></slot>
        </div>
        <div>
            <div class="inline-flex space-x-1.5 items-center">
                <FontAwesomeIcon
                    :icon="faArrowUp"
                    class="text-sm text-success"
                />
                <div class="text-xs text-gray-600">dari bulan lalu</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { faArrowUp, faBell } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { computed, ref } from "vue";

const props = defineProps({
    icon: Object,
    title: String,
});
</script>
