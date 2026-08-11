<template>
    <Container>
        <template #header>
            <ContainerHeader title="Faktur (B2B)" />
            <div class="flex gap-2">
                <Link :href="route('sales.invoices.create')" class="btn btn-main">
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Buat Faktur</span>
                </Link>
            </div>
        </template>
        <Table :headers="headers" :data="invoices.data" :action="true">
            <template #actions="{ item }">
                <Link :href="route('sales.invoices.show', item.id)" class="btn btn-sm btn-outline-main">
                    Detail
                </Link>
            </template>
        </Table>
        <template #footer>
            <Pagination :data="invoices" />
        </template>
    </Container>
</template>

<script setup>
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import Container from '@/Components/Container.vue';
import ContainerHeader from '@/Components/ContainerHeader.vue';
import Table from '@/Components/Table.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    invoices: Object,
    filters: Object
});

const headers = [
    { label: 'Nomor Struk', key: 'receipt_number' },
    { label: 'Pelanggan', key: 'customer.name' },
    { label: 'Total', key: 'total' },
    { label: 'Status', key: 'status' },
    { label: 'Jatuh Tempo', key: 'due_date' },
];
</script>
