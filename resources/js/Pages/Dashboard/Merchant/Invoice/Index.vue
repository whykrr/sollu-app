<template>
    <Container>
        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="invoices.data"
            @row-click="goDetail"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
        >
            <template #created_at="{ row }">
                {{ formatDateTimeID(row.created_at) }}
            </template>
            <template #plan="{ row }">
                {{ row.plan.name }} ({{ row.plan.duration }} hari)
            </template>
            <template #total="{ row }">
                {{ formatIDR(row.total) }}
            </template>
            <template #status="{ row }">
                <label
                    v-if="row.status === 'unpaid'"
                    class="badge pill text-sm badge-warning"
                    >Belum Dibayar</label
                >
                <label
                    v-if="row.status === 'payment'"
                    class="badge pill text-sm badge-warning"
                    >Proses Pembayaran</label
                >
                <label
                    v-else-if="row.status === 'paid'"
                    class="badge pill text-sm badge-success"
                    >Terbayar</label
                >
                <label
                    v-else-if="row.status === 'canceled'"
                    class="badge pill text-sm badge-danger"
                    >Dibatalkan</label
                >
                <label v-else class="badge pill text-sm badge-gray-400"
                    >Expired</label
                >
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="invoices.links"
                :from="invoices.from"
                :to="invoices.to"
                :total="invoices.total"
                :per-page="invoices.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import Pagination from "@/Components/Dashboard/Tables/Pagination.vue";
import Filter from "@/Pages/Dashboard/Merchant/Invoice/Components/Filter.vue";
import { Link, router } from "@inertiajs/vue3";
import Container from "@/Components/Dashboard/UI/Container.vue";
import Table from "@/Components/Dashboard/Tables/Table.vue";
import { template } from "lodash";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faPlus } from "@fortawesome/free-solid-svg-icons";
import { formatIDR } from "@/helpers/Dashboard/currency-format";
import { formatDateID, formatDateTimeID } from "@/helpers/Dashboard/date";

defineProps({
    invoices: Object,
    params: Object,
});

const tableHeaders = [
    { field: "code", label: "No. Invoice" },
    {
        field: "created_at",
        label: "Tanggal",
        slot: "created_at",
        sortable: true,
    },
    { field: "plan", label: "Langganan", slot: "plan" },
    { field: "note", label: "Keterangan" },
    { field: "total", label: "Total", slot: "total" },
    { field: "status", label: "Status", slot: "status" },
];

const goDetail = (row) => {
    router.get(
        route("dashboard.merchant.invoices.show", {
            code: row.code,
        })
    );
};
</script>
