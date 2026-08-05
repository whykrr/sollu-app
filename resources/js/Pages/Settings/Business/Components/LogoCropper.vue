<script setup>
import {
    faCamera,
    faCheck,
    faCloudArrowUp,
    faImage,
    faPen,
    faTrash,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed, ref, watch } from 'vue';
import { CircleStencil, Cropper } from 'vue-advanced-cropper';

import 'vue-advanced-cropper/dist/style.css';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    /*
    |--------------------------------------------------------------------------
    | Existing image url
    |--------------------------------------------------------------------------
    */

    url: {
        type: String,
        default: null,
    },
});

/*
|--------------------------------------------------------------------------
| Emit
|--------------------------------------------------------------------------
*/

const emit = defineEmits(['action']);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const cropper = ref(null);

const image = ref(null);
const preview = ref(null);
const dragging = ref(false);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const hasLogo = computed(() => !!preview.value);

/*
|--------------------------------------------------------------------------
| Watch Existing URL
|--------------------------------------------------------------------------
*/

watch(
    () => props.url,
    (value) => {
        if (value) {
            preview.value = value;
        }
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| Handle File
|--------------------------------------------------------------------------
*/

const handleFile = (file) => {
    if (!file) return;

    const reader = new FileReader();

    reader.onload = (event) => {
        image.value = event.target.result;
    };

    reader.readAsDataURL(file);
};

/*
|--------------------------------------------------------------------------
| File Change
|--------------------------------------------------------------------------
*/

const onFileChange = (e) => {
    handleFile(e.target.files[0]);
};

/*
|--------------------------------------------------------------------------
| Drop File
|--------------------------------------------------------------------------
*/

const onDrop = (e) => {
    dragging.value = false;

    handleFile(e.dataTransfer.files[0]);
};

/*
|--------------------------------------------------------------------------
| Crop Image
|--------------------------------------------------------------------------
*/

const cropImage = () => {
    const { canvas } = cropper.value.getResult();

    if (!canvas) return;

    preview.value = canvas.toDataURL('image/png');

    canvas.toBlob((blob) => {
        const file = new File([blob], 'logo.png', {
            type: 'image/png',
        });

        emit('action', file);

        image.value = null;
    }, 'image/png');
};

/*
|--------------------------------------------------------------------------
| Remove Image
|--------------------------------------------------------------------------
*/

const removeImage = () => {
    image.value = null;
    preview.value = null;

    emit('action', null);
};

/*
|--------------------------------------------------------------------------
| Edit Existing Image
|--------------------------------------------------------------------------
*/

const editImage = () => {
    image.value = preview.value;
};
</script>

<template>
    <div class="space-y-5">
        <!-- Existing Logo -->
        <div v-if="hasLogo && !image" class="flex flex-col items-center gap-5">
            <div class="relative">
                <img :src="preview" class="size-60 rounded-full object-cover" />
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="btn btn-success rounded-lg"
                    @click="editImage"
                >
                    <FontAwesomeIcon :icon="faPen" />

                    Ubah Logo
                </button>

                <button
                    type="button"
                    class="btn btn-outline-danger rounded-lg"
                    @click="removeImage"
                >
                    <FontAwesomeIcon :icon="faTrash" />

                    Hapus
                </button>
            </div>
        </div>

        <!-- Upload Area -->
        <div v-if="!hasLogo || image" class="space-y-5">
            <div
                class="relative overflow-hidden rounded-3xl border-2 border-dashed transition-all duration-300"
                :class="[
                    dragging
                        ? 'border-primary bg-primary/5 scale-[1.01]'
                        : 'border-neutral-300 hover:border-primary hover:bg-neutral-50',
                ]"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
                <input
                    type="file"
                    accept="image/*"
                    class="absolute inset-0 z-10 cursor-pointer opacity-0"
                    @change="onFileChange"
                />

                <div class="flex flex-col items-center gap-4 p-8 text-center">
                    <div
                        class="flex size-18 items-center justify-center rounded-full bg-primary/10"
                    >
                        <FontAwesomeIcon
                            :icon="faCloudArrowUp"
                            class="text-primary text-3xl"
                        />
                    </div>

                    <div class="space-y-1">
                        <h3 class="text-lg font-semibold text-neutral-800">
                            Upload Logo
                        </h3>

                        <p class="text-sm text-neutral-500">
                            Drag & drop gambar atau klik untuk memilih file
                        </p>
                    </div>

                    <div
                        class="rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-500"
                    >
                        PNG, JPG, WEBP
                    </div>
                </div>
            </div>

            <!-- Cropper -->
            <div class="overflow-hidden rounded-3xl border bg-white">
                <!-- Empty -->
                <div
                    v-if="!image"
                    class="flex aspect-square flex-col items-center justify-center gap-4 bg-neutral-50"
                >
                    <div
                        class="flex size-50 items-center justify-center rounded-full border-4 border-dashed border-neutral-300"
                    >
                        <FontAwesomeIcon
                            :icon="faImage"
                            class="text-6xl text-neutral-300"
                        />
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-neutral-400">
                            Belum ada logo di upload
                        </p>
                    </div>
                </div>

                <!-- Cropper -->
                <div
                    v-if="image"
                    class="relative w-full aspect-square max-h-[300px] overflow-hidden"
                >
                    <Cropper
                        ref="cropper"
                        :src="image"
                        :auto-zoom="true"
                        :stencil-component="CircleStencil"
                        :stencil-props="{
                            aspectRatio: 1,
                        }"
                        class="h-full w-full"
                        :canvas="{
                            maxWidth: 200,
                            maxHeight: 200,
                        }"
                    />

                    <div
                        class="absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs text-white backdrop-blur"
                    >
                        Geser untuk menyesuaikan logo
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div v-if="image" class="flex gap-3">
                <button
                    type="button"
                    class="btn btn-main flex-1 rounded-2xl"
                    @click="cropImage"
                >
                    <FontAwesomeIcon :icon="faCamera" class="mr-2" />

                    Simpan Logo
                </button>

                <button
                    type="button"
                    class="flex size-12 items-center justify-center rounded-2xl border border-red-200 bg-red-50 text-red-500 transition hover:bg-red-100"
                    @click="removeImage"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </div>
        </div>
    </div>
</template>
