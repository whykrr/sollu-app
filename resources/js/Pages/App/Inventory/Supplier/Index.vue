<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Data Supplier"
                description="Kelola daftar pemasok, kontak, dan bahan baku yang disuplai untuk kebutuhan pengadaan tokomu."
            />
        </template>

        <template #filter>
            <SupplierFilter :filters="filters" @create="openForm()" />
        </template>
        <Table
            :headers="headers"
            :data="suppliers.data"
            :action="true"
            :sort="filters?.sort"
            :sort-direction="filters?.direction"
            @row-click="openForm"
        >
            <template #contact="{ row }">
                <div class="flex flex-col">
                    <span v-if="row.phone" class="text-sm">
                        <FontAwesomeIcon :icon="faPhone" class="mr-1 text-gray-500" />
                        {{ row.phone }}
                    </span>
                    <span v-if="row.email" class="text-sm text-gray-500">
                        <FontAwesomeIcon :icon="faEnvelope" class="mr-1" />
                        {{ row.email }}
                    </span>
                    <span v-if="!row.phone && !row.email">-</span>
                </div>
            </template>
            <template #is_active="{ row }">
                <span class="badge" :class="row.is_active ? 'badge-success' : 'badge-danger'">
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </template>
            <template #created_at="{ row }">
                {{ formatDateTime(row.created_at) }}
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1" @click.stop>
                    <button class="btn btn-highlight-main btn-sm" @click="openForm(row)">
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button class="btn btn-flat btn-sm text-danger" @click="confirmDelete(row)">
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="suppliers.links"
                :from="suppliers.from"
                :to="suppliers.to"
                :total="suppliers.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { faPencil, faTrash, faPhone, faEnvelope } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Form from './Components/Form.vue'
import SupplierFilter from './Components/SupplierFilter.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import { formatDateTime } from '@/Composable/time'

const modalStore = useModalStore()
const popUpStore = usePopUpStore()

defineProps({
    suppliers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nama', field: 'name', sortable: true },
    { label: 'Kontak', field: 'contact', slot: 'contact', sortable: false },
    { label: 'Alamat', field: 'address', sortable: false, show: 'md' },
    { label: 'Status', field: 'is_active', slot: 'is_active', sortable: false },
    {
        label: 'Dibuat',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
        show: 'lg',
    },
]

const openForm = (item = null) => {
    popUpStore.open({
        title: item ? 'Ubah Supplier' : 'Supplier Baru',
        size: 'lg',
        component: Form,
        props: { supplier: item },
    })
}

const confirmDelete = item => {
    modalStore.openModalDelete(route('inventory.suppliers.destroy', item.id))
}
</script>
