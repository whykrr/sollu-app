<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Template Resep & BOM Produk"
                description="Kelola pustaka resep, komposisi bahan baku (BOM), dan kalkulasi estimasi HPP per varian produk."
            />
        </template>

        <template #filter>
            <ActionBar>
                <template #search>
                    <FilterSearch v-model="search" placeholder="Cari resep produk..." />
                </template>

                <template #create>
                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                        @click="openModal()"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                        <span>Tambah Resep</span>
                    </button>
                </template>
            </ActionBar>
        </template>

        <Table :headers="headers" :data="recipes.data" :action="true">
            <template #product="{ row }">
                <div class="font-medium text-slate-800">
                    {{ row.product?.name }}
                </div>
                <div v-if="row.product?.category?.name" class="text-[11px] text-slate-400">
                    {{ row.product.category.name }}
                </div>
            </template>

            <template #variant="{ row }">
                <span
                    v-if="row.product_item?.variant_combination"
                    class="font-medium text-xs text-slate-700"
                >
                    {{ row.product_item.variant_combination }}
                </span>
                <span v-else class="text-xs text-slate-400 italic"> Default (Semua) </span>
            </template>

            <template #version="{ row }">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200"
                >
                    v{{ row.version_number }}
                </span>
            </template>

            <template #items_count="{ row }">
                <span class="text-xs font-semibold text-slate-700">
                    {{ row.items?.length || 0 }} Bahan
                </span>
            </template>

            <template #estimated_hpp="{ row }">
                <span class="font-semibold text-xs text-slate-800">
                    {{ formatIDR(row.estimated_hpp) }}
                </span>
            </template>

            <template #status="{ row }">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium"
                    :class="
                        row.is_active
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-slate-100 text-slate-500'
                    "
                >
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button
                        class="btn btn-highlight-main btn-sm"
                        title="Ubah Resep (Versi Baru)"
                        @click="openModal(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-outline-danger btn-sm"
                        title="Nonaktifkan Resep"
                        @click="deleteRecipe(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="recipes.links"
                :from="recipes.from"
                :to="recipes.to"
                :total="recipes.total"
                :per-page="recipes.per_page ?? 15"
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
import { formatIDR } from '@/Composable/currency-format'
import Form from './Components/Form.vue'

const props = defineProps({
    recipes: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        default: () => [],
    },
    rawMaterials: {
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
    { label: 'Produk', field: 'product', slot: 'product', sortable: true },
    { label: 'Varian', field: 'variant', slot: 'variant', sortable: false },
    { label: 'Versi', field: 'version_number', slot: 'version', sortable: true },
    { label: 'Jumlah Bahan', field: 'items_count', slot: 'items_count', sortable: false },
    { label: 'Estimasi HPP', field: 'estimated_hpp', slot: 'estimated_hpp', sortable: false },
    { label: 'Status', field: 'is_active', slot: 'status', sortable: false },
]

const search = ref(props.filters?.search || '')

watch(
    search,
    debounce(newVal => {
        router.get(
            route('master.recipes.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true }
        )
    }, 500)
)

const openModal = (recipe = null) => {
    popUpStore.open({
        title: recipe ? 'Edit Template Resep' : 'Tambah Template Resep',
        subTitle: recipe
            ? 'Memperbarui resep akan membuat nomor versi baru dan menonaktifkan versi sebelumnya'
            : 'Tentukan formula resep dan bahan baku produk',
        size: 'lg',
        component: Form,
        props: {
            recipe,
            products: props.products,
            rawMaterials: props.rawMaterials,
        },
    })
}

const deleteRecipe = id => {
    modalStore.openModalDelete(route('master.recipes.destroy', id))
}
</script>
