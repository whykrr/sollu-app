<template>
    <div>
        <h3 class="text-lg font-medium text-neutral-900 mb-1">Informasi Dasar</h3>
        <p class="text-sm text-neutral-500 mb-4">Lengkapi informasi dasar mengenai produk Anda.</p>
        
        <div class="space-y-4 max-w-xl">
            <!-- Image Cropper -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 mb-2">Foto Produk</label>
                <ProductImageCropper
                    v-model="form.image"
                    :error="form.errors.image"
                />
            </div>

            <!-- Form Fields -->
            <TextField 
                v-model="form.name"
                label="Nama Produk *"
                placeholder="Contoh: Kopi Susu Aren"
                :feedback="form.errors.name"
            />
            
            <TextField 
                v-model="form.code"
                label="Kode Produk / SKU"
                placeholder="Contoh: KSA-01"
                :feedback="form.errors.code"
            />

            <DropdownField
                v-model="form.category_id"
                label="Kategori *"
                :options="categories"
                :feedback="form.errors.category_id"
            />

            <div>
                <label class="block text-sm font-medium text-neutral-700 mb-1">Deskripsi</label>
                <textarea 
                    v-model="form.description"
                    rows="3"
                    class="block w-full rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                    placeholder="Tulis deskripsi produk di sini..."
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import ProductImageCropper from './ProductImageCropper.vue';

const form = inject('productForm');

const categories = [
    { value: 1, label: 'Makanan Utama' },
    { value: 2, label: 'Minuman Dingin' },
    { value: 3, label: 'Snack' },
];
</script>
