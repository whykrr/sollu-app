<template>
    <div class="btn-group">
        <button
            class="btn btn-outline-main"
            :class="{
                active: isViewList,
            }"
            @click="switchView(true)"
        >
            <FontAwesomeIcon :icon="faList" />
        </button>
        <button
            class="right"
            :class="{
                active: !isViewList,
            }"
            @click="switchView(false)"
        >
            <FontAwesomeIcon :icon="faBorderAll" />
        </button>
    </div>
</template>

<script setup>
import { faBorderAll, faList } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { ref, defineEmits } from "vue";

const isViewList = ref(true);
const emit = defineEmits(["isList"]);

const switchView = (val) => {
    isViewList.value = val;
    emit("isList", isViewList.value);
};
</script>
