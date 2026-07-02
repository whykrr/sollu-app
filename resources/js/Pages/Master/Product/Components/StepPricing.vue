<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Pengaturan Harga</h3>
            <p class="text-sm text-neutral-500 mb-4">Tentukan harga jual produk. Anda juga bisa mengatur harga berbeda untuk setiap varian atau outlet.</p>
            
            <div class="max-w-xs mb-6">
                <NumberField 
                    v-model="form.base_price"
                    label="Harga Dasar (Rp)"
                    placeholder="0"
                />
            </div>
        </div>

        <!-- Harga Varian -->
        <div v-if="form.product_type === 'variant' && combinations.length > 0">
            <h4 class="text-md font-medium text-neutral-900 mb-2">Harga per Varian</h4>
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="variantHeaders" :data="combinations">
                    <template #name="{ row }">
                        <span class="text-sm text-neutral-900">{{ row.name }}</span>
                    </template>
                    <template #price="{ row }">
                        <input 
                            type="number" 
                            v-model="row.price" 
                            class="block w-full rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                        />
                    </template>
                </Table>
            </div>
        </div>

        <!-- Harga Outlet -->
        <div>
            <h4 class="text-md font-medium text-neutral-900 mb-2 mt-4">Harga Khusus Outlet</h4>
            <p class="text-sm text-neutral-500 mb-3">Kosongkan jika harga di outlet sama dengan Harga Dasar.</p>
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="outletHeaders" :data="form.outlet_prices">
                    <template #name="{ row }">
                        <span class="text-sm text-neutral-900">{{ row.name }}</span>
                    </template>
                    <template #price="{ row }">
                        <input 
                            type="number" 
                            v-model="row.price" 
                            placeholder="Ikut harga dasar"
                            class="block w-full rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                        />
                    </template>
                </Table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue';
import NumberField from '@/Components/Form/NumberField.vue';
import Table from '@/Components/Tables/Table.vue';

const form = inject('productForm');

// Dummy outlets
const outlets = [
    { id: 1, name: 'Outlet Utama Jakarta' },
    { id: 2, name: 'Cabang Bandung' },
];

// Generate combinations just like in StepVariantSetup
const combinations = computed(() => {
    if (form.product_type !== 'variant' || form.variants.length === 0) return [];
    
    const validGroups = form.variants.filter(g => g.name && g.options.length > 0);
    if (validGroups.length === 0) return [];

    let combos = validGroups[0].options.map(opt => ({ name: opt }));

    for (let i = 1; i < validGroups.length; i++) {
        const currentOptions = validGroups[i].options;
        const newCombos = [];
        
        for (const combo of combos) {
            for (const opt of currentOptions) {
                newCombos.push({ name: `${combo.name} - ${opt}` });
            }
        }
        combos = newCombos;
    }

    // Initialize variant_prices if not matching
    if (form.variant_prices.length !== combos.length) {
        form.variant_prices = combos.map((c, idx) => {
            const existing = form.variant_prices.find(vp => vp.name === c.name);
            return {
                id: idx.toString(),
                name: c.name,
                price: existing ? existing.price : form.base_price,
            };
        });
    }

    return form.variant_prices;
});

// Initialize outlet prices
if (form.outlet_prices.length === 0) {
    form.outlet_prices = outlets.map(o => ({
        id: o.id.toString(),
        outlet_id: o.id,
        name: o.name,
        price: null // null means use base price
    }));
}

const variantHeaders = [
    { field: 'name', label: 'Varian', slot: 'name' },
    { field: 'price', label: 'Harga (Rp)', slot: 'price' },
];

const outletHeaders = [
    { field: 'name', label: 'Outlet', slot: 'name' },
    { field: 'price', label: 'Harga (Rp)', slot: 'price' },
];
</script>
