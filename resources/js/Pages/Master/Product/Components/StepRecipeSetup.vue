<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Setup Resep</div>
        <p class="text-xs text-slate-500">Tentukan bahan baku yang digunakan untuk produk ini.</p>
        <div class="text-danger text-sm">Form detail resep diintegrasikan disini.</div>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const form = inject('productForm')
</script>
