<template>
    <div class="space-y-3">
        <!-- 1. Harga Dasar Produk -->
        <div class="border border-slate-200 p-3 rounded-xl bg-white space-y-1">
            <NumberField
                v-model="form.base_price"
                label="Harga Dasar Produk"
                placeholder="0"
                prefix="Rp"
                :error="form.errors.base_price"
                required
            />
            <p class="text-[11px] text-slate-500">
                Harga acuan default yang berlaku di seluruh outlet dan varian jika tidak diatur
                khusus.
            </p>
        </div>

        <!-- 2. Non-Variant Pricing Setup -->
        <div v-if="!form.has_variant" class="space-y-2">
            <div
                v-if="outlets.length > 1"
                class="border border-slate-200 p-2.5 rounded-xl bg-white space-y-2"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-xs text-slate-800">
                            Atur Harga Berbeda per Outlet
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Kustomisasi nominal harga produk untuk cabang tertentu
                        </div>
                    </div>
                    <Switch v-model="customizeOutletPrices" size="sm" />
                </div>

                <div v-if="customizeOutletPrices" class="space-y-2 pt-2 border-t border-slate-100">
                    <div
                        v-for="outlet in outlets"
                        v-show="outletStatusMap[outlet.id]"
                        :key="outlet.id"
                        class="flex items-center gap-2 bg-slate-50/70 p-2 rounded-lg border border-slate-200"
                    >
                        <div class="w-1/3 text-xs font-semibold text-slate-700 truncate">
                            {{ outlet.name }}
                        </div>
                        <div class="w-2/3">
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
        </div>

        <!-- 3. Variant Pricing Setup -->
        <div v-else class="space-y-2">
            <div class="border border-slate-200 p-2.5 rounded-xl bg-white space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-xs text-slate-800">
                            Kustomisasi Harga Varian & Outlet
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Tentukan harga spesifik untuk setiap variasi atau per cabang
                        </div>
                    </div>
                    <Switch v-model="customizeVariantPrices" size="sm" />
                </div>

                <div v-if="customizeVariantPrices" class="space-y-2 pt-2 border-t border-slate-100">
                    <div
                        v-for="(combo, cIdx) in form.variant_combinations"
                        :key="cIdx"
                        class="border border-slate-200 p-2.5 rounded-xl bg-slate-50/60 space-y-2"
                    >
                        <div
                            class="font-semibold text-xs text-main border-b border-slate-200 pb-1 flex items-center justify-between"
                        >
                            <span>Varian: {{ Object.values(combo.options).join(' / ') }}</span>
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

                            <div
                                v-if="outlets.length > 1"
                                class="col-span-1 sm:col-span-2 space-y-1.5"
                            >
                                <label class="block text-xs font-semibold text-slate-700">
                                    Harga Khusus per Outlet (Opsional)
                                </label>
                                <div class="space-y-1">
                                    <div
                                        v-for="outlet in outlets"
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
                                                    variantOutletPriceMap[
                                                        getComboKey(combo.options)
                                                    ][outlet.id]
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
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'

const form = inject('productForm')
const outlets = inject('outlets', [])
const outletStatusMap = inject('outletStatusMap', {})
const outletPriceMap = inject('outletPriceMap', {})
const variantOutletPriceMap = inject('variantOutletPriceMap', {})
const customizeVariantPrices = inject('customizeVariantPrices')
const customizeOutletPrices = inject('customizeOutletPrices')
const getComboKey = inject('getComboKey')
</script>
