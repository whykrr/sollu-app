<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Setup Bundle / Paket</h3>
            <p class="text-sm text-neutral-500 mb-4">Tambahkan produk yang menjadi bagian dari paket ini. Harga total normal akan dihitung sebagai referensi Anda dalam menentukan harga bundle.</p>
            
            <!-- Search Produk -->
            <div class="relative max-w-md mb-4">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <FontAwesomeIcon :icon="faSearch" class="text-neutral-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="block w-full rounded-lg border-neutral-300 pl-10 focus:border-main focus:ring-main sm:text-sm"
                    placeholder="Cari nama produk komponen..."
                />
                
                <!-- Search Results Dropdown -->
                <div v-if="filteredProducts.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-neutral-200 rounded-md shadow-lg max-h-60 overflow-auto">
                    <ul class="py-1">
                        <li v-for="product in filteredProducts" :key="product.id" 
                            class="px-4 py-2 hover:bg-neutral-100 cursor-pointer flex justify-between items-center"
                            @click="addBundleItem(product)"
                        >
                            <span class="text-sm font-medium text-neutral-900">{{ product.name }}</span>
                            <span class="text-xs text-neutral-500">Rp {{ product.price.toLocaleString('id-ID') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Daftar Komponen Bundle -->
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="tableHeaders" :data="form.bundle_items" :action="true">
                    <template #name="{ row }">
                        <span class="text-sm text-neutral-900">{{ row.name }}</span>
                    </template>
                    <template #qty="{ row }">
                        <input 
                            type="number" 
                            v-model="row.qty" 
                            min="1" step="1"
                            class="block w-24 rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                        />
                    </template>
                    <template #normal_price="{ row }">
                        <span class="text-sm text-neutral-500">Rp {{ row.normal_price.toLocaleString('id-ID') }}</span>
                    </template>
                    <template #subtotal="{ row }">
                        <span class="text-sm font-medium text-neutral-900">Rp {{ (row.qty * row.normal_price).toLocaleString('id-ID') }}</span>
                    </template>
                    <template #actions="{ row }">
                        <button type="button" class="btn btn-outline-danger btn-sm" @click="removeBundleItem(row.product_id)">
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </template>
                </Table>
            </div>
            
            <div class="mt-4 flex justify-end">
                <div class="bg-neutral-50 p-3 rounded-lg border border-neutral-200 min-w-[200px]">
                    <div class="text-sm text-neutral-500 mb-1">Total Harga Normal</div>
                    <div class="text-lg font-bold text-neutral-900">Rp {{ totalNormalPrice.toLocaleString('id-ID') }}</div>
                    <div class="text-xs text-neutral-400 mt-1">Anda dapat mengatur harga khusus bundle pada langkah Pengaturan Harga.</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, ref, computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSearch, faTrash } from '@fortawesome/free-solid-svg-icons';
import Table from '@/Components/Tables/Table.vue';

const form = inject('productForm');

const searchQuery = ref('');

// Dummy products for now, in real app fetch from API
const existingProducts = [
    { id: 1, name: 'Burger Spesial', type: 'simple', price: 25000 },
    { id: 2, name: 'Kentang Goreng', type: 'simple', price: 15000 },
    { id: 3, name: 'Es Teh Manis', type: 'simple', price: 5000 },
];

const filteredProducts = computed(() => {
    if (!searchQuery.value) return [];
    const q = searchQuery.value.toLowerCase();
    const addedIds = form.bundle_items.map(i => i.product_id);
    return existingProducts.filter(p => p.name.toLowerCase().includes(q) && !addedIds.includes(p.id));
});

const addBundleItem = (product) => {
    form.bundle_items.push({
        id: Math.random().toString(),
        product_id: product.id,
        name: product.name,
        qty: 1,
        normal_price: product.price
    });
    searchQuery.value = '';
};

const removeBundleItem = (productId) => {
    const index = form.bundle_items.findIndex(i => i.product_id === productId);
    if (index !== -1) {
        form.bundle_items.splice(index, 1);
    }
};

const totalNormalPrice = computed(() => {
    return form.bundle_items.reduce((total, item) => total + (item.qty * item.normal_price), 0);
});

const tableHeaders = [
    { field: 'name', label: 'Nama Produk', slot: 'name' },
    { field: 'qty', label: 'Jumlah (Qty)', slot: 'qty' },
    { field: 'normal_price', label: 'Harga Normal/Pcs', slot: 'normal_price' },
    { field: 'subtotal', label: 'Subtotal Normal', slot: 'subtotal' },
];
</script>
