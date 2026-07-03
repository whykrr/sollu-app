<template>
    <div class="space-y-3">
        <div class="font-semibold text-lg border-b pb-1">Setup Paket (Bundle)</div>
        <p class="text-xs text-slate-500">Pilih produk-produk yang masuk ke dalam paket ini.</p>
        <div class="text-danger text-sm">Form pemilihan item bundle diintegrasikan disini.</div>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const form = inject('productForm')
</script>
