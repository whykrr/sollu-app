<template>
    <div class="flex flex-col gap-4">
        <Card title="Example Modal">
            <button class="btn btn-info" @click="openModal('basicModal')">
                Click to show modal!
            </button>

            <Modal
                :class="{
                    show: activeModal == 'basicModal',
                    hide: !activeModal != 'basicModal',
                }"
                title="Modal Title"
                @close="closeModal"
            >
                <p class="text-gray-600">
                    This is a simple modal. You can add any content here.
                </p>
                <template #footer>
                    <button class="btn btn-main" @click="closeModal">
                        Close
                    </button>
                </template>
            </Modal>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
import Modal from "@/Components/UI/Modal.vue";
import { ref } from "vue";

const activeModal = ref(null);
const openModal = (modalName) => {
    activeModal.value = modalName;
};
const closeModal = () => {
    activeModal.value = null;
};
</script>
