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
            @row-click="openDetail"
        >
            <template #outlet="{ item }">
                {{ item.outlet?.name || '-' }}
            </template>
            <template #created_at="{ item }">
                {{ formatDateTimeSimple(item.created_at) }}
            </template>
            <template #items_count="{ item }">
                <span>{{ item.items_count ?? 0 }} Item</span>
            </template>
            <template #status="{ item }">
                <span
                    class="badge"
                    :class="getColor('StockOpnameStatus', item.status) || 'badge-gray'"
                >
                    {{ getLabel('StockOpnameStatus', item.status) }}
                </span>
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-1.5">
                    <button
                        v-if="item.status === $enums.StockOpnameStatus.InProgress && can('inventory.opname.update')"
                        class="btn btn-highlight-main btn-sm"
                        title="Lanjutkan Opname"
                        @click.stop="openForm(item)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status === $enums.StockOpnameStatus.PendingApproval && can('inventory.opname.approve')"
                        class="btn btn-info btn-sm"
                        title="Review & Setujui"
                        @click.stop="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Review
                    </button>
                    <button
                        v-if="
                            (item.status === $enums.StockOpnameStatus.Approved ||
                            item.status === $enums.StockOpnameStatus.Rejected) &&
                            can('inventory.opname.read')
                        "
                        class="btn btn-flat btn-sm"
                        title="Lihat Detail"
                        @click.stop="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <button
                        v-if="item.status === $enums.StockOpnameStatus.InProgress && can('inventory.opname.delete')"
                        class="btn btn-flat btn-sm text-danger"
                        title="Batalkan Opname"
                        @click.stop="confirmDelete(item)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                    <button
                        v-if="item.status !== $enums.StockOpnameStatus.InProgress && can('inventory.opname.export')"
                        class="btn btn-flat btn-sm text-danger"
                        title="Ekspor PDF"
                        @click.stop="exportPdf(item.id)"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
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
import { faPencil, faTrash, faCheck, faEye, faFilePdf } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { usePage } from '@inertiajs/vue3'
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

const can = permission => {
    return (
        page.props.auth?.permissions?.includes(permission) ||
        page.props.auth?.permissions?.includes('inventory.*')
    )
}

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

const exportPdf = id => {
    window.open(route('inventory.opnames.export.pdf', id), '_blank')
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

const confirmDelete = item => {
    modalStore.openModalDelete(route('inventory.opnames.destroy', item.id))
}
</script>
