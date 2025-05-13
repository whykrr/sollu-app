<template>
    <div class="modal">
        <div class="modal-container">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="text-lg font-bold">{{ title }}</h2>
                <button
                    id="closeModalBtn"
                    class="text-gray-500 hover:text-gray-700 focus:outline-none"
                    @click="closeModal"
                >
                    ✖
                </button>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <slot></slot>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer">
                <slot name="footer"></slot>
            </div>
        </div>
    </div>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

defineProps({
    title: String,
});

const emit = defineEmits(["close"]);
const closeModal = () => {
    emit("close");
};
</script>
