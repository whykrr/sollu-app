<template>
    <MainPage>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex gap-2">
                    <TextField
                        placeholder="Search invoice or merchant..."
                        class="w-64"
                    />
                    <DropdownField
                        :options="[
                            { value: 'pending', label: 'Pending Validation' },
                            { value: 'approved', label: 'Approved' },
                            { value: 'rejected', label: 'Rejected' },
                        ]"
                    />
                </div>
            </div>
        </template>

        <Table :headers="tableHeaders" :data="invoices.data" :action="true">
            <template #date="{ row }">
                <span class="text-neutral-600">{{ row.date }}</span>
            </template>
            <template #invoice_id="{ row }">
                <span class="font-medium">{{ row.invoice_id }}</span>
            </template>
            <template #merchant="{ row }">
                {{ row.merchant }}
            </template>
            <template #amount="{ row }">
                <span class="font-medium">{{ row.amount }}</span>
            </template>
            <template #status="{ row }">
                <span
                    v-if="row.status === 'Pending Review'"
                    class="px-2 py-1 bg-warning/10 text-warning text-xs rounded-full font-medium"
                >
                    Pending Review
                </span>
                <span
                    v-else-if="row.status === 'Approved'"
                    class="px-2 py-1 bg-success/10 text-success text-xs rounded-full font-medium"
                >
                    Approved
                </span>
                <span
                    v-else
                    class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full font-medium"
                >
                    Rejected
                </span>
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-neutral-100 text-neutral-600 btn-sm"
                    title="View Proof"
                >
                    <FontAwesomeIcon :icon="faEye" />
                </button>
                <button
                    v-if="row.status === 'Pending Review'"
                    class="btn btn-success/10 text-success btn-sm"
                    title="Approve"
                >
                    <FontAwesomeIcon :icon="faCheck" />
                </button>
                <button
                    v-if="row.status === 'Pending Review'"
                    class="btn btn-danger/10 text-danger btn-sm"
                    title="Reject"
                >
                    <FontAwesomeIcon :icon="faTimes" />
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
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye, faCheck, faTimes } from '@fortawesome/free-solid-svg-icons';
import { ref } from 'vue';

const tableHeaders = [
    { field: 'date', label: 'Date', slot: 'date', sortable: true },
    {
        field: 'invoice_id',
        label: 'Invoice ID',
        slot: 'invoice_id',
        sortable: true,
    },
    { field: 'merchant', label: 'Merchant', slot: 'merchant' },
    { field: 'amount', label: 'Amount', slot: 'amount', sortable: true },
    { field: 'status', label: 'Status', slot: 'status' },
];

const invoices = ref({
    data: [
        {
            id: 1,
            date: '14 Jun 2026 14:30',
            invoice_id: 'INV-2026-892',
            merchant: 'Toko Sejahtera',
            amount: 'Rp 4.500.000',
            status: 'Pending Review',
        },
        {
            id: 2,
            date: '13 Jun 2026 09:15',
            invoice_id: 'INV-2026-891',
            merchant: 'Warung Makan Bahari',
            amount: 'Rp 1.200.000',
            status: 'Pending Review',
        },
    ],
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/cockpit/invoices?page=1', label: '1', active: true },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 2,
    total: 2,
    per_page: 20,
});
</script>
