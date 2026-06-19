<script setup>
import {
    faCamera,
    faCheck,
    faCloudArrowUp,
    faImage,
    faPen,
    faTrash,
    faUpload,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';
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
| Edit Existing Image
|--------------------------------------------------------------------------
*/

const editImage = () => {
    image.value = preview.value;
};

/*
|--------------------------------------------------------------------------
| Remove Image / Cancel Crop
|--------------------------------------------------------------------------
*/

const removeImage = () => {
    image.value = null;
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
                    class="btn border-2 border-dashed border-neutral-400 text-neutral-400 rounded-lg"
                    @click="editImage"
                >
                    <FontAwesomeIcon :icon="faUpload" />

                    Ganti Foto
                </button>

                <Link
                    as="button"
                    method="DELETE"
                    class="btn btn-outline-danger rounded-lg"
                    :href="route('settings.account.profile.destroy.photo')"
                    preserve-scroll
                    :only="['profile']"
                >
                    <FontAwesomeIcon :icon="faTrash" />

                    Hapus
                </Link>
            </div>
        </div>

        <!-- Upload Area -->
        <div v-if="!hasLogo || image" class="space-y-5">
            <!-- Cropper -->
            <div class="overflow-hidden">
                <!-- Empty -->
                <div
                    v-if="!image"
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

                    <div
                        class="flex flex-col items-center gap-4 p-8 text-center"
                    >
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
                <div
                    v-if="image"
                    class="relative aspect-square overflow-hidden"
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
                    />

                    <div
                        class="absolute left-2 top-2 rounded-full bg-black/60 px-3 py-1 text-xs text-white backdrop-blur"
                    >
                        Geser untuk menyesuaikan foto
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div v-if="image" class="flex gap-3">
                <button
                    type="button"
                    class="btn btn-main flex-1 rounded-lg"
                    @click="cropImage"
                >
                    <FontAwesomeIcon :icon="faCamera" class="mr-2" />

                    Simpan Foto
                </button>

                <button
                    type="button"
                    class="flex size-12 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition hover:bg-red-100"
                    @click="removeImage"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </div>
        </div>
    </div>
</template>
