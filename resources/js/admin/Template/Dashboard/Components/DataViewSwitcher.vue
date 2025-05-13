<template>
    <div class="btn-group">
        <button
            class="btn btn-outline-main"
            :class="{
                active: isViewList,
            }"
            @click="switchView(true)"
        >
            <fa icon="fa-list" />
        </button>
        <button
            class="right"
            :class="{
                active: !isViewList,
            }"
            @click="switchView(false)"
        >
            <fa icon="fa-border-all" />
        </button>
    </div>
</template>

<script setup>
import { ref, defineEmits } from "vue";

const isViewList = ref(true);
const emit = defineEmits(["isList"]);

const switchView = (val) => {
    isViewList.value = val;
    emit("isList", isViewList.value);
};
</script>
