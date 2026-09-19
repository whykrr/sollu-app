<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Manajemen Jenis Bisnis"
                description="Kelola data klasifikasi bisnis, urutan tampilan pendaftaran, serta personalisasi hak akses fitur bawaan"
            />
        </template>

        <template #widgets>
            <BusinessTypeWidgets
                :total-types="businessTypes.length"
                :visible-count="visibleCount"
                :hidden-count="hiddenCount"
                :average-features-count="averageFeaturesCount"
                :total-merchants-count="totalMerchantsCount"
            />
        </template>

        <template #filter>
            <!-- Extracted Toolbar: Search, Filters, and Sorters -->
            <BusinessTypeFilter
                v-model:search="searchQuery"
                v-model:visibility="visibilityFilter"
                :visible-count="visibleCount"
                :hidden-count="hiddenCount"
                @create="openCreate"
            />
        </template>

        <!-- Table View (Empty state handled natively by Table component) -->
        <Table
            :headers="tableHeaders"
            :data="displayedTypes"
            :action="true"
            :sort="typeof params?.sort === 'string' ? params.sort : 'sort_order'"
            :sort-direction="typeof params?.direction === 'string' ? params.direction : 'asc'"
        >
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
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-neutral-700">
                    <FontAwesomeIcon :icon="faStore" class="text-neutral-400 text-[11px]" />
                    <span class="font-bold text-neutral-800">{{ row.businesses_count || 0 }}</span>
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
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil, faSliders, faTrash, faStore } from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import BusinessTypeWidgets from './Components/BusinessTypeWidgets.vue'
import BusinessTypeFilter from './Components/BusinessTypeFilter.vue'
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
    params: {
        type: Object,
        default: () => ({}),
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const searchQuery = ref('')
const visibilityFilter = ref('all')

const tableHeaders = [
    { field: 'name', label: 'Jenis Bisnis & Kode', slot: 'code_name', sortable: true },
    { field: 'features_count', label: 'Fitur Bawaan', slot: 'features' },
    { field: 'businesses_count', label: 'Merchant Terdaftar', slot: 'businesses', sortable: true },
    { field: 'sort_order', label: 'Urutan', slot: 'sort_order', sortable: true },
    { field: 'is_visible', label: 'Visibilitas', slot: 'is_visible', sortable: true },
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

// Filtered Types
const displayedTypes = computed(() => {
    return (props.businessTypes || []).filter(b => {
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
})

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
        confirmClass: isShowing ? 'btn-main' : 'btn-danger',
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
        confirmClass: 'btn-danger',
        onConfirm: () => {
            router.delete(route('cockpit.business-types.destroy', businessType.id), {
                preserveScroll: true,
            })
        },
    })
}
</script>
