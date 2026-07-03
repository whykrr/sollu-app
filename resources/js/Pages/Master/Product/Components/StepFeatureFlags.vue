<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Pengaturan Lanjutan</div>
        <div class="space-y-2">
            <label
                v-if="form.product_type === 'basic'"
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm">Memiliki Varian</div>
                    <div class="text-xs text-slate-500">
                        Produk ini memiliki pilihan varian seperti ukuran (S, M, L).
                    </div>
                </div>
                <input
                    type="checkbox"
                    v-model="form.has_variant"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>

            <label
                v-if="form.product_type !== 'bundle'"
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm">
                        Memiliki Modifier (Opsi Tambahan)
                    </div>
                    <div class="text-xs text-slate-500">
                        Bisa menambahkan topping atau instruksi khusus.
                    </div>
                </div>
                <input
                    type="checkbox"
                    v-model="form.has_modifier"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>

            <label
                v-if="form.product_type === 'basic'"
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm">Memiliki Resep</div>
                    <div class="text-xs text-slate-500">
                        Stok dipotong berdasarkan bahan baku pembentuk.
                    </div>
                </div>
                <input
                    type="checkbox"
                    v-model="form.has_recipe"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>

            <label
                v-if="form.product_type === 'basic' && !form.has_recipe"
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm">
                        Lacak Inventori (Stok)
                    </div>
                    <div class="text-xs text-slate-500">
                        Lacak stok masuk dan keluar untuk produk ini.
                    </div>
                </div>
                <input
                    type="checkbox"
                    v-model="form.track_inventory"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const form = inject('productForm')
</script>
