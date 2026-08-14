<template>
    <div class="space-y-4">
        <div class="font-semibold text-lg border-b pb-1">Setup Harga</div>
        <div class="mb-4 border p-3 rounded-lg bg-slate-50">
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
            <label
                class="flex items-center gap-2 border p-3 rounded-lg bg-slate-50 cursor-pointer hover:bg-slate-100 transition"
            >
                <input
                    v-model="customizeOutletPrices"
                    type="checkbox"
                    class="rounded text-primary cursor-pointer"
                />
                <span class="text-sm font-semibold text-slate-700"
                    >Atur harga berbeda per outlet</span
                >
            </label>

            <div
                v-if="customizeOutletPrices"
                class="space-y-2 border p-3 rounded-lg bg-slate-50"
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

            <!-- Initial Stock Setup (only on create & if track_inventory = true) -->
            <div
                v-if="form.track_inventory && !isEdit"
                class="border p-3 rounded-lg bg-neutral-50 space-y-3 mt-4"
            >
                <h3 class="font-bold text-sm text-neutral-700">Setup Stok</h3>
                <div class="grid grid-cols-1 gap-3">
                    <NumberField
                        v-model="form.min_stock"
                        label="Minimal Stok"
                        placeholder="0"
                    />
                </div>
            </div>
        </div>

        <!-- Variant Pricing -->
        <div v-else class="space-y-4">
            <label
                class="flex items-center gap-2 border p-3 rounded-lg bg-slate-50 cursor-pointer hover:bg-slate-100 transition"
            >
                <input
                    v-model="customizeVariantPrices"
                    type="checkbox"
                    class="rounded text-primary cursor-pointer"
                />
                <span class="text-sm font-semibold text-slate-700"
                    >Atur harga berbeda per varian & outlet</span
                >
            </label>

            <div v-if="customizeVariantPrices" class="space-y-4">
                <h3 class="font-bold text-sm text-slate-700">
                    Harga Detail per Varian & Outlet
                </h3>
                <div
                    v-for="(combo, cIdx) in form.variant_combinations"
                    :key="cIdx"
                    class="border p-3 rounded-lg bg-slate-50 space-y-3"
                >
                    <div class="font-bold text-sm border-b pb-1 text-primary">
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
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Harga per Outlet (Opsional)</label
                            >
                            <div
                                v-for="outlet in outlets"
                                v-show="outletStatusMap[outlet.id]"
                                :key="outlet.id"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="w-1/3 text-xs text-slate-600 font-medium"
                                    >{{ outlet.name }}</span
                                >
                                <div class="w-2/3">
                                    <NumberField
                                        v-model="
                                            variantOutletPriceMap[
                                                getComboKey(combo.options)
                                            ][outlet.id]
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
import { inject } from 'vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextField from '@/Components/Form/TextField.vue';

const form = inject('productForm');
const isEdit = inject('isEdit');
const outlets = inject('outlets');
const outletStatusMap = inject('outletStatusMap');
const outletPriceMap = inject('outletPriceMap');
const variantOutletPriceMap = inject('variantOutletPriceMap');
const customizeVariantPrices = inject('customizeVariantPrices');
const customizeOutletPrices = inject('customizeOutletPrices');
const getComboKey = inject('getComboKey');
</script>
