<template>
    <div>
        <h3 class="text-lg font-medium text-neutral-900 mb-1">Tipe Produk</h3>
        <p class="text-sm text-neutral-500 mb-4">Pilih tipe produk yang sesuai. Pilihan ini akan menentukan langkah pengaturan selanjutnya.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label 
                v-for="type in productTypes" 
                :key="type.id"
                class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none"
                :class="form.product_type === type.id ? 'border-main ring-1 ring-main' : 'border-neutral-300'"
            >
                <input 
                    type="radio" 
                    name="product_type" 
                    :value="type.id" 
                    v-model="form.product_type" 
                    class="sr-only"
                >
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center">
                        <div class="text-sm">
                            <p class="font-medium text-neutral-900">{{ type.name }}</p>
                            <div class="text-neutral-500 mt-1">
                                <p class="text-xs">{{ type.description }}</p>
                            </div>
                        </div>
                    </div>
                    <div 
                        class="h-5 w-5 rounded-full border flex items-center justify-center"
                        :class="form.product_type === type.id ? 'border-main' : 'border-neutral-300'"
                    >
                        <div v-if="form.product_type === type.id" class="h-2.5 w-2.5 rounded-full bg-main"></div>
                    </div>
                </div>
            </label>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue';

const form = inject('productForm');

const productTypes = [
    {
        id: 'simple',
        name: 'Produk Standar (Simple)',
        description: 'Produk fisik biasa tanpa varian atau resep khusus. Contoh: Minuman kemasan, Botol air.'
    },
    {
        id: 'variant',
        name: 'Produk dengan Varian',
        description: 'Produk yang memiliki pilihan ukuran, warna, dll. Contoh: Baju (S, M, L), Sepatu.'
    },
    {
        id: 'recipe',
        name: 'Produk Resep (BOM)',
        description: 'Produk yang dibuat dari bahan baku (memotong stok bahan). Contoh: Kopi Susu, Makanan jadi.'
    },
    {
        id: 'bundle',
        name: 'Produk Paket (Bundle)',
        description: 'Gabungan dari beberapa produk dijual bersamaan. Contoh: Paket Hemat (Burger + Minum).'
    },
    {
        id: 'service',
        name: 'Layanan / Service',
        description: 'Produk non-fisik yang tidak memiliki stok. Contoh: Jasa pengiriman, Biaya admin.'
    }
];
</script>
