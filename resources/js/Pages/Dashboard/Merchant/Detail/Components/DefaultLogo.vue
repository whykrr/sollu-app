<template>
    <div
        class="flex justify-center items-center w-full aspect-square rounded-full bg-secondary/30 border border-neutral-200"
    >
        <FontAwesomeIcon
            :icon="faShop"
            class="text-secondary-dark text-[100px]"
        />
    </div>
</template>

<script setup>
import { faShop } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
</script>
