<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Pengaturan Outlet</h3>
            <p class="text-sm text-neutral-500 mb-4">Pilih outlet mana saja yang dapat menjual produk ini. Anda juga dapat mengatur status ketersediaan (Stok Habis / Tersedia) per outlet.</p>
            
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="tableHeaders" :data="form.outlets">
                    <template #is_enabled="{ row }">
                        <input 
                            type="checkbox" 
                            v-model="row.is_enabled"
                            class="rounded border-neutral-300 text-main focus:ring-main h-4 w-4"
                        />
                    </template>
                    <template #name="{ row }">
                        <div :class="row.is_enabled ? 'text-neutral-900' : 'text-neutral-400'">
                            <div class="text-sm font-medium">{{ row.name }}</div>
                            <div class="text-xs text-neutral-500">{{ row.address }}</div>
                        </div>
                    </template>
                    <template #is_available="{ row }">
                        <div class="flex justify-center">
                            <label class="inline-flex items-center cursor-pointer" :class="{'opacity-50 pointer-events-none': !row.is_enabled}">
                                <input type="checkbox" v-model="row.is_available" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-main/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-main"></div>
                            </label>
                        </div>
                    </template>
                </Table>
            </div>
            
            <div class="mt-4 flex gap-4 text-sm text-neutral-500">
                <div class="flex items-center">
                    <span class="w-3 h-3 rounded-full bg-main mr-2"></span>
                    Aktif di {{ form.outlets.filter(o => o.is_enabled).length }} Outlet
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue';
import Table from '@/Components/Tables/Table.vue';

const form = inject('productForm');

// Dummy outlets
const availableOutlets = [
    { id: 1, name: 'Outlet Utama Jakarta', address: 'Jl. Sudirman No. 1' },
    { id: 2, name: 'Cabang Bandung', address: 'Jl. Braga No. 10' },
];

if (form.outlets.length === 0) {
    // default to all enabled
    form.outlets = availableOutlets.map(o => ({
        id: o.id.toString(),
        outlet_id: o.id,
        name: o.name,
        address: o.address,
        is_enabled: true,
        is_available: true,
    }));
}

// table component doesn't natively support header checkbox in slots unless customized, 
// so we'll just omit the "select all" for simplicity in this standard Table usage, 
// or implement it if we had access to Table headers slots.
const tableHeaders = [
    { field: 'is_enabled', label: '#', slot: 'is_enabled' },
    { field: 'name', label: 'Nama Outlet', slot: 'name' },
    { field: 'is_available', label: 'Ketersediaan', slot: 'is_available' },
];
</script>
