<template>
    <Container>
        <div class="flex flex-col gap-2">
            <div class="bg-white border rounded-md py-4">
                <div class="flex">
                    <span
                        class="bg-gradient-to-r from-main to-secondary-dark py-1.5 px-4 rounded-r-md text-white font-semibold text-sm"
                    >
                        Terdaftar Sejak
                        {{ formatDateID(auth.merchant.created_at) }}
                    </span>
                </div>
                <div class="mt-2 flex flex-col px-4">
                    <div class="font-bold text-xl">
                        {{ auth.subscription.plan.name }}
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Tanggal Berakhir</div>
                        <div class="text-gray-600">
                            {{ formatDateID(auth.merchant.expired_at) }}
                        </div>
                        <div
                            v-if="
                                gapDaysFromNow(auth.merchant.expired_at) <= 10
                            "
                            class="text-danger"
                        >
                            (Sisa
                            {{ gapDaysFromNow(auth.merchant.expired_at) }} hari
                            lagi)
                        </div>
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Jumlah Outlet</div>
                        <div class="text-gray-600">
                            {{ auth.merchant.outlets.length }} Outlet
                        </div>
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Email Notifikasi</div>
                        <div class="text-gray-600">
                            {{ auth.merchant.email }}
                        </div>
                    </div>
                    <hr class="border-t border-neutral-300 my-2" />
                    <div class="space-x-2">
                        <Link
                            :href="route('dashboard.merchant.billing.plans')"
                            v-if="auth.subscription.plan.is_trial"
                            class="btn btn-outline-main btn-sm"
                        >
                            Langganan Sekarang
                        </Link>

                        <button
                            v-if="auth.subscription.plan.is_trial === false"
                            class="btn btn-outline-info btn-sm"
                        >
                            Ubah Langganan
                        </button>

                        <button
                            v-if="
                                gapDaysFromNow(auth.merchant.expired_at) <=
                                    10 &&
                                auth.subscription.plan.is_trial === false
                            "
                            class="btn btn-outline-main btn-sm"
                        >
                            Bayar Langganan Sekarang
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white border rounded-md p-4">
                <div class="font-bold text-xl">
                    {{ auth.subscription.plan.name }}
                </div>
                <p class="text-sm">
                    {{ auth.subscription.plan.description }}
                </p>
                <div
                    class="font-semibold text-success"
                    v-if="auth.subscription.plan.is_trial"
                >
                    Gratis
                </div>
                <div v-else class="font-semibold text-main">
                    {{ formatIDR(auth.subscription.plan.price) }} per Outlet /
                    {{
                        auth.subscription.plan.billing_cycle === "monthly"
                            ? "Bulan"
                            : "Tahun"
                    }}
                </div>
            </div>
        </div>
    </Container>
</template>

<script setup>
import Container from "@/Components/Dashboard/UI/Container.vue";
import { formatIDR } from "@/helpers/Dashboard/currency-format";
import { formatDateID, gapDaysFromNow } from "@/helpers/Dashboard/date";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const auth = computed(() => page.props.auth);
</script>
