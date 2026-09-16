<template>
    <MainPage>
        <template #header>
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-4 rounded-xl shadow-xs border border-neutral-200/60 mb-4 gap-3"
            >
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">Manajemen Jenis Bisnis</h1>
                    <div class="text-sm text-neutral-500">
                        Kelola data klasifikasi bisnis, urutan tampilan pendaftaran, serta
                        personalisasi hak akses fitur bawaan
                    </div>
                </div>
                <div class="flex items-center gap-2 self-stretch sm:self-auto">
                    <button type="button" class="btn btn-main btn-sm" @click="openCreate">
                        <FontAwesomeIcon :icon="faPlus" class="mr-1.5" />
                        Tambah Jenis Bisnis
                    </button>
                </div>
            </div>
        </template>

        <!-- Mini KPI Overview Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div
                class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
            >
                <div
                    class="w-10 h-10 rounded-lg bg-main/10 text-main flex items-center justify-center text-base shrink-0"
                >
                    <FontAwesomeIcon :icon="faBriefcase" />
                </div>
                <div>
                    <div class="text-xs text-neutral-500 font-medium">Total Jenis Bisnis</div>
                    <div class="text-lg font-bold text-neutral-800 leading-tight">
                        {{ businessTypes.length }} Tipe
                    </div>
                </div>
            </div>
            <div
                class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs flex items-center gap-3"
            >
                <div
                    class="w-10 h-10 rounded-lg bg-success/10 text-success flex items-center justify-center text-base shrink-0"
                >
                    <FontAwesomeIcon :icon="faEye" />
                </div>
                <div>
                    <div class="text-xs text-neutral-500 font-medium">Tampil di Registrasi</div>
                    <div class="text-lg font-bold text-success leading-tight">
                        {{ visibleCount }} Aktif
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
                    class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-base shrink-0"
                >
                    <FontAwesomeIcon :icon="faSliders" />
                </div>
                <div>
                    <div class="text-xs text-neutral-500 font-medium">Rata-Rata Fitur</div>
                    <div class="text-lg font-bold text-sky-700 leading-tight">
                        {{ averageFeaturesCount }} Fitur / Tipe
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
                    <div class="text-xs text-neutral-500 font-medium">Total Merchant</div>
                    <div class="text-lg font-bold text-neutral-800 leading-tight">
                        {{ totalMerchantsCount }} Terdaftar
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar: Search, Filters, and Sorters -->
        <div
            class="bg-white p-3.5 rounded-xl border border-neutral-200/70 shadow-xs mb-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3"
        >
            <div class="flex flex-wrap items-center gap-2">
                <FilterSearch
                    v-model="searchQuery"
                    placeholder="Cari nama atau kode jenis bisnis..."
                    class="w-full sm:w-64"
                />

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
                        Semua Status
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                        :class="
                            visibilityFilter === 'visible'
                                ? 'bg-white shadow-xs text-success font-bold'
                                : 'text-neutral-500 hover:text-neutral-800'
                        "
                        @click="visibilityFilter = 'visible'"
                    >
                        Tampil ({{ visibleCount }})
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                        :class="
                            visibilityFilter === 'hidden'
                                ? 'bg-white shadow-xs text-neutral-600 font-bold'
                                : 'text-neutral-500 hover:text-neutral-800'
                        "
                        @click="visibilityFilter = 'hidden'"
                    >
                        Tersembunyi ({{ hiddenCount }})
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 justify-end">
                <div class="w-48">
                    <DropdownField v-model="sortBy" :options="sortOptions" />
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div
            v-if="displayedTypes.length"
            class="bg-white rounded-xl border border-neutral-200/80 shadow-xs overflow-hidden"
        >
            <Table :headers="tableHeaders" :data="displayedTypes" :action="true">
                <template #code_name="{ row }">
                    <div class="py-1">
                        <span
                            class="font-bold text-neutral-800 text-sm hover:text-main transition-colors"
                        >
                            {{ row.name }}
                        </span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span
                                class="text-[11px] font-mono uppercase tracking-wider text-neutral-400 font-semibold"
                            >
                                {{ row.code }}
                            </span>
                        </div>
                    </div>
                </template>

                <template #features="{ row }">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-main/10 text-main hover:bg-main hover:text-white transition-colors cursor-pointer"
                        title="Klik untuk membuka drawer konfigurasi fitur bawaan"
                        @click="openManageFeatures(row)"
                    >
                        <FontAwesomeIcon :icon="faSliders" class="text-[10px]" />
                        <span>{{ (row.features || []).length }} Fitur Bawaan</span>
                    </button>
                </template>

                <template #businesses="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-700"
                    >
                        <FontAwesomeIcon :icon="faStore" class="text-neutral-400 text-[11px]" />
                        <span class="font-bold text-neutral-800">{{
                            row.businesses_count || 0
                        }}</span>
                        Merchant
                    </span>
                </template>

                <template #sort_order="{ row }">
                    <span
                        class="text-xs font-semibold px-2 py-0.5 bg-neutral-100 rounded text-neutral-700"
                    >
                        #{{ row.sort_order }}
                    </span>
                </template>

                <template #is_visible="{ row }">
                    <button
                        type="button"
                        class="px-2.5 py-0.5 text-[11px] rounded-full font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer"
                        :class="
                            row.is_visible
                                ? 'bg-success/10 text-success hover:bg-success/20'
                                : 'bg-neutral-200 text-neutral-600 hover:bg-neutral-300'
                        "
                        :title="
                            row.is_visible
                                ? 'Tampil di form registrasi. Klik untuk sembunyikan.'
                                : 'Disembunyikan. Klik untuk tampilkan.'
                        "
                        @click="confirmToggleVisibility(row)"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full"
                            :class="row.is_visible ? 'bg-success' : 'bg-neutral-400'"
                        ></span>
                        {{ row.is_visible ? 'Tampil' : 'Tersembunyi' }}
                    </button>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-1 justify-end">
                        <button
                            type="button"
                            class="btn btn-outline-main btn-xs text-[11px] px-2 py-1"
                            title="Atur Hak Akses Fitur Bawaan"
                            @click="openManageFeatures(row)"
                        >
                            <FontAwesomeIcon :icon="faSliders" class="mr-1 text-[10px]" />
                            Fitur
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-main btn-xs text-[11px] px-2 py-1"
                            title="Edit Data Dasar Jenis Bisnis"
                            @click="openEdit(row.id)"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="mr-1 text-[10px]" />
                            Edit
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-xs text-[11px] px-2 py-1"
                            title="Hapus Jenis Bisnis"
                            @click="confirmDelete(row)"
                        >
                            <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                        </button>
                    </div>
                </template>
            </Table>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="text-center py-16 bg-white rounded-xl border border-neutral-200 shadow-xs"
        >
            <div
                class="w-12 h-12 mx-auto rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-3 text-lg"
            >
                <FontAwesomeIcon :icon="faBoxOpen" />
            </div>
            <h3 class="text-base font-bold text-neutral-700 mb-1">
                Tidak Ada Jenis Bisnis yang Sesuai
            </h3>
            <p class="text-xs text-neutral-400 max-w-sm mx-auto mb-4">
                Tidak ditemukan jenis bisnis dengan kriteria pencarian dan filter yang Anda pilih.
            </p>
            <button
                type="button"
                class="btn btn-outline-main btn-sm inline-flex items-center gap-1.5 cursor-pointer"
                @click="resetFilters"
            >
                <FontAwesomeIcon :icon="faRotateRight" class="text-xs" />
                Reset Filter
            </button>
        </div>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import Table from '@/Components/Tables/Table.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPencil,
    faPlus,
    faSliders,
    faEye,
    faTrash,
    faStore,
    faBriefcase,
    faRotateRight,
    faBoxOpen,
} from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import BusinessTypeFormPopUp from './Components/BusinessTypeFormPopUp.vue'
import BusinessTypeFeaturesPopUp from './Components/BusinessTypeFeaturesPopUp.vue'

