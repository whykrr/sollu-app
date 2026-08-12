<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Opsi Tambahan Produk">
                <button class="btn btn-highlight-main" @click="openModal()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </MainPageHeader>
            <div class="flex-1 border-r border-slate-200 pr-2">
                <div class="flex items-center gap-2">
                    <div>
                        <FilterSearch v-model="search" />
                    </div>
                </div>
            </div>
        </template>

        <Table :headers="headers" :data="modifiers.data" :action="true">
            <template #type="{ row }">
                {{
                    row.selection_type === 'single'
                        ? 'Pilih Satu'
                        : 'Pilih Banyak'
                }}
            </template>
            <template #options_count="{ row }">
                {{ row.options.length }}
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-highlight-main btn-sm mr-1"
                    title="Ubah"
                    @click="openModal(row)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button
                    class="btn btn-outline-danger btn-sm"
                    title="Hapus"
                    @click="deleteModifier(row.id)"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="modifiers.links"
                :from="modifiers.from"
                :to="modifiers.to"
                :total="modifiers.total"
                :per-page="modifiers.per_page ?? 10"
            />
        </template>

        <!-- Dynamic popup handled by PopUpStore -->
    </MainPage>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';
import { debounce } from 'lodash';
import { useModalStore } from '@/store/notification';
import { usePopUpStore } from '@/store/popup';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import ModifierForm from './Components/ModifierForm.vue';

const props = defineProps({
    modifiers: Object,
    filters: Object,
});

const modalStore = useModalStore();
const popUpStore = usePopUpStore();

const headers = [
    { label: 'Nama Grup', field: 'name', sortable: true },
    {
        label: 'Tipe Pilihan',
        field: 'selection_type',
        slot: 'type',
        sortable: false,
    },
    {
        label: 'Jumlah Opsi',
        field: 'options_count',
        slot: 'options_count',
        sortable: false,
    },
];

const search = ref(props.filters?.search || '');

watch(
    search,
    debounce((newVal) => {
        router.get(
            route('master.modifiers.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true },
        );
    }, 500),
);

const openModal = (modifier = null) => {
    popUpStore.open({
        title: modifier ? 'Edit Modifier' : 'Tambah Modifier',
        size: 'lg',
        component: ModifierForm,
        props: { modifier },
    });
};

const deleteModifier = (id) => {
    modalStore.openModalDelete(route('master.modifiers.destroy', id));
};
</script>
