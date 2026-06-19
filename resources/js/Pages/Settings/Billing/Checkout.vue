<template>
    <Container>
        <div class="max-w-3xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                Selesaikan Pembayaran
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Main Content -->
                <div class="md:col-span-2 space-y-4">
                    <!-- Plan Info -->
                    <div class="bg-white border rounded-xl p-5">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-sm text-gray-500 font-medium">
                                    Paket Terpilih
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">
                                    {{ plan.name }}
                                </h3>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-main">
                                    {{ formatIDR(plan.price_per_outlet) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    / outlet / bulan
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="plan.yearly_discount_percent > 0"
                            class="mt-4 bg-blue-50 text-blue-700 text-sm p-3 rounded-lg border border-blue-100 flex items-start gap-2"
                        >
                            <FontAwesomeIcon
                                :icon="faInfoCircle"
                                class="mt-0.5"
                            />
                            <div>
                                Dapatkan diskon sebesar
                                <strong
                                    >{{ plan.yearly_discount_percent }}%</strong
                                >
                                dengan memilih siklus penagihan tahunan!
                            </div>
                        </div>
                    </div>

                    <!-- Billing Cycle Selector -->
                    <div class="bg-white border rounded-xl p-5">
                        <h4 class="font-bold text-gray-800 mb-4">
                            Pilih Siklus Tagihan
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Monthly -->
                            <div
                                @click="billingCycle = 'monthly'"
                                class="border-2 rounded-lg p-4 cursor-pointer transition-colors relative"
                                :class="
                                    billingCycle === 'monthly'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <div class="font-semibold text-gray-800">
                                    Bulanan
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    Bayar setiap bulan
                                </div>
                                <div
                                    class="mt-3 text-lg font-bold text-gray-800"
                                >
                                    {{
                                        formatIDR(
                                            plan.price_per_outlet *
                                                activeOutlets,
                                        )
                                    }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    total / bulan
                                </div>

                                <div
                                    v-if="billingCycle === 'monthly'"
                                    class="absolute top-4 right-4 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>

                            <!-- Yearly -->
                            <div
                                @click="billingCycle = 'yearly'"
                                class="border-2 rounded-lg p-4 cursor-pointer transition-colors relative"
                                :class="
                                    billingCycle === 'yearly'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <div
                                    v-if="plan.yearly_discount_percent > 0"
                                    class="absolute -top-3 right-4 bg-success text-white text-xs font-bold px-2 py-0.5 rounded-full"
                                >
                                    Hemat {{ plan.yearly_discount_percent }}%
                                </div>
                                <div class="font-semibold text-gray-800">
                                    Tahunan
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    Bayar untuk 1 tahun
                                </div>
                                <div
                                    class="mt-3 text-lg font-bold text-gray-800"
                                >
                                    {{ formatIDR(yearlyTotal) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    total / tahun
                                </div>

                                <div
                                    v-if="billingCycle === 'yearly'"
                                    class="absolute top-4 right-4 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="bg-white border rounded-xl p-5">
                        <h4 class="font-bold text-gray-800 mb-4">
                            Pilih Metode Pembayaran
                        </h4>
                        <div class="space-y-3">
                            <!-- Midtrans -->
                            <div
                                @click="paymentMethod = 'midtrans'"
                                class="border-2 rounded-lg p-4 cursor-pointer transition-all relative flex items-start gap-4 animate-fadeIn"
                                :class="
                                    paymentMethod === 'midtrans'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <div
                                    class="p-2.5 bg-blue-50 text-blue-600 rounded-lg mt-0.5"
                                >
                                    <FontAwesomeIcon
                                        :icon="faCreditCard"
                                        class="w-5 h-5"
                                    />
                                </div>
                                <div class="flex-1 pr-6">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-bold text-gray-800 text-sm"
                                            >Pembayaran Online Otomatis</span
                                        >
                                        <span
                                            class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-100"
                                        >
                                            Rekomendasi
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Bayar secara instan menggunakan QRIS,
                                        Virtual Account (BCA, Mandiri, BNI,
                                        dll), GoPay, ShopeePay, atau Kartu
                                        Kredit. Pembayaran Anda langsung
                                        diverifikasi secara real-time.
                                    </p>
                                </div>
                                <div
                                    v-if="paymentMethod === 'midtrans'"
                                    class="absolute top-4 right-4 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>

                            <!-- Manual Bank Transfer -->
                            <div
                                @click="paymentMethod = 'manual'"
                                class="border-2 rounded-lg p-4 cursor-pointer transition-all relative flex items-start gap-4"
                                :class="
                                    paymentMethod === 'manual'
                                        ? 'border-main bg-main/5'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <div
                                    class="p-2.5 bg-slate-50 text-slate-650 rounded-lg mt-0.5"
                                >
                                    <FontAwesomeIcon
                                        :icon="faBuildingColumns"
                                        class="w-5 h-5"
                                    />
                                </div>
                                <div class="flex-1 pr-6">
                                    <div
                                        class="font-bold text-gray-800 text-sm"
                                    >
                                        Transfer Bank Manual
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Lakukan transfer ke rekening bank resmi
                                        perusahaan kami. Anda perlu mengunggah
                                        bukti transfer setelah membayar.
                                        Verifikasi dilakukan secara manual oleh
                                        admin kami dalam waktu 1-24 jam.
                                    </p>
                                </div>
                                <div
                                    v-if="paymentMethod === 'manual'"
                                    class="absolute top-4 right-4 text-main"
                                >
                                    <FontAwesomeIcon :icon="faCheckCircle" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="md:col-span-1">
                    <div class="bg-white border rounded-xl p-5 sticky top-6">
                        <h4 class="font-bold text-gray-800 mb-4 border-b pb-2">
                            Ringkasan Pembayaran
                        </h4>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600"
                                    >Siklus Tagihan</span
                                >
                                <span class="font-medium capitalize">{{
                                    billingCycle === 'monthly'
                                        ? 'Bulanan'
                                        : 'Tahunan'
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600"
                                    >Jumlah Outlet Aktif</span
                                >
                                <span class="font-medium"
                                    >{{ activeOutlets }} Outlet</span
                                >
                            </div>

                            <div class="border-t pt-3 mt-3"></div>

                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-gray-800">{{
                                    formatIDR(subtotal)
                                }}</span>
                            </div>

                            <div
                                v-if="
                                    billingCycle === 'yearly' &&
                                    plan.yearly_discount_percent > 0
                                "
                                class="flex justify-between text-success"
                            >
                                <span
                                    >Diskon ({{
                                        plan.yearly_discount_percent
                                    }}%)</span
                                >
                                <span class="font-medium"
                                    >-{{ formatIDR(discountAmount) }}</span
                                >
                            </div>

                            <div class="border-t pt-3 mt-3"></div>

                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800"
                                    >Total Pembayaran</span
                                >
                                <span class="font-bold text-lg text-main">{{
                                    formatIDR(finalTotal)
                                }}</span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <Link
                                :href="
                                    subscription
                                        ? route(
                                              'settings.subscriptions.change-plan',
                                          )
                                        : route(
                                              'settings.subscriptions.subscribe',
                                          )
                                "
                                method="post"
                                as="button"
                                :data="{
                                    plan_id: plan.id,
                                    billing_cycle: billingCycle,
                                    payment_method: paymentMethod,
                                }"
                                class="btn btn-main w-full py-3 text-center flex justify-center items-center rounded-lg font-bold shadow hover:shadow-md transition-all duration-150"
                            >
                                Lanjutkan Pembayaran
                            </Link>
                            <p class="text-xs text-center text-gray-500 mt-3">
                                Dengan melanjutkan, Anda menyetujui syarat &
                                ketentuan berlangganan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Container>
</template>

<script setup>
import Container from '@/Components/UI/Container.vue';
import { formatIDR } from '@/Composable/currency-format';
import {
    faCheckCircle,
    faInfoCircle,
    faCreditCard,
    faBuildingColumns,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plan: Object,
    subscription: Object,
});

const page = usePage();
const auth = computed(() => page.props.auth);

// Set default to monthly or based on current active subscription
const billingCycle = ref(
    props.subscription ? props.subscription.billing_cycle : 'monthly',
);
const paymentMethod = ref('midtrans');

const activeOutlets = computed(() => {
    return auth.value.outlets
        ? auth.value.outlets.filter((o) => o.is_active).length ||
              auth.value.outlets.length
        : 0;
});

// Calculations
const subtotal = computed(() => {
    const basePrice = props.plan.price_per_outlet * activeOutlets.value;
    if (billingCycle.value === 'yearly') {
        return basePrice * 12;
    }
    return basePrice;
});

const discountAmount = computed(() => {
    if (
        billingCycle.value === 'yearly' &&
        props.plan.yearly_discount_percent > 0
    ) {
        return subtotal.value * (props.plan.yearly_discount_percent / 100);
    }
    return 0;
});

const yearlyTotal = computed(() => {
    const baseYearly = props.plan.price_per_outlet * activeOutlets.value * 12;
    const discount = baseYearly * (props.plan.yearly_discount_percent / 100);
    return baseYearly - discount;
});

const finalTotal = computed(() => {
    return subtotal.value - discountAmount.value;
});
</script>