const props = defineProps({
    businessTypes: {
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

const searchQuery = ref('')
const visibilityFilter = ref('all')
const sortBy = ref('order_asc')

const sortOptions = [
    { value: 'order_asc', label: 'Urutan: Terendah' },
    { value: 'name_asc', label: 'Nama: (A - Z)' },
    { value: 'merchants_desc', label: 'Merchant Terbanyak' },
    { value: 'features_desc', label: 'Fitur Terbanyak' },
]

const tableHeaders = [
    { field: 'name', label: 'Jenis Bisnis & Kode', slot: 'code_name' },
    { field: 'features_count', label: 'Fitur Bawaan', slot: 'features' },
    { field: 'businesses_count', label: 'Merchant Terdaftar', slot: 'businesses' },
    { field: 'sort_order', label: 'Urutan', slot: 'sort_order' },
    { field: 'is_visible', label: 'Visibilitas', slot: 'is_visible' },
]

// Metrics Overview
const visibleCount = computed(() => (props.businessTypes || []).filter(b => b.is_visible).length)
const hiddenCount = computed(() => (props.businessTypes || []).filter(b => !b.is_visible).length)
const totalMerchantsCount = computed(() => {
    return (props.businessTypes || []).reduce((acc, b) => acc + (b.businesses_count || 0), 0)
})
const averageFeaturesCount = computed(() => {
    if (!props.businessTypes.length) {
        return 0
    }
    const total = props.businessTypes.reduce(
        (acc, b) => acc + (b.features ? b.features.length : 0),
        0
    )
    return Math.round(total / props.businessTypes.length)
})

// Filtered & Sorted Types
const displayedTypes = computed(() => {
    const list = (props.businessTypes || []).filter(b => {
        if (searchQuery.value.trim()) {
            const query = searchQuery.value.trim().toLowerCase()
            const nameMatch = (b.name || '').toLowerCase().includes(query)
            const codeMatch = (b.code || '').toLowerCase().includes(query)
            if (!nameMatch && !codeMatch) {
                return false
            }
        }

        if (visibilityFilter.value === 'visible' && !b.is_visible) {
            return false
        }
        if (visibilityFilter.value === 'hidden' && b.is_visible) {
            return false
        }

        return true
    })

    return list.slice().sort((a, b) => {
        if (sortBy.value === 'order_asc') {
            return (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0)
        }
        if (sortBy.value === 'name_asc') {
            return (a.name || '').localeCompare(b.name || '')
        }
        if (sortBy.value === 'merchants_desc') {
            return (b.businesses_count || 0) - (a.businesses_count || 0)
        }
        if (sortBy.value === 'features_desc') {
            return (b.features?.length || 0) - (a.features?.length || 0)
        }
        return 0
    })
})

const resetFilters = () => {
    searchQuery.value = ''
    visibilityFilter.value = 'all'
    sortBy.value = 'order_asc'
}

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Jenis Bisnis',
        size: 'lg',
        component: BusinessTypeFormPopUp,
        props: {
            businessTypeId: null,
        },
    })
}

