<template>
    <MainPage>
        <template #widgets>
            <SubscriptionPlanWidgets
                :plans-count="plans.length"
                :active-count="activeCount"
                :inactive-count="inactiveCount"
                :public-count="publicCount"
                :hidden-count="hiddenCount"
                :total-subscribers-count="totalSubscribersCount"
            />
        </template>

        <template #header>
            <MainPageHeader
                title="Pengaturan Paket Langganan"
                description="Kelola data paket, harga, visibilitas katalog, hak akses fitur, serta status aktif/nonaktif"
            >
                <button type="button" class="btn btn-main" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" class="mr-1.5" />
                    Tambah Paket
                </button>
            </MainPageHeader>

            <SubscriptionPlanFilter
                v-model:search="searchQuery"
                v-model:status="statusFilter"
                v-model:visibility="visibilityFilter"
                :plans-count="plans.length"
                :active-count="activeCount"
                :inactive-count="inactiveCount"
                :public-count="publicCount"
                :hidden-count="hiddenCount"
                :custom-count="customCount"
            />
        </template>
        <Table
            :headers="tableHeaders"
            :data="displayedPlans"
            :action="true"
            :sort="typeof params?.sort === 'string' ? params.sort : 'price_per_outlet'"
            :sort-direction="typeof params?.direction === 'string' ? params.direction : 'asc'"
        >
            <template #code_name="{ row }">
                <div class="py-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span
                            class="font-bold text-neutral-800 text-sm hover:text-main transition-colors"
                        >
                            {{ row.name }}
                        </span>
                        <span
                            v-if="row.business_id"
                            class="px-1.5 py-0.2 bg-purple-100 text-purple-700 text-[9px] rounded font-bold uppercase tracking-wider"
                        >
                            Custom
                        </span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                        <span
                            class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 font-semibold"
                        >
                            {{ row.code }}
                        </span>
                        <span
                            v-if="row.features && row.features.length"
                            class="text-[10px] text-neutral-400"
                        >
                            • {{ row.features.length }} poin brosur
                        </span>
                    </div>
                    <div v-if="row.business" class="mt-1">
                        <span
                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200"
                            :title="`Ditugaskan khusus untuk: ${row.business.name} (${row.business.owner_name || row.business.email})`"
                        >
                            <FontAwesomeIcon :icon="faBuilding" class="text-[9px]" />
                            {{ row.business.name }}
                        </span>
                    </div>
                </div>
            </template>

            <template #price="{ row }">
                <div>
                    <span class="font-bold text-main text-sm">
                        {{ formatIDR(row.price_per_outlet) }}
                    </span>
                    <span class="text-[11px] text-neutral-500 font-normal"> / bln</span>
                    <div v-if="row.yearly_discount_percent > 0" class="mt-0.5">
                        <span
                            class="text-[10px] font-bold text-success bg-success/10 px-1.5 py-0.5 rounded"
                        >
                            Diskon {{ row.yearly_discount_percent }}% Tahunan
                        </span>
                    </div>
                </div>
            </template>

            <template #features="{ row }">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-main/10 text-main hover:bg-main hover:text-white transition-colors cursor-pointer"
                    title="Klik untuk membuka drawer konfigurasi fitur sistem"
                    @click="openManageFeatures(row)"
                >
                    <FontAwesomeIcon :icon="faSliders" class="text-[10px]" />
                    <span>{{ row.features_count ?? row.system_features?.length ?? 0 }} Fitur</span>
                </button>
            </template>

            <template #subscribers="{ row }">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-700">
                    <FontAwesomeIcon :icon="faStore" class="text-neutral-400 text-[11px]" />
                    <span class="font-bold text-neutral-800">{{
                        row.subscriptions_count || 0
                    }}</span>
                    Bisnis
                </span>
            </template>

            <template #is_public="{ row }">
                <button
                    type="button"
                    class="px-2.5 py-0.5 text-[11px] rounded-full font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer"
                    :class="
                        row.is_public
                            ? 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100'
                            : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'
                    "
                    :title="
                        row.is_public
                            ? 'Paket tampil di katalog publik. Klik untuk sembunyikan.'
                            : 'Paket disembunyikan. Klik untuk tampilkan di katalog publik.'
                    "
                    @click="confirmToggleVisibility(row)"
                >
                    <FontAwesomeIcon
                        :icon="row.is_public ? faEye : faEyeSlash"
                        class="text-[9px]"
                    />
                    {{ row.is_public ? 'Publik' : 'Tersembunyi' }}
                </button>
            </template>

            <template #is_active="{ row }">
                <button
                    type="button"
                    class="px-2.5 py-0.5 text-[11px] rounded-full font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer"
                    :class="
                        row.is_active
                            ? 'bg-success/10 text-success hover:bg-success/20'
                            : 'bg-neutral-200 text-neutral-600 hover:bg-neutral-300'
                    "
                    :title="
                        row.is_active
                            ? 'Paket aktif. Klik untuk menonaktifkan.'
                            : 'Paket nonaktif. Klik untuk mengaktifkan.'
                    "
                    @click="confirmToggleStatus(row)"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full"
                        :class="row.is_active ? 'bg-success' : 'bg-neutral-400'"
                    ></span>
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                </button>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end">
                    <button
                        type="button"
                        class="btn btn-outline-main btn-xs"
                        title="Atur Hak Akses Fitur Sistem"
                        @click="openManageFeatures(row)"
                    >
                        <FontAwesomeIcon :icon="faSliders" class="text-[10px]" />
                        Fitur
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-warning btn-xs"
                        title="Edit Data Dasar Paket"
                        @click="openEdit(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPencil" class="text-[10px]" />
                        Edit
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-xs"
                        title="Hapus Paket"
                        @click="confirmDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                        Hapus
                    </button>
                </div>
            </template>
        </Table>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import SubscriptionPlanFilter from './Components/SubscriptionPlanFilter.vue'
