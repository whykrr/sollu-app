<template>
    <MainPage>
        <div
            v-if="invoice"
            class="alert alert-warning inline-flex justify-between items-center w-full mb-4"
        >
            <div class="text-sm">
                <div class="font-bold">
                    Tagihan Anda masih menunggu pembayaran.
                </div>
                Silakan lakukan pembayaran atau batalkan untuk mengganti paket
                langganan.
            </div>
            <div>
                <Link
                    :href="
                        route(
                            'settings.billing.invoices.show',
                            invoice.invoice_number,
                        )
                    "
                    class="btn btn-main btn-sm"
                >
                    Lihat Tagihan
                </Link>
            </div>
        </div>

        <div
            class="flex justify-between items-center w-full mb-4 rounded-lg bg-white border p-4"
        >
            <div class="text-sm">
                <div class="font-bold text-lg text-gray-800">
                    Lebih Hemat & Praktis
                </div>
                <p class="text-gray-600">
                    Bayar 10 bulan, nikmati layanan 12 bulan penuh. Dapatkan
                    tambahan diskon dengan paket tahunan!
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="(plan, index) in plans" :key="index">
                <div
                    class="p-4 space-y-4 rounded-xl border bg-white flex flex-col h-full hover:shadow-md transition-shadow relative"
                    :class="{
                        'border-main ':
                            subscription && subscription.plan_id === plan.id,
                        'border-gray-200':
                            !subscription || subscription.plan_id !== plan.id,
                    }"
                >
                    <div
                        v-if="subscription && subscription.plan_id === plan.id"
                        class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-main text-white text-xs font-bold px-3 py-1 rounded-full"
                    >
                        Paket Saat Ini
                    </div>

                    <div class="text-center pb-4 border-b">
                        <div class="font-bold text-2xl text-gray-800 mb-1">
                            {{ plan.name }}
                        </div>
                        <div class="text-3xl font-extrabold text-main my-2">
                            {{ formatIDR(plan.price_per_outlet) }}
                        </div>
                        <div class="text-sm text-gray-500 font-medium">
                            per bulan / outlet
                        </div>
                        <div
                            v-if="plan.yearly_discount_percent > 0"
                            class="mt-2 text-xs font-bold text-success bg-success/10 py-1 px-2 rounded-md inline-block"
                        >
                            Hemat {{ plan.yearly_discount_percent }}% jika Bayar
                            Tahunan!
                        </div>
                    </div>

                    <div class="flex-1 mt-4">
                        <ul class="space-y-3">
                            <li
                                v-for="(feature, fIndex) in plan.features"
                                :key="fIndex"
                                class="flex items-start gap-3"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-success mt-1"
                                />
                                <div class="text-sm text-gray-700">
                                    <p class="font-bold">{{ feature.title }}</p>
                                    <p>{{ feature.detail }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 pt-4">
                        <Link
                            v-if="
                                !invoice &&
                                (!subscription ||
                                    subscription.plan_id !== plan.id)
                            "
                            :href="route('settings.billing.checkout', plan.id)"
                            class="btn btn-highlight-main w-full py-3"
                        >
                            {{
                                subscription
                                    ? 'Ganti ke Paket Ini'
                                    : 'Pilih Paket'
                            }}
                        </Link>

                        <button
                            v-else-if="
                                subscription && subscription.plan_id === plan.id
                            "
                            disabled
                            class="btn btn-outline-main w-full py-3 opacity-50 cursor-not-allowed"
                        >
                            Paket Aktif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>
<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import { formatIDR } from '@/Composable/currency-format';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    subscription: Object,
    plans: Array,
    invoice: Object,
});
</script>
