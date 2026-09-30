<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Promo & Diskon"
                description="Kelola strategi promosi, kupon voucher, diskon bertingkat, dan jadwal happy hour tokomu"
            />
        </template>

        <template #filter>
            <PromotionFilter :filters="filters" @create="openCreate" />
        </template>

        <Table
            :headers="headers"
            :data="promotionsList.data"
            :sort="filters?.sort"
            :sort-direction="filters?.direction"
            :action="true"
            @row-click="openDetail"
        >
            <!-- Nama & Kode Promo -->
            <template #promo_name="{ row }">
                <div class="space-y-0.5">
                    <span class="font-semibold text-slate-900 block">{{ row.name }}</span>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span
                            v-if="row.application_mode === 'manual' && row.promo_code"
                            class="badge badge-info text-xs"
                        >
                            KODE: {{ row.promo_code }}
                        </span>
                        <span v-else class="badge badge-success text-xs"> Otomatis </span>
                    </div>
                </div>
            </template>

            <!-- Target & Syarat -->
            <template #target_scope="{ row }">
                <div class="space-y-0.5">
                    <span class="badge badge-main text-xs">
                        {{ getTargetScopeLabel(row.target_scope || row.target_type) }}
                    </span>
                    <div class="text-[11px] text-slate-500 space-y-0.5">
                        <span v-if="row.min_subtotal" class="block">
                            Min. {{ formatIDR(row.min_subtotal) }}
                        </span>
                        <span v-if="row.min_quantity" class="block">
                            Min. {{ row.min_quantity }} pcs
                        </span>
                    </div>
                </div>
            </template>

            <!-- Benefit Diskon -->
            <template #benefit="{ row }">
                <div v-if="isPercentage(row)">
                    <span class="font-bold text-slate-900 text-sm">{{ row.discount_value }}%</span>
                    <span
                        v-if="row.max_discount_amount || row.max_discount"
                        class="text-[11px] text-slate-500 block"
                    >
                        (Maks. {{ formatIDR(row.max_discount_amount || row.max_discount) }})
                    </span>
                </div>
                <div v-else>
                    <span class="font-bold text-slate-900 text-sm">
                        {{ formatIDR(row.discount_value) }}
                    </span>
                </div>
            </template>

            <!-- Periode & Jam -->
            <template #period="{ row }">
                <div class="text-xs font-medium text-slate-800">
                    {{ formatDateID(row.start_date) }} - {{ formatDateID(row.end_date) }}
                </div>
                <div v-if="row.start_time && row.end_time" class="text-[11px] text-slate-500">
                    {{ formatTime(row.start_time) }} - {{ formatTime(row.end_time) }} WIB
                </div>
                <div
                    v-if="
                        row.days_of_week &&
                        row.days_of_week.length > 0 &&
                        row.days_of_week.length < 7
                    "
                    class="text-[10px] text-main font-medium"
                >
                    {{ formatDaysSummary(row.days_of_week) }}
                </div>
            </template>

            <!-- Status Badge -->
            <template #status="{ row }">
                <span :class="getStatusBadgeClass(row)">
                    {{ getStatusLabel(row) }}
                </span>
            </template>

            <!-- Actions Baris -->
            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end" @click.stop>
                    <button
                        v-can="'promo.view'"
                        class="btn btn-flat btn-sm"
                        title="Lihat Detail"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>

                    <button
                        v-if="
                            getComputedStatus(row) === 'draft' ||
                            getComputedStatus(row) === 'inactive' ||
                            row.status === 'draft' ||
                            row.status === 'inactive'
                        "
                        v-can="'promo.update'"
                        class="btn btn-flat btn-sm"
                        title="Ubah Promo"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>

                    <button
                        v-if="
                            getComputedStatus(row) === 'draft' ||
                            getComputedStatus(row) === 'inactive' ||
                            row.status === 'draft' ||
                            row.status === 'inactive'
                        "
                        v-can="'promo.publish'"
                        class="btn btn-flat btn-sm text-emerald-600 hover:text-emerald-700"
                        title="Publikasikan"
                        @click="publishPromo(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPlay" />
                    </button>

                    <button
                        v-if="getComputedStatus(row) === 'active' && !isExpired(row)"
                        v-can="'promo.publish'"
                        class="btn btn-flat btn-sm text-amber-600 hover:text-amber-700"
                        title="Nonaktifkan"
                        @click="unpublishPromo(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPause" />
                    </button>

                    <button
                        v-if="
                            row.status === 'draft' || row.status === $enums?.PromotionStatus?.Draft
                        "
                        v-can="'promo.delete'"
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
                :links="promotionsList.links"
                :from="promotionsList.from"
                :to="promotionsList.to"
                :total="promotionsList.total"
                :per-page="promotionsList.per_page ?? 20"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEye, faPencil, faPlay, faPause, faTrash } from '@fortawesome/free-solid-svg-icons'
import PromotionFilter from './Components/PromotionFilter.vue'
import PromotionForm from './Components/PromotionForm.vue'
import PromotionDetail from './Components/PromotionDetail.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import { useEnum } from '@/Composable/useEnum'
import { formatIDR } from '@/Composable/currency-format'
import { formatDateID } from '@/Composable/date'

const popUpStore = usePopUpStore()
const modal = useModalStore()
const { getLabel } = useEnum()

const props = defineProps({
    promotions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    promos: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const promotionsList = computed(() => {
    if (props.promotions?.data) return props.promotions
    if (props.promos?.data) return props.promos
    return { data: [], links: [] }
})

const headers = [
    { label: 'Nama & Kode Promo', field: 'name', slot: 'promo_name', sortable: true },
    {
        label: 'Target & Syarat',
        field: 'target_scope',
        slot: 'target_scope',
        sortable: true,
        show: 'sm',
    },
    {
        label: 'Benefit Diskon',
        field: 'discount_value',
        slot: 'benefit',
        sortable: true,
    },
    { label: 'Periode & Jam', field: 'start_date', slot: 'period', sortable: true, show: 'md' },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
]

const formatTime = timeString => {
    if (!timeString) return ''
    return timeString.substring(0, 5)
}

const isPercentage = promo => {
    const type = promo.discount_type || promo.promo_type
    return type === 'percentage'
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

const getStatusBadgeClass = promo => {
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
    return getLabel('PromotionStatus', status) || status
}

const getTargetScopeLabel = scope => {
    return (
        getLabel('PromotionTargetScope', scope) || (scope === 'bill' ? 'Seluruh Transaksi' : scope)
    )
}

const formatDaysSummary = days => {
    const dayNames = { 1: 'Sen', 2: 'Sel', 3: 'Rab', 4: 'Kam', 5: 'Jum', 6: 'Sab', 7: 'Min' }
    return days.map(d => dayNames[d] || d).join(', ')
}

const openCreate = () => {
    popUpStore.open({
        title: 'Buat Promo Baru',
        component: PromotionForm,
        size: 'lg',
        props: {
            promotion: null,
            outlets: props.outlets || [],
        },
    })
}

const openEdit = promo => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromotionForm,
        size: 'lg',
        props: {
            promotion: promo,
            outlets: props.outlets || [],
        },
    })
}

const openDetail = promo => {
    popUpStore.open({
        title: 'Detail Promo',
        component: PromotionDetail,
        size: 'lg',
        props: {
            promotion: promo,
            computedStatus: getComputedStatus(promo),
            outlets: props.outlets || [],
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
        message: 'Promo draf ini akan dihapus secara permanen.',
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
