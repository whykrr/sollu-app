<template>
    <Container>
        <div class="grid grid-cols-12 gap-2 h-full">
            <div class="col-span-8">
                <div
                    class="flex flex-col border rounded-lg bg-white p-2 h-full"
                >
                    <div class="text-lg font-medium">Informasi Usaha</div>
                    <div class="grid grid-cols-2 border gap-2 p-2 rounded-lg">
                        <div class="col-span-2">
                            {{ auth.merchant.name }}
                            <hr />
                        </div>
                        <div class="col-span-2">
                            {{ auth.merchant.owner_name }}
                            <hr />
                        </div>
                        <div>
                            {{ auth.merchant.phone }}
                            <hr />
                        </div>
                        <div>
                            {{ auth.merchant.email }}
                            <hr />
                        </div>

                        <div class="col-span-2">
                            {{ auth.merchant.address }}
                        </div>
                    </div>

                    <div class="text-lg font-medium mt-4">Informasi Outlet</div>
                    <div class="grid grid-flow-row border p-2 rounded-lg">
                        <tr v-for="outlet in outlets">
                            <td class="pr-4">
                                - {{ outlet.name }}
                                <span
                                    v-if="outlet.is_main_outlet"
                                    class="badge badge-info"
                                    >Pusat</span
                                >
                            </td>
                        </tr>
                    </div>

                    <div class="text-lg font-medium mt-4">
                        Informasi Langganan
                    </div>
                    <div class="grid grid-cols-2 border gap-2 p-2 rounded-lg">
                        <div>
                            <div class="font-semibold">
                                {{ plan.name }}
                            </div>
                            <div class="text-sm">
                                <span
                                    v-if="plan.billing_cycle === 'monthly'"
                                    class=""
                                    >Bulanan</span
                                >
                                <span v-else class="">Tahunan</span>
                                ({{ plan.duration }} hari) - Aktif sampai
                                {{
                                    formatDateID(
                                        addDays(
                                            auth.merchant.expired_at,
                                            plan.duration
                                        )
                                    )
                                }}
                            </div>
                        </div>
                        <div class="text-right">
                            {{ formatIDR(plan.price) }}
                            <span class="text-sm font-semibold">/ Outlet</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-4">
                <div class="flex flex-col border rounded-lg bg-white p-2">
                    <div class="text-lg font-medium text-center">
                        Total Pembayaran
                    </div>
                    <div class="text-3xl text-center text-main mb-6">
                        {{ formatIDR(form.total) }}
                    </div>

                    <table class="mt-2">
                        <tr>
                            <td class="pr-4 font-medium">Langganan</td>
                            <td class="text-right">
                                {{ outlets.length }} x
                                {{ formatIDR(plan.price) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pr-4 font-medium">Tambahan</td>
                            <td class="text-right">
                                {{ formatIDR(0) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="pr-4 font-medium">
                                PPN
                                <span class="text-xs text-neutral-600">
                                    (11%)
                                </span>
                            </td>
                            <td class="text-right">
                                {{ formatIDR(form.tax) }}
                            </td>
                        </tr>

                        <tr></tr>

                        <tr>
                            <td class="pr-4 font-medium">Diskon</td>
                            <td class="text-right">
                                - {{ formatIDR(form.discount) }}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <div class="w-full border-b my-2"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="pr-4 font-medium">Total</td>
                            <td class="text-right">
                                {{ formatIDR(form.total) }}
                            </td>
                        </tr>
                    </table>

                    <div class="mt-4 text-sm text-gray-600">
                        *Dengan klik "lanjutkan". Anda menyetujui Syarat &
                        Ketentuan kami.
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-between gap-2">
                <Link
                    :href="route('dashboard.merchant.billing.plans')"
                    class="btn btn-highlight-neutral-600"
                >
                    Kembali
                </Link>
                <button class="btn btn-main" @click="createInvoice">
                    Lanjutkan
                    <FontAwesomeIcon :icon="faArrowRight" />
                </button>
            </div>
        </template>
    </Container>
</template>
<script setup>
import Modal from "@/Components/Dashboard/Notifications/Modal.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { formatIDR } from "@/helpers/Dashboard/currency-format";
import { addDays, formatDateID } from "@/helpers/Dashboard/date";
import {
    faArrowLeft,
    faArrowRight,
    faCheck,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { template } from "lodash";
import { computed } from "vue";

const auth = usePage().props.auth;
const props = defineProps({
    plan: Object,
    outlets: Array,
});

const subtotal = computed(() => props.plan.price * props.outlets.length);
const tax = computed(() => subtotal.value * (11 / 100));
const total = computed(() => subtotal.value + tax.value);

const getItems = props.outlets.map((outlet) => ({
    outlet_id: outlet.id,
    total: props.plan.price,
}));

const form = useForm({
    merchant_id: auth.merchant.id,
    subscription_plan_id: props.plan.id,
    subtotal: subtotal,
    add_ons: 0,
    tax: tax,
    discount: 0,
    total: total,
    note: "Langganan Baru",
    start_date: auth.merchant.expired_at,
    period_end: addDays(auth.merchant.expired_at, props.plan.duration),
    items: getItems,
});

const createInvoice = () =>
    form.post(route("dashboard.merchant.billing.subscribe.store"));
</script>
