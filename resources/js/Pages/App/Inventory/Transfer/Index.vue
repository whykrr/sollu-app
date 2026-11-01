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

        <!-- Quick Filter Tabs (Konteks Outlet Terpilih) -->
        <div
            v-if="currentOutletId"
            class="flex items-center gap-1 border-b border-slate-200 mb-3 px-1 text-xs"
        >
            <button
                type="button"
                class="px-3 py-2 font-medium border-b-2 transition-colors -mb-px cursor-pointer"
                :class="
                    !filters?.transfer_type || filters.transfer_type === 'all'
                        ? 'border-main text-main font-semibold'
                        : 'border-transparent text-slate-500 hover:text-slate-700'
                "
                @click="setTab('all')"
            >
                Semua
            </button>
            <button
                type="button"
                class="px-3 py-2 font-medium border-b-2 transition-colors -mb-px flex items-center gap-1.5 cursor-pointer"
                :class="
                    filters?.transfer_type === 'incoming'
                        ? 'border-emerald-600 text-emerald-700 font-semibold'
                        : 'border-transparent text-slate-500 hover:text-slate-700'
                "
                @click="setTab('incoming')"
            >
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                Mutasi Masuk
            </button>
            <button
                type="button"
                class="px-3 py-2 font-medium border-b-2 transition-colors -mb-px flex items-center gap-1.5 cursor-pointer"
                :class="
                    filters?.transfer_type === 'outgoing'
                        ? 'border-amber-600 text-amber-700 font-semibold'
                        : 'border-transparent text-slate-500 hover:text-slate-700'
                "
                @click="setTab('outgoing')"
            >
                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                Mutasi Keluar
            </button>
        </div>

        <Table
            :headers="headers"
            :data="transfers.data"
            :action="true"
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
            <template #direction="{ row }">
                <span
                    v-if="row.to_outlet_id === currentOutletId"
                    class="badge badge-success text-[11px] inline-flex items-center gap-1"
                >
                    <FontAwesomeIcon :icon="faArrowDown" class="text-[10px]" />
                    Mutasi Masuk
                </span>
                <span
                    v-else-if="row.from_outlet_id === currentOutletId"
                    class="badge badge-warning text-[11px] inline-flex items-center gap-1"
                >
                    <FontAwesomeIcon :icon="faArrowUp" class="text-[10px]" />
                    Mutasi Keluar
                </span>
                <span v-else class="badge badge-gray text-[11px]">-</span>
            </template>
            <template #counterparty="{ row }">
                <div v-if="row.to_outlet_id === currentOutletId" class="text-xs">
                    <span class="text-slate-400">Asal:</span>
                    <span class="font-medium text-slate-700 ml-1">{{
                        row.from_outlet?.name || row.fromOutlet?.name || '-'
                    }}</span>
                </div>
                <div v-else-if="row.from_outlet_id === currentOutletId" class="text-xs">
                    <span class="text-slate-400">Tujuan:</span>
                    <span class="font-medium text-slate-700 ml-1">{{
                        row.to_outlet?.name || row.toOutlet?.name || '-'
                    }}</span>
                </div>
                <div v-else class="text-xs text-slate-600">
                    {{ row.from_outlet?.name || '-' }} ➔ {{ row.to_outlet?.name || '-' }}
                </div>
            </template>
            <template #route="{ row }">
                <div class="text-xs text-slate-700 font-medium flex items-center gap-1.5">
                    <span>{{ row.from_outlet?.name || row.fromOutlet?.name || '-' }}</span>
                    <FontAwesomeIcon :icon="faArrowRight" class="text-[10px] text-slate-400" />
                    <span>{{ row.to_outlet?.name || row.toOutlet?.name || '-' }}</span>
                </div>
            </template>
            <template #items_count="{ row }">
                {{ row.items_count ?? row.items?.length ?? 0 }} Item
            </template>
            <template #transfer_mode="{ row }">
                <span
                    class="badge text-[11px]"
                    :class="row.transfer_mode === 'two_step' ? 'badge-purple' : 'badge-info'"
                >
                    {{ row.transfer_mode === 'two_step' ? '2-Langkah' : '1-Langkah' }}
                </span>
            </template>
            <template #status="{ row }">
                <span class="badge" :class="getColor('StockTransferStatus', row.status)">
                    {{ getLabel('StockTransferStatus', row.status) }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1.5 whitespace-nowrap" @click.stop>
                    <!-- Terima Barang Mutasi (in_transit & untuk outlet tujuan atau HQ) -->
                    <button
                        v-if="
                            row.status === 'in_transit' &&
                            (!currentOutletId || row.to_outlet_id === currentOutletId)
                        "
                        type="button"
                        class="btn btn-highlight-main btn-sm h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Terima Barang Mutasi Masuk"
                        @click="openReceive(row)"
                    >
                        <FontAwesomeIcon :icon="faBoxOpen" />
                        <span>Terima</span>
                    </button>

                    <!-- Cetak Bukti Mutasi PDF -->
                    <button
                        v-if="row.status !== 'void' && row.status !== 'rejected'"
                        type="button"
                        class="btn btn-flat btn-sm text-slate-600 hover:text-red-600 h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Cetak Bukti Transfer Mutasi"
                        @click="exportPdf(row.id)"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                        <span>Cetak</span>
                    </button>
                </div>
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
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowDown,
    faArrowUp,
    faArrowRight,
    faBoxOpen,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons'
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

const page = usePage()
const popUpStore = usePopUpStore()
const { can: _can } = useAuth()
const { getLabel, getColor } = useEnum()

const props = defineProps({
    transfers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const currentOutletId = computed(() => {
    return (
        props.filters?.outlet_id ||
        page.props.selectedOutlet?.id ||
        page.props.auth?.selected_outlet?.id ||
        null
    )
})

const setTab = type => {
    router.get(
        route('inventory.transfers.index'),
        {
            ...props.filters,
            transfer_type: type === 'all' ? null : type,
            page: 1,
        },
        { preserveState: true, preserveScroll: true }
    )
}

const headers = computed(() => {
    if (currentOutletId.value) {
        return [
            { label: 'Nomor Transfer', field: 'transfer_number', sortable: true },
            {
                label: 'Tanggal',
                field: 'created_at',
                slot: 'created_at',
                sortable: true,
            },
            {
                label: 'Arah',
                field: 'direction',
                slot: 'direction',
                sortable: false,
            },
            {
                label: 'Pihak Lawan',
                field: 'counterparty',
                slot: 'counterparty',
                sortable: false,
            },
            { label: 'Jumlah Item', field: 'items_count', slot: 'items_count', sortable: false },
            { label: 'Mode', field: 'transfer_mode', slot: 'transfer_mode', sortable: true },
            { label: 'Status', field: 'status', slot: 'status', sortable: true },
        ]
    }

    return [
        { label: 'Nomor Transfer', field: 'transfer_number', sortable: true },
        {
            label: 'Tanggal',
            field: 'created_at',
            slot: 'created_at',
            sortable: true,
        },
        {
            label: 'Rute',
            field: 'route',
            slot: 'route',
            sortable: false,
        },
        { label: 'Jumlah Item', field: 'items_count', slot: 'items_count', sortable: false },
        { label: 'Mode', field: 'transfer_mode', slot: 'transfer_mode', sortable: true },
        { label: 'Status', field: 'status', slot: 'status', sortable: true },
    ]
})

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
        size: 'xl',
        component: TransferReceiveForm,
        props: { transferData: data },
        events: {
            refresh: refreshData,
        },
    })
}

const exportPdf = id => {
    window.open(route('inventory.transfers.export.pdf', id), '_blank')
}

const refreshData = () => {
    router.reload({ only: ['transfers'] })
}
</script>
