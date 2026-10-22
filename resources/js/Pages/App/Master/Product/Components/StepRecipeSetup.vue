<template>
    <div class="space-y-4">
        <FeatureLock :feature="$enums.FeatureEnum.RECIPE_MANAGEMENT" as="div" class="w-full">
            <div class="space-y-3 border border-slate-200 p-3 rounded-xl bg-white">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <div class="font-semibold text-xs text-slate-800">
                            Resep & Komposisi Bahan Baku (BOM)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Tentukan bahan baku yang dikonsumsi setiap kali produk ini terjual.
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm inline-flex items-center gap-1.5"
                        @click="addIngredient"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                        <span>Tambah Bahan</span>
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-if="form.recipe_items.length === 0"
                    class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-lg"
                >
                    <FontAwesomeIcon :icon="faUtensils" class="text-2xl text-slate-300 mb-2" />
                    <div class="text-xs font-medium text-slate-600">
                        Belum ada bahan baku dalam resep
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Klik tombol "Tambah Bahan" untuk menentukan komposisi produk ini.
                    </div>
                </div>

                <!-- Recipe Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-slate-500 font-semibold bg-slate-50/50"
                            >
                                <th class="p-2">Bahan Baku</th>
                                <th class="p-2 w-28">Jumlah (Qty)</th>
                                <th class="p-2 w-20">Satuan</th>
                                <th class="p-2 w-32 text-right">Estimasi Biaya</th>
                                <th class="p-2 w-12 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, index) in form.recipe_items"
                                :key="index"
                                class="hover:bg-slate-50/50 transition-colors"
                            >
                                <td class="p-2">
                                    <SearchableDropdownField
                                        v-model="item.product_item_id"
                                        :options="rawMaterialOptions"
                                        placeholder="Pilih Bahan Baku"
                                        search-placeholder="Cari bahan baku..."
                                        class="w-full"
                                        @update:model-value="onRawMaterialChange(item)"
                                    />
                                </td>
                                <td class="p-2">
                                    <NumberField
                                        v-model="item.qty"
                                        placeholder="0.0"
                                        step="0.001"
                                        min="0.0001"
                                    />
                                </td>
                                <td class="p-2">
                                    <span
                                        class="inline-block px-2 py-1 rounded bg-slate-100 text-slate-700 font-medium text-xs"
                                    >
                                        {{ item.uom || '-' }}
                                    </span>
                                </td>
                                <td class="p-2 text-right font-medium text-slate-700">
                                    {{ formatIDR(getItemCost(item)) }}
                                </td>
                                <td class="p-2 text-center">
                                    <button
                                        type="button"
                                        class="p-1 text-slate-400 hover:text-red-500 transition-colors"
                                        title="Hapus baris"
                                        @click="removeIngredient(index)"
                                    >
                                        <FontAwesomeIcon :icon="faTrash" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-200 font-semibold bg-slate-50">
                                <td colspan="3" class="p-2.5 text-right text-slate-700">
                                    Total Estimasi HPP Bahan Baku:
                                </td>
                                <td class="p-2.5 text-right text-main font-bold">
                                    {{ formatIDR(totalEstimatedCost) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="form.errors.recipe_items" class="text-xs text-red-500 mt-1">
                    {{ form.errors.recipe_items }}
                </div>
            </div>
        </FeatureLock>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faTrash, faUtensils } from '@fortawesome/free-solid-svg-icons'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import { formatIDR } from '@/Composable/currency-format'

const form = inject('productForm')
const rawMaterials = inject(
    'rawMaterials',
    computed(() => [])
)

const rawMaterialList = computed(() => {
    const list =
        rawMaterials && rawMaterials.value !== undefined ? rawMaterials.value : rawMaterials
    return Array.isArray(list) ? list : []
})

const rawMaterialOptions = computed(() => {
    return rawMaterialList.value.map(rm => ({
        label: rm.name,
        value: rm.id,
    }))
})

const addIngredient = () => {
    form.recipe_items.push({
        product_item_id: '',
        qty: 1,
        uom: '',
    })
}

const removeIngredient = index => {
    form.recipe_items.splice(index, 1)
}

const onRawMaterialChange = item => {
    const rm = rawMaterialList.value.find(r => r.id === item.product_item_id)
    if (rm) {
        item.uom = rm.uom?.name || 'Pcs'
    } else {
        item.uom = ''
    }
}

const getItemAvgCost = item => {
    const rm = rawMaterialList.value.find(r => r.id === item.product_item_id)
    if (!rm) return 0
    const balances = rm.inventory_item?.balances || rm.inventoryItem?.balances || []
    if (balances.length === 0) return 0
    const total = balances.reduce((sum, b) => sum + (parseFloat(b.average_cost) || 0), 0)
    return total / balances.length
}

const getItemCost = item => {
    const qty = parseFloat(item.qty) || 0
    const avg = getItemAvgCost(item)
    return qty * avg
}

const totalEstimatedCost = computed(() => {
    return (form.recipe_items || []).reduce((sum, item) => sum + getItemCost(item), 0)
})
</script>
