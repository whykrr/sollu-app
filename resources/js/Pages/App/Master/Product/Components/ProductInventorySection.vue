<template>
    <ProductDisclosureCard
        :id="id"
        title="Stok & Inventori"
        description="Pantau stok masuk, keluar, dan peringatan batas minimum stok"
        :icon="faBoxesStacked"
        :feature="$enums.FeatureEnum?.INVENTORY_MANAGEMENT || 'inventory_management'"
        :switch-value="Boolean(form.track_inventory)"
        :is-open="isOpen"
        :badge="badgeText"
        :error="hasError"
        @update:switch-value="onSwitchChange"
        @update:is-open="$emit('update:isOpen', $event)"
    >
        <div class="space-y-3">
            <div class="space-y-1.5">
                <SearchableDropdownField
                    v-model="form.uom_id"
                    :options="uomOptions"
                    label="Satuan Dasar (UOM)"
                    placeholder="Pilih Satuan"
                    search-placeholder="Cari satuan (UOM)..."
                    :error="form.errors.uom_id"
                    required
                    clearable
                />
                <p class="text-[11px] text-slate-500">
                    Satuan pengukuran default untuk pelacakan persediaan dan kartu stok produk ini.
                </p>
            </div>

            <!-- Bagian Stok Awal & Modal (Hanya saat pembuatan produk baru non-varian) -->
            <div v-if="!isEdit && !form.has_variant" class="border border-slate-200/80 rounded-xl p-3 bg-slate-50/60 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-800">
                            Stok Awal & Modal Beli (Opsional)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Isi jika barang fisik sudah tersedia di tokomu saat ini.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                    <div>
                        <NumberField
                            v-model="form.initial_stock"
                            label="Jumlah Stok Awal"
                            placeholder="0"
                            size="sm"
                            min="0"
                            step="0.01"
                            :error="form.errors.initial_stock"
                        />
                        <p class="text-[10px] text-slate-400 mt-0.5">Jumlah barang siap jual saat ini.</p>
                    </div>

                    <div>
                        <NumberField
                            v-model="form.purchase_price"
                            label="Modal Beli Satuan"
                            prefix="Rp"
                            placeholder="0"
                            size="sm"
                            min="0"
                            :error="form.errors.purchase_price"
                        />
                        <p class="text-[10px] text-slate-400 mt-0.5">Modal beli untuk hitung laba bersih.</p>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Jika Produk Bervarian -->
            <div
                v-if="!isEdit && form.has_variant"
                class="p-2.5 bg-blue-50/70 border border-blue-200/60 rounded-lg text-[11px] text-blue-800"
            >
                Produk ini memiliki varian. Stok awal dan modal beli untuk masing-masing varian dapat diatur pada bagian <strong>Matriks Harga Khusus</strong> di bawah.
            </div>

            <!-- Batas Minimum Stok -->
            <div class="pt-1">
                <NumberField
                    v-model="form.minimum_stock"
                    label="Batas Minimum Stok (Peringatan Stok Menipis)"
                    placeholder="0"
                    size="sm"
                    min="0"
                    step="0.01"
                    :error="form.errors.minimum_stock"
                />
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Sistem akan menandai status "Menipis" jika sisa stok di outlet berada di bawah angka ini.
                </p>
            </div>
        </div>
    </ProductDisclosureCard>
</template>

<script setup>
import { inject, computed } from 'vue'
import { faBoxesStacked } from '@fortawesome/free-solid-svg-icons'
import ProductDisclosureCard from './ProductDisclosureCard.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'

defineProps({
    id: { type: String, default: 'card-inventory' },
    isOpen: { type: Boolean, default: false },
})

defineEmits(['update:isOpen'])

const form = inject('productForm')
const isEdit = inject('isEdit', false)
const uoms = inject('uoms', [])

const uomOptions = computed(() => {
    const raw = uoms && uoms.value !== undefined ? uoms.value : uoms
    const list = Array.isArray(raw) ? raw : []
    return list.map(u => ({
        label:
            u.label ||
            (u.code && u.name && u.code.toLowerCase() !== u.name.toLowerCase()
                ? `${u.name} (${u.code})`
                : u.name || ''),
        value: u.value !== undefined && u.value !== null ? u.value : u.id || '',
    }))
})

const badgeText = computed(() => {
    if (!form.track_inventory) return null
    const selected = uomOptions.value.find(u => u.value === form.uom_id)
    return selected ? selected.label : 'Stok Aktif'
})

const hasError = computed(() => {
    return Boolean(form.errors.uom_id || form.errors.track_inventory)
})

const onSwitchChange = val => {
    form.track_inventory = val
    if (!val) {
        form.uom_id = ''
    }
}
</script>
