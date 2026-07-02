<template>
    <Container>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex-1 border-r border-slate-200 pr-2">
                    <FilterSearch v-model="search" />
                </div>
                <div>
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="router.visit(route('master.products.create'))"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Produk
                    </button>
                </div>
            </div>
        </template>

        <Table :headers="headers" :data="products.data" :action="true">
            <template #code="{ row }">
                {{ row.code || '-' }}
            </template>
            <template #type="{ row }">
                <span class="capitalize">{{ row.product_type }}</span>
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
                <button 
                    class="btn btn-highlight-main btn-sm mr-1"
                    title="Ubah"
                    @click="router.visit(route('master.products.edit', row.id))"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button 
                    class="btn btn-outline-danger btn-sm"
                    title="Hapus"
                    @click="archiveProduct(row.id)"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination 
                :links="products.links"
                :from="products.from"
                :to="products.to"
                :total="products.total"
                :per-page="products.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Container from '@/Components/UI/Container.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons'
import { debounce } from 'lodash'

const props = defineProps({
    products: Object,
})

const headers = [
    { label: 'Kode', field: 'code', slot: 'code', sortable: true },
    { label: 'Nama Produk', field: 'name', sortable: true },
    { label: 'Tipe', field: 'product_type', slot: 'type', sortable: true },
    { label: 'Kategori', field: 'category', slot: 'category', sortable: false },
    { label: 'Harga Dasar', field: 'base_price', slot: 'base_price', sortable: false },
    { label: 'Status', field: 'is_show', slot: 'status', sortable: false },
]

const search = ref('')

watch(
    search,
    debounce((newVal) => {
        router.get(
            route('master.products.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true }
        )
    }, 500)
)

const getBasePrice = (product) => {
    const price = product.prices?.find(p => p.outlet_id === null && p.inventory_item_id === null)
    return price ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price.amount) : '-'
}

const archiveProduct = (id) => {
    if (confirm('Yakin ingin mengarsipkan produk ini?')) {
        router.delete(route('master.products.destroy', id))
    }
}
</script>
