<template>
    <ProductDisclosureCard
        :id="id"
        title="Stok & Inventori"
        description="Pantau stok masuk, keluar, dan peringatan batas minimum stok"
        :icon="faBoxesStacked"
        :feature="$enums.FeatureEnum.INVENTORY_MANAGEMENT"
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

            <!-- Bagian Stok Awal & Modal (Non-Varian) -->
            <div v-if="!form.has_variant && enabledInitialStocks.length > 0" class="space-y-2.5">
                <!-- Single Outlet Mode -->
                <div
                    v-if="enabledInitialStocks.length === 1"
                    class="border border-slate-200/80 rounded-xl p-3 bg-slate-50/60 space-y-2.5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <span>Stok Awal & Modal Beli (HPP)</span>
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-semibold"
                                >
                                    <FontAwesomeIcon :icon="faStore" class="text-[9px]" />
                                    {{ enabledInitialStocks[0]?.outlet_name || 'Outlet Utama' }}
                                </span>
                                <span
                                    v-if="isEdit && !enabledInitialStocks[0]?.can_edit"
                                    class="badge badge-warning text-[10px]"
                                >
                                    <FontAwesomeIcon :icon="faLock" class="mr-1" />
                                    Terkunci
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                <span v-if="!isEdit"
                                    >Isi jika barang fisik sudah tersedia di outlet ini.</span
                                >
                                <span v-else-if="enabledInitialStocks[0]?.can_edit"
                                    >Stok awal dapat diubah karena belum ada mutasi
                                    operasional.</span
                                >
                                <span v-else class="text-amber-700"
                                    >Stok awal telah dikunci karena sudah memiliki riwayat mutasi
                                    operasional. Gunakan Penyesuaian Stok untuk koreksi stok
                                    fisik.</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        <div>
                            <NumberField
                                v-model="enabledInitialStocks[0].initial_stock"
                                label="Jumlah Stok Awal"
                                placeholder="0"
                                size="sm"
                                min="0"
                                step="1"
                                :disabled="isEdit && !enabledInitialStocks[0]?.can_edit"
                                :error="
                                    form.errors.initial_stock ||
                                    form.errors['initial_stocks.0.initial_stock']
                                "
                            />
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Jumlah barang siap jual saat ini.
                            </p>
                        </div>

                        <div>
                            <NumberField
                                v-model="enabledInitialStocks[0].purchase_price"
                                label="Modal Beli Satuan (HPP)"
                                prefix="Rp"
                                placeholder="0"
                                size="sm"
                                min="0"
                                :disabled="isEdit && !enabledInitialStocks[0]?.can_edit"
                                :error="
                                    form.errors.purchase_price ||
                                    form.errors['initial_stocks.0.purchase_price']
                                "
                            />
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Modal beli untuk hitung laba bersih.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Multi Outlet Mode -->
                <div
                    v-else
                    class="border border-slate-200/80 rounded-xl p-3 bg-slate-50/60 space-y-3"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 pb-2.5"
                    >
                        <div>
                            <div class="text-xs font-bold text-slate-800">
                                Stok Awal & Modal Beli per Outlet
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Tentukan stok fisik awal dan modal beli (HPP) untuk masing-masing
                                outlet.
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 bg-white px-2.5 py-1.5 rounded-lg border border-slate-200 shadow-xs"
                        >
                            <Switch v-model="syncAllOutlets" size="sm" />
                            <span class="text-xs font-medium text-slate-700"
                                >Samakan semua outlet</span
                            >
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <div
                            v-for="(stockItem, sIdx) in enabledInitialStocks"
                            :key="stockItem.outlet_id"
                            class="p-2.5 bg-white rounded-lg border border-slate-200/80 space-y-2 shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-1.5 text-xs font-bold text-slate-700"
                                >
                                    <FontAwesomeIcon
                                        :icon="faStore"
                                        class="text-slate-400 text-xs"
                                    />
                                    <span>{{ stockItem.outlet_name }}</span>
                                </div>
                                <span
                                    v-if="isEdit && !stockItem.can_edit"
                                    class="badge badge-warning text-[10px]"
                                >
                                    <FontAwesomeIcon :icon="faLock" class="mr-1" />
                                    Terkunci
                                </span>
                                <span
                                    v-else-if="syncAllOutlets && sIdx > 0"
                                    class="badge badge-info text-[10px]"
                                >
                                    Mengikuti Outlet 1
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <NumberField
                                        v-model="stockItem.initial_stock"
                                        label="Jumlah Stok Awal"
                                        placeholder="0"
                                        size="sm"
                                        min="0"
                                        step="1"
                                        :disabled="
                                            (isEdit && !stockItem.can_edit) ||
                                            (syncAllOutlets && sIdx > 0)
                                        "
                                        :error="form.errors[`initial_stocks.${sIdx}.initial_stock`]"
                                    />
                                </div>
                                <div>
                                    <NumberField
                                        v-model="stockItem.purchase_price"
                                        label="Modal Beli Satuan (HPP)"
                                        prefix="Rp"
                                        placeholder="0"
                                        size="sm"
                                        min="0"
                                        :disabled="
                                            (isEdit && !stockItem.can_edit) ||
                                            (syncAllOutlets && sIdx > 0)
                                        "
                                        :error="
                                            form.errors[`initial_stocks.${sIdx}.purchase_price`]
                                        "
                                    />
                                </div>
                            </div>
                            <p
                                v-if="isEdit && !stockItem.can_edit"
                                class="text-[10px] text-amber-700"
                            >
                                Stok awal terkunci karena outlet ini sudah memiliki transaksi
                                operasional.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Jika Produk Bervarian -->
            <div
                v-if="form.has_variant"
                class="p-2.5 bg-blue-50/70 border border-blue-200/60 rounded-lg text-[11px] text-blue-800"
            >
                Produk ini memiliki varian. Stok awal dan modal beli untuk masing-masing varian
                dapat diatur pada bagian <strong>Matriks Harga Khusus</strong> di bawah.
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
                    Sistem akan menandai status "Menipis" jika sisa stok di outlet berada di bawah
                    angka ini.
                </p>
            </div>
        </div>
    </ProductDisclosureCard>
</template>

<script setup>
import { inject, computed, ref } from 'vue'
import { faBoxesStacked, faStore, faLock } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ProductDisclosureCard from './ProductDisclosureCard.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'

defineProps({
    id: { type: String, default: 'card-inventory' },
    isOpen: { type: Boolean, default: false },
})

defineEmits(['update:isOpen'])

const form = inject('productForm')
const isEdit = inject('isEdit', false)
const uoms = inject('uoms', [])
const outletStatusMap = inject('outletStatusMap', {})
const syncAllOutlets = inject('syncAllOutlets', ref(false))

const enabledInitialStocks = computed(() => {
    if (!Array.isArray(form.initial_stocks)) return []
    return form.initial_stocks.filter(s => outletStatusMap.value[s.outlet_id] !== false)
})

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
