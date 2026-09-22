<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Daftar Promo & Diskon"
                description="Kelola diskon per produk atau per total transaksi (bill) untuk tokomu"
            />
        </template>

        <template #filter>
            <PromoFilter :filters="filters" @create="openCreate" />
        </template>

        <Table
            :headers="headers"
            :data="promos.data"
            :sort="filters?.sort"
            :sort-direction="filters?.direction"
            :action="true"
            @row-click="openDetail"
        >
            <template #target_type="{ row }">
                <span class="badge badge-neutral">
                    {{ row.target_type === $enums?.PromoTarget?.Product || row.target_type === 'product' ? 'Per Produk' : 'Per Bill' }}
                </span>
            </template>
            <template #promo_value="{ row }">
                <div v-if="row.promo_type === $enums?.PromoType?.Percentage || row.promo_type === 'percentage'">
                    <span class="font-bold text-slate-800">{{ row.discount_value }}%</span>
                    <span v-if="row.max_discount" class="text-[11px] text-slate-500 block">
                        (Maks. {{ formatIDR(row.max_discount) }})
                    </span>
                </div>
                <div v-else>
                    <span class="font-bold text-slate-800">{{ formatIDR(row.discount_value) }}</span>
                </div>
            </template>
            <template #period="{ row }">
                <div class="text-xs font-medium text-slate-800">
                    {{ formatDateID(row.start_date) }} -
                    {{ formatDateID(row.end_date) }}
                </div>
                <div v-if="row.start_time && row.end_time" class="text-[11px] text-slate-500">
                    {{ formatTime(row.start_time) }} -
                    {{ formatTime(row.end_time) }} WIB
                </div>
            </template>
            <template #status="{ row }">
                <span :class="getStatusBadge(row)">
                    {{ getStatusLabel(row) }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1.5 justify-end" @click.stop>
                    <button
                        class="btn btn-flat btn-sm"
                        title="Lihat Detail"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <button
                        v-if="row.status === 'draft' || row.status === $enums?.PromoStatus?.Draft"
                        class="btn btn-flat btn-sm"
                        title="Ubah Promo"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="row.status === 'draft' || row.status === 'inactive' || row.status === $enums?.PromoStatus?.Draft || row.status === $enums?.PromoStatus?.Inactive"
                        class="btn btn-flat btn-sm text-emerald-600 hover:text-emerald-700"
                        title="Publikasikan"
                        @click="publishPromo(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPlay" />
                    </button>
                    <button
                        v-if="row.status === 'active' || row.status === $enums?.PromoStatus?.Active"
                        class="btn btn-flat btn-sm text-amber-600 hover:text-amber-700"
                        title="Nonaktifkan"
                        @click="unpublishPromo(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPause" />
                    </button>
                    <button
                        v-if="row.status === 'draft' || row.status === $enums?.PromoStatus?.Draft"
                        class="btn btn-flat btn-sm text-danger hover:text-rose-700"
                        title="Hapus"
                        @click="deletePromo(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="promos.links"
                :from="promos.from"
                :to="promos.to"
                :total="promos.total"
                :per-page="promos.per_page ?? 20"
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEye, faPencil, faPlay, faPause, faTrash } from '@fortawesome/free-solid-svg-icons'
import PromoFilter from './Components/PromoFilter.vue'
import PromoForm from './Components/PromoForm.vue'
import PromoDetail from './Components/PromoDetail.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import { formatIDR } from '@/Composable/currency-format'
import { formatDateID } from '@/Composable/date'

const popUpStore = usePopUpStore()
const modal = useModalStore()

const props = defineProps({
    promos: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nama Promo', field: 'name', sortable: true },
    {
        label: 'Target',
        field: 'target_type',
        slot: 'target_type',
        sortable: false,
    },
    {
        label: 'Tipe & Nilai',
        field: 'discount_value',
        slot: 'promo_value',
        sortable: true,
    },
    { label: 'Periode', field: 'start_date', slot: 'period', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
]

const formatTime = timeString => {
    if (!timeString) return ''
    return timeString.substring(0, 5)
}

const isExpired = promo => {
    if (!promo.end_date) return false
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const endDate = new Date(promo.end_date)
    endDate.setHours(0, 0, 0, 0)
    return endDate < today
}

const getComputedStatus = promo => {
    if (promo.status === 'active' && isExpired(promo)) {
        return 'expired'
    }
    return promo.status
}

const getStatusBadge = promo => {
    const status = getComputedStatus(promo)
    switch (status) {
        case 'active':
            return 'badge badge-success'
        case 'inactive':
            return 'badge badge-warning'
        case 'expired':
            return 'badge badge-danger'
        case 'draft':
        default:
            return 'badge badge-neutral'
    }
}

const getStatusLabel = promo => {
    const status = getComputedStatus(promo)
    switch (status) {
        case 'active':
            return 'Aktif'
        case 'inactive':
            return 'Nonaktif'
        case 'expired':
            return 'Kedaluwarsa'
        case 'draft':
            return 'Draf'
        default:
            return status
    }
}

const openCreate = () => {
    popUpStore.open({
        title: 'Buat Promo Baru',
        component: PromoForm,
        size: 'lg',
        props: {
            promo: null,
        },
    })
}

const openEdit = promo => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromoForm,
        size: 'lg',
        props: {
            promo,
        },
    })
}

const openDetail = promo => {
    popUpStore.open({
        title: 'Detail Promo',
        component: PromoDetail,
        size: 'lg',
        props: {
            promo,
            computedStatus: getComputedStatus(promo),
            isExpired: isExpired(promo),
        },
    })
}

const publishPromo = id => {
    modal.open({
        title: 'Publikasikan Promo Ini?',
        message:
            'Promo akan langsung aktif dan mulai berlaku di kasir sesuai jadwal periode yang telah ditentukan.',
        confirmButtonText: 'Ya, Publikasikan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.publish', id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                }
            )
        },
    })
}

const unpublishPromo = id => {
    modal.open({
        title: 'Nonaktifkan Promo Ini?',
        type: 'warning',
        message:
            'Promo tidak akan lagi memotong tagihan transaksi kasir hingga diaktifkan kembali.',
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.unpublish', id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                }
            )
        },
    })
}

const deletePromo = id => {
    modal.open({
        title: 'Hapus Promo Draf Ini?',
        type: 'danger',
        message:
            'Promo draf ini akan dihapus secara permanen.',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('promotions.destroy', id), {
                preserveScroll: true,
                preserveState: true,
            })
        },
    })
}
</script>
