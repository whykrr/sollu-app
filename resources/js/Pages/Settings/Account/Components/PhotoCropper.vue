<template>
    <div class="space-y-4 w-full">
        <!-- Existing Photo -->
        <div v-if="hasLogo && !image" class="flex flex-col items-center gap-4">
            <div class="relative">
                <img :src="preview" class="size-48 rounded-full object-cover border-2 border-slate-200 shadow-xs" />
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-outline-main btn-sm text-xs rounded-lg px-3 py-1.5 flex items-center gap-1.5"
                    @click="editImage"
                >
                    <FontAwesomeIcon :icon="faUpload" />
                    <span>Ganti Foto</span>
                </button>

                <Link
                    as="button"
                    method="DELETE"
                    class="btn btn-outline-danger btn-sm text-xs rounded-lg px-3 py-1.5 flex items-center gap-1.5"
                    :href="route('settings.account.profile.destroy.photo')"
                    preserve-scroll
                    :only="['profile']"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                    <span>Hapus</span>
                </Link>
            </div>
        </div>

        <!-- Upload Area -->
        <div v-if="!hasLogo || image" class="space-y-4">
            <!-- Cropper / Drop Area -->
            <div class="overflow-hidden">
                <!-- Empty Dropzone -->
                <div
                    v-if="!image"
                    class="relative overflow-hidden rounded-2xl border-2 border-dashed transition-all duration-200 cursor-pointer"
                    :class="[
                        dragging
                            ? 'border-main bg-main/5 scale-[1.01]'
                            : 'border-slate-300 hover:border-main hover:bg-slate-50',
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

                    <div class="flex flex-col items-center gap-3 p-6 text-center">
                        <div class="flex size-14 items-center justify-center rounded-full bg-main/10 text-main">
                            <FontAwesomeIcon :icon="faCloudArrowUp" class="text-2xl" />
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-sm font-semibold text-slate-800">
                                Unggah Foto Profil
                            </h4>
                            <p class="text-xs text-slate-500">
                                Drag & drop foto atau klik untuk memilih
                            </p>
                        </div>

                        <div class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-500">
                            PNG, JPG, WEBP (Maks. 2MB)
                        </div>
                    </div>
                </div>

                <!-- Cropper Canvas -->
                <div
                    v-if="image"
                    class="relative aspect-square max-h-[280px] overflow-hidden rounded-2xl border border-slate-200 bg-slate-900"
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

                    <div class="absolute left-2 top-2 rounded-full bg-black/60 px-2.5 py-1 text-[11px] text-white backdrop-blur-xs">
                        Sesuaikan area crop foto
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div v-if="image" class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-main flex-1 rounded-lg text-xs py-2 font-medium flex items-center justify-center gap-2"
                    @click="cropImage"
                >
                    <FontAwesomeIcon :icon="faCamera" />
                    <span>Simpan Foto</span>
                </button>

                <button
                    type="button"
                    class="btn btn-outline-danger size-9 rounded-lg flex items-center justify-center"
                    title="Batal"
                    @click="removeImage"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCamera,
    faCloudArrowUp,
    faTrash,
    faUpload,
} from '@fortawesome/free-solid-svg-icons';
import { CircleStencil, Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({
    url: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['action']);

const cropper = ref(null);
const image = ref(null);
const preview = ref(null);
const dragging = ref(false);

const hasLogo = computed(() => !!preview.value);

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

const handleFile = (file) => {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        image.value = event.target.result;
    };
    reader.readAsDataURL(file);
};

const onFileChange = (e) => {
    handleFile(e.target.files[0]);
};

const onDrop = (e) => {
    dragging.value = false;
    handleFile(e.dataTransfer.files[0]);
};

const cropImage = () => {
    const { canvas } = cropper.value.getResult();
    if (!canvas) return;

    preview.value = canvas.toDataURL('image/png');

    canvas.toBlob((blob) => {
        const file = new File([blob], 'photo.png', {
            type: 'image/png',
        });
        emit('action', file);
        image.value = null;
    }, 'image/png');
};

const editImage = () => {
    image.value = preview.value;
};

const removeImage = () => {
    image.value = null;
};
</script>
