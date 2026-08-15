<template>
    <div class="flex flex-col items-center pb-2">
        <div class="flex justify-center mb-6 mt-2">
            <div class="w-16 h-16 bg-main/10 rounded-full flex items-center justify-center text-main text-2xl">
                <FontAwesomeIcon :icon="faKey" />
            </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-800 text-center mb-2">
            Hubungkan Perangkat
        </h3>
        <p class="text-slate-500 text-center text-sm mb-8 px-4">
            Buka aplikasi POS Sollu di perangkat Anda, lalu masukkan
            8-digit kode OTP di bawah ini.
        </p>

        <div
            class="bg-slate-50 border border-slate-200 rounded-xl p-6 w-full flex flex-col items-center justify-center mb-8 relative overflow-hidden"
        >
            <div
                class="text-4xl sm:text-5xl font-mono font-bold tracking-[0.15em] text-slate-800 mb-4"
            >
                {{ formattedOtp }}
            </div>
            <button
                class="flex items-center gap-2 text-sm font-medium transition px-4 py-2 rounded-lg"
                :class="isCopied ? 'bg-success/10 text-success' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-main'"
                @click="copyOtp"
            >
                <FontAwesomeIcon :icon="faCopy" />
                {{ isCopied ? 'Kode Tersalin!' : 'Salin Kode' }}
            </button>
        </div>

        <div class="flex items-center justify-center w-full gap-3 mb-8 bg-warning/10 text-warning-800 py-3 px-4 rounded-lg">
            <span class="text-sm font-medium">Berlaku dalam:</span>
            <span
                class="text-xl font-bold font-mono tabular-nums"
                :class="{
                    'text-danger': timerMinutes === 0 && timerSeconds <= 30,
                }"
            >
                {{ timerMinutes.toString().padStart(2, '0') }}:{{
                    timerSeconds.toString().padStart(2, '0')
                }}
            </span>
        </div>
        
        <div v-if="isExpired" class="text-center w-full mb-6 p-3 bg-danger/10 text-danger rounded-lg text-sm font-medium">
            Kode OTP telah kadaluarsa. Silakan tutup dan buat ulang.
        </div>

        <button
            class="btn btn-main w-full py-3.5 text-base rounded-xl font-semibold shadow-sm shadow-main/30"
            @click="handleDone"
        >
            Selesai
        </button>
    </div>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faKey, faCopy } from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    otpData: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const timerMinutes = ref(5)
const timerSeconds = ref(0)
const isExpired = ref(false)
const isCopied = ref(false)
let timerInterval = null

const formattedOtp = computed(() => {
    const otp = props.otpData?.otp || '00000000'
    return otp.slice(0, 4) + '-' + otp.slice(4)
})

const startTimer = () => {
    if (!props.otpData?.expires_at) return
    const expiresAt = new Date(props.otpData.expires_at).getTime()

    const updateTimer = () => {
        const now = new Date().getTime()
        const distance = expiresAt - now

        if (distance <= 0) {
            clearInterval(timerInterval)
            timerMinutes.value = 0
            timerSeconds.value = 0
            isExpired.value = true
            return
        }

        timerMinutes.value = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))
        timerSeconds.value = Math.floor((distance % (1000 * 60)) / 1000)
    }

    updateTimer()
    timerInterval = setInterval(updateTimer, 1000)
}

const copyOtp = async () => {
    const textToCopy = props.otpData?.otp;
    if (!textToCopy) return;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(textToCopy);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
        
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
}

const handleDone = () => {
    emit('close')
    router.reload({ only: ['devices', 'outlet'] })
}

onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>
