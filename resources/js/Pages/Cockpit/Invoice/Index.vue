<template>
    <MainPage>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex gap-2">
                    <TextField
                        placeholder="Search invoice or merchant..."
                        class="w-64"
                    />
                </div>
            </div>
        </template>

        <Table :headers="tableHeaders" :data="invoices.data" :action="true">
            <template #date="{ row }">
                <span class="text-neutral-600">{{ row.date }}</span>
            </template>
            <template #invoice_id="{ row }">
                <span class="font-medium">{{ row.invoice_number }}</span>
            </template>
            <template #merchant="{ row }">
                {{ row.merchant }}
            </template>
            <template #outlet_name="{ row }">
                {{ row.outlet_name }}
            </template>
            <template #amount="{ row }">
                <span class="font-medium">{{ row.amount }}</span>
            </template>
            <template #status="{ row }">
                <span
                    v-if="row.status === 'pending review'"
                    class="px-2 py-1 bg-warning/10 text-warning text-xs rounded-full font-medium"
                >
                    Pending Review
                </span>
                <span
                    v-else-if="row.status === 'paid'"
                    class="px-2 py-1 bg-success/10 text-success text-xs rounded-full font-medium"
                >
                    Paid
                </span>
                <span
                    v-else-if="row.status === 'rejected'"
                    class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full font-medium"
                >
                    Rejected
                </span>
                <span
                    v-else
                    class="px-2 py-1 bg-neutral-100 text-neutral-600 text-xs rounded-full font-medium capitalize"
                >
                    {{ row.status }}
                </span>
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-neutral-100 text-neutral-600 btn-sm"
                    title="View Details"
                    @click="openDetails(row)"
                >
                    <FontAwesomeIcon :icon="faEye" />
                    Details
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="invoices.links"
                :from="invoices.from"
                :to="invoices.to"
                :total="invoices.total"
                :per-page="invoices.per_page"
            />
        </template>

        <RejectReasonModal
            :show="showRejectModal"
            :invoice-id="selectedInvoice?.id"
            @close="showRejectModal = false"
            @success="selectedInvoice = null; showRejectModal = false; popUpStore.close()"
        />
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import TextField from '@/Components/Form/TextField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye } from '@fortawesome/free-solid-svg-icons';
import { ref, onMounted } from 'vue';
import InvoiceDetailDrawer from './Components/InvoiceDetailDrawer.vue';
import RejectReasonModal from './Components/RejectReasonModal.vue';
import { usePopUpStore } from '@/store/popup';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const popUpStore = usePopUpStore();

const tableHeaders = [
    { field: 'date', label: 'Date', slot: 'date' },
    { field: 'invoice_id', label: 'Invoice ID', slot: 'invoice_id' },
    { field: 'merchant', label: 'Merchant', slot: 'merchant' },
    { field: 'outlet_name', label: 'Outlet', slot: 'outlet_name' },
    { field: 'amount', label: 'Amount', slot: 'amount' },
    { field: 'status', label: 'Status', slot: 'status' },
];

const selectedInvoice = ref(null);
const showRejectModal = ref(false);

const openDetails = (invoice) => {
    selectedInvoice.value = invoice;
    popUpStore.open({
        title: 'Detail Invoice',
        description: 'Detail tagihan dan bukti pembayaran.',
        component: InvoiceDetailDrawer,
        props: { invoice: invoice },
        events: {
            reject: () => openRejectModal(invoice),
            onReject: () => openRejectModal(invoice)
        }
    });
};

const openRejectModal = (invoice) => {
    popUpStore.close();
    selectedInvoice.value = invoice;
    showRejectModal.value = true;
};

onMounted(() => {
    const targetInvoiceNumber = props.filters?.open_invoice || new URLSearchParams(window.location.search).get('open_invoice');
    if (targetInvoiceNumber && props.invoices?.data) {
        const found = props.invoices.data.find(inv => inv.invoice_number === targetInvoiceNumber);
        if (found) {
            openDetails(found);
        }
    }
});
</script>
