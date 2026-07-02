<template>
    <div>
        <!-- Preview and Button -->
        <div v-if="!modelValue" class="flex flex-col items-center justify-center w-full h-48 border-2 border-neutral-300 border-dashed rounded-lg cursor-pointer bg-neutral-50 hover:bg-neutral-100" @click="triggerFileInput">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <FontAwesomeIcon :icon="faCloudUploadAlt" class="w-8 h-8 mb-3 text-neutral-400" />
                <p class="mb-2 text-sm text-neutral-500"><span class="font-semibold">Klik untuk unggah</span> foto produk</p>
                <p class="text-xs text-neutral-500">PNG, JPG atau WEBP (Max. 2MB)</p>
            </div>
        </div>

        <div v-else class="relative w-48 h-48 rounded-lg overflow-hidden border border-neutral-200">
            <img :src="modelValue" class="w-full h-full object-cover" alt="Product Image Preview" />
            <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button type="button" class="btn btn-outline-main btn-sm text-white border-white hover:bg-white hover:text-black" @click="triggerFileInput">
                    Ganti
                </button>
                <button type="button" class="btn btn-danger btn-sm" @click="removeImage">
                    Hapus
                </button>
            </div>
        </div>

        <input 
            type="file" 
            ref="fileInput" 
            class="hidden" 
            accept="image/png, image/jpeg, image/webp"
            @change="onFileChange"
        />

        <div v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</div>

        <!-- Cropper Modal -->
        <PopUpPage v-if="showCropper" title="Potong Gambar" size="md" @close="closeCropper">
            <div class="p-3">
                <div class="w-full h-64 bg-neutral-900 rounded-lg overflow-hidden">
                    <Cropper
                        ref="cropperRef"
                        class="h-full w-full"
                        :src="imageSrc"
                        :stencil-props="{
                            aspectRatio: 1,
                        }"
                    />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <button type="button" class="btn btn-outline-main btn-sm" @click="closeCropper">Batal</button>
                    <button type="button" class="btn btn-highlight-main btn-sm" @click="crop">Potong & Simpan</button>
                </div>
            </template>
        </PopUpPage>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCloudUploadAlt } from '@fortawesome/free-solid-svg-icons';
import PopUpPage from '@/Components/UI/PopUpPage.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    error: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const cropperRef = ref(null);
const imageSrc = ref(null);
const showCropper = ref(false);

const triggerFileInput = () => {
    fileInput.value.click();
};

const removeImage = () => {
    emit('update:modelValue', null);
    if (fileInput.value) fileInput.value.value = '';
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        imageSrc.value = event.target.result;
        showCropper.value = true;
    };
    reader.readAsDataURL(file);
    e.target.value = '';
};

const closeCropper = () => {
    showCropper.value = false;
    imageSrc.value = null;
};

const crop = () => {
    if (cropperRef.value) {
        const { canvas } = cropperRef.value.getResult();
        if (canvas) {
            emit('update:modelValue', canvas.toDataURL());
            closeCropper();
        }
    }
};
</script>
