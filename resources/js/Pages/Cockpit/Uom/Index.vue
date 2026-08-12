<template>
    <MainPage>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex gap-2">
                    <TextField
                        placeholder="Search code or name..."
                        class="w-64"
                    />
                </div>
                <div>
                    <button class="btn btn-main btn-sm">
                        <FontAwesomeIcon :icon="faPlus" class="mr-2" />Add UOM
                    </button>
                </div>
            </div>
        </template>

        <Table :headers="tableHeaders" :data="uoms.data" :action="true">
            <template #code="{ row }">
                <span class="font-medium text-neutral-800">{{ row.code }}</span>
            </template>
            <template #name="{ row }">
                {{ row.name }}
            </template>
            <template #category="{ row }">
                {{ row.category }}
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-neutral-100 text-main btn-sm"
                    title="Edit"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button
                    class="btn btn-danger/10 text-danger btn-sm"
                    title="Delete"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="uoms.links"
                :from="uoms.from"
                :to="uoms.to"
                :total="uoms.total"
                :per-page="uoms.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import TextField from '@/Components/Form/TextField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';
import { ref } from 'vue';

const tableHeaders = [
    { field: 'code', label: 'Code', slot: 'code', sortable: true },
    { field: 'name', label: 'Name', slot: 'name', sortable: true },
    { field: 'category', label: 'Category', slot: 'category', sortable: true },
];

const uoms = ref({
    data: [
        { id: 1, code: 'PCS', name: 'Pieces', category: 'General' },
        { id: 2, code: 'KG', name: 'Kilogram', category: 'Weight' },
        { id: 3, code: 'BOX', name: 'Box', category: 'Packaging' },
    ],
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/cockpit/uoms?page=1', label: '1', active: true },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 3,
    total: 3,
    per_page: 20,
});
</script>
