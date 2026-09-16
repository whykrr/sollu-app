<template>
    <MainPage>
        <div class="max-w-4xl mx-auto">
            <h2 class="text-xl font-bold text-gray-900 mb-3">Selesaikan Pembayaran</h2>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <!-- Main Content -->
                <div class="md:col-span-3 space-y-3">
                    <!-- Plan Info -->
                    <div class="bg-white border rounded-xl p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-xs text-gray-500 font-medium">Paket Terpilih</div>
                                <h3 class="text-lg font-bold text-gray-900">
                                    {{ plan.name }}
                                </h3>
                            </div>
                            <div class="text-right">
                                <div class="text-base font-bold text-main">
                                    {{ formatIDR(plan.price_per_outlet) }}
                                </div>
                                <div class="text-[11px] text-gray-500">/ outlet / bulan</div>
                            </div>
                        </div>
                        <div
                            v-if="plan.yearly_discount_percent > 0"
                            class="mt-3 bg-blue-50 text-blue-700 text-xs p-2.5 rounded-lg border border-blue-100 flex items-start gap-2"
                        >
                            <FontAwesomeIcon :icon="faInfoCircle" class="mt-0.5 shrink-0" />
                            <div>
                                Dapatkan diskon sebesar
                                <strong>{{ plan.yearly_discount_percent }}%</strong>
                                dengan memilih siklus penagihan tahunan!
                            </div>
                        </div>
                    </div>

                    <!-- Billing Cycle Selector -->
                    <div class="bg-white border rounded-xl p-3">
                        <h4 class="font-bold text-gray-900 text-xs mb-3">Pilih Siklus Tagihan</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Monthly -->
                            <div
                                class="border-2 rounded-lg p-3 cursor-pointer transition-colors relative"
                                :class="
                                    billingCycle === 'monthly'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                                @click="billingCycle = 'monthly'"
                            >
                                <div class="font-semibold text-gray-800 text-xs">Bulanan</div>
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    Bayar setiap bulan
                                </div>
                                <div class="mt-2 text-base font-bold text-gray-900">
                                    {{ formatIDR(plan.price_per_outlet * activeOutlets) }}
                                </div>
                                <div class="text-[10px] text-gray-500">total / bulan</div>

                                <div
                                    v-if="billingCycle === 'monthly'"
                                    class="absolute top-3 right-3 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>

                            <!-- Yearly -->
                            <div
                                class="border-2 rounded-lg p-3 cursor-pointer transition-colors relative"
                                :class="
                                    billingCycle === 'yearly'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                                @click="billingCycle = 'yearly'"
                            >
                                <div
                                    v-if="plan.yearly_discount_percent > 0"
                                    class="absolute -top-2.5 right-3 bg-success text-white text-[10px] font-bold px-2 py-0.5 rounded-full"
                                >
                                    Hemat {{ plan.yearly_discount_percent }}%
                                </div>
                                <div class="font-semibold text-gray-800 text-xs">Tahunan</div>
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    Bayar untuk 1 tahun
                                </div>
                                <div class="mt-2 text-base font-bold text-gray-900">
                                    {{ formatIDR(yearlyTotal) }}
                                </div>
                                <div class="text-[10px] text-gray-500">total / tahun</div>

                                <div
                                    v-if="billingCycle === 'yearly'"
                                    class="absolute top-3 right-3 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="bg-white border rounded-xl p-3">
                        <h4 class="font-bold text-gray-900 text-xs mb-3">
                            Pilih Metode Pembayaran
                        </h4>
                        <div class="space-y-2.5">
                            <!-- Midtrans -->
                            <div
                                v-if="isMidtransEnabled"
                                class="border-2 rounded-lg p-3 cursor-pointer transition-all relative flex items-start gap-3"
                                :class="
                                    paymentMethod === 'midtrans'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                                @click="paymentMethod = 'midtrans'"
                            >
                                <div
                                    class="p-2 bg-blue-50 text-blue-600 rounded-lg shrink-0 mt-0.5"
                                >
                                    <FontAwesomeIcon :icon="faCreditCard" class="w-4 h-4" />
                                </div>
                                <div class="flex-1 pr-5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-900 text-xs"
                                            >Pembayaran Online Otomatis</span
                                        >
                                        <span
                                            class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-1.5 py-0.5 rounded border border-emerald-100"
                                        >
                                            Rekomendasi
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                                        Bayar secara instan menggunakan QRIS, Virtual Account (BCA,
                                        Mandiri, BNI, dll), GoPay, ShopeePay, atau Kartu Kredit.
                                    </p>
                                </div>
                                <div
                                    v-if="paymentMethod === 'midtrans'"
                                    class="absolute top-3 right-3 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>

                            <!-- Manual Bank Transfer -->
                            <div
                                class="border-2 rounded-lg p-3 cursor-pointer transition-all relative flex items-start gap-3"
                                :class="
                                    paymentMethod === 'manual'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                                @click="paymentMethod = 'manual'"
                            >
                                <div
                                    class="p-2 bg-slate-50 text-slate-650 rounded-lg shrink-0 mt-0.5"
                                >
                                    <FontAwesomeIcon :icon="faBuildingColumns" class="w-4 h-4" />
                                </div>
                                <div class="flex-1 pr-5">
                                    <div class="font-bold text-gray-900 text-xs">
                                        Transfer Bank Manual
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                                        Lakukan transfer ke rekening bank resmi perusahaan kami.
                                        Unggah bukti transfer setelah membayar untuk diverifikasi
                                        admin (1-24 jam).
                                    </p>

                                    <!-- Daftar Bank Dinamis -->
                                    <div
                                        v-if="
                                            paymentMethod === 'manual' &&
                                            manualPaymentMethods.length > 0
                                        "
                                        class="mt-2.5 grid grid-cols-1 gap-1.5 pt-2.5 border-t border-slate-200"
                                    >
                                        <div
                                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-0.5"
                                        >
                                            Rekening Tersedia:
                                        </div>
                                        <div
                                            v-for="bank in manualPaymentMethods"
                                            :key="bank.id"
                                            class="flex flex-col sm:flex-row sm:items-center justify-between bg-white border border-slate-100 p-2 rounded-lg"
                                        >
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-800">{{
                                                    bank.bank_name
                                                }}</span>
                                                <span class="text-[11px] text-gray-500"
                                                    >{{ bank.account_number }} (a/n
                                                    {{ bank.account_name }})</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="paymentMethod === 'manual'"
                                    class="absolute top-3 right-3 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="md:col-span-2">
                    <div class="bg-white border rounded-xl p-3 sticky top-4 space-y-3">
                        <h4 class="font-bold text-gray-900 text-xs border-b pb-2">
                            Ringkasan Pembayaran
                        </h4>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Siklus Tagihan</span>
                                <span class="font-medium capitalize">{{
                                    billingCycle === 'monthly' ? 'Bulanan' : 'Tahunan'
                                }}</span>
                            </div>
                            <div class="flex flex-col">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Jumlah Outlet Aktif</span>
                                    <span class="font-medium">{{ activeOutlets }} Outlet</span>
                                </div>
                                <div
                                    v-if="activeOutletsList.length > 0"
                                    class="mt-1 text-[11px] text-gray-500 pl-2 border-l-2 border-gray-200"
                                >
                                    <div v-for="outlet in activeOutletsList" :key="outlet.id">
                                        - {{ outlet.name }}
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-2"></div>

                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-gray-800">{{
                                    formatIDR(subtotal)
                                }}</span>
                            </div>

                            <div
                                v-if="billingCycle === 'yearly' && plan.yearly_discount_percent > 0"
                                class="flex justify-between text-success font-medium"
                            >
                                <span>Diskon ({{ plan.yearly_discount_percent }}%)</span>
                                <span>-{{ formatIDR(discountAmount) }}</span>
                            </div>

                            <div class="border-t pt-2"></div>

                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Pembayaran</span>
                                <span class="font-bold text-base text-main">{{
                                    formatIDR(finalTotal)
                                }}</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <Link
                                :href="
                                    isRenewal
                                        ? route('settings.subscriptions.renew')
                                        : subscription
                                          ? route('settings.subscriptions.change-plan')
                                          : route('settings.subscriptions.subscribe')
                                "
                                method="post"
                                as="button"
                                :data="{
                                    plan_id: plan.id,
                                    billing_cycle: billingCycle,
                                    payment_method: paymentMethod,
                                }"
                                class="btn btn-main w-full py-2.5 text-center flex justify-center items-center rounded-lg font-bold text-xs shadow-xs hover:shadow-sm transition-all"
                            >
                                Lanjutkan Pembayaran
                            </Link>
                            <p class="text-[11px] text-center text-gray-500 mt-2">
                                Dengan melanjutkan, Anda menyetujui syarat & ketentuan berlangganan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBuildingColumns,
    faCheckCircle,
    faCreditCard,
    faInfoCircle,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import { formatIDR } from '@/Composable/currency-format'

