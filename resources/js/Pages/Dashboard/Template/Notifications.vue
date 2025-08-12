<template>
    <Container>
        <div class="flex flex-col gap-3">
            <Card title="Alert">
                <div class="flex flex-col gap-2">
                    <div class="alert">Basic Alert</div>
                    <div class="alert alert-success">Alert Success</div>
                    <div class="alert alert-warning">Alert Warning</div>
                    <div class="alert alert-danger">Alert Danger</div>
                    <div class="alert alert-success">
                        <span class="font-semibold">Well Done!</span>
                        <div class="text-sm">
                            Lorem ipsum dolor sit, amet consectetur adipisicing
                            elit. Facere possimus numquam repellendus minus
                            doloribus reprehenderit alias veniam. Repudiandae
                            sed mollitia aliquam aperiam, est molestias eveniet.
                            Vero placeat esse enim soluta.
                        </div>
                    </div>
                </div>
            </Card>

            <Card title="Badge">
                <div class="flex flex-col gap-1">
                    <p>Available for all color and shades</p>
                    <span class="badge badge-main text-2xl">Main</span>
                    <span class="badge badge-secondary text-xl">Secondary</span>
                    <span class="badge badge-info text-lg">Info</span>
                    <span class="badge badge-success text-base">Success</span>
                    <span class="badge badge-warning text-sm">Warning</span>
                    <span class="badge badge-danger text-xs">Danger</span>
                </div>
            </Card>
            <Card title="Example Modal">
                <button class="btn btn-main" @click="openModal('basicModal')">
                    Click to show modal!
                </button>

                <Modal
                    :class="{
                        show: activeModal == 'basicModal',
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
            <Card title="Example Toast">
                <button class="btn btn-main" @click="openToast()">
                    Click to show toast!
                </button>

                <Toast
                    v-if="showToast"
                    :icon="faCheck"
                    title="Success!"
                    color="success"
                    @hide="updateToast()"
                >
                    Your action was successful Your action was successful.
                </Toast>
            </Card>
        </div>
    </Container>
</template>
<script setup>
import Modal from "@/Components/Dashboard/Notifications/Modal.vue";
import Toast from "@/Components/Dashboard/Notifications/Toast.vue";
import Card from "@/Components/Dashboard/UI/Card.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { faCheck } from "@fortawesome/free-solid-svg-icons";
import { ref } from "vue";

const activeModal = ref(null);
const openModal = (modalName) => {
    activeModal.value = modalName;
};
const closeModal = () => {
    activeModal.value = null;
};

const showToast = ref(false);
const openToast = () => {
    showToast.value = true;
};
const updateToast = () => {
    showToast.value = false;
};
</script>
