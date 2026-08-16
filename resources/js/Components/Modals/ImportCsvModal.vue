<template>
    <Modal :show="show" :title="`Impor ${moduleName}`" @close="close">
        <p class="text-sm text-gray-600 mb-6">
            Unduh template Excel, isi data Anda, lalu unggah kembali file tersebut
            ke sini. Pastikan nama kolom pada baris pertama tidak diubah agar
            sistem dapat membaca data dengan benar.
        </p>

        <div class="flex gap-2 mb-6">
            <a
                :href="templateUrl"
                class="btn btn-outline-main btn-sm w-full text-center"
            >
                <FontAwesomeIcon :icon="faDownload" class="mr-2" />
                Unduh Template Excel
            </a>
        </div>

        <div
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition relative mb-2"
        >
            <input
                type="file"
                accept=".csv,.xls,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                :disabled="form.processing"
                @change="handleFileChange"
            />

            <FontAwesomeIcon
                :icon="faUpload"
                class="text-3xl text-gray-400 mb-2"
            />

            <div v-if="!form.file">
                <p class="text-sm font-semibold">
                    Klik atau seret file Excel/CSV ke sini
                </p>
                <p class="text-xs text-gray-500">Maks. 10MB</p>
            </div>
            <div v-else class="text-[var(--color-main)] font-semibold text-sm">
                {{ form.file.name }}
            </div>
        </div>

        <div
            v-if="form.errors.file"
            class="text-red-500 text-sm mt-2 text-center"
        >
            {{ form.errors.file }}
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-outline-main"
                    :disabled="form.processing"
                    @click="close"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main"
                    :disabled="!form.file || form.processing"
                    @click="submit"
                >
                    <span v-if="form.processing">Mengunggah...</span>
                    <span v-else>Mulai Impor</span>
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Notifications/Modal.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faDownload, faUpload } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    moduleName: {
        type: String,
        required: true,
    },
    templateUrl: {
        type: String,
        required: true,
    },
    importUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    file: null,
});

const handleFileChange = (e) => {
    form.file = e.target.files[0];
};

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.post(props.importUrl, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    });
};
</script>
