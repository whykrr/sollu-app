<script setup>
import { onMounted, ref } from "vue";
import { CircleStencil, Cropper } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css";

const emit = defineEmits(["action"]);

const image = ref(null);
const cropper = ref(null);

onMounted(() => {
    image.value = null;
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            image.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const cropImage = () => {
    const { canvas } = cropper.value.getResult();
    if (canvas) {
        canvas.toBlob((blob) => {
            const file = new File([blob], "logo.png", { type: "image/png" });
            emit("action", file);
        }, "image/png");
    }
};
</script>

<template>
    <div class="space-y-4">
        <input
            type="file"
            accept="image/*"
            class="form"
            @change="onFileChange"
        />

        <!-- Cropper area -->
        <div
            v-if="image"
            class="w-full aspect-square border rounded-lg overflow-hidden"
        >
            <Cropper
                ref="cropper"
                :src="image"
                :stencil-component="CircleStencil"
                class="w-full h-full"
            />
        </div>

        <!-- Action -->
        <button
            type="button"
            class="btn btn-main btn-sm block"
            @click="cropImage"
            v-if="image"
        >
            Simpan
        </button>
    </div>
</template>
