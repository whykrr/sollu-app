<template>
    <div
        class="toast"
        :class="{
            hide: !showToast,
            show: showToast,
        }"
    >
        <div class="flex flex-col">
            <strong>
                <fa :icon />
                {{ title }}
            </strong>
            <div class="text-sm">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    title: String,
    icon: String,
});
import { onMounted, ref } from "vue";

const showToast = ref(false);

const emit = defineEmits(["hide"]);

onMounted(() => {
    showToast.value = false;
    setTimeout(() => {
        showToast.value = true;
    }, 100);
    setTimeout(() => {
        showToast.value = false;
    }, 3100);
    setTimeout(() => {
        emit("hide");
    }, 3200);
});
</script>
