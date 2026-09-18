<template>
    <div class="space-y-4">
        <div class="font-semibold text-lg border-b border-slate-200 pb-1">Setup Harga</div>
        <div class="mb-4 border border-slate-200 p-3 rounded-xl bg-slate-50">
            <NumberField
                v-model="form.base_price"
                label="Harga Dasar Produk"
                :class="{ 'is-invalid': form.errors.base_price }"
                :error="form.errors.base_price"
                required
            />
        </div>

        <!-- Non-Variant Pricing -->
        <div v-if="!form.has_variant" class="space-y-3">
            <div
                class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm text-slate-800">
                        Atur Harga Berbeda per Outlet
                    </div>
                    <div class="text-xs text-slate-500">
                        Aktifkan jika harga produk berbeda pada masing-masing cabang / outlet.
                    </div>
                </div>
                <Switch v-model="customizeOutletPrices" size="md" />
            </div>

            <div
                v-if="customizeOutletPrices"
                class="space-y-2 border border-slate-200 p-3 rounded-xl bg-slate-50"
            >
                <h3 class="font-bold text-sm text-slate-700 mb-2">
                    Timpa Harga per Outlet (Opsional)
                </h3>
                <div
                    v-for="outlet in outlets"
                    v-show="outletStatusMap[outlet.id]"
                    :key="outlet.id"
                    class="flex items-center gap-3"
                >
                    <div class="w-1/3 text-sm font-medium text-slate-600">
                        {{ outlet.name }}
                    </div>
                    <div class="w-2/3">
                        <NumberField
                            v-model="outletPriceMap[outlet.id]"
                            placeholder="Biarkan kosong untuk pakai harga dasar"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Variant Pricing -->
        <div v-else class="space-y-4">
            <div
                class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm text-slate-800">
                        Atur Harga Berbeda per Varian & Outlet
                    </div>
                    <div class="text-xs text-slate-500">
                        Kustomisasi harga spesifik per kombinasi varian dan outlet.
                    </div>
                </div>
                <Switch v-model="customizeVariantPrices" size="md" />
            </div>

            <div v-if="customizeVariantPrices" class="space-y-4">
                <h3 class="font-bold text-sm text-slate-700">Harga Detail per Varian & Outlet</h3>
                <div
                    v-for="(combo, cIdx) in form.variant_combinations"
                    :key="cIdx"
                    class="border border-slate-200 p-3 rounded-xl bg-slate-50 space-y-3"
                >
                    <div class="font-bold text-sm border-b border-slate-200 pb-1 text-primary">
                        Varian: {{ Object.values(combo.options).join(' / ') }}
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <NumberField
                                v-model="combo.price"
                                label="Harga Dasar Varian"
                                required
                            />
                        </div>
                        <div class="col-span-2 space-y-2">
                            <label class="block text-sm font-medium text-slate-700"
                                >Harga per Outlet (Opsional)</label
                            >
                            <div
                                v-for="outlet in outlets"
                                v-show="outletStatusMap[outlet.id]"
                                :key="outlet.id"
                                class="flex items-center gap-2"
                            >
                                <span class="w-1/3 text-xs text-slate-600 font-medium">{{
                                    outlet.name
                                }}</span>
                                <div class="w-2/3">
                                    <NumberField
                                        v-model="
                                            variantOutletPriceMap[getComboKey(combo.options)][
                                                outlet.id
                                            ]
                                        "
                                        placeholder="Gunakan harga dasar varian"
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
</template>

<script setup>
import { inject } from 'vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'

const form = inject('productForm')
const outlets = inject('outlets')
const outletStatusMap = inject('outletStatusMap')
const outletPriceMap = inject('outletPriceMap')
const variantOutletPriceMap = inject('variantOutletPriceMap')
const customizeVariantPrices = inject('customizeVariantPrices')
const customizeOutletPrices = inject('customizeOutletPrices')
const getComboKey = inject('getComboKey')
</script>
