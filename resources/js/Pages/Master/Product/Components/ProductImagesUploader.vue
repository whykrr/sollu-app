<template>
    <div class="space-y-4">
        <!-- Drag & Drop Dropzone (Visible if empty) -->
        <div 
            v-if="modelValue.length === 0"
            class="relative overflow-hidden rounded-xl border-2 border-dashed transition-all duration-300 text-center cursor-pointer p-8"
            :class="[
                dragging 
                    ? 'border-primary bg-primary/5 scale-[1.01]' 
                    : 'border-slate-300 hover:border-primary hover:bg-neutral-50'
            ]"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
            @click="triggerFileInput"
        >
            <div class="flex flex-col items-center gap-2">
                <div class="flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <FontAwesomeIcon :icon="faCloudArrowUp" class="text-2xl" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold text-slate-800">Unggah Foto Produk</h3>
                    <p class="text-xs text-slate-500">Drag & drop gambar ke sini, atau klik untuk memilih file</p>
                </div>
                <div class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-500">
                    PNG, JPG, WEBP (Max. 2MB)
                </div>
            </div>
        </div>

        <!-- Grid of images (if not empty) -->
        <div v-else class="space-y-2">
            <div class="text-[11px] text-slate-500 font-medium mb-2 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                💡 <strong>Informasi:</strong> Foto urutan pertama (<strong>#1</strong>) akan digunakan sebagai foto utama / cover produk di aplikasi POS dan e-commerce. Gunakan tombol panah untuk mengubah urutan.
            </div>
            
            <div class="grid grid-cols-4 gap-4">
                <div 
                    v-for="(img, idx) in modelValue" 
                    :key="img.id || idx" 
                    class="relative border rounded-xl overflow-hidden group aspect-square bg-slate-50 flex flex-col justify-between shadow-sm hover:shadow transition"
                >
                    <img :src="img.preview_url || img.url || img.image_url" class="w-full h-full object-cover absolute inset-0 z-0" alt="Product image" />
                    
                    <!-- Cover Badge -->
                    <div v-if="idx === 0" class="absolute top-2 left-2 z-10 bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                        Foto Utama
                    </div>

                    <!-- Overlay Actions -->
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex flex-col justify-between p-2">
                        <!-- Top Actions: Delete -->
                        <div class="flex justify-end">
                            <button 
                                type="button" 
                                @click="removeImage(idx)" 
                                class="bg-red-500 hover:bg-red-600 text-white rounded-lg p-1.5 text-xs cursor-pointer transition shadow"
                                title="Hapus Foto"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>
                        
                        <!-- Bottom Actions: Move/Reorder -->
                        <div class="flex justify-between items-center text-white text-xs z-20">
                            <button 
                                type="button" 
                                @click="moveImage(idx, -1)" 
                                :disabled="idx === 0" 
                                class="bg-white/20 hover:bg-white/40 disabled:opacity-30 disabled:pointer-events-none rounded-lg p-1.5 cursor-pointer transition"
                                title="Pindah Kiri"
                            >
                                <FontAwesomeIcon :icon="faArrowLeft" />
                            </button>
                            <span class="font-bold bg-black/40 px-2 py-0.5 rounded-full text-[10px]">#{{ idx + 1 }}</span>
                            <button 
                                type="button" 
                                @click="moveImage(idx, 1)" 
                                :disabled="idx === modelValue.length - 1" 
                                class="bg-white/20 hover:bg-white/40 disabled:opacity-30 disabled:pointer-events-none rounded-lg p-1.5 cursor-pointer transition"
                                title="Pindah Kanan"
                            >
                                <FontAwesomeIcon :icon="faArrowRight" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Add more upload card inside grid -->
                <div 
                    v-if="modelValue.length < 8"
                    @click="triggerFileInput"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop"
                    class="border-2 border-dashed rounded-xl cursor-pointer flex flex-col items-center justify-center aspect-square transition"
                    :class="[
                        dragging 
                            ? 'border-primary bg-primary/5 scale-[1.01]' 
                            : 'border-slate-300 hover:border-primary hover:bg-neutral-50 text-slate-400 hover:text-slate-600'
                    ]"
                >
                    <FontAwesomeIcon :icon="faPlus" class="text-lg mb-1" />
                    <span class="text-xs font-semibold">Tambah Foto</span>
                </div>
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

        <!-- Cropper Modal Overlay -->
        <div v-if="showCropper" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl border flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="font-bold text-slate-800 text-base">Potong Gambar</h3>
                    <button type="button" @click="closeCropper" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✖</button>
                </div>
                <!-- Body -->
                <div class="p-4 flex-1 overflow-hidden bg-slate-900 min-h-[300px]">
                    <div class="w-full h-72 relative">
                        <Cropper
                            ref="cropperRef"
                            class="h-full w-full"
                            :src="imageSrc"
                            :stencil-props="{
                                aspectRatio: 1,
                            }"
                        />
                        <div class="absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs text-white backdrop-blur">
                            Geser / perbesar untuk menyesuaikan foto
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex justify-end gap-2 p-4 border-t bg-slate-50">
                    <button type="button" class="btn btn-outline-main btn-sm rounded-lg" @click="closeCropper">Batal</button>
                    <button type="button" class="btn btn-highlight-main btn-sm rounded-lg" @click="crop">Potong & Simpan</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faTrash, faArrowLeft, faArrowRight, faCloudArrowUp } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    error: {
        type: String,
        default: null
    }
})

