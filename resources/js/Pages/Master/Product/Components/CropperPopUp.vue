<template>
    <div>
        <div class="p-3">
            <div class="w-full h-64 bg-neutral-900 rounded-lg overflow-hidden">
                <Cropper
                    ref="cropperRef"
                    class="h-full w-full"
                    :src="imageSrc"
                    :stencil-props="{ aspectRatio: 1 }"
                />
            </div>
        </div>
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button type="button" class="btn btn-outline-main btn-sm" @click="close">Batal</button>
                <button type="button" class="btn btn-highlight-main btn-sm" @click="crop">Potong & Simpan</button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const props = defineProps({ imageSrc: String });
const emit = defineEmits(['close', 'crop']);

const cropperRef = ref(null);
const isMounted = ref(false);
onMounted(() => isMounted.value = true);

const close = () => emit('close');
const crop = () => {
    if (cropperRef.value) {
        const { canvas } = cropperRef.value.getResult();
        if (canvas) {
            emit('crop', canvas.toDataURL());
            close();
        }
    }
};
</script>