const props = defineProps({
    plan: Object,
    subscription: Object,
    isRenewal: {
        type: Boolean,
        default: false,
    },
    manualPaymentMethods: {
        type: Array,
        default: () => [],
    },
    isMidtransEnabled: {
        type: Boolean,
        default: true,
    },
})

const page = usePage()
const auth = computed(() => page.props.auth)

const billingCycle = ref(props.subscription ? props.subscription.billing_cycle : 'monthly')
const paymentMethod = ref(props.isMidtransEnabled ? 'midtrans' : 'manual')

const activeOutletsList = computed(() => {
    return auth.value.outlets ? auth.value.outlets.filter(o => o.is_active) : []
})

const activeOutlets = computed(() => {
    return activeOutletsList.value.length > 0
        ? activeOutletsList.value.length
        : auth.value.outlets
          ? auth.value.outlets.length
          : 0
})

// Calculations
const subtotal = computed(() => {
    const basePrice = props.plan.price_per_outlet * activeOutlets.value
    if (billingCycle.value === 'yearly') {
        return basePrice * 12
    }
    return basePrice
})

const discountAmount = computed(() => {
    if (billingCycle.value === 'yearly' && props.plan.yearly_discount_percent > 0) {
        return subtotal.value * (props.plan.yearly_discount_percent / 100)
    }
    return 0
})

const yearlyTotal = computed(() => {
    const baseYearly = props.plan.price_per_outlet * activeOutlets.value * 12
    const discount = baseYearly * (props.plan.yearly_discount_percent / 100)
    return baseYearly - discount
})

const finalTotal = computed(() => {
    return subtotal.value - discountAmount.value
})
</script>
