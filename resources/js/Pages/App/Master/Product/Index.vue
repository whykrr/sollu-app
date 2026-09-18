<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Data Produk"
                description="Kelola katalog produk, harga, varian, dan ketersediaan di outlet"
            >
                <button class="btn btn-main btn-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Produk
                </button>
            </MainPageHeader>
        </template>

        <template #filter>
            <ProductFilter
                :filters="activeFilters"
                :categories="categories"
                :view-mode="viewMode"
                @update:view-mode="setViewMode"
                @open-import="showImportModal = true"
            />
        </template>

        <!-- Table View -->
        <Table
            v-if="viewMode === 'table'"
            :headers="headers"
            :data="products.data"
            :sort="activeFilters?.sort ?? 'created_at'"
            :sort-direction="activeFilters?.direction ?? 'desc'"
            :action="true"
            @row-click="openEdit"
        >
            <template #image="{ row }">
                <img
                    v-if="row.cover_image_url"
                    :src="row.cover_image_url"
                    class="w-10 h-10 object-cover rounded-lg border border-slate-200"
                    alt="Product thumbnail"
                />
                <div
                    v-else
                    class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 border border-slate-200"
                >
                    <FontAwesomeIcon :icon="faImage" class="text-sm" />
                </div>
            </template>
            <template #code="{ row }">
                {{ row.code || '-' }}
            </template>
            <template #type="{ row }">
                <span v-if="row.product_type === 'service'" class="badge badge-info">Layanan</span>
                <span v-else-if="row.product_type === 'bundle'" class="badge badge-warning"
                    >Bundle</span
                >
                <span v-else class="badge badge-neutral-400">Barang</span>
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
                        title="Ubah Produk"
                        @click.stop="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm text-danger"
                        title="Hapus"
                        @click.stop="archiveProduct(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <!-- Grid View (Cards) -->
        <DataGrid v-else :data="products.data" @row-click="openEdit">
            <template #default="{ row }">
                <ProductCard
                    :product="row"
                    :active-outlet-id="activeOutletId"
                    @click="openEdit(row)"
                    @edit="openEdit(row)"
                    @archive="archiveProduct"
                />
            </template>
        </DataGrid>

        <template #footer>
            <Pagination :meta="products.meta || products" />
        </template>

        <ImportCsvModal
            :show="showImportModal"
            module-name="Produk"
            :template-url="route('master.products.importTemplate')"
            :import-url="route('master.products.import')"
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
import { faPlus, faPencil, faTrash, faImage } from '@fortawesome/free-solid-svg-icons'
import ProductFilter from './Components/ProductFilter.vue'
import ProductCard from './Components/ProductCard.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { usePopUpStore } from '@/store/popup'
import CreateEditWrapper from './CreateEditWrapper.vue'
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue'
import { useModalStore } from '@/store/notification.js'
import { useAuth } from '@/Composable/useAuth'

const VIEW_MODE_KEY = 'sollu_product_view_mode'

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
    products: {
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

const activeFilters = computed(() => props.params || props.filters || {})

const headers = [
    { label: 'Foto', field: 'image', slot: 'image', sortable: false },
    { label: 'Kode', field: 'code', slot: 'code', sortable: true },
    { label: 'Nama Produk', field: 'name', sortable: true },
    { label: 'Tipe', field: 'product_type', slot: 'type', sortable: true },
    { label: 'Kategori', field: 'category', slot: 'category', sortable: false },
    {
        label: 'Harga Dasar',
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

const getBasePrice = product => {
    if (!product.prices || product.prices.length === 0) return '-'

    let price = null
    if (activeOutletId.value) {
        price = product.prices.find(p => p.outlet_id === activeOutletId.value)
    }
    if (!price) {
        price = product.prices.find(p => !p.outlet_id)
    }
    if (!price) {
        price = product.prices[0]
    }

    return price
        ? new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
          }).format(price.amount)
        : '-'
}

const archiveProduct = id => {
    modalStore.confirm({
        title: 'Konfirmasi Pengarsipan',
        type: 'danger',
        message: 'Apakah Anda yakin ingin mengarsipkan produk ini?',
        confirmText: 'Ya, Arsipkan',
        cancelText: 'Batal',
        onConfirm: () => {
            router.delete(route('master.products.destroy', id))
        },
    })
}

// Wizard configurations for Popup
const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Produk',
        size: 'xl',
        component: CreateEditWrapper,
        props: {
            initialStep: 0,
            editMode: false,
            targetStepId: 'basic',
        },
    })
}

const openEdit = (row, targetStepId = 'basic') => {
    const stepIndexMap = { basic: 0, inventory: 1, pricing: 2 }
    popUpStore.open({
        title: `${row.name}`,
        subTitle: row.code ? `#${row.code}` : undefined,
        size: 'xl',
        component: CreateEditWrapper,
        props: {
            initialStep: stepIndexMap[targetStepId] ?? 0,
            editMode: true,
            targetStepId,
            product: row,
        },
    })
}
</script>
