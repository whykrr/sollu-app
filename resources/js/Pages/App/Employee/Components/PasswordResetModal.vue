<template>
    <Modal
        :show="show"
        :title="isSuccess ? 'Kata Sandi Baru Berhasil Dibuat' : 'Konfirmasi Reset Kata Sandi'"
        :type="isSuccess ? 'success' : 'warning'"
        :size="'max-w-md'"
        :show-close="!isLoading"
        :close-on-backdrop="!isLoading && !isSuccess"
        @close="handleClose"
    >
        <!-- Tahap 1: Konfirmasi Reset -->
        <div v-if="!isSuccess" class="space-y-3">
            <p class="text-xs text-slate-600 leading-relaxed">
                Apakah Anda yakin ingin mereset kata sandi akun web untuk staf
                <strong class="text-slate-800 font-semibold">{{ user?.name || 'ini' }}</strong
                >?
            </p>

            <div
                class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2.5"
            >
                <FontAwesomeIcon
                    :icon="faTriangleExclamation"
                    class="mt-0.5 shrink-0 text-amber-600"
                />
                <div class="space-y-1">
                    <span class="font-semibold block">Tanpa Pengiriman Email</span>
                    <span class="text-amber-700 leading-normal block text-[11px]">
                        Sistem tidak akan mengirimkan email notifikasi. Kata sandi acak baru akan
                        langsung ditampilkan di layar ini untuk Anda berikan secara langsung kepada
                        staf.
                    </span>
                </div>
            </div>

            <!-- Error Notification if API fails -->
            <div
                v-if="errorMessage"
                class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 leading-relaxed"
            >
                {{ errorMessage }}
            </div>
        </div>

        <!-- Tahap 2: Tampilan Hasil Kata Sandi Baru -->
        <div v-else class="space-y-3.5">
            <p class="text-xs text-slate-600 leading-relaxed">
                Kata sandi akun web untuk staf
                <strong class="text-slate-800 font-semibold">{{ user?.name || 'ini' }}</strong>
                berhasil diperbarui. Silakan salin dan berikan kata sandi ini kepada staf:
            </p>

            <!-- Display Box Kata Sandi -->
            <div
                class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center space-y-3"
            >
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                    Kata Sandi Baru Staf
                </span>
                <div
                    class="font-mono text-2xl sm:text-3xl font-bold tracking-wider text-slate-900 select-all py-1 px-3 bg-white border border-slate-200 rounded-lg w-full text-center"
                >
                    {{ newPassword }}
                </div>
                <button
                    type="button"
                    class="btn btn-sm flex items-center gap-2 transition touch-target-sm"
                    :class="
                        isCopied
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white border-transparent'
                            : 'btn-outline-main'
                    "
                    @click="copyPassword"
                >
                    <FontAwesomeIcon :icon="isCopied ? faCheck : faCopy" />
                    <span>{{ isCopied ? 'Kata Sandi Tersalin!' : 'Salin Kata Sandi' }}</span>
                </button>
            </div>

            <!-- Catatan Pengamanan -->
            <div
                class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-start gap-2"
            >
                <FontAwesomeIcon :icon="faInfoCircle" class="mt-0.5 shrink-0 text-slate-400" />
                <span class="text-[11px] leading-relaxed">
                    Kata sandi ini hanya ditampilkan <strong>satu kali</strong> pada dialog ini.
                    Pastikan Anda telah menyalin atau memberitahukannya kepada staf sebelum menutup.
                </span>
            </div>
        </div>

        <!-- Footer Aksi -->
        <template #footer>
            <div v-if="!isSuccess" class="flex items-center justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-flat btn-sm"
                    :disabled="isLoading"
                    @click="handleClose"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-warning btn-sm flex items-center gap-1.5"
                    :disabled="isLoading"
                    @click="executeReset"
                >
                    <FontAwesomeIcon v-if="isLoading" :icon="faSpinner" class="animate-spin" />
                    <span>{{ isLoading ? 'Mereset...' : 'Ya, Reset Sandi' }}</span>
                </button>
            </div>
            <div v-else class="flex items-center justify-end w-full">
                <button type="button" class="btn btn-main btn-sm" @click="handleClose">
                    Selesai & Tutup
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Notifications/Modal.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faTriangleExclamation,
    faCheck,
    faCopy,
    faInfoCircle,
    faSpinner,
} from '@fortawesome/free-solid-svg-icons'
import { useToastStore } from '@/store/toast'
import axios from 'axios'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:show', 'close', 'success'])

const toastStore = useToastStore()

const isLoading = ref(false)
const isSuccess = ref(false)
const newPassword = ref('')
const isCopied = ref(false)
const errorMessage = ref('')

watch(
    () => props.show,
    isOpen => {
        if (isOpen) {
            isLoading.value = false
            isSuccess.value = false
            newPassword.value = ''
            isCopied.value = false
            errorMessage.value = ''
        }
    }
)

const handleClose = () => {
    if (isLoading.value) return
    emit('update:show', false)
    emit('close')
}

const executeReset = async () => {
    if (!props.user?.id) {
        errorMessage.value = 'Data staf tidak valid.'
        return
    }

    isLoading.value = true
    errorMessage.value = ''

    try {
        const response = await axios.post(route('employees.resetPassword', props.user.id))

        if (response?.data?.password) {
            newPassword.value = response.data.password
            isSuccess.value = true
            emit('success', response.data.password)
        } else {
            errorMessage.value = response?.data?.message || 'Gagal memproses kata sandi baru.'
        }
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message ||
            'Terjadi kesalahan saat mereset kata sandi. Silakan periksa koneksi dan coba lagi.'
    } finally {
        isLoading.value = false
    }
}

const copyPassword = async () => {
    if (!newPassword.value) return

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(newPassword.value)
        } else {
            const textArea = document.createElement('textarea')
            textArea.value = newPassword.value
            textArea.style.position = 'fixed'
            textArea.style.left = '-999999px'
            document.body.appendChild(textArea)
            textArea.focus()
            textArea.select()
            document.execCommand('copy')
            textArea.remove()
        }

        isCopied.value = true
        toastStore.success('Kata sandi berhasil disalin ke papan klip')
        setTimeout(() => {
            isCopied.value = false
        }, 2000)
    } catch (err) {
        console.error('Gagal menyalin kata sandi: ', err)
    }
}
</script>
