<template>
    <div class="space-y-4 text-left">
        <!-- Header / Intro Banner -->
        <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-3.5 sm:p-4">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500 text-white shadow-xs">
                    <FontAwesomeIcon :icon="faBoxesStacked" class="text-sm" />
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-semibold text-slate-800">
                        {{ isSetupMode ? 'Yuk, Tentukan Metode Perhitungan Aset Tokomu 👋' : 'Pilih Metode Perhitungan Aset Inventaris' }}
                    </h4>
                    <p class="text-xs leading-relaxed text-slate-600">
                        Metode ini menentukan cara sistem menghitung harga pokok penjualan (HPP) barang keluar dan menghitung nilai rupiah sisa persediaan di tokomu.
                    </p>
                </div>
            </div>
        </div>

        <!-- Option Cards: FIFO vs Moving Average -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <!-- Option 1: FIFO -->
            <div
                class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                :class="[
                    selectedMethod === 'fifo'
                        ? 'border-main bg-main/5 ring-2 ring-main/20'
                        : 'border-slate-200 bg-white'
                ]"
                @click="selectedMethod = 'fifo'"
            >
                <div class="space-y-2.5">
                    <!-- Top row: Radio + Badges -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200/70">
                            <FontAwesomeIcon :icon="faUtensils" class="text-[10px]" />
                            <span>Paling Pas untuk F&B</span>
                        </span>
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                            :class="[
                                selectedMethod === 'fifo'
                                    ? 'border-main bg-main text-white'
                                    : 'border-slate-300 bg-white'
                            ]"
                        >
                            <FontAwesomeIcon v-if="selectedMethod === 'fifo'" :icon="faCheck" class="text-[10px]" />
                        </div>
                    </div>

                    <!-- Title & Tagline -->
                    <div>
                        <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <span>FIFO</span>
                            <span class="text-xs font-normal text-slate-500">(First-In, First-Out)</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Masuk pertama, keluar pertama
                        </p>
                    </div>

                    <!-- Explanation -->
                    <p class="text-xs leading-relaxed text-slate-600">
                        Stok yang dibeli <strong class="font-semibold text-slate-700">lebih dulu</strong> akan dicatat keluar lebih dulu. Nilai aset persediaan di gudang selalu mengikuti harga beli terbaru.
                    </p>

                    <!-- Real-life Example Box -->
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1">
                        <div class="font-medium text-slate-700 flex items-center gap-1">
                            <FontAwesomeIcon :icon="faLightbulb" class="text-amber-500" />
                            <span>Contoh Nyata:</span>
                        </div>
                        <p class="leading-normal text-slate-500">
                            Beli 10 kg @Rp25rb, lalu 10 kg @Rp28rb. Saat pakai 5 kg, sistem memotong HPP batch awal yaitu <strong class="text-slate-700">Rp25rb/kg</strong>.
                        </p>
                    </div>
                </div>

                <!-- Footer Recommendation note -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-1 text-[11px] text-slate-500">
                    <FontAwesomeIcon :icon="faCheckCircle" class="text-emerald-500 shrink-0" />
                    <span>Sangat cocok untuk makanan, minuman, obat, & barang mudah basi.</span>
                </div>
            </div>

            <!-- Option 2: Moving Average -->
            <div
                class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                :class="[
                    selectedMethod === 'average'
                        ? 'border-main bg-main/5 ring-2 ring-main/20'
                        : 'border-slate-200 bg-white'
                ]"
                @click="selectedMethod = 'average'"
            >
                <div class="space-y-2.5">
                    <!-- Top row: Radio + Badges -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/70">
                            <FontAwesomeIcon :icon="faBagShopping" class="text-[10px]" />
                            <span>Paling Pas untuk Retail</span>
                        </span>
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                            :class="[
                                selectedMethod === 'average'
                                    ? 'border-main bg-main text-white'
                                    : 'border-slate-300 bg-white'
                            ]"
                        >
                            <FontAwesomeIcon v-if="selectedMethod === 'average'" :icon="faCheck" class="text-[10px]" />
                        </div>
                    </div>

                    <!-- Title & Tagline -->
                    <div>
                        <div class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <span>Moving Average</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Rata-rata bergerak otomatis
                        </p>
                    </div>

                    <!-- Explanation -->
                    <p class="text-xs leading-relaxed text-slate-600">
                        Harga modal dihitung dari <strong class="font-semibold text-slate-700">rata-rata tertimbang</strong> setiap kali ada stok masuk baru. Menghasilkan HPP yang stabil dan merata.
                    </p>

                    <!-- Real-life Example Box -->
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1">
                        <div class="font-medium text-slate-700 flex items-center gap-1">
                            <FontAwesomeIcon :icon="faLightbulb" class="text-amber-500" />
                            <span>Contoh Nyata:</span>
                        </div>
                        <p class="leading-normal text-slate-500">
                            Punya 10 pcs @Rp50rb, beli lagi 10 pcs @Rp60rb. HPP seluruh stok otomatis menjadi <strong class="text-slate-700">Rp55rb/pcs</strong>.
                        </p>
                    </div>
                </div>

                <!-- Footer Recommendation note -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center gap-1 text-[11px] text-slate-500">
                    <FontAwesomeIcon :icon="faCheckCircle" class="text-indigo-500 shrink-0" />
                    <span>Sangat cocok untuk fashion, aksesoris, ATK, elektronik, & retail umum.</span>
                </div>
            </div>
        </div>

        <!-- Peace of mind callout -->
        <div class="rounded-lg border border-amber-200/80 bg-amber-50/60 p-3 text-xs text-amber-800 flex items-start gap-2.5">
            <FontAwesomeIcon :icon="faShieldHeart" class="text-amber-600 mt-0.5 text-sm shrink-0" />
            <div class="space-y-0.5">
                <span class="font-semibold">Tenang, fleksibel tanpa risiko!</span>
                <p class="text-[11px] leading-relaxed text-amber-700">
                    Sistem Sollu otomatis menyinkronkan data secara ganda di latar belakang. Kamu dapat mengganti metode ini kapan saja di menu Pengaturan tanpa merusak transaksi yang sudah berjalan.
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-2 pt-2">
            <button
                type="button"
                class="btn btn-flat w-full sm:w-auto"
                :disabled="form.processing"
                @click="handleCancel"
            >
                {{ isSetupMode ? 'Lewati Dulu (Gunakan FIFO)' : 'Batal' }}
            </button>
            <button
                type="button"
                class="btn btn-main w-full sm:w-auto flex items-center justify-center gap-2"
                :disabled="form.processing"
                @click="handleSubmit"
            >
                <FontAwesomeIcon v-if="form.processing" :icon="faSpinner" class="animate-spin" />
                <FontAwesomeIcon v-else :icon="faCheck" />
                <span>{{ form.processing ? 'Menyimpan...' : 'Terapkan Metode Ini' }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBoxesStacked,
    faUtensils,
    faBagShopping,
    faCheck,
    faLightbulb,
    faCheckCircle,
    faShieldHeart,
    faSpinner,
} from '@fortawesome/free-solid-svg-icons'
import { useModalStore } from '@/store/notification'
import { useToastStore } from '@/store/toast'

