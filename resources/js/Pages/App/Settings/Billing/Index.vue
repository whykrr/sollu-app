<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Detail Langganan"
                description="Kelola paket langganan bisnis Anda dan lihat riwayat pembayaran invoice."
            >
                <Link
                    v-if="!subscription || subscription.status !== $enums.SubscriptionStatus.Active"
                    :href="route('settings.billing.plans')"
                    class="btn btn-highlight-main btn-sm text-xs font-semibold py-2 px-3"
                >
                    <FontAwesomeIcon :icon="faGem" />
                    Pilih Paket
                </Link>
                <Link
                    v-else
                    :href="route('settings.billing.plans')"
                    class="btn btn-outline-main btn-sm text-xs font-semibold py-2 px-3"
                >
                    Ubah Paket
                </Link>
            </MainPageHeader>
            <BillingFilter :filters="params" />
        </template>

        <div class="flex flex-col gap-3">
            <!-- BANNER TAGIHAN BELUM DIBAYAR -->
            <div
                v-if="pendingInvoice"
                class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900"
            >
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-100 rounded-lg text-amber-700 shrink-0 mt-0.5">
                        <FontAwesomeIcon :icon="faCircleExclamation" class="text-base" />
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-950 text-sm">
                            Tagihan Menunggu Pembayaran
                        </h4>
                        <p class="text-amber-800 text-xs mt-0.5">
                            Invoice
                            <strong>#{{ pendingInvoice.invoice_number }}</strong>
                            sebesar
                            <strong>{{ formatIDR(pendingInvoice.total_amount) }}</strong>
                            jatuh tempo pada
                            <strong>{{ formatDateID(pendingInvoice.due_date) }}</strong
                            >.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                    <button
                        class="btn btn-warning btn-sm font-semibold"
                        @click="getDetail(pendingInvoice.invoice_number)"
                    >
                        Bayar Tagihan
                        <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
                    </button>
                </div>
            </div>

            <!-- TAMPILAN JIKA BELUM BERLANGGANAN (MASA UJI COBA / TRIAL) -->
            <div v-if="!subscription" class="bg-white border border-slate-200 rounded-xl p-3">
                <div
                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 border-b border-slate-100 pb-3 mb-3"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-main shadow-xs shrink-0"
                        >
                            <FontAwesomeIcon :icon="faBolt" class="text-base" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900">
                                    Masa Uji Coba Gratis (Free Trial)
                                </h2>
                                <span
                                    class="badge text-xs"
                                    :class="
                                        gapDaysFromNow(auth.business?.trial_end_at) > 0
                                            ? 'badge-info'
                                            : 'badge-danger'
                                    "
                                >
                                    {{
                                        gapDaysFromNow(auth.business?.trial_end_at) > 0
                                            ? 'Aktif'
                                            : 'Berakhir'
                                    }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Tingkatkan ke paket berbayar untuk menikmati akses penuh dan kelola
                                multi-outlet tanpa batasan.
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="route('settings.billing.plans')"
                        class="btn btn-main btn-sm text-xs font-semibold py-2 px-3 rounded-lg w-full md:w-auto text-center shrink-0"
                    >
                        <FontAwesomeIcon :icon="faGem" />
                        Pilih Paket Langganan
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Masa Berlaku Trial -->
                    <div
                        class="border border-slate-100 rounded-lg p-3 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faCalendarDays" class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500">
                                Masa Berlaku Uji Coba
                            </span>
                            <span class="block text-sm font-bold text-gray-800 mt-0.5">
                                {{
                                    auth.business?.trial_end_at
                                        ? formatDateID(auth.business.trial_end_at)
                                        : '-'
                                }}
                                <span
                                    v-if="
                                        auth.business?.trial_end_at &&
                                        gapDaysFromNow(auth.business.trial_end_at) > 0
                                    "
                                    class="text-xs font-normal text-amber-600 ml-1"
                                >
                                    (tersisa {{ gapDaysFromNow(auth.business.trial_end_at) }} hari)
                                </span>
                                <span
                                    v-else-if="auth.business?.trial_end_at"
                                    class="text-xs font-normal text-rose-600 ml-1"
                                >
                                    (telah berakhir)
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Kuota Outlet -->
                    <div
                        class="border border-slate-100 rounded-lg p-3 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faShop" class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500">
                                Penggunaan Outlet
                            </span>
                            <span class="block text-sm font-bold text-gray-800 mt-0.5">
                                {{ auth.outlets?.length ?? 0 }} Outlet Terdaftar
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN JIKA SUDAH BERLANGGANAN AKTIF -->
            <div v-else class="bg-white border border-slate-200 rounded-xl p-3">
                <div
                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 border-b border-slate-100 pb-3 mb-3"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-main shadow-xs shrink-0"
                        >
                            <FontAwesomeIcon :icon="faCreditCard" class="text-base" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900">
                                    Paket {{ subscription.plan?.name }}
                                </h2>
                                <span
                                    class="capitalize text-xs font-medium px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100"
                                >
                                    {{
                                        subscription.billing_cycle === 'yearly'
                                            ? 'Tahunan'
                                            : 'Bulanan'
                                    }}
                                </span>
                                <span
                                    class="badge text-xs"
                                    :class="
                                        subscription.status === $enums.SubscriptionStatus.Active
                                            ? 'badge-success'
                                            : 'badge-warning'
                                    "
                                >
                                    {{
                                        subscription.status === $enums.SubscriptionStatus.Active
                                            ? 'Aktif'
                                            : 'Tidak Aktif'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 w-full md:w-auto shrink-0">
                        <Link
                            :href="route('settings.billing.plans')"
                            class="btn btn-outline-main text-xs font-semibold py-2 px-3 rounded-lg w-full md:w-auto text-center"
                        >
                            Ubah Paket
                        </Link>
                    </div>
                </div>

                <!-- Expiring Warning Alert -->
                <div
                    v-if="gapDaysFromNow(subscription.expired_at) <= 10"
                    class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-3 mb-3 text-amber-900"
                >
                    <FontAwesomeIcon
                        :icon="faCircleExclamation"
                        class="text-amber-600 text-base mt-0.5 shrink-0"
                    />
                    <div class="flex-1 text-xs sm:text-sm">
                        <h4 class="font-bold text-amber-950">Masa Langganan Hampir Habis!</h4>
                        <p class="text-amber-800 mt-0.5">
                            Paket Anda akan berakhir pada
                            <strong>{{ formatDateID(subscription.expired_at) }}</strong>
                            (tersisa
                            <strong>{{ gapDaysFromNow(subscription.expired_at) }} hari</strong>).
                            Segera perpanjang agar operasional outlet tidak terganggu.
                        </p>
                        <div class="mt-2">
                            <Link
                                :href="
                                    route('settings.billing.checkout', {
                                        plan_id: subscription?.plan?.id,
                                        is_renewal: 1,
                                    })
                                "
                                class="inline-flex items-center gap-1 text-xs font-bold text-amber-955 hover:underline"
                            >
                                Perpanjang Sekarang
                                <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Masa Berlaku -->
                    <div
                        class="border border-slate-100 rounded-lg p-3 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faCalendarDays" class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500">
                                Masa Berlaku
                            </span>
                            <span class="block text-sm font-bold text-gray-800 mt-0.5">
                                {{
                                    subscription.expired_at
                                        ? formatDateID(subscription.expired_at)
                                        : '-'
                                }}
                                <span
                                    v-if="subscription.expired_at"
                                    class="text-xs font-normal text-gray-500 ml-1"
                                >
                                    (tersisa {{ gapDaysFromNow(subscription.expired_at) }} hari)
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Kuota Outlet -->
                    <div
                        class="border border-slate-100 rounded-lg p-3 bg-slate-50/50 flex items-start gap-3"
                    >
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500">
                            <FontAwesomeIcon :icon="faShop" class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-gray-500">
                                Penggunaan Outlet
                            </span>
                            <span class="block text-sm font-bold text-gray-800 mt-0.5">
                                {{ auth.outlets?.length ?? 0 }} Outlet Terdaftar
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL INVOICE -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <h3 class="text-base font-bold text-gray-900">Riwayat Pembayaran & Invoice</h3>
                </div>
                <Table
                    :headers="tableSetting"
                    :data="invoices.data"
                    :sort="params?.sort ?? 'created_at'"
                    :sort-direction="params?.direction ?? 'desc'"
                    :action="true"
                >
                    <template #invoice_number="{ row }">
                        <span class="font-bold text-gray-900">{{ row.invoice_number }}</span>
                    </template>
                    <template #created_at="{ row }">
                        <span class="text-gray-600 text-sm">
                            {{ formatDateID(row.created_at) }}
                        </span>
                    </template>
                    <template #total_amount="{ row }">
                        <span class="font-semibold text-gray-900 text-sm">
                            {{ formatIDR(row.total_amount) }}
                        </span>
                    </template>
                    <template #status="{ row }">
                        <span
                            v-if="
                                row.payment_manual_validation?.validation_status ===
                                $enums.PaymentManualValidationStatus.Pending
                            "
                            class="badge pill text-xs badge-warning"
                        >
                            Verifikasi Tertunda
                        </span>
                        <span
                            v-else-if="
                                row.payment_manual_validation?.validation_status ===
                                $enums.PaymentManualValidationStatus.Rejected
                            "
                            class="badge pill text-xs badge-danger"
                        >
                            Bukti Ditolak
                        </span>
                        <span
                            v-else
                            class="badge pill text-xs"
                            :class="$enums.InvoiceStatus._meta[row.status]?.color || 'badge-gray'"
                        >
                            {{ $enums.InvoiceStatus._meta[row.status]?.label || row.status }}
                        </span>
                    </template>
                    <template #actions="{ row }">
                        <button class="btn btn-flat btn-sm" @click="getDetail(row.invoice_number)">
                            <FontAwesomeIcon :icon="faEye" class="text-[10px]" />
                            Detail
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
import { computed, onMounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowRight,
    faBolt,
    faCalendarDays,
    faCircleExclamation,
    faCreditCard,
    faEye,
    faGem,
    faShop,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import BillingFilter from './Components/BillingFilter.vue'

import { usePopUpStore } from '@/store/popup'
import { formatDateID, gapDaysFromNow } from '@/Composable/date'
import { formatIDR } from '@/Composable/currency-format'
import DetailInvoice from './DetailInvoice.vue'

defineProps({
    subscription: Object,
    pendingInvoice: Object,
    invoices: Object,
    params: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()
const auth = computed(() => page.props.auth)
const popUpStore = usePopUpStore()

const tableSetting = [
    { field: 'invoice_number', label: 'No Invoice', slot: 'invoice_number', sortable: true },
    { field: 'created_at', label: 'Tanggal', slot: 'created_at', sortable: true },
    { field: 'total_amount', label: 'Total', slot: 'total_amount', sortable: true },
    { field: 'status', label: 'Status', slot: 'status', sortable: true },
]

const getDetail = invoice_number => {
    axios
        .get(route('settings.billing.invoices.show', invoice_number), {
            headers: { Accept: 'application/json' },
        })
        .then(response => {
            const data = response.data
            popUpStore.open({
                title: 'Detail Invoice',
                size: 'xl',
                component: DetailInvoice,
                props: {
                    invoice: data.invoice,
                    midtransClientKey: data.midtransClientKey,
                    payment: data.payment,
                    manualValidation: data.manualValidation,
                    manualPaymentMethods: data.manualPaymentMethods,
                    isMidtransEnabled: data.isMidtransEnabled,
                },
            })
        })
        .catch(error => {
            console.error(error)
        })
}

watch(
    () => page.url,
    url => {
        if (url.includes('open_invoice=')) {
            const urlParams = new URLSearchParams(url.split('?')[1])
            const openInvoice = urlParams.get('open_invoice')
            if (openInvoice) {
                getDetail(openInvoice)
                const newUrl =
                    window.location.protocol +
                    '//' +
                    window.location.host +
                    window.location.pathname
                window.history.replaceState({ path: newUrl }, '', newUrl)
            }
        }
    }
)

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const openInvoice = urlParams.get('open_invoice')
    if (openInvoice) {
        getDetail(openInvoice)
        const newUrl =
            window.location.protocol + '//' + window.location.host + window.location.pathname
        window.history.replaceState({ path: newUrl }, '', newUrl)
    }
})
</script>
