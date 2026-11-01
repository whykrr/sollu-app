<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Stok Opname"
                description="Kelola sesi penghitungan fisik persediaan barang dan sesuaikan saldo stok di outlet tokomu"
            />
        </template>

        <template #filter>
            <Filter :filters="filters" @create="openForm()" @freeze="openFreezeModal()" />
        </template>

        <Table
            :headers="headers"
            :data="opnames.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
            @row-click="handleRowClick"
        >
            <template #outlet="{ row }">
                {{ row.outlet?.name || '-' }}
            </template>
            <template #created_at="{ row }">
                {{ formatDateTimeSimple(row.created_at) }}
            </template>
            <template #items_count="{ row }">
                <span>{{ row.items_count ?? 0 }} Item</span>
            </template>
            <template #status="{ row }">
                <span
                    class="badge"
                    :class="getColor('StockOpnameStatus', row.status) || 'badge-gray'"
                >
                    {{ getLabel('StockOpnameStatus', row.status) }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1.5 whitespace-nowrap" @click.stop>
                    <!-- Lanjutkan Hitung Fisik (InProgress) -->
                    <button
                        v-if="
                            row.status === $enums.StockOpnameStatus.InProgress &&
                            can('inventory.opname.update')
                        "
                        type="button"
                        class="btn btn-highlight-main btn-sm h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Lanjutkan Penginputan Opname"
                        @click="openForm(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                        <span>Hitung</span>
                    </button>

                    <!-- Review & Setujui (PendingApproval) -->
                    <button
                        v-if="
                            row.status === $enums.StockOpnameStatus.PendingApproval &&
                            (can('inventory.opname.validate') || can('inventory.opname.approve'))
                        "
                        type="button"
                        class="btn btn-info btn-sm h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Review Hasil Opname"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faCheck" />
                        <span>Review</span>
                    </button>

                    <!-- Cetak Berita Acara PDF (Approved / Rejected) -->
                    <button
                        v-if="
                            row.status !== $enums.StockOpnameStatus.InProgress &&
                            (can('inventory.opname.view') || can('inventory.opname.export'))
                        "
                        type="button"
                        class="btn btn-flat btn-sm text-slate-600 hover:text-red-600 h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Cetak Berita Acara Opname"
                        @click="exportPdf(row.id)"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                        <span>Cetak</span>
                    </button>

                    <!-- Batalkan Sesi Opname (InProgress) -->
                    <button
                        v-if="
                            row.status === $enums.StockOpnameStatus.InProgress &&
                            can('inventory.opname.delete')
                        "
                        type="button"
                        class="btn btn-flat btn-sm text-danger h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Batalkan Sesi Opname"
                        @click="confirmDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                        <span>Batalkan</span>
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="opnames.links"
                :from="opnames.from"
                :to="opnames.to"
                :total="opnames.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { faPencil, faTrash, faCheck, faFilePdf } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import axios from 'axios'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import OpnameFormPopUp from './Components/OpnameFormPopUp.vue'
import OpnameDetailPopUp from './Components/OpnameDetailPopUp.vue'
import FreezeStockPopUp from '@/Components/Inventory/FreezeStockPopUp.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import { useEnum } from '@/Composable/useEnum'
import { formatDateTimeSimple } from '@/Composable/date.js'

const page = usePage()
const modalStore = useModalStore()
const popUpStore = usePopUpStore()
const { getLabel, getColor } = useEnum()

const can = permission => {
    return (
        page.props.auth?.permissions?.includes(permission) ||
        page.props.auth?.permissions?.includes('inventory.*') ||
        page.props.auth?.permissions?.includes('business.*') ||
        Boolean(page.props.auth?.user?.is_root_user)
    )
}

const exportPdf = id => {
    window.open(route('inventory.opnames.export.pdf', id), '_blank')
}

const confirmDelete = item => {
    modalStore.openModalDelete(route('inventory.opnames.destroy', item.id))
}

defineProps({
    opnames: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nomor Opname', field: 'opname_number', sortable: true },
    {
        label: 'Tanggal Mulai',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Outlet', slot: 'outlet', sortable: false },
    { label: 'Jumlah Item', slot: 'items_count', sortable: false },
    { label: 'Catatan', field: 'notes', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
]

const isLoading = ref(false)

const openFreezeModal = () => {
    popUpStore.open({
        title: 'Kelola Pembekuan Stok',
        description:
            'Pembekuan stok akan memblokir seluruh transaksi persediaan pada outlet yang dipilih selama proses Stock Opname berlangsung.',
        size: 'lg',
        component: FreezeStockPopUp,
    })
}

const handleRowClick = row => {
    if (row.status === 'in_progress') {
        openForm(row)
    } else {
        openDetail(row)
    }
}

const openForm = async (item = null) => {
    if (item?.id) {
        try {
            isLoading.value = true
            const response = await axios.get(route('inventory.opnames.show', item.id))
            popUpStore.open({
                title: 'Lanjutkan Stok Opname',
                size: 'xl',
                component: OpnameFormPopUp,
                props: { opname: response.data },
            })
        } catch (error) {
            console.error('Failed to load detail', error)
        } finally {
            isLoading.value = false
        }
    } else {
        popUpStore.open({
            title: 'Mulai Stok Opname Baru',
            size: 'xl',
            component: OpnameFormPopUp,
            props: { opname: null },
        })
    }
}

const openDetail = async item => {
    if (!item?.id) return
    try {
        isLoading.value = true
        const response = await axios.get(route('inventory.opnames.show', item.id))
        popUpStore.open({
            title: 'Detail Stok Opname',
            size: 'xl',
            component: OpnameDetailPopUp,
            props: { opname: response.data },
        })
    } catch (error) {
        console.error('Failed to load detail', error)
    } finally {
        isLoading.value = false
    }
}
</script>