const props = defineProps({
    currentMethod: {
        type: String,
        default: 'fifo',
    },
    isSetupMode: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'saved'])

const modalStore = useModalStore()
const toastStore = useToastStore()
const page = usePage()

const initial = props.currentMethod || page.props.auth?.business?.inventory_costing_method || 'fifo'
const selectedMethod = ref(initial)

const form = useForm({
    costing_method: selectedMethod.value,
})

const handleSubmit = () => {
    form.costing_method = selectedMethod.value

    form.put(route('settings.inventory.update'), {
        preserveScroll: true,
        onSuccess: () => {
            modalStore.activeModal.isVisible = false
            toastStore.success('Metode perhitungan aset berhasil diterapkan!')
            emit('saved', selectedMethod.value)
        },
        onError: () => {
            toastStore.error('Terjadi kesalahan saat menyimpan pengaturan.')
        },
    })
}

const handleCancel = () => {
    if (props.isSetupMode && !page.props.auth?.business?.is_costing_configured) {
        // Jika setup mode dan lewati, kita set default FIFO agar tidak ditanyakan terus
        form.costing_method = 'fifo'
        form.put(route('settings.inventory.update'), {
            preserveScroll: true,
            onSuccess: () => {
                modalStore.activeModal.isVisible = false
                emit('close')
            },
        })
        return
    }

    modalStore.activeModal.isVisible = false
    emit('close')
}
</script>
