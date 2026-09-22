<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Mutasi Stok"
                description="Kelola perpindahan stok barang antar cabang dan outlet"
            />
        </template>

        <template #filter>
            <Filter :filters="filters" @create="openForm()" />
        </template>

        <Table
            :headers="headers"
            :data="transfers.data"
            :sort="filters?.sort || 'created_at'"
            :sort-direction="filters?.direction || 'desc'"
            @row-click="openDetail"
        >
            <template #created_at="{ row }">
                {{
                    new Date(row.created_at).toLocaleString('id-ID', {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                    })
                }}
            </template>
            <template #from_outlet_name="{ row }">
                {{ row.from_outlet?.name || row.fromOutlet?.name || '-' }}
            </template>
            <template #to_outlet_name="{ row }">
                {{ row.to_outlet?.name || row.toOutlet?.name || '-' }}
            </template>
            <template #items_count="{ row }">
                {{ row.items_count ?? row.items?.length ?? 0 }} Item
            </template>
            <template #status="{ row }">
                <span class="badge" :class="getColor('StockTransferStatus', row.status)">
                    {{ getLabel('StockTransferStatus', row.status) }}
                </span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="transfers.links"
                :from="transfers.from"
                :to="transfers.to"
                :total="transfers.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import TransferForm from './Components/TransferForm.vue'
import TransferDetail from './Components/TransferDetail.vue'
import TransferReceiveForm from './Components/TransferReceiveForm.vue'
import { usePopUpStore } from '@/store/popup'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'

const popUpStore = usePopUpStore()
const { can: _can } = useAuth()
const { getLabel, getColor } = useEnum()

defineProps({
    transfers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nomor Transfer', field: 'transfer_number', sortable: true },
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    {
        label: 'Dari Outlet',
        field: 'from_outlet.name',
        slot: 'from_outlet_name',
        sortable: false,
    },
    {
        label: 'Ke Outlet',
        field: 'to_outlet.name',
        slot: 'to_outlet_name',
        sortable: false,
    },
    { label: 'Jumlah Item', field: 'items_count', slot: 'items_count', sortable: false },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
]

const openForm = () => {
    popUpStore.open({
        title: 'Buat Mutasi Stok',
        size: 'xl',
        component: TransferForm,
        events: {
            refresh: refreshData,
        },
    })
}

const openDetail = row => {
    popUpStore.open({
        title: 'Detail Mutasi Stok',
        size: 'xl',
        component: TransferDetail,
        props: { transferId: row.id },
        events: {
            refresh: refreshData,
            openReceive: data => openReceive(data),
        },
    })
}

const openReceive = data => {
    popUpStore.open({
        title: 'Terima Barang Mutasi',
        size: 'lg',
        component: TransferReceiveForm,
        props: { transferData: data },
        events: {
            refresh: refreshData,
        },
    })
}

const refreshData = () => {
    router.reload({ only: ['transfers'] })
}
</script>
