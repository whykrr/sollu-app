<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Penyesuaian Stok"
                description="Kelola koreksi selisih, barang rusak, atau barang hilang untuk menjaga akurasi inventaris tokomu"
            />
        </template>

        <template #filter>
            <Filter :filters="filters" @create="openForm()" @freeze="openFreezeModal()" />
        </template>

        <Table
            :headers="headers"
            :data="adjustments.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
            @row-click="openDetail"
        >
            <template #status="{ row }">
                <span
                    class="badge"
                    :class="getColor('AdjustmentStatus', row.status) || 'badge-gray'"
                >
                    {{ getLabel('AdjustmentStatus', row.status) }}
                </span>
            </template>
            <template #outlet="{ row }">
                {{ row.outlet?.name || '-' }}
            </template>
            <template #reason="{ row }">
                <span>
                    {{ getLabel('AdjustmentReason', row.reason) }}
                </span>
            </template>
            <template #items_count="{ row }">
                <span>{{ row.items_count }} Item</span>
            </template>
            <template #created_at="{ row }">
                <span>{{ formatDateTimeSimple(row.created_at) }}</span>
            </template>
            <template #creator="{ row }">
                {{ row.creator?.name || '-' }}
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1.5 whitespace-nowrap" @click.stop>
                    <!-- Review Supervisi (Pending) -->
                    <button
                        v-if="
                            row.status === 'pending' &&
                            (can('inventory.adjustment.approve') || can('inventory.adjust.view'))
                        "
                        type="button"
                        class="btn btn-info btn-sm h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Review & Otorisasi Penyesuaian"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faCheck" />
                        <span>Review</span>
                    </button>

                    <!-- Cetak Berita Acara PDF -->
                    <button
                        v-if="can('inventory.adjustment.export') || can('inventory.adjust.view')"
                        type="button"
                        class="btn btn-flat btn-sm text-slate-600 hover:text-red-600 h-[30px] px-2.5 inline-flex items-center gap-1.5 cursor-pointer"
                        title="Cetak Berita Acara Penyesuaian"
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
                :links="adjustments.links"
                :from="adjustments.from"
                :to="adjustments.to"
                :total="adjustments.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { faFilePdf, faCheck } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import axios from 'axios'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import AdjustmentFormPopUp from './Components/AdjustmentFormPopUp.vue'
import AdjustmentDetailPopUp from './Components/AdjustmentDetailPopUp.vue'
import FreezeStockPopUp from '@/Components/Inventory/FreezeStockPopUp.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { useEnum } from '@/Composable/useEnum'
import { usePopUpStore } from '@/store/popup'

const page = usePage()
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
    window.open(route('inventory.adjustments.export.pdf', id), '_blank')
}

defineProps({
    adjustments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nomor', field: 'adjustment_number', sortable: true },
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Outlet', slot: 'outlet', sortable: false },
    { label: 'Alasan', field: 'reason', slot: 'reason', sortable: true },
    {
        label: 'Item',
        field: 'items_count',
        slot: 'items_count',
        sortable: false,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
    { label: 'Dibuat Oleh', slot: 'creator', sortable: false },
]

const isLoadingDetail = ref(false)

const openForm = () => {
    popUpStore.open({
        title: 'Buat Draf Penyesuaian Stok',
        size: 'xl',
        component: AdjustmentFormPopUp,
    })
}

const openDetail = async item => {
    if (!item?.id) return
    isLoadingDetail.value = true
    try {
        const response = await axios.get(route('inventory.adjustments.show', item.id))
        popUpStore.open({
            title: 'Detail Penyesuaian Stok',
            size: 'lg',
            component: AdjustmentDetailPopUp,
            props: { adjustment: response.data },
        })
    } catch (error) {
        console.error('Failed to load detail:', error)
    } finally {
        isLoadingDetail.value = false
    }
}

const openFreezeModal = () => {
    popUpStore.open({
        title: 'Kelola Pembekuan Stok',
        size: 'lg',
        component: FreezeStockPopUp,
    })
}
</script>