const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const cropperRef = ref(null)
const imageSrc = ref(null)
const showCropper = ref(false)
const dragging = ref(false)

const triggerFileInput = () => {
    fileInput.value.click()
}

const handleFile = (file) => {
    if (!file) return

    const reader = new FileReader()
    reader.onload = (event) => {
        imageSrc.value = event.target.result
        showCropper.value = true
    }
    reader.readAsDataURL(file)
}

const onFileChange = (e) => {
    const file = e.target.files[0]
    handleFile(file)
    e.target.value = ''
}

const onDrop = (e) => {
    dragging.value = false
    const file = e.dataTransfer.files[0]
    handleFile(file)
}

const closeCropper = () => {
    showCropper.value = false
    imageSrc.value = null
}

const crop = () => {
    if (cropperRef.value) {
        const { canvas } = cropperRef.value.getResult()
        if (canvas) {
            canvas.toBlob((blob) => {
                const file = new File([blob], 'product_image.png', {
                    type: 'image/png'
                })
                
                const previewUrl = URL.createObjectURL(blob)
                
                const updated = [...props.modelValue, {
                    id: 'new_' + Math.random().toString(36).substr(2, 9),
                    image_url: '',
                    image_file: file,
                    preview_url: previewUrl,
                    sort_order: props.modelValue.length
                }]
                emit('update:modelValue', updated)
                closeCropper()
            }, 'image/png')
        }
    }
}

const removeImage = (index) => {
    const updated = [...props.modelValue]
    const removed = updated.splice(index, 1)[0]
    
    if (removed && removed.preview_url) {
        URL.revokeObjectURL(removed.preview_url)
    }
    
    // Re-adjust sort orders
    updated.forEach((img, idx) => {
        img.sort_order = idx
    })

    emit('update:modelValue', updated)
}

const moveImage = (index, direction) => {
    const targetIndex = index + direction
    if (targetIndex < 0 || targetIndex >= props.modelValue.length) return

    const updated = [...props.modelValue]
    const temp = updated[index]
    updated[index] = updated[targetIndex]
    updated[targetIndex] = temp

    // Re-adjust sort orders
    updated.forEach((img, idx) => {
        img.sort_order = idx
    })

    emit('update:modelValue', updated)
}

onBeforeUnmount(() => {
    props.modelValue.forEach(img => {
        if (img.preview_url) {
            URL.revokeObjectURL(img.preview_url)
        }
    })
})
</script>