import SubscriptionPlanWidgets from './Components/SubscriptionPlanWidgets.vue'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPencil,
    faPlus,
    faSliders,
    faEye,
    faEyeSlash,
    faTrash,
    faStore,
    faBuilding,
} from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import SubscriptionPlanFormPopUp from './Components/SubscriptionPlanFormPopUp.vue'
import SubscriptionPlanFeaturesPopUp from './Components/SubscriptionPlanFeaturesPopUp.vue'

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    params: {
        type: Object,
        default: () => ({}),
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

// Filters & Search State
const searchQuery = ref('')
const statusFilter = ref('all')
const visibilityFilter = ref('all')

const tableHeaders = [
    { field: 'name', label: 'Paket & Kode', slot: 'code_name', sortable: true },
    { field: 'price_per_outlet', label: 'Harga / Outlet', slot: 'price', sortable: true },
    { field: 'features_count', label: 'Fitur Sistem', slot: 'features', sortable: true },
    { field: 'subscriptions_count', label: 'Pelanggan Aktif', slot: 'subscribers', sortable: true },
    { field: 'is_public', label: 'Katalog', slot: 'is_public', sortable: true },
    { field: 'is_active', label: 'Status', slot: 'is_active', sortable: true },
]

// Metrics Overview
const activeCount = computed(() => (props.plans || []).filter(p => p.is_active).length)
const inactiveCount = computed(() => (props.plans || []).filter(p => !p.is_active).length)
const publicCount = computed(
    () => (props.plans || []).filter(p => p.is_public && !p.business_id).length
)
const hiddenCount = computed(
    () => (props.plans || []).filter(p => !p.is_public && !p.business_id).length
)
const customCount = computed(() => (props.plans || []).filter(p => Boolean(p.business_id)).length)
const totalSubscribersCount = computed(() => {
    return (props.plans || []).reduce((acc, p) => acc + (p.subscriptions_count || 0), 0)
})

// Filtered Plans
const displayedPlans = computed(() => {
    return (props.plans || []).filter(p => {
        // Search Filter
        if (searchQuery.value.trim()) {
            const query = searchQuery.value.trim().toLowerCase()
            const nameMatch = (p.name || '').toLowerCase().includes(query)
            const codeMatch = (p.code || '').toLowerCase().includes(query)
            const merchantMatch = (p.business?.name || '').toLowerCase().includes(query)
            if (!nameMatch && !codeMatch && !merchantMatch) {
                return false
            }
        }

        // Status Filter
        if (statusFilter.value === 'active' && !p.is_active) {
            return false
        }
        if (statusFilter.value === 'inactive' && p.is_active) {
            return false
        }

        // Visibility / Type Filter
        if (visibilityFilter.value === 'public' && (!p.is_public || p.business_id)) {
            return false
        }
        if (visibilityFilter.value === 'hidden' && (p.is_public || p.business_id)) {
            return false
        }
        if (visibilityFilter.value === 'custom' && !p.business_id) {
            return false
        }

        return true
    })
})

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Paket Langganan',
        size: 'lg',
        component: SubscriptionPlanFormPopUp,
        props: {
            planId: null,
        },
    })
}

