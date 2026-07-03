<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Tipe Produk</div>
        <div class="grid grid-cols-3 gap-3">
            <div
                @click="form.product_type = 'basic'"
                class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition"
                :class="{
                    'border-main bg-slate-100': form.product_type === 'basic',
                }"
            >
                <div class="font-bold mb-1">Produk Fisik (Basic)</div>
                <div class="text-xs text-slate-500">
                    Barang fisik yang dikelola stoknya (seperti Kopi, Makanan).
                </div>
            </div>
            <div
                @click="form.product_type = 'service'"
                class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition"
                :class="{
                    'border-main bg-slate-100': form.product_type === 'service',
                }"
            >
                <div class="font-bold mb-1">Layanan (Service)</div>
                <div class="text-xs text-slate-500">
                    Produk tak berwujud, tanpa stok (seperti Ongkir, Jasa).
                </div>
            </div>
            <div
                @click="form.product_type = 'bundle'"
                class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition"
                :class="{
                    'border-main bg-slate-100': form.product_type === 'bundle',
                }"
            >
                <div class="font-bold mb-1">Paket (Bundle)</div>
                <div class="text-xs text-slate-500">
                    Kombinasi dari beberapa produk fisik atau layanan.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue';

const form = inject('productForm');
</script>
