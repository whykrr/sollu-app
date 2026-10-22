<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Data Bahan Baku"
                description="Kelola master data bahan baku mentah dan satuan untuk formula resep (BOM)."
            />
        </template>

        <template #filter>
            <ActionBar>
                <template #search>
                    <FilterSearch
                        v-model="search"
                        placeholder="Cari nama, SKU, atau barcode bahan baku..."
                    />
                </template>

                <template #create>
                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                        @click="openModal()"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                        <span>Tambah Bahan Baku</span>
                    </button>
                </template>
            </ActionBar>
        </template>

        <Table :headers="headers" :data="rawMaterials.data" :action="true">
            <template #name="{ row }">
                <div class="font-medium text-slate-800">
                    {{ row.name || row.product_item?.name }}
                </div>
            </template>

            <template #sku="{ row }">
                <span class="font-mono text-xs text-slate-600">
                    {{ row.sku || row.product_item?.sku || '-' }}
                </span>
            </template>

            <template #barcode="{ row }">
                <span class="font-mono text-xs text-slate-500">
                    {{ row.barcode || row.product_item?.barcode || '-' }}
                </span>
            </template>

            <template #uom="{ row }">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700"
                >
                    {{ row.uom?.name || row.unit || '-' }}
                </span>
            </template>

            <template #stock="{ row }">
                <div v-if="row.track_inventory" class="font-semibold text-slate-700">
                    {{ formatNumber(getTotalStock(row)) }}
                    <span class="text-[11px] font-normal text-slate-400 ml-0.5">
                        {{ row.uom?.name || row.unit || '' }}
                    </span>
                </div>
                <div v-else class="text-slate-400 italic text-xs">Tidak Dilacak</div>
            </template>

            <template #minimum_stock="{ row }">
                <span v-if="row.track_inventory" class="text-xs text-slate-600">
                    {{ formatNumber(row.minimum_stock ?? 0) }}
                </span>
                <span v-else class="text-slate-400">-</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button
                        class="btn btn-highlight-main btn-sm"
                        title="Ubah Bahan Baku"
                        @click="openModal(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-outline-danger btn-sm"
                        title="Hapus Bahan Baku"
                        @click="deleteRawMaterial(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="rawMaterials.links"
                :from="rawMaterials.from"
                :to="rawMaterials.to"
                :total="rawMaterials.total"
                :per-page="rawMaterials.per_page ?? 15"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import Form from './Components/Form.vue'

const props = defineProps({
    rawMaterials: {
        type: Object,
        required: true,
    },
    uoms: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const modalStore = useModalStore()
const popUpStore = usePopUpStore()

const headers = [
    { label: 'Nama Bahan Baku', field: 'name', slot: 'name', sortable: true },
    { label: 'SKU', field: 'sku', slot: 'sku', sortable: false },
    { label: 'Barcode', field: 'barcode', slot: 'barcode', sortable: false },
    { label: 'Satuan', field: 'uom', slot: 'uom', sortable: false },
    { label: 'Total Stok', field: 'stock', slot: 'stock', sortable: false },
    { label: 'Min. Stok', field: 'minimum_stock', slot: 'minimum_stock', sortable: false },
]

const search = ref(props.filters?.search || '')

watch(
    search,
    debounce(newVal => {
        router.get(
            route('master.raw-materials.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true }
        )
    }, 500)
)

const getTotalStock = row => {
    if (!row.balances || !Array.isArray(row.balances) || row.balances.length === 0) {
        return 0
    }
    return row.balances.reduce((acc, b) => {
        const val =
            parseFloat(b.stock_available !== undefined ? b.stock_available : b.stock_on_hand) || 0
        return acc + val
    }, 0)
}

const formatNumber = num => {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 3,
    }).format(num || 0)
}

const openModal = (rawMaterial = null) => {
    popUpStore.open({
        title: rawMaterial ? 'Edit Bahan Baku' : 'Tambah Bahan Baku',
        subTitle: rawMaterial
            ? 'Perbarui detail data master bahan baku'
            : 'Masukkan informasi bahan baku baru',
        size: 'md',
        component: Form,
        props: {
            rawMaterial,
            uoms: props.uoms,
        },
    })
}

const deleteRawMaterial = id => {
    modalStore.openModalDelete(route('master.raw-materials.destroy', id))
}
</script>
