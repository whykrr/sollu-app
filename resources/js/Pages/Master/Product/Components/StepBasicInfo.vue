<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Informasi Dasar</div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2 mb-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Foto Produk</label>
                <ProductImagesUploader v-model="form.images" :error="form.errors.images" />
            </div>
            <TextField v-model="form.name" label="Nama Produk" :class="{ 'is-invalid': form.errors.name }" :feedback="form.errors.name" required />
            <TextField v-model="form.code" label="Kode / SKU (Opsional)" :class="{ 'is-invalid': form.errors.code }" :feedback="form.errors.code" />
            <div class="col-span-2">
                <DropdownField 
                    v-model="form.product_category_id" 
                    :options="categoryOptions" 
                    label="Kategori" 
                    :class="{ 'is-invalid': form.errors.product_category_id }"
                    :feedback="form.errors.product_category_id"
                />
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                <textarea v-model="form.description" class="form w-full border-slate-300 rounded-md" rows="3"></textarea>
            </div>
            <div class="col-span-2 flex gap-4 mt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.is_show" class="rounded text-primary cursor-pointer"> 
                    <span class="text-sm">Tampilkan di POS</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.sellable" class="rounded text-primary cursor-pointer"> 
                    <span class="text-sm">Dapat Dijual</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.purchasable" class="rounded text-primary cursor-pointer"> 
                    <span class="text-sm">Dapat Dibeli (PO)</span>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import ProductImagesUploader from './ProductImagesUploader.vue'

const form = inject('productForm')
const categories = inject('categories')
const categoryOptions = computed(() => categories.map(c => ({ label: c.name, value: c.id })))
</script>
