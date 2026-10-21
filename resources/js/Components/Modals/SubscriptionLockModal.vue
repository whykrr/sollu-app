<template>
    <div
        v-if="shouldShowModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 backdrop-blur-xs p-4"
    >
        <div
            class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-center animate-in fade-in zoom-in duration-200"
        >
            <!-- Status Badge & Icon -->
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full mb-4 shadow-inner"
                :class="
                    lockReason === 'payment_pending'
                        ? 'bg-amber-100 text-amber-600 ring-8 ring-amber-50'
                        : lockReason === 'trial_expired'
                          ? 'bg-purple-100 text-purple-600 ring-8 ring-purple-50'
                          : 'bg-rose-100 text-rose-600 ring-8 ring-rose-50'
                "
            >
                <FontAwesomeIcon :icon="modalIcon" size="2x" class="h-8 w-8" />
            </div>

            <span
                class="inline-block px-3 py-1 mb-2 text-xs font-bold rounded-full uppercase tracking-wider"
                :class="
                    lockReason === 'payment_pending'
                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                        : lockReason === 'trial_expired'
                          ? 'bg-purple-50 text-purple-700 border border-purple-200'
                          : 'bg-rose-50 text-rose-700 border border-rose-200'
                "
            >
                {{ badgeText }}
            </span>

            <!-- Title -->
            <h3 class="text-xl font-bold text-gray-900 mb-2">
                {{ modalTitle }}
            </h3>

            <!-- Explanatory Guidance -->
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                {{ modalMessage }}
            </p>

            <!-- Actions -->
            <div class="flex flex-col gap-2.5">
                <Link
                    v-if="primaryActionUrl"
                    :href="primaryActionUrl"
                    class="btn btn-main w-full py-2.5 text-sm font-bold justify-center"
                >
                    {{ primaryActionLabel }}
                </Link>

                <Link
                    v-if="lockReason === 'payment_pending' && plansUrl"
                    :href="plansUrl"
                    class="btn btn-slate-400 w-full py-2 text-xs font-semibold justify-center"
                >
                    Lihat Pilihan Paket Lain
                </Link>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-gray-500">
                    <Link
                        :href="route('settings.billing.index')"
                        class="hover:text-main font-medium underline underline-offset-2"
                    >
                        Menu Billing & Riwayat
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hover:text-rose-600 font-medium inline-flex items-center gap-1"
                    >
                        <FontAwesomeIcon :icon="faRightFromBracket" class="w-3.5 h-3.5" />
                        Keluar Akun
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCreditCard,
    faHourglassEnd,
    faLock,
    faRightFromBracket,
} from '@fortawesome/free-solid-svg-icons'

const page = usePage()

const lockData = computed(() => page.props.subscription_lock || {})
const isLocked = computed(() => Boolean(lockData.value.is_locked))
const lockReason = computed(() => lockData.value.lock_reason || 'subscription_expired')
const invoiceUrl = computed(() => lockData.value.invoice_url)
const plansUrl = computed(() => lockData.value.plans_url)

// Do not show modal on Billing pages or Profile pages
const isExemptRoute = computed(() => {
    const url = page.url || ''
    return (
        url.startsWith('/settings/billing') ||
        url.startsWith('/settings/profile') ||
        url.startsWith('/logout')
    )
})

const shouldShowModal = computed(() => isLocked.value && !isExemptRoute.value)

const modalIcon = computed(() => {
    if (lockReason.value === 'payment_pending') return faCreditCard
    if (lockReason.value === 'trial_expired') return faHourglassEnd
    return faLock
})

const badgeText = computed(() => {
    if (lockReason.value === 'payment_pending') return 'Menunggu Pembayaran'
    if (lockReason.value === 'trial_expired') return 'Masa Uji Coba Berakhir'
    return 'Langganan Kedaluwarsa'
})

const modalTitle = computed(() => {
    if (lockReason.value === 'payment_pending') return 'Pembayaran Tagihan Diperlukan'
    if (lockReason.value === 'trial_expired') return 'Masa Uji Coba Gratis Selesai'
    return 'Masa Langganan Telah Berakhir'
})

const modalMessage = computed(() => {
    if (lockReason.value === 'payment_pending') {
        return 'Bisnis Anda memiliki tagihan langganan yang belum diselesaikan. Harap selesaikan pembayaran tagihan agar operasional kasir dan manajemen produk dapat dibuka kembali.'
    }
    if (lockReason.value === 'trial_expired') {
        return 'Masa uji coba gratis 14 hari Anda telah selesai. Pilih salah satu paket langganan Sollu untuk melanjutkan aktivitas penjualan dan operasional usaha Anda.'
    }
    return 'Masa aktif paket langganan Anda telah berakhir. Silakan lakukan perpanjangan atau pilih paket baru untuk membuka kembali akses fitur toko Anda.'
})

const primaryActionUrl = computed(() => {
    if (lockReason.value === 'payment_pending' && invoiceUrl.value) {
        return invoiceUrl.value
    }
    return plansUrl.value
})

const primaryActionLabel = computed(() => {
    if (lockReason.value === 'payment_pending') return 'Bayar Tagihan Sekarang'
    if (lockReason.value === 'trial_expired') return 'Pilih Paket Langganan'
    return 'Perpanjang / Pilih Paket'
})
</script>