const openEdit = businessTypeId => {
    popUpStore.open({
        title: 'Edit Data Dasar Jenis Bisnis',
        size: 'lg',
        component: BusinessTypeFormPopUp,
        props: {
            businessTypeId,
        },
    })
}

const openManageFeatures = businessType => {
    popUpStore.open({
        title: `Personalisasi Fitur: ${businessType.name}`,
        size: '2xl',
        component: BusinessTypeFeaturesPopUp,
        props: {
            businessTypeId: businessType.id,
            allFeatures: props.allFeatures,
        },
    })
}

const confirmToggleVisibility = businessType => {
    const isShowing = !businessType.is_visible
    modalStore.confirm({
        title: isShowing ? 'Tampilkan Jenis Bisnis' : 'Sembunyikan Jenis Bisnis',
        message: isShowing
            ? `Apakah Anda yakin ingin menampilkan jenis bisnis "${businessType.name}" pada formulir pendaftaran merchant?`
            : `Apakah Anda yakin ingin menyembunyikan jenis bisnis "${businessType.name}" dari formulir pendaftaran merchant? Merchant baru tidak akan dapat memilih opsi ini.`,
        type: isShowing ? 'info' : 'danger',
        confirmText: isShowing ? 'Ya, Tampilkan' : 'Ya, Sembunyikan',
        cancelText: 'Batal',
        confirmClass: isShowing
            ? 'btn-main'
            : 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.post(
                route('cockpit.business-types.toggle-visibility', businessType.id),
                {},
                {
                    preserveScroll: true,
                }
            )
        },
    })
}

const confirmDelete = businessType => {
    modalStore.confirm({
        title: 'Hapus Jenis Bisnis',
        message: `Apakah Anda yakin ingin menghapus jenis bisnis "${businessType.name}"? Tindakan ini tidak dapat dibatalkan. Catatan: Jenis bisnis yang telah terhubung dengan merchant tidak dapat dihapus.`,
        type: 'danger',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        confirmClass: 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.delete(route('cockpit.business-types.destroy', businessType.id), {
                preserveScroll: true,
            })
        },
    })
}
</script>
