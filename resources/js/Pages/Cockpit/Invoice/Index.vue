<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Invoice Langganan"
                description="Kelola tagihan langganan paket merchant, verifikasi bukti pembayaran manual, dan pantau status pelunasan"
            />
        </template>

        <template #widgets>
            <InvoiceWidgets :metrics="metrics" />
        </template>

        <template #filter>
            <InvoiceFilter :filters="filters" />
        </template>

        <Table
            :headers="tableHeaders"
            :data="invoices.data"
            :action="true"
            :sort="typeof filters?.sort === 'string' ? filters.sort : 'created_at'"
            :sort-direction="typeof filters?.direction === 'string' ? filters.direction : 'desc'"
        >
            <template #date="{ row }">
                <span class="text-xs text-neutral-600">{{ row.date }}</span>
            </template>

            <template #invoice_number="{ row }">
                <span class="text-xs font-bold text-neutral-800">{{ row.invoice_number }}</span>
            </template>

            <template #merchant="{ row }">
                <span class="text-xs font-medium text-neutral-700">{{ row.merchant }}</span>
            </template>

            <template #amount="{ row }">
                <span class="text-xs font-bold text-neutral-800">{{ row.amount }}</span>
            </template>

            <template #status="{ row }">
                <span
                    class="px-2 py-0.5 text-[11px] rounded-full font-semibold inline-flex items-center gap-1"
                    :class="
                        row.status === 'paid'
                            ? 'bg-success/10 text-success'
                            : row.status === 'pending_review'
                              ? 'bg-amber-100 text-amber-700'
                              : row.status === 'rejected'
                                ? 'bg-danger/10 text-danger'
                                : 'bg-neutral-100 text-neutral-600'
                    "
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full"
                        :class="
                            row.status === 'paid'
                                ? 'bg-success'
                                : row.status === 'pending_review'
                                  ? 'bg-amber-600'
                                  : row.status === 'rejected'
                                    ? 'bg-danger'
                                    : 'bg-neutral-400'
                        "
                    ></span>
                    {{ row.status_label || row.status }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end">
                    <button
                        type="button"
                        class="btn btn-flat btn-xs"
                        title="Lihat Detail Invoice"
                        @click="openDetails(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" class="text-[10px]" />
                        Detail
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination :meta="invoices.meta || invoices" />
        </template>

        <RejectReasonModal
            :show="showRejectModal"
            :invoice-id="selectedInvoice?.id"
            @close="showRejectModal = false"
            @success="handleRejectSuccess"
        />
    </MainPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import InvoiceWidgets from './Components/InvoiceWidgets.vue'
import InvoiceFilter from './Components/InvoiceFilter.vue'
import InvoiceDetailDrawer from './Components/InvoiceDetailDrawer.vue'
import RejectReasonModal from './Components/RejectReasonModal.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEye } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [] }),
    },
    metrics: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const popUpStore = usePopUpStore()

const tableHeaders = [
    { field: 'created_at', label: 'Tanggal', slot: 'date', sortable: true },
    { field: 'invoice_number', label: 'No. Invoice', slot: 'invoice_number', sortable: true },
    { field: 'merchant', label: 'Merchant', slot: 'merchant' },
    { field: 'total_amount', label: 'Total Tagihan', slot: 'amount', sortable: true },
    { field: 'status', label: 'Status', slot: 'status', sortable: true },
]

const selectedInvoice = ref(null)
const showRejectModal = ref(false)

const openDetails = invoice => {
    selectedInvoice.value = invoice
    popUpStore.open({
        title: 'Detail Invoice & Pembayaran',
        size: 'lg',
        component: InvoiceDetailDrawer,
        props: {
            invoiceId: invoice.id,
            invoice: invoice,
        },
        events: {
            reject: () => openRejectModal(invoice),
        },
    })
}

const openRejectModal = invoice => {
    popUpStore.close()
    selectedInvoice.value = invoice
    showRejectModal.value = true
}

const handleRejectSuccess = () => {
    selectedInvoice.value = null
    showRejectModal.value = false
    popUpStore.close()
}

onMounted(() => {
    const targetInvoiceNumber =
        props.filters?.open_invoice ||
        new URLSearchParams(window.location.search).get('open_invoice')
    if (targetInvoiceNumber && props.invoices?.data) {
        const found = props.invoices.data.find(inv => inv.invoice_number === targetInvoiceNumber)
        if (found) {
            openDetails(found)
        }
    }
})
</script>
