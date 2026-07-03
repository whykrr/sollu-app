<template>
    <div v-if="show" class="overlay-backdrop">
        <div class="overlay-modal max-w-sm">
            <!-- Header -->
            <div class="overlay-header">
                <h3 class="overlay-title">Pembayaran Tertunda</h3>
                <button type="button" @click="$emit('close')" class="overlay-close">✖</button>
            </div>
            <!-- Body -->
            <div class="flex flex-col items-center text-center p-5 space-y-4">
                <div class="flex size-16 items-center justify-center rounded-full bg-red-50 text-red-500">
                    <FontAwesomeIcon :icon="faCreditCard" class="text-3xl" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-bold text-slate-800">
                        Pembayaran Diperlukan
                    </h3>
                    <p class="text-sm text-slate-500">
                        Outlet tidak dapat diaktifkan karena invoice penambahan
                        outlet (<strong>{{ unpaidInvoice?.number }}</strong>) belum dilunasi.
                    </p>
                    <p class="text-xs text-slate-400">
                        Silakan selesaikan pembayaran tagihan prorasi terlebih dahulu untuk mengaktifkan outlet ini.
                    </p>
                </div>
            </div>
            <!-- Footer -->
            <div class="overlay-footer">
                <button type="button" class="btn btn-secondary flex-1 justify-center rounded-lg" @click="$emit('close')">
                    Batal
                </button>
                <Link
                    :href="unpaidInvoice?.url || '#'"
                    class="btn btn-main flex-1 justify-center text-center rounded-lg"
                    as="button"
                >
                    Bayar Sekarang
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCreditCard } from '@fortawesome/free-solid-svg-icons';

defineProps({
    show: Boolean,
    unpaidInvoice: Object,
});

defineEmits(['close']);
</script>
