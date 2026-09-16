<template>
    <div class="flex flex-col h-full">
        <!-- Stepper -->
        <div class="flex items-center justify-center mb-6">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                        :class="step >= 1 ? 'bg-main text-white' : 'bg-slate-200 text-slate-500'"
                    >
                        1
                    </div>
                    <span
                        class="ml-2 font-medium"
                        :class="step >= 1 ? 'text-slate-800' : 'text-slate-400'"
                        >Informasi</span
                    >
                </div>
                <div class="w-12 h-0.5 bg-slate-200" :class="{ 'bg-main': step >= 2 }"></div>
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                        :class="step >= 2 ? 'bg-main text-white' : 'bg-slate-200 text-slate-500'"
                    >
                        2
                    </div>
                    <span
                        class="ml-2 font-medium"
                        :class="step >= 2 ? 'text-slate-800' : 'text-slate-400'"
                        >{{ hasProratedAmount ? 'Estimasi' : 'Konfirmasi' }}</span
                    >
                </div>

                <template v-if="hasProratedAmount">
                    <div class="w-12 h-0.5 bg-slate-200" :class="{ 'bg-main': step >= 3 }"></div>
                    <div class="flex items-center">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                            :class="
                                step >= 3 ? 'bg-main text-white' : 'bg-slate-200 text-slate-500'
                            "
                        >
                            3
                        </div>
                        <span
                            class="ml-2 font-medium"
                            :class="step >= 3 ? 'text-slate-800' : 'text-slate-400'"
                            >Pembayaran</span
                        >
                    </div>
                </template>
            </div>
        </div>

        <!-- Step 1: Informasi Dasar -->
        <div v-if="step === 1" class="flex-1">
            <div class="mb-4">
                <p class="text-sm text-slate-500">
                    Lengkapi informasi dasar mengenai outlet baru Anda.
                </p>
            </div>
            <div class="space-y-2">
                <TextField
                    id="name"
                    v-model="formOutlet.name"
                    label="Nama Outlet"
                    placeholder="Contoh: Cabang Sudirman"
                    :feedback="formOutlet.errors.name"
                    required
                />
                <TextareaField
                    id="address"
                    v-model="formOutlet.address"
                    placeholder="Masukkan alamat lengkap outlet"
                    :feedback="formOutlet.errors.address"
                    label="Alamat Lengkap"
                    rows="4"
                />
            </div>
        </div>

        <!-- Step 2: Konfirmasi / Estimasi -->
        <div v-if="step === 2" class="flex-1">
            <div class="mb-4">
                <p class="text-sm text-slate-500">
                    {{
                        hasProratedAmount
                            ? 'Berikut adalah estimasi biaya prorasi untuk penambahan outlet.'
                            : 'Mohon periksa kembali informasi outlet sebelum menyimpan.'
                    }}
                </p>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4">
                <div class="mb-3">
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold"
                        >Nama Outlet</span
                    >
                    <p class="font-medium text-slate-800 mt-1">
                        {{ formOutlet.name || '-' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold"
                        >Alamat</span
                    >
                    <p class="font-medium text-slate-800 mt-1 whitespace-pre-wrap">
                        {{ formOutlet.address || '-' }}
                    </p>
                </div>
            </div>

            <div
                v-if="hasProratedAmount"
                class="border border-slate-200 rounded-xl p-4 bg-white mb-4"
            >
                <h4 class="font-bold text-gray-800 mb-3 text-sm">Estimasi Tagihan Prorasi</h4>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-sm text-gray-600">Penambahan 1 Outlet Baru</span>
                    <span class="font-semibold text-gray-800">{{ formatIDR(proratedAmount) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 mt-1">
                    <span class="font-bold text-gray-800">Total Pembayaran</span>
                    <span class="font-bold text-lg text-main">{{ formatIDR(proratedAmount) }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                    * Tagihan prorasi dihitung berdasarkan sisa hari aktif dari paket langganan Anda
                    saat ini. Outlet akan aktif setelah pembayaran diselesaikan.
                </p>
            </div>

            <div
                v-if="!hasProratedAmount"
                class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm flex gap-3 items-start"
            >
                <FontAwesomeIcon :icon="faInfoCircle" class="mt-0.5" />
                <p>
                    Setelah outlet dibuat, Anda dapat mengkonfigurasi pengaturan tambahan seperti
                    karyawan, jam operasional, dan perangkat di halaman detail outlet.
                </p>
            </div>
        </div>

        <!-- Step 3: Pembayaran -->
        <div v-if="step === 3 && hasProratedAmount" class="flex-1">
            <div class="mb-4">
                <p class="text-sm text-slate-500">
                    Pilih metode pembayaran untuk melunasi tagihan outlet baru.
                </p>
            </div>

            <div class="space-y-3">
                <label
                    class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none"
                    :class="
                        formOutlet.payment_method === 'midtrans'
                            ? 'border-main ring-1 ring-main bg-main/5'
                            : 'border-gray-300'
                    "
                >
                    <input
                        v-model="formOutlet.payment_method"
                        type="radio"
                        name="payment_method"
                        value="midtrans"
                        class="sr-only"
                    />
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-bold text-gray-900"
                                >Pembayaran Online Otomatis</span
                            >
                            <span
                                class="mt-1 flex items-center text-xs text-gray-500 leading-relaxed"
                                >QRIS, Virtual Account, Kartu Kredit, Gopay. Verifikasi
                                instan.</span
                            >
                        </span>
                    </span>
                    <FontAwesomeIcon
                        v-if="formOutlet.payment_method === 'midtrans'"
                        :icon="faCheckCircle"
                        class="h-5 w-5 text-main"
                    />
                </label>

                <label
                    class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none"
                    :class="
                        formOutlet.payment_method === 'manual'
                            ? 'border-main ring-1 ring-main bg-main/5'
                            : 'border-gray-300'
                    "
                >
                    <input
                        v-model="formOutlet.payment_method"
                        type="radio"
                        name="payment_method"
                        value="manual"
                        class="sr-only"
                    />
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-bold text-gray-900"
                                >Transfer Bank Manual</span
                            >
                            <span
                                class="mt-1 flex items-center text-xs text-gray-500 leading-relaxed"
                                >Transfer langsung ke rekening resmi. Butuh unggah bukti
                                bayar.</span
                            >
                        </span>
                    </span>
                    <FontAwesomeIcon
                        v-if="formOutlet.payment_method === 'manual'"
                        :icon="faCheckCircle"
                        class="h-5 w-5 text-main"
                    />
                </label>
            </div>

            <div
                class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm flex gap-3 items-start mt-4"
            >
                <FontAwesomeIcon :icon="faInfoCircle" class="mt-0.5" />
                <p>Anda akan dialihkan ke halaman tagihan setelah menekan tombol "Buat Outlet".</p>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex justify-between w-full">
            <button v-if="step > 1" class="btn btn-outline-main" @click="step--">Kembali</button>
            <div v-else></div>
            <!-- Spacer -->

            <button v-if="step < totalSteps" class="btn btn-main" @click="nextStep">
                Selanjutnya
            </button>
            <button
                v-if="step === totalSteps"
                class="btn btn-success"
                :disabled="formOutlet.processing"
                @click="submitForm"
            >
                <FontAwesomeIcon :icon="faCheck" class="mr-1" />
                Buat Outlet
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faInfoCircle, faCheck, faCheckCircle } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import { formatIDR } from '@/Composable/currency-format'

import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'

const popUpStore = usePopUpStore()
const isMounted = ref(false)

const props = defineProps({
    subscription: Object,
    proratedAmount: {
        type: Number,
        default: 0,
    },
})

onMounted(() => {
    isMounted.value = true
})

const hasActiveSubscription = computed(() => {
    return !!(props.subscription && props.subscription.status === 'active')
})

const hasProratedAmount = computed(() => {
    return hasActiveSubscription.value && props.proratedAmount > 0
})

const totalSteps = computed(() => {
    return hasProratedAmount.value ? 3 : 2
})

const step = ref(1)

const formOutlet = useForm({
    name: null,
    address: null,
    payment_method: 'midtrans',
})

const nextStep = () => {
    // Basic validation
    if (!formOutlet.name) {
        formOutlet.setError('name', 'Nama outlet wajib diisi.')
        return
    }
    formOutlet.clearErrors()
    step.value++
}

const closeWizard = () => {
    formOutlet.reset()
    formOutlet.clearErrors()
    step.value = 1
    popUpStore.close()
}

const submitForm = () => {
    formOutlet.post(route('settings.outlets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeWizard()
        },
    })
}
</script>
