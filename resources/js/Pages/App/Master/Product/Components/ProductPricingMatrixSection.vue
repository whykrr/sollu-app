<template>
    <ProductDisclosureCard
        :id="id"
        title="Matriks Harga Khusus"
        :description="sectionDescription"
        :icon="faTag"
        :feature="$enums.FeatureEnum.PRODUCT_PRICE_MATRIX"
        :switch-value="isCustomPriceActive"
        :is-open="isOpen"
        :badge="badgeText"
        :error="hasError"
        @switch-change="handleSwitchChange"
        @update:is-open="$emit('update:isOpen', $event)"
    >
        <div class="space-y-3">
            <!-- Non-Variant Multi-Outlet Pricing -->
            <div v-if="!form.has_variant" class="space-y-2">
                <p class="text-[11px] text-slate-500">
                    Tentukan harga jual khusus untuk cabang tertentu. Jika dikosongkan, cabang
                    tersebut akan menggunakan Harga Jual Dasar (Rp {{ form.base_price || 0 }}).
                </p>

                <div class="space-y-2">
                    <div
                        v-for="outlet in outletsList"
                        v-show="outletStatusMap[outlet.id]"
                        :key="outlet.id"
                        class="flex flex-col sm:flex-row sm:items-center gap-2 bg-slate-50/70 p-2.5 rounded-lg border border-slate-200"
                    >
                        <div class="sm:w-1/3 text-xs font-semibold text-slate-700 truncate">
                            {{ outlet.name }}
                        </div>
                        <div class="sm:w-2/3">
                            <NumberField
                                v-model="outletPriceMap[outlet.id]"
                                placeholder="Gunakan harga dasar"
                                prefix="Rp"
                                size="sm"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variant Pricing Matrix -->
            <div v-else class="space-y-2.5">
                <p class="text-[11px] text-slate-500">
                    Atur harga dasar untuk setiap varian atau tentukan harga khusus per cabang
                    outlet.
                </p>

                <div
                    v-for="(combo, cIdx) in form.variant_combinations"
                    :key="cIdx"
                    class="border border-slate-200 p-2.5 rounded-xl bg-slate-50/60 space-y-2"
                >
                    <div
                        class="font-semibold text-xs text-main border-b border-slate-200 pb-1.5 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-1.5">
                            <span>Varian: {{ Object.values(combo.options).join(' / ') }}</span>
                            <span
                                v-if="isEdit && combo.can_edit_initial_stock === false"
                                class="badge badge-warning text-[10px]"
                                title="Stok awal varian terkunci karena sudah ada transaksi"
                            >
                                <FontAwesomeIcon :icon="faLock" class="mr-1" />
                                Terkunci
                            </span>
                        </div>
                        <span v-if="combo.sku" class="text-[10px] text-slate-500 font-mono">
                            {{ combo.sku }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="col-span-1">
                            <NumberField
                                v-model="combo.price"
                                label="Harga Dasar Varian"
                                prefix="Rp"
                                size="sm"
                                required
                            />
                        </div>

                        <div v-if="combo.track_inventory ?? form.track_inventory" class="col-span-1">
                            <NumberField
                                v-model="combo.initial_stock"
                                label="Stok Awal Varian"
                                placeholder="0"
                                size="sm"
                                min="0"
                                step="1"
                                :disabled="isEdit && combo.can_edit_initial_stock === false"
                            />
                        </div>

                        <div v-if="combo.track_inventory ?? form.track_inventory" class="col-span-1">
                            <NumberField
                                v-model="combo.purchase_price"
                                label="Modal Beli Satuan"
                                prefix="Rp"
                                placeholder="0"
                                size="sm"
                                min="0"
                                :disabled="isEdit && combo.can_edit_initial_stock === false"
                            />
                        </div>
                    </div>

                    <div
                        v-if="outletsList.length > 1"
                        class="space-y-1.5 pt-1.5 border-t border-slate-200/60"
                    >
                        <label class="block text-xs font-semibold text-slate-700">
                            Harga Khusus per Outlet (Opsional)
                        </label>
                        <div class="space-y-1">
                            <div
                                v-for="outlet in outletsList"
                                v-show="outletStatusMap[outlet.id]"
                                :key="outlet.id"
                                class="flex items-center gap-2"
                            >
                                <span class="w-1/3 text-xs text-slate-600 truncate">
                                    {{ outlet.name }}
                                </span>
                                <div class="w-2/3">
                                    <NumberField
                                        v-model="
                                            variantOutletPriceMap[getComboKey(combo.options)][
                                                outlet.id
                                            ]
                                        "
                                        placeholder="Gunakan harga varian"
                                        prefix="Rp"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ProductDisclosureCard>
</template>

<script setup>
import { inject, computed } from 'vue'
import { faTag, faLock } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ProductDisclosureCard from './ProductDisclosureCard.vue'
import NumberField from '@/Components/Form/NumberField.vue'

defineProps({
    id: { type: String, default: 'card-pricing' },
    isOpen: { type: Boolean, default: false },
})

defineEmits(['update:isOpen'])

const form = inject('productForm')
const isEdit = inject('isEdit', false)
const outlets = inject('outlets', [])
const outletStatusMap = inject('outletStatusMap', {})
const outletPriceMap = inject('outletPriceMap', {})
const variantOutletPriceMap = inject('variantOutletPriceMap', {})
const customizeVariantPrices = inject('customizeVariantPrices')
const customizeOutletPrices = inject('customizeOutletPrices')
const getComboKey = inject('getComboKey')

const outletsList = computed(() => {
    const list = outlets && outlets.value !== undefined ? outlets.value : outlets
    return Array.isArray(list) ? list : []
})

const isCustomPriceActive = computed(() => {
    return form.has_variant ? customizeVariantPrices.value : customizeOutletPrices.value
})

const sectionDescription = computed(() => {
    return form.has_variant
        ? 'Kustomisasi harga spesifik per variasi atau per cabang outlet'
        : 'Kustomisasi nominal harga jual khusus per cabang outlet'
})

const badgeText = computed(() => {
    return isCustomPriceActive.value ? 'Kustom Aktif' : null
})

const hasError = computed(() => {
    return Boolean(
        form.errors.outlet_prices || Object.keys(form.errors).some(k => k.includes('outlet_prices'))
    )
})

const handleSwitchChange = val => {
    if (form.has_variant) {
        customizeVariantPrices.value = val
    } else {
        customizeOutletPrices.value = val
    }
}
</script>
