<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Setup Resep (BOM)</h3>
            <p class="text-sm text-neutral-500 mb-4">Tambahkan bahan baku yang digunakan untuk membuat produk ini. Stok bahan baku akan otomatis terpotong saat produk terjual.</p>
            
            <!-- Search Bahan Baku -->
            <div class="relative max-w-md mb-4">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <FontAwesomeIcon :icon="faSearch" class="text-neutral-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="block w-full rounded-lg border-neutral-300 pl-10 focus:border-main focus:ring-main sm:text-sm"
                    placeholder="Cari nama bahan baku..."
                />
                
                <!-- Search Results Dropdown -->
                <div v-if="filteredMaterials.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-neutral-200 rounded-md shadow-lg max-h-60 overflow-auto">
                    <ul class="py-1">
                        <li v-for="material in filteredMaterials" :key="material.id" 
                            class="px-4 py-2 hover:bg-neutral-100 cursor-pointer flex justify-between items-center"
                            @click="addIngredient(material)"
                        >
                            <span class="text-sm font-medium text-neutral-900">{{ material.name }}</span>
                            <span class="text-xs text-neutral-500">{{ material.uom }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Daftar Resep -->
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="tableHeaders" :data="form.ingredients" :action="true">
                    <template #name="{ row }">
                        <span class="text-sm text-neutral-900">{{ row.name }}</span>
                    </template>
                    <template #qty="{ row }">
                        <input 
                            type="number" 
                            v-model="row.qty" 
                            min="0.01" step="any"
                            class="block w-24 rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                        />
                    </template>
                    <template #uom="{ row }">
                        <span class="text-sm text-neutral-500">{{ row.uom }}</span>
                    </template>
                    <template #actions="{ row }">
                        <button type="button" class="btn btn-outline-danger btn-sm" @click="removeIngredient(row.item_id)">
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </template>
                </Table>
            </div>
            
            <div class="mt-4 flex justify-end">
                <div class="bg-neutral-50 p-3 rounded-lg border border-neutral-200 min-w-[200px]">
                    <div class="text-sm text-neutral-500 mb-1">Estimasi HPP (Berdasarkan Harga Beli Terakhir)</div>
                    <div class="text-lg font-bold text-neutral-900">Rp {{ estimatedHPP.toLocaleString('id-ID') }}</div>
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

// Dummy materials, in real app fetch from API
const rawMaterials = [
    { id: 1, name: 'Biji Kopi Arabica', uom: 'gram', price: 200 },
    { id: 2, name: 'Susu Segar', uom: 'ml', price: 15 },
    { id: 3, name: 'Gula Aren Cair', uom: 'ml', price: 25 },
    { id: 4, name: 'Cup Plastik', uom: 'pcs', price: 500 },
];

const filteredMaterials = computed(() => {
    if (!searchQuery.value) return [];
    const q = searchQuery.value.toLowerCase();
    const addedIds = form.ingredients.map(i => i.item_id);
    return rawMaterials.filter(m => m.name.toLowerCase().includes(q) && !addedIds.includes(m.id));
});

const addIngredient = (material) => {
    form.ingredients.push({
        id: Math.random().toString(), // dummy id for list keys
        item_id: material.id,
        name: material.name,
        qty: 1,
        uom: material.uom,
        unit_price: material.price
    });
    searchQuery.value = '';
};

const removeIngredient = (itemId) => {
    const index = form.ingredients.findIndex(i => i.item_id === itemId);
    if (index !== -1) {
        form.ingredients.splice(index, 1);
    }
};

const estimatedHPP = computed(() => {
    return form.ingredients.reduce((total, item) => total + (item.qty * item.unit_price), 0);
});

const tableHeaders = [
    { field: 'name', label: 'Nama Bahan', slot: 'name' },
    { field: 'qty', label: 'Jumlah (Qty)', slot: 'qty' },
    { field: 'uom', label: 'Satuan', slot: 'uom' },
];
</script>
