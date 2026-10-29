<template>
    <ProductDisclosureCard
        :id="id"
        title="Varian Produk"
        description="Aktifkan jika produk memiliki variasi opsi seperti Ukuran, Rasa, atau Warna"
        :icon="faTags"
        :feature="$enums.FeatureEnum?.PRODUCT_VARIANTS || 'product_variants'"
        :switch-value="Boolean(form.has_variant)"
        :is-open="isOpen"
        :badge="badgeText"
        :error="hasError"
        @switch-change="handleVariantSwitch"
        @update:is-open="$emit('update:isOpen', $event)"
    >
        <div class="space-y-3">
            <div>
                <TextField
                    v-model="form.code"
                    label="Prefix SKU Varian (Opsional)"
                    placeholder="Misal: KOP-AREN"
                    :error="form.errors.code"
                />
            </div>

            <!-- Variant Groups List -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Grup & Opsi Varian
                    </span>
                </div>

                <div
                    v-for="(group, gIdx) in form.variants"
                    :key="gIdx"
                    class="border border-slate-200 p-2.5 rounded-xl bg-slate-50/60 relative space-y-2"
                >
                    <button
                        v-if="form.variants.length > 1"
                        type="button"
                        class="absolute top-2 right-2 text-danger hover:text-red-700 text-xs p-1"
                        title="Hapus Grup Varian"
                        @click="deleteVariantGroup(gIdx)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="col-span-1">
                            <TextField
                                v-model="group.name"
                                label="Nama Grup"
                                placeholder="Misal: Ukuran"
                                size="sm"
                                required
                            />
                        </div>
                        <div class="col-span-1 sm:col-span-2 space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">
                                Pilihan Opsi
                            </label>
                            <div class="flex flex-wrap gap-1.5 items-center">
                                <div
                                    v-for="(opt, oIdx) in group.options"
                                    :key="oIdx"
                                    class="inline-flex items-center bg-white border border-slate-200 rounded-lg px-2 py-0.5 text-xs text-slate-700 gap-1.5 shadow-none"
                                >
                                    <span class="font-medium">{{ opt.name || '(Kosong)' }}</span>
                                    <button
                                        type="button"
                                        class="text-slate-400 hover:text-danger text-[10px] cursor-pointer"
                                        @click="deleteVariantOption(gIdx, oIdx)"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Add Option Inline Form -->
                                <div class="inline-flex items-center gap-1">
                                    <TextField
                                        v-model="newOptionInputs[gIdx]"
                                        size="sm"
                                        placeholder="+ Opsi baru..."
                                        class="w-28"
                                        @keydown.enter.prevent="addOptionFromInput(gIdx)"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-flat btn-xs h-[30px]"
                                        @click="addOptionFromInput(gIdx)"
                                    >
                                        <FontAwesomeIcon :icon="faPlus" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-flat btn-sm w-full border-dashed"
                    @click="addVariantGroup"
                >
                    <FontAwesomeIcon :icon="faPlus" class="mr-1 text-xs" />
                    Tambah Grup Varian Baru
                </button>
            </div>

            <!-- Combinations Preview Table -->
            <div v-if="form.variant_combinations.length > 0" class="space-y-2 pt-2">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1.5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Daftar Kombinasi SKU ({{ form.variant_combinations.length }})
                        </span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-flat btn-xs text-main self-start sm:self-auto"
                        @click="autoGenerateAllSkus"
                    >
                        <FontAwesomeIcon :icon="faWandMagicSparkles" class="mr-1" />
                        Generate SKU Otomatis
                    </button>
                </div>

                <div
                    class="overflow-x-auto touch-pan-x border border-slate-200 rounded-xl bg-white"
                >
                    <table class="w-full text-left text-xs min-w-[600px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600">
                                <th class="p-2 font-semibold w-12 text-center">Foto</th>
                                <th class="p-2 font-semibold">Kombinasi</th>
                                <th class="p-2 font-semibold w-32">SKU</th>
                                <th class="p-2 font-semibold w-28">Barcode</th>
                                <th
                                    v-if="form.track_inventory"
                                    class="p-2 font-semibold w-24 text-center"
                                >
                                    Lacak Stok
                                </th>
                                <th class="p-2 font-semibold w-24 text-center">Dapat Dijual</th>
                                <th class="p-2 font-semibold w-20 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(combo, cIdx) in form.variant_combinations"
                                :key="cIdx"
                                class="hover:bg-slate-50/50 transition-colors"
                                :class="{ 'opacity-60 bg-slate-50/60': !combo.is_active }"
                            >
                                <td class="p-1.5 text-center">
                                    <div
                                        class="size-8 mx-auto bg-slate-100 rounded-lg flex items-center justify-center border border-slate-200 overflow-hidden text-slate-400"
                                    >
                                        <img
                                            v-if="combo.image_url"
                                            :src="combo.image_url"
                                            class="size-full object-cover"
                                            alt="Variant image"
                                        />
                                        <FontAwesomeIcon v-else :icon="faImage" class="text-xs" />
                                    </div>
                                </td>
                                <td class="p-2 font-medium text-slate-800">
                                    {{ Object.values(combo.options).join(' / ') }}
                                </td>
                                <td class="p-1.5">
                                    <TextField
                                        v-model="combo.sku"
                                        size="sm"
                                        placeholder="SKU"
                                        :disabled="!combo.is_active"
                                    />
                                </td>
                                <td class="p-1.5">
                                    <TextField
                                        v-model="combo.barcode"
                                        size="sm"
                                        placeholder="Barcode"
                                        :disabled="!combo.is_active"
                                    />
                                </td>
                                <td v-if="form.track_inventory" class="p-1.5 text-center">
                                    <Switch
                                        v-model="combo.track_inventory"
                                        size="sm"
                                        :disabled="!combo.is_active"
                                    />
                                </td>
                                <td class="p-1.5 text-center">
                                    <Switch
                                        v-model="combo.sellable"
                                        size="sm"
                                        :disabled="!combo.is_active"
                                    />
                                </td>
                                <td class="p-1.5 text-center">
                                    <Switch v-model="combo.is_active" size="sm" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ProductDisclosureCard>
