<template>
    <MainPage>
        <template #header>
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-4 rounded-xl shadow-xs border border-neutral-200/60 gap-3"
            >
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">Pengaturan Paket Langganan</h1>
                    <div class="text-sm text-neutral-500">
                        Kelola data paket, harga, visibilitas katalog, hak akses fitur, serta status
                        aktif/nonaktif
                    </div>
                </div>
                <div class="flex items-center gap-2 self-stretch sm:self-auto">
                    <button type="button" class="btn btn-main btn-sm" @click="openCreate">
                        <FontAwesomeIcon :icon="faPlus" class="mr-1.5" />
                        Tambah Paket
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <div
                    class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-lg bg-main/10 text-main flex items-center justify-center text-base shrink-0"
                    >
                        <FontAwesomeIcon :icon="faLayerGroup" />
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500 font-medium">Total Paket</div>
                        <div class="text-lg font-bold text-neutral-800 leading-tight">
                            {{ plans.length }} Varian
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-lg bg-success/10 text-success flex items-center justify-center text-base shrink-0"
                    >
                        <FontAwesomeIcon :icon="faCheckCircle" />
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500 font-medium">Paket Aktif</div>
                        <div class="text-lg font-bold text-success leading-tight">
                            {{ activeCount }} Aktif
                            <span class="text-xs text-neutral-400 font-normal"
                                >({{ inactiveCount }} nonaktif)</span
                            >
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-base shrink-0"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500 font-medium">Katalog Publik</div>
                        <div class="text-lg font-bold text-sky-700 leading-tight">
                            {{ publicCount }} Tampil
                            <span class="text-xs text-neutral-400 font-normal"
                                >({{ hiddenCount }} hidden)</span
                            >
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-base shrink-0"
                    >
                        <FontAwesomeIcon :icon="faStore" />
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500 font-medium">Pelanggan Aktif</div>
                        <div class="text-lg font-bold text-neutral-800 leading-tight">
                            {{ totalSubscribersCount }} Merchant
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search, Filters, Sorters, and View Switcher -->
            <div
                class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs mb-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3"
            >
                <!-- Left Controls: Search & Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Search Input -->
                    <FilterSearch
                        v-model="searchQuery"
                        placeholder="Cari nama atau kode paket..."
                        class="w-full sm:w-60"
                    />

                    <!-- Status Filter Segmented -->
                    <div
                        class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium"
                    >
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                statusFilter === 'all'
                                    ? 'bg-white shadow-xs text-neutral-800 font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="statusFilter = 'all'"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                statusFilter === 'active'
                                    ? 'bg-white shadow-xs text-success font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="statusFilter = 'active'"
                        >
                            Aktif ({{ activeCount }})
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                statusFilter === 'inactive'
                                    ? 'bg-white shadow-xs text-danger font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="statusFilter = 'inactive'"
                        >
                            Nonaktif ({{ inactiveCount }})
                        </button>
                    </div>

                    <!-- Visibility Filter Segmented -->
                    <div
                        class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium"
                    >
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                visibilityFilter === 'all'
                                    ? 'bg-white shadow-xs text-neutral-800 font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="visibilityFilter = 'all'"
                        >
                            Semua Katalog
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                visibilityFilter === 'public'
                                    ? 'bg-white shadow-xs text-sky-700 font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="visibilityFilter = 'public'"
                        >
                            Publik ({{ publicCount }})
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                            :class="
                                visibilityFilter === 'hidden'
                                    ? 'bg-white shadow-xs text-amber-700 font-bold'
                                    : 'text-neutral-500 hover:text-neutral-800'
                            "
                            @click="visibilityFilter = 'hidden'"
                        >
                            Tersembunyi ({{ hiddenCount }})
                        </button>
                    </div>
                </div>

                <!-- Right Controls: Sorter -->
                <div class="flex items-center gap-2 justify-end">
                    <div class="w-44">
                        <DropdownField v-model="sortBy" :options="sortOptions" />
                    </div>
                </div>
            </div>
        </template>

        <!-- Mini KPI Overview Metrics -->

        <!-- Table View -->
        <Table :headers="tableHeaders" :data="displayedPlans" :action="true">
            <template #code_name="{ row }">
                <div class="py-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span
                            class="font-bold text-neutral-800 text-sm hover:text-main transition-colors"
                        >
                            {{ row.name }}
                        </span>
                        <span
                            v-if="row.is_custom"
                            class="px-1.5 py-0.2 bg-purple-100 text-purple-700 text-[9px] rounded font-bold uppercase tracking-wider"
                        >
                            Custom
                        </span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
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

            <template #max_outlet="{ row }">
                <span class="text-xs font-semibold text-neutral-700">
                    {{ row.max_outlet ? `${row.max_outlet} Outlet` : 'Tanpa Batas' }}
                </span>
            </template>

            <template #features="{ row }">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-main/10 text-main hover:bg-main hover:text-white transition-colors cursor-pointer"
                    title="Klik untuk membuka drawer konfigurasi fitur sistem"
                    @click="openManageFeatures(row)"
                >
                    <FontAwesomeIcon :icon="faSliders" class="text-[10px]" />
                    <span>{{ row.system_features?.length || 0 }} Fitur</span>
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
                        class="btn btn-outline-main btn-xs text-[11px] px-2 py-1"
                        title="Atur Hak Akses Fitur Sistem"
                        @click="openManageFeatures(row)"
                    >
                        <FontAwesomeIcon :icon="faSliders" class="mr-1 text-[10px]" />
                        Fitur
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-main btn-xs text-[11px] px-2 py-1"
                        title="Edit Data Dasar Paket"
                        @click="openEdit(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPencil" class="mr-1 text-[10px]" />
                        Edit
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-xs text-[11px] px-2 py-1"
                        title="Hapus Paket"
                        @click="confirmDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                    </button>
                </div>
            </template>
        </Table>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import Table from '@/Components/Tables/Table.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCheckCircle,
    faPencil,
    faPlus,
    faSliders,
    faEye,
    faEyeSlash,
    faTrash,
    faStore,
    faLayerGroup,
    faRotateRight,
    faBoxOpen,
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
    allFeatures: {
        type: Array,
        default: () => [],
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

// Filters & Search State
const searchQuery = ref('')
const statusFilter = ref('all')
const visibilityFilter = ref('all')
const sortBy = ref('price_asc')

const sortOptions = [
    { value: 'price_asc', label: 'Harga: Termurah' },
    { value: 'price_desc', label: 'Harga: Termahal' },
    { value: 'name_asc', label: 'Nama Paket (A - Z)' },
    { value: 'subscribers_desc', label: 'Pelanggan Terbanyak' },
    { value: 'features_desc', label: 'Fitur Terbanyak' },
]

const tableHeaders = [
    { field: 'name', label: 'Paket & Kode', slot: 'code_name' },
    { field: 'price_per_outlet', label: 'Harga / Outlet', slot: 'price' },
    { field: 'max_outlet', label: 'Batas Outlet', slot: 'max_outlet' },
    { field: 'features_count', label: 'Fitur Sistem', slot: 'features' },
    { field: 'subscriptions_count', label: 'Pelanggan Aktif', slot: 'subscribers' },
    { field: 'is_public', label: 'Katalog', slot: 'is_public' },
    { field: 'is_active', label: 'Status', slot: 'is_active' },
]

// Metrics Overview
const activeCount = computed(() => (props.plans || []).filter(p => p.is_active).length)
const inactiveCount = computed(() => (props.plans || []).filter(p => !p.is_active).length)
const publicCount = computed(() => (props.plans || []).filter(p => p.is_public).length)
const hiddenCount = computed(() => (props.plans || []).filter(p => !p.is_public).length)
const totalSubscribersCount = computed(() => {
    return (props.plans || []).reduce((acc, p) => acc + (p.subscriptions_count || 0), 0)
})

// Filtered & Sorted Plans
const displayedPlans = computed(() => {
    const list = (props.plans || []).filter(p => {
        // Search Filter
        if (searchQuery.value.trim()) {
            const query = searchQuery.value.trim().toLowerCase()
            const nameMatch = (p.name || '').toLowerCase().includes(query)
            const codeMatch = (p.code || '').toLowerCase().includes(query)
            if (!nameMatch && !codeMatch) {
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

        // Visibility Filter
        if (visibilityFilter.value === 'public' && !p.is_public) {
            return false
        }
        if (visibilityFilter.value === 'hidden' && p.is_public) {
            return false
        }

        return true
    })

    // Sort
    return list.slice().sort((a, b) => {
        if (sortBy.value === 'price_asc') {
            return Number(a.price_per_outlet) - Number(b.price_per_outlet)
        }
        if (sortBy.value === 'price_desc') {
            return Number(b.price_per_outlet) - Number(a.price_per_outlet)
        }
        if (sortBy.value === 'name_asc') {
            return (a.name || '').localeCompare(b.name || '')
        }
        if (sortBy.value === 'subscribers_desc') {
            return (b.subscriptions_count || 0) - (a.subscriptions_count || 0)
        }
        if (sortBy.value === 'features_desc') {
            return (b.system_features?.length || 0) - (a.system_features?.length || 0)
        }
        return 0
    })
})

const resetFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    visibilityFilter.value = 'all'
    sortBy.value = 'price_asc'
}

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
            allFeatures: props.allFeatures,
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
