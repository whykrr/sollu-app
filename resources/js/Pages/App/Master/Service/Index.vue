<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Produk Layanan"
                description="Kelola data produk jasa & layanan, tarif, dan ketersediaan di outlet"
            />
        </template>

        <template #filter>
            <ServiceFilter
                :filters="activeFilters"
                :categories="categories"
                :view-mode="viewMode"
                @update:view-mode="setViewMode"
                @open-import="showImportModal = true"
                @create="openCreate"
            />
        </template>

        <!-- Table View -->
        <Table
            v-if="viewMode === 'table'"
            :headers="headers"
            :data="services.data"
            :sort="typeof activeFilters.sort === 'string' ? activeFilters.sort : 'created_at'"
            :sort-direction="
                typeof activeFilters.direction === 'string' ? activeFilters.direction : 'desc'
            "
            :action="true"
            @row-click="openEdit"
        >
            <template #image="{ row }">
                <img
                    v-if="row.cover_image_url"
                    :src="row.cover_image_url"
                    :alt="row.name"
                    class="w-10 h-10 object-cover rounded-lg border border-slate-200"
                />
                <div
                    v-else
                    class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 border border-slate-200"
                >
                    <FontAwesomeIcon :icon="faBellConcierge" class="text-sm" />
                </div>
            </template>
            <template #code="{ row }">
                {{ row.code || '-' }}
            </template>
            <template #category="{ row }">
                {{ row.category?.name || '-' }}
            </template>
            <template #base_price="{ row }">
                {{ getBasePrice(row) }}
            </template>
            <template #status="{ row }">
                <span v-if="row.is_show" class="badge badge-success">Aktif</span>
                <span v-else class="badge badge-neutral-500">Non-Aktif</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end">
                    <button
                        class="btn btn-flat btn-sm"
                        title="Ubah Layanan"
                        @click.stop="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm text-danger"
                        title="Hapus Layanan"
                        @click.stop="archiveService(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <!-- Grid View (Cards) -->
        <DataGrid v-else :data="services.data" @row-click="openEdit">
            <template #default="{ row }">
                <ServiceCard
                    :service="row"
                    :active-outlet-id="activeOutletId"
                    @click="openEdit(row)"
                    @edit="openEdit(row)"
                    @archive="archiveService"
                />
            </template>
        </DataGrid>

        <template #footer>
            <Pagination :meta="services.meta || services" />
        </template>

        <ImportCsvModal
            :show="showImportModal"
            module-name="Produk Layanan"
            :template-url="route('master.services.importTemplate')"
            :import-url="route('master.services.import')"
            @close="showImportModal = false"
        />
    </MainPage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import Table from '@/Components/Tables/Table.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil, faTrash, faBellConcierge } from '@fortawesome/free-solid-svg-icons'
import ServiceFilter from './Components/ServiceFilter.vue'
import ServiceCard from './Components/ServiceCard.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { usePopUpStore } from '@/store/popup'
import CreateEditWrapper from './CreateEditWrapper.vue'
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue'
import { useModalStore } from '@/store/notification.js'
import { useAuth } from '@/Composable/useAuth'

const VIEW_MODE_KEY = 'sollu_service_view_mode'

const getInitialViewMode = () => {
    if (typeof window === 'undefined') return 'table'
    const saved = localStorage.getItem(VIEW_MODE_KEY)
    if (saved === 'table' || saved === 'grid') return saved
    return window.innerWidth < 768 ? 'grid' : 'table'
}

const viewMode = ref(getInitialViewMode())

const setViewMode = mode => {
    viewMode.value = mode
    if (typeof window !== 'undefined') {
        localStorage.setItem(VIEW_MODE_KEY, mode)
    }
}

const handleResize = () => {
    if (!localStorage.getItem(VIEW_MODE_KEY)) {
        viewMode.value = window.innerWidth < 768 ? 'grid' : 'table'
    }
}

onMounted(() => {
    window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { selectedOutlet } = useAuth()

const props = defineProps({
    services: {
        type: Object,
        default: () => ({}),
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const activeFilters = computed(() => {
    if (props.params && typeof props.params === 'object' && !Array.isArray(props.params)) {
        return props.params
    }
    if (props.filters && typeof props.filters === 'object' && !Array.isArray(props.filters)) {
        return props.filters
    }
    return {}
})

const headers = [
    { label: 'Foto', field: 'image', slot: 'image', sortable: false },
    { label: 'Kode', field: 'code', slot: 'code', sortable: true },
    { label: 'Nama Layanan', field: 'name', sortable: true },
    { label: 'Kategori', field: 'category', slot: 'category', sortable: false },
    {
        label: 'Tarif Dasar',
        field: 'base_price',
        slot: 'base_price',
        sortable: false,
    },
    { label: 'Status', field: 'is_show', slot: 'status', sortable: false },
]

const showImportModal = ref(false)

const activeOutletId = computed(() => {
    return props.params?.outlet || selectedOutlet.value?.id || null
})

const getBasePrice = service => {
    if (!service.prices || service.prices.length === 0) return '-'

    let price = null
    if (activeOutletId.value) {
        price = service.prices.find(p => p.outlet_id === activeOutletId.value)
    }
    if (!price) {
        price = service.prices.find(p => !p.outlet_id)
    }
    if (!price) {
        price = service.prices[0]
    }

    return price
        ? new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0,
          }).format(price.amount)
        : '-'
}

const archiveService = id => {
    modalStore.confirm({
        title: 'Hapus Layanan Ini?',
        type: 'danger',
        message: 'Data layanan akan dipindahkan ke sampah dan dapat dipulihkan kapan saja.',
        confirmText: 'Ya, Pindahkan ke Sampah',
        cancelText: 'Batal',
        onConfirm: () => {
            router.delete(route('master.services.destroy', id))
        },
    })
}

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Layanan',
        size: 'lg',
        component: CreateEditWrapper,
        props: {
            editMode: false,
        },
    })
}

const openEdit = row => {
    popUpStore.open({
        title: `${row.name}`,
        subTitle: row.code ? `#${row.code}` : undefined,
        size: 'lg',
        component: CreateEditWrapper,
        props: {
            editMode: true,
            service: row,
        },
    })
}
</script>