</template>

<script setup>
import { inject, ref, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faTags,
    faTrash,
    faPlus,
    faImage,
    faWandMagicSparkles,
} from '@fortawesome/free-solid-svg-icons'
import ProductDisclosureCard from './ProductDisclosureCard.vue'
import TextField from '@/Components/Form/TextField.vue'
import Switch from '@/Components/Form/Switch.vue'

defineProps({
    id: { type: String, default: 'card-variant' },
    isOpen: { type: Boolean, default: false },
})

defineEmits(['update:isOpen'])

const form = inject('productForm')
const isEdit = inject('isEdit')
const originalProduct = inject('originalProduct')
const autoGenerateAllSkus = inject('autoGenerateAllSkus')

const newOptionInputs = ref({})

const badgeText = computed(() => {
    if (!form.has_variant) return null
    const count = form.variant_combinations?.length || 0
    return count > 0 ? `${count} varian` : 'Varian Aktif'
})

const hasError = computed(() => {
    return Boolean(
        form.errors.variants ||
        form.errors.variant_combinations ||
        Object.keys(form.errors).some(k => k.startsWith('variant_combinations.'))
    )
})

const handleVariantSwitch = isChecked => {
    if (!isChecked && isEdit.value && originalProduct?.has_variant) {
        if (
            window.confirm(
                'PERINGATAN: Menonaktifkan opsi varian akan menonaktifkan seluruh SKU varian sebelumnya. Yakin ingin melanjutkan?'
            )
        ) {
            form.has_variant = false
        }
    } else {
        form.has_variant = isChecked
    }
}

const addVariantGroup = () => {
    form.variants.push({
        name: '',
        options: [],
    })
}

const deleteVariantGroup = gIdx => {
    form.variants.splice(gIdx, 1)
}

const addOptionFromInput = gIdx => {
    const text = (newOptionInputs.value[gIdx] || '').trim()
    if (!text) return

    if (!form.variants[gIdx].options) {
        form.variants[gIdx].options = []
    }

    if (!form.variants[gIdx].options.some(o => o.name.toLowerCase() === text.toLowerCase())) {
        form.variants[gIdx].options.push({ name: text })
    }

    newOptionInputs.value[gIdx] = ''
}

const deleteVariantOption = (gIdx, oIdx) => {
    form.variants[gIdx].options.splice(oIdx, 1)
}
</script>
