<template>
    <MainPage>
        <div
            v-if="invoice"
            class="alert alert-warning inline-flex justify-between items-center w-full mb-4"
        >
            <div class="text-sm">
                <div class="font-bold">Tagihan Anda masih menunggu pembayaran.</div>
                Silakan lakukan pembayaran atau batalkan untuk mengganti paket langganan.
            </div>
            <div>
                <Link
                    :href="route('settings.billing.invoices.show', invoice.invoice_number)"
                    class="btn btn-main btn-sm"
                >
                    Lihat Tagihan
                </Link>
            </div>
        </div>

        <!-- Custom Catalog Banner vs Standard Banner -->
        <div
            v-if="isCustomCatalog"
            class="flex justify-between items-center w-full mb-4 rounded-lg bg-purple-50 border border-purple-200 p-4"
        >
            <div class="text-sm">
                <div class="font-bold text-lg text-purple-900 flex items-center gap-2">
                    <span>Penawaran Paket Khusus Bisnis Anda</span>
                    <span
                        class="px-2 py-0.5 bg-purple-200 text-purple-800 text-xs rounded-full font-bold uppercase tracking-wider"
                    >
                        Khusus
                    </span>
                </div>
                <p class="text-purple-700 mt-0.5">
                    Paket kustom yang telah disesuaikan secara eksklusif untuk kebutuhan operasional
                    dan cabang bisnis Anda.
                </p>
            </div>
        </div>

        <div
            v-else
            class="flex justify-between items-center w-full mb-4 rounded-lg bg-white border p-4"
        >
            <div class="text-sm">
                <div class="font-bold text-lg text-gray-800">Lebih Hemat & Praktis</div>
                <p class="text-gray-600">
                    Bayar 10 bulan, nikmati layanan 12 bulan penuh. Dapatkan tambahan diskon dengan
                    paket tahunan!
                </p>
            </div>
        </div>

        <div class="grid gap-4" :class="gridClass">
            <div
                v-for="(plan, index) in activePlans"
                :key="index"
                class="bg-white rounded-xl border flex flex-col h-full transition-colors relative overflow-hidden hover:border-slate-300"
                :class="{
                    'border-main ring-2 ring-main/20':
                        subscription && subscription.plan_id === plan.id,
                    'border-purple-300 ring-1 ring-purple-100':
                        (!subscription || subscription.plan_id !== plan.id) && plan.business_id,
                    'border-neutral-200':
                        (!subscription || subscription.plan_id !== plan.id) && !plan.business_id,
                    'opacity-80 bg-neutral-50/50': !plan.is_active,
                }"
            >
                <!-- Top Status Stripe -->
                <div
                    class="h-1.5 w-full"
                    :class="
                        subscription && subscription.plan_id === plan.id
                            ? 'bg-main'
                            : plan.business_id
                              ? 'bg-purple-600'
                              : plan.is_active
                                ? 'bg-main'
                                : 'bg-neutral-300'
                    "
                />

                <div class="p-4 flex-1 flex flex-col">
                    <!-- Header Info & Badges -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span
                                    class="text-xs font-mono uppercase tracking-wider text-neutral-400 font-semibold"
                                >
                                    {{ plan.code }}
                                </span>
                                <span
                                    v-if="plan.business_id"
                                    class="px-1.5 py-0.2 bg-purple-100 text-purple-700 text-[9px] rounded font-bold uppercase tracking-wider"
                                >
                                    Khusus Bisnis Anda
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-neutral-800">
                                {{ plan.name }}
                            </h2>
                        </div>
                        <div>
                            <span
                                v-if="subscription && subscription.plan_id === plan.id"
                                class="px-2.5 py-1 bg-main text-white text-xs rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"
                                ></span>
                                Paket Saat Ini
                            </span>
                            <span
                                v-else-if="!plan.is_active"
                                class="px-2.5 py-1 bg-neutral-200 text-neutral-600 text-xs rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                Tidak Tersedia
                            </span>
                        </div>
                    </div>

                    <!-- Pricing Info -->
                    <div class="py-3 border-y border-neutral-100 mb-3 text-center">
                        <div class="text-2xl font-black text-main">
                            {{ formatIDR(plan.price_per_outlet) }}
                        </div>
                        <div class="text-xs text-neutral-500 font-medium mt-0.5">
                            per bulan / outlet
                        </div>
                        <div
                            v-if="plan.yearly_discount_percent > 0"
                            class="mt-2 text-xs font-bold text-success bg-success/10 py-0.5 px-2 rounded-md inline-block"
                        >
                            Diskon Tahunan {{ plan.yearly_discount_percent }}%
                        </div>
                    </div>

                    <!-- Meta Specs -->
                    <div
                        class="space-y-1.5 text-xs text-neutral-600 mb-4 bg-neutral-50 p-2.5 rounded-lg border border-neutral-100"
                    >
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Siklus Penagihan:</span>
                            <span class="font-semibold text-neutral-800"> Bulanan / Tahunan </span>
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="flex-1 mb-4">
                        <div
                            class="text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2"
                        >
                            Fitur Termasuk:
                        </div>
                        <ul v-if="plan.features && plan.features.length" class="space-y-2">
                            <li
                                v-for="(feature, fIndex) in plan.features"
                                :key="fIndex"
                                class="flex items-start gap-2 text-xs text-neutral-700"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-success mt-0.5 shrink-0"
                                />
                                <div>
                                    <div class="font-medium text-neutral-800">
                                        {{ feature.title }}
                                    </div>
                                    <div v-if="feature.detail" class="text-neutral-500 text-[11px]">
                                        {{ feature.detail }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="text-xs text-neutral-400 italic">
                            Belum ada fitur tercatat
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-neutral-100">
                        <Link
                            v-if="
                                !invoice &&
                                (!subscription || subscription.plan_id !== plan.id) &&
                                plan.is_active
                            "
                            :href="route('settings.billing.checkout', plan.id)"
                            class="btn btn-highlight-main w-full py-2.5 text-sm"
                        >
                            {{ subscription ? 'Ganti ke Paket Ini' : 'Pilih Paket' }}
                        </Link>

                        <button
                            v-else-if="subscription && subscription.plan_id === plan.id"
                            disabled
                            class="btn btn-outline-main w-full py-2.5 text-sm opacity-60 cursor-not-allowed"
                        >
                            Paket Aktif
                        </button>

                        <button
                            v-else-if="!plan.is_active"
                            disabled
                            class="btn btn-neutral-100 text-neutral-400 w-full py-2.5 text-sm cursor-not-allowed"
                        >
                            Tidak Tersedia
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>
<script setup>
import { computed } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import { formatIDR } from '@/Composable/currency-format'
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    subscription: Object,
    plans: Array,
    invoice: Object,
    isCustomCatalog: {
        type: Boolean,
        default: false,
    },
})

const activePlans = computed(() => {
    return (props.plans || []).filter(
        plan => plan.is_active || (props.subscription && props.subscription.plan_id === plan.id)
    )
})

const gridClass = computed(() => {
    const count = activePlans.value.length
    if (count <= 1) {
        return 'grid-cols-1 max-w-md mx-auto'
    }
    if (count === 2) {
        return 'grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto'
    }
    if (count === 3) {
        return 'grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto'
    }
    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'
})
</script>