const openEdit = planId => {
    popUpStore.open({
        title: 'Edit Data Paket Langganan',
        size: 'lg',
        component: SubscriptionPlanFormPopUp,
        props: {
            planId,
        },
    })
}

const openManageFeatures = plan => {
    popUpStore.open({
        title: `Atur Hak Akses Fitur: ${plan.name}`,
        size: '2xl',
        component: SubscriptionPlanFeaturesPopUp,
        props: {
            planId: plan.id,
        },
    })
}

const confirmToggleStatus = plan => {
    const isActivating = !plan.is_active
    modalStore.confirm({
        title: isActivating ? 'Aktifkan Paket Langganan' : 'Nonaktifkan Paket Langganan',
        message: isActivating
            ? `Apakah Anda yakin ingin mengaktifkan kembali paket ${plan.name}? Paket ini akan dapat dipilih kembali oleh merchant.`
            : `Apakah Anda yakin ingin menonaktifkan paket ${plan.name}? Paket ini tidak akan dapat dipilih untuk langganan baru oleh merchant.`,
        type: isActivating ? 'info' : 'danger',
        confirmText: isActivating ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        cancelText: 'Batal',
        confirmClass: isActivating
            ? 'btn-main'
            : 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.post(
                route('cockpit.subscription-plans.toggle-status', plan.id),
                {},
                {
                    preserveScroll: true,
                }
            )
        },
    })
}

const confirmToggleVisibility = plan => {
    const isShowing = !plan.is_public
    modalStore.confirm({
        title: isShowing ? 'Tampilkan di Katalog Publik' : 'Sembunyikan dari Katalog',
        message: isShowing
            ? `Apakah Anda yakin ingin menampilkan paket ${plan.name} di katalog paket publik? Merchant akan dapat melihat dan memilih paket ini di halaman langganan.`
            : `Apakah Anda yakin ingin menyembunyikan paket ${plan.name} dari katalog publik? Paket ini hanya dapat digunakan untuk pesanan privat/khusus dan tidak tampil di halaman upgrade merchant.`,
        type: 'info',
        confirmText: isShowing ? 'Ya, Tampilkan' : 'Ya, Sembunyikan',
        cancelText: 'Batal',
        confirmClass: isShowing ? 'btn-main' : 'btn-outline-main',
        onConfirm: () => {
            router.post(
                route('cockpit.subscription-plans.toggle-visibility', plan.id),
                {},
                {
                    preserveScroll: true,
                }
            )
        },
    })
}

const confirmDelete = plan => {
    modalStore.confirm({
        title: 'Hapus Paket Langganan',
        message: `Apakah Anda yakin ingin menghapus paket ${plan.name}? Tindakan ini tidak dapat dibatalkan. Catatan: Paket yang masih memiliki data langganan merchant tidak dapat dihapus.`,
        type: 'danger',
        confirmText: 'Ya, Hapus Paket',
        cancelText: 'Batal',
        confirmClass: 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.delete(route('cockpit.subscription-plans.destroy', plan.id), {
                preserveScroll: true,
            })
        },
    })
}
</script>
