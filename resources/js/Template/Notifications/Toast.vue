<template>
    <div class="flex flex-col gap-4">
        <Card title="Example Toast">
            <button class="btn btn-main" @click="openToast()">
                Click to show toast!
            </button>

            <Toast
                v-if="showToast"
                icon="fa-check"
                title="Success!"
                class="bg-success"
                @hide="updateToast()"
            >
                Your action was successful Your action was successful.
            </Toast>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
import Toast from "@/Components/UI/Toast.vue";
import { ref } from "vue";

const showToast = ref(false);
const openToast = () => {
    showToast.value = true;
};
const updateToast = () => {
    showToast.value = false;
};
</script>
