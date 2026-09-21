<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Pembelian"
                description="Kelola pesanan pembelian barang ke pemasok dan pantau status penerimaan stok tokomu"
            />
        </template>

        <template #filter>
            <PurchaseFilter
                :params="params || filters"
                :suppliers="suppliers"
                :outlets="outlets"
                @create="openForm('po')"
                @create-direct="openForm('direct')"
                @export-csv="handleExportCsv"
            />
        </template>

        <Table
            :headers="headers"
            :data="purchases.data"
            :action="true"
            :sort="params?.sort ?? 'created_at'"
            :sort-direction="params?.direction ?? 'desc'"
            @row-click="openDetail"
        >
            <template #po_number="{ row }">
                <div class="font-bold text-xs text-slate-800">{{ row.po_number }}</div>
                <div v-if="row.reference_number" class="text-[11px] text-slate-400">
                    Ref: {{ row.reference_number }}
                </div>
            </template>
            <template #order_date="{ row }">
                {{ formatDateID(row.order_date || row.created_at) }}
            </template>
            <template #supplier="{ row }">
                <span class="font-medium text-slate-800">{{ row.supplier?.name || '-' }}</span>
            </template>
            <template #outlet="{ row }">
                <span class="text-slate-600">{{ row.outlet?.name || '-' }}</span>
            </template>
            <template #status="{ row }">
                <span
                    class="badge"
                    :class="$enums.PurchaseOrderStatus._meta[row.status]?.color || 'badge-gray'"
                >
                    {{ $enums.PurchaseOrderStatus._meta[row.status]?.label || row.status }}
                </span>
            </template>
            <template #total_amount="{ row }">
                <span class="font-semibold text-slate-800">
                    {{ formatCurrency(row.total_amount) }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1" @click.stop>
                    <!-- Order PO (Draf -> Ordered) -->
                    <button
                        v-if="row.status === $enums.PurchaseOrderStatus.Draft"
                        class="btn btn-highlight-success btn-sm h-[30px]"
                        title="Tandai sebagai Dipesan"
                        @click="confirmOrder(row)"
                    >
                        <FontAwesomeIcon :icon="faCheck" />
                        <span class="hidden sm:inline">Order</span>
                    </button>

                    <!-- Terima Barang (Ordered / PartialReceived) -->
                    <button
                        v-if="
                            row.status === $enums.PurchaseOrderStatus.Ordered ||
                            row.status === $enums.PurchaseOrderStatus.PartialReceived
                        "
                        class="btn btn-highlight-main btn-sm h-[30px]"
                        title="Terima Barang Masuk"
                        @click="openReceive(row)"
                    >
                        <FontAwesomeIcon :icon="faBoxOpen" />
                        <span class="hidden sm:inline">Terima</span>
                    </button>

                    <!-- Retur Barang (PartialReceived / Received) -->
                    <button
                        v-if="
                            row.status === $enums.PurchaseOrderStatus.PartialReceived ||
                            row.status === $enums.PurchaseOrderStatus.Received
                        "
                        class="btn btn-outline-danger btn-sm h-[30px]"
                        title="Retur Barang ke Pemasok"
                        @click="openReturn(row)"
                    >
                        <FontAwesomeIcon :icon="faRotateLeft" />
                        <span class="hidden sm:inline">Retur</span>
                    </button>

                    <!-- Batalkan PO (Ordered) -->
                    <button
                        v-if="row.status === $enums.PurchaseOrderStatus.Ordered"
                        class="btn btn-flat btn-sm text-danger h-[30px] w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                        title="Batalkan Pesanan"
                        @click="confirmCancel(row)"
                    >
                        <FontAwesomeIcon :icon="faBan" />
                    </button>

                    <!-- Unduh PDF PO -->
                    <a
                        :href="route('inventory.purchases.pdf', row.id)"
                        target="_blank"
                        class="btn btn-flat btn-sm text-slate-500 hover:text-red-600 h-[30px] w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                        title="Unduh Berkas PDF PO"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                    </a>

                    <!-- Edit Draf PO -->
                    <button
                        v-if="row.status === $enums.PurchaseOrderStatus.Draft"
                        class="btn btn-flat btn-sm text-slate-600 h-[30px] w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                        title="Edit Draf"
                        @click="openForm('po', row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>

                    <!-- Hapus Draf PO -->
                    <button
                        v-if="row.status === $enums.PurchaseOrderStatus.Draft"
                        class="btn btn-flat btn-sm text-danger h-[30px] w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                        title="Hapus Draf"
                        @click="confirmDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination :meta="purchases.meta || purchases" />
        </template>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import {
    faPencil,
    faTrash,
    faBoxOpen,
    faCheck,
    faBan,
    faFilePdf,
    faRotateLeft,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useModalStore } from '@/store/notification'
import { useToastStore } from '@/store/toast'
import { usePopUpStore } from '@/store/popup'
import { formatDateID } from '@/Composable/date.js'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Form from './Components/Form.vue'
import Receive from './Components/Receive.vue'
import Detail from './Components/Detail.vue'
import ReturnFormPopUp from './Components/ReturnFormPopUp.vue'
import PurchaseFilter from './Components/PurchaseFilter.vue'

const modalStore = useModalStore()
const toastStore = useToastStore()
const popUpStore = usePopUpStore()

const props = defineProps({
    purchases: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    uoms: {
        type: Array,
        default: () => [],
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nomor PO', field: 'po_number', slot: 'po_number', sortable: true },
    {
        label: 'Tanggal',
        field: 'order_date',
        slot: 'order_date',
        sortable: true,
    },
    { label: 'Supplier / Pemasok', slot: 'supplier', sortable: false },
    { label: 'Outlet Tujuan', slot: 'outlet', sortable: false },
    {
        label: 'Total Pembelian',
        field: 'total_amount',
        slot: 'total_amount',
        sortable: true,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
]

const formatCurrency = value => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const isLoadingData = ref(false)

const fetchPurchaseDetails = async id => {
    isLoadingData.value = true
    try {
        const response = await axios.get(route('inventory.purchases.show', id))
        return response.data
    } catch (error) {
        console.error(error)
        modalStore.addNotification({
            type: 'error',
            title: 'Gagal',
            message: 'Gagal mengambil detail pembelian.',
        })
        return null
    } finally {
        isLoadingData.value = false
    }
}

const openForm = async (mode = 'po', item = null) => {
    let data = null
    if (item) {
        data = await fetchPurchaseDetails(item.id)
        if (!data) return
    }
    popUpStore.open({
        title: item
            ? 'Edit Pembelian'
            : mode === 'direct'
            ? 'Pembelian Langsung (Direct Purchase)'
            : 'Pesanan Pembelian Baru (PO)',
        subTitle: item ? '#' + item.po_number : undefined,
        size: 'xl',
        component: Form,
        props: {
            purchase: data,
            suppliers: props.suppliers,
            uoms: props.uoms,
            initialMode: mode,
        },
    })
}

const openReceive = async item => {
    const data = await fetchPurchaseDetails(item.id)
    if (!data) return
    popUpStore.open({
        title: 'Penerimaan Barang',
        subTitle: '#' + data.po_number,
        size: 'xl',
        component: Receive,
        props: { purchase: data },
    })
}

const openReturn = async item => {
    const data = await fetchPurchaseDetails(item.id)
    if (!data) return
    popUpStore.open({
        title: 'Retur Pembelian ke Pemasok',
        subTitle: '#' + data.po_number,
        size: 'xl',
        component: ReturnFormPopUp,
        props: { purchase: data },
    })
}

const openDetail = async item => {
    const data = await fetchPurchaseDetails(item.id)
    if (!data) return
    popUpStore.open({
        title: 'Detail Pembelian',
        subTitle: '#' + data.po_number,
        size: 'xl',
        component: Detail,
        props: {
            purchase: data,
        },
        listeners: {
            'open-receive': po => openReceive(po),
            'open-return': po => openReturn(po),
        },
    })
}

const confirmOrder = item => {
    modalStore.confirm({
        title: 'Proses Pesanan',
        message: `Yakin ingin memproses PO ${item.po_number} menjadi pesanan aktif?`,
        type: 'info',
        confirmText: 'Ya, Proses Pesanan',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.order', item.id),
                {},
                { preserveScroll: true, preserveState: true }
            )
        },
    })
}

const confirmCancel = item => {
    modalStore.confirm({
        title: 'Batalkan Pembelian',
        message: `Yakin ingin membatalkan PO ${item.po_number}? Pesanan yang dibatalkan tidak dapat diproses lagi.`,
        type: 'warning',
        confirmText: 'Ya, Batalkan',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.cancel', item.id),
                {},
                { preserveScroll: true, preserveState: true }
            )
        },
    })
}

const confirmDelete = item => {
    modalStore.openModalDelete(route('inventory.purchases.destroy', item.id))
}

const handleExportCsv = filterData => {
    toastStore.showToast({
        title: 'Ekspor Sedang Diproses',
        message: 'Permintaan ekspor data pembelian telah dikirim. Berkas akan segera diunduh.',
        type: 'info',
    })
    window.location.href = route('inventory.purchases.export-csv', filterData)
}
</script>
