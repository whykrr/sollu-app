<template>
    <MainPage>
        <template #header>
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-955">
                        Detail Langganan
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Kelola paket langganan bisnis Anda dan lihat riwayat
                        pembayaran invoice.
                    </p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4">
            <!-- TAMPILAN JIKA BELUM BERLANGGANAN -->
            <div
                v-if="!subscription"
                class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-6 md:p-8 text-center flex flex-col items-center justify-center"
            >
                <div
                    class="w-14 h-14 bg-blue-100/80 rounded-full flex items-center justify-center text-blue-600 mb-4 shadow-sm"
                >
                    <FontAwesomeIcon :icon="faCreditCard" class="text-xl" />
                </div>
                <h2 class="text-xl font-bold text-gray-900">
                    Belum Berlangganan Paket
                </h2>
                <p class="text-gray-600 max-w-md mt-2 mb-6 text-sm">
                    Aktifkan langganan Anda sekarang untuk menikmati akses penuh
                    ke semua fitur, kelola outlet tanpa batas, dan optimalkan
                    operasional bisnis Anda.
                </p>
                <div class="flex flex-wrap gap-3 justify-center mb-6 max-w-lg">
                    <div
                        class="flex items-center gap-2 text-xs font-medium text-gray-700 bg-white/90 backdrop-blur px-3.5 py-2 rounded-full border border-slate-150 shadow-sm"
                    >
                        <FontAwesomeIcon
                            :icon="faCheck"
                            class="text-emerald-500 text-sm"
                        />
                        <span>Kelola Multi-Outlet</span>
                    </div>
                    <div
                        class="flex items-center gap-2 text-xs font-medium text-gray-700 bg-white/90 backdrop-blur px-3.5 py-2 rounded-full border border-slate-150 shadow-sm"
                    >
                        <FontAwesomeIcon
                            :icon="faCheck"
                            class="text-emerald-500 text-sm"
                        />
                        <span>Laporan Terintegrasi</span>
                    </div>
                    <div
                        class="flex items-center gap-2 text-xs font-medium text-gray-700 bg-white/90 backdrop-blur px-3.5 py-2 rounded-full border border-slate-150 shadow-sm"
                    >
                        <FontAwesomeIcon
                            :icon="faCheck"
                            class="text-emerald-500 text-sm"
                        />
                        <span>Dukungan Prioritas</span>
                    </div>
                </div>
                <Link
                    :href="route('settings.billing.plans')"
                    class="btn btn-main rounded-lg font-semibold shadow hover:shadow-md transition-all duration-200"
                >
                    <FontAwesomeIcon :icon="faGem" />
                    Pilih Paket Sekarang
                </Link>
            </div>

            <!-- TAMPILAN JIKA SUDAH BERLANGGANAN -->
            <div
                v-else
                class="bg-white border border-slate-200 rounded-xl p-4 md:p-3"
            >
                <div
                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 pb-4 mb-4"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm"
                        >
                            <FontAwesomeIcon
                                :icon="faCreditCard"
                                class="text-lg"
                            />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-gray-900">
                                    Paket {{ subscription.plan.name }}
                                </h2>
                                <span
                                    class="capitalize text-xs font-medium px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100"
                                >
                                    {{ subscription.billing_cycle }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span
                                    class="badge text-xs"
                                    :class="
                                        subscription.status === 'active'
                                            ? 'badge-success'
                                            : 'badge-warning'
                                    "
                                >
                                    {{
                                        subscription.status === 'active'
                                            ? 'Aktif'
                                            : 'Tidak Aktif'
                                    }}
                                </span>
                                <span class="text-xs text-gray-400">•</span>
                                <span class="text-xs text-gray-500">
                                    Terdaftar sejak
                                    {{
                                        formatDateTimeSimple(
                                            auth.business.created_at,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 w-full md:w-auto">
                        <Link
                            :href="route('settings.billing.plans')"
                            class="btn btn-outline-main text-xs font-semibold py-2 px-4 rounded-lg w-full md:w-auto text-center"
                        >
                            Ubah Paket
                        </Link>
                    </div>
                </div>

                <!-- Expiring Warning Alert -->
                <div
                    v-if="gapDaysFromNow(subscription.expired_at) <= 10"
                    class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3 mb-5 text-amber-900"
                >
                    <FontAwesomeIcon
                        :icon="faCircleExclamation"
                        class="text-amber-600 text-lg mt-0.5"
                    />
                    <div class="flex-1 text-sm">
                        <h4 class="font-bold text-amber-955">
                            Masa Langganan Hampir Habis!
                        </h4>
                        <p class="text-amber-800 mt-0.5">
                            Paket langganan Anda akan berakhir pada
                            <strong>{{
                                formatDateID(subscription.expired_at)
                            }}</strong>
                            (tersisa
                            <strong
                                >{{
                                    gapDaysFromNow(subscription.expired_at)
                                }}
                                hari</strong
                            >). Segera perpanjang agar operasional outlet Anda
                            tidak terganggu.
                        </p>
                        <div class="mt-2.5">
                            <Link
                                :href="route('settings.billing.plans')"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-955 hover:underline"
                            >
                                Perpanjang Sekarang
                                <FontAwesomeIcon
                                    :icon="faArrowRight"
                                    class="text-[10px]"
                                />
                            </Link>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                >
                    <!-- Billing Cycle Info -->
                    <div
                        class="border border-slate-100 rounded-lg p-3.5 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faClock" class="w-4 h-4" />
                        </div>
                        <div>
                            <span
                                class="block text-xs font-medium text-gray-500"
                                >Siklus Tagihan</span
                            >
                            <span
                                class="block text-sm font-bold text-gray-800 mt-0.5 capitalize"
                            >
                                {{ subscription.billing_cycle }}
                            </span>
                        </div>
                    </div>

                    <!-- End Date Info -->
                    <div
                        class="border border-slate-100 rounded-lg p-3.5 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon
                                :icon="faCalendarDays"
                                class="w-4 h-4"
                            />
                        </div>
                        <div>
                            <span
                                class="block text-xs font-medium text-gray-500"
                                >Tanggal Berakhir</span
                            >
                            <span
                                class="block text-sm font-bold text-gray-800 mt-0.5"
                            >
                                {{
                                    subscription.expired_at
                                        ? formatDateID(subscription.expired_at)
                                        : '-'
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Active Outlets Count -->
                    <div
                        class="border border-slate-100 rounded-lg p-3.5 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faShop" class="w-4 h-4" />
                        </div>
                        <div>
                            <span
                                class="block text-xs font-medium text-gray-500"
                                >Jumlah Outlet Aktif</span
                            >
                            <span
                                class="block text-sm font-bold text-gray-800 mt-0.5"
                            >
                                {{ auth.outlets ? auth.outlets.length : 0 }}
                                Outlet
                            </span>
                        </div>
                    </div>

                    <!-- Email Notification -->
                    <div
                        class="border border-slate-100 rounded-lg p-3.5 bg-slate-50/50 flex items-start gap-3 sm:col-span-2 lg:col-span-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon
                                :icon="faEnvelope"
                                class="w-4 h-4"
                            />
                        </div>
                        <div>
                            <span
                                class="block text-xs font-medium text-gray-500"
                                >Email Notifikasi Tagihan</span
                            >
                            <span
                                class="block text-sm font-bold text-gray-800 mt-0.5"
                            >
                                {{ auth.business.email }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL INVOICE -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-base font-bold text-gray-900">
                        Riwayat Pembayaran & Invoice
                    </h3>
                </div>
                <Table
                    :headers="tableSetting"
                    :data="invoices.data"
                    :action="true"
                >
                    <template #invoice_number="{ row }">
                        <span class="font-bold text-gray-900">{{
                            row.invoice_number
                        }}</span>
                    </template>
                    <template #created_at="{ row }">
                        <span class="text-gray-600 text-sm">
                            {{ formatDateTimeSimple(row.created_at) }}
                        </span>
                    </template>
                    <template #total_amount="{ row }">
                        <span class="font-semibold text-gray-900 text-sm">
                            {{ formatIDR(row.total_amount) }}
                        </span>
                    </template>
                    <template #status="{ row }">
                        <label
                            v-if="row.status === 'paid'"
                            class="badge pill text-xs badge-success"
                        >
                            Lunas
                        </label>
                        <label
                            v-else-if="row.status === 'open'"
                            class="badge pill text-xs badge-warning"
                        >
                            Menunggu Pembayaran
                        </label>
                        <label
                            v-else-if="row.status === 'void'"
                            class="badge pill text-xs badge-danger"
                        >
                            Dibatalkan
                        </label>
                        <label
                            v-else
                            class="badge pill text-xs badge-info capitalize"
                        >
                            {{ row.status }}
                        </label>
                    </template>
                    <template #actions="{ row }">
                        <button
                            @click="getDetail(row.invoice_number)"
                            class="btn btn-highlight-main btn-sm"
                        >
                            Detail
                            <FontAwesomeIcon
                                :icon="faArrowRight"
                                class="text-[10px]"
                            />
                        </button>
                    </template>
                </Table>
            </div>
        </div>

        <template #footer>
            <Pagination
                v-if="invoices && invoices.data.length > 0"
                :links="invoices.links"
                :from="invoices.from"
                :to="invoices.to"
                :total="invoices.total"
                :per-page="invoices.per_page ?? 20"
            />
        </template>
    </MainPage>
</template>

<script setup>
import Pagination from '@/Components/Tables/Pagination.vue';
import Table from '@/Components/Tables/Table.vue';
import MainPage from '@/Components/UI/MainPage.vue';
import DetailInvoice from './DetailInvoice.vue';
import { usePopUpStore } from '@/store/popup';
import { router } from '@inertiajs/vue3';
import {
    formatDateID,
    formatDateTimeSimple,
    gapDaysFromNow,
} from '@/Composable/date';
import {
    faArrowUp,
    faArrowRight,
    faCheck,
    faCreditCard,
    faCalendarDays,
    faShop,
    faEnvelope,
    faCircleExclamation,
    faClock,
    faGem,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { formatIDR } from '@/Composable/currency-format';

const props = defineProps({
    subscription: Object,
    invoices: Object,
});

const tableSetting = [
    { field: 'invoice_number', label: 'No Invoice', slot: 'invoice_number' },
    { field: 'created_at', label: 'Tanggal', slot: 'created_at' },
    { field: 'total_amount', label: 'Total', slot: 'total_amount' },
    { field: 'status', label: 'Status', slot: 'status' },
];

const popUpStore = usePopUpStore();

const getDetail = (invoice_number) => {
    router.visit(route('settings.billing.invoices.show', invoice_number), {
        only: ['invoice', 'midtransClientKey', 'payment', 'manualValidation'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            popUpStore.open({
                title: 'Detail Invoice',
                size: 'xl',
                component: DetailInvoice,
                props: {
                    invoice: page.props.invoice,
                    midtransClientKey: page.props.midtransClientKey,
                    payment: page.props.payment,
                    manualValidation: page.props.manualValidation,
                },
            });
        },
    });
};

const page = usePage();
const auth = computed(() => page.props.auth);
</script>
