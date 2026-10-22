<template>
    <div>
        <form class="space-y-4" @submit.prevent="submit">
            <!-- Product & Variant Selection -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <SearchableDropdownField
                        v-model="form.product_id"
                        label="Produk"
                        placeholder="Pilih Produk"
                        search-placeholder="Cari produk..."
                        :options="productOptions"
                        :error="form.errors.product_id"
                        :disabled="isEdit"
                        required
                        @update:model-value="onProductChange"
                    />
                </div>

                <div v-if="hasVariantSelected">
                    <DropdownField
                        v-model="form.product_item_id"
                        label="Varian Produk"
                        placeholder="-- Pilih Varian Produk --"
                        :options="variantOptions"
                        :error="form.errors.product_item_id"
                        :disabled="isEdit"
                        required
                    />
                </div>
            </div>

            <!-- Recipe Ingredients Section -->
            <div class="border border-slate-200 p-3 rounded-xl bg-white space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <div class="font-semibold text-xs text-slate-800">
                            Komposisi Bahan Baku (Formula BOM)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Tentukan bahan dan takaran yang digunakan untuk 1 porsi / unit produk.
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm inline-flex items-center gap-1.5"
                        @click="addIngredient"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                        <span>Tambah Baris</span>
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-if="form.items.length === 0"
                    class="py-6 text-center text-slate-400 border border-dashed border-slate-200 rounded-lg"
                >
                    <div class="text-xs font-medium text-slate-600">
                        Belum ada bahan baku dalam formula
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Klik tombol "Tambah Baris" untuk menambahkan bahan mentah.
                    </div>
                </div>

                <!-- Ingredients Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-slate-500 font-semibold bg-slate-50/50"
                            >
                                <th class="p-2">Bahan Baku</th>
                                <th class="p-2 w-28">Kuantitas</th>
                                <th class="p-2 w-20">Satuan</th>
                                <th class="p-2 w-32 text-right">Estimasi Biaya</th>
                                <th class="p-2 w-12 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, index) in form.items"
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
                                    <div
                                        v-if="form.errors[`items.${index}.product_item_id`]"
                                        class="text-[10px] text-red-500 mt-0.5"
                                    >
                                        {{ form.errors[`items.${index}.product_item_id`] }}
                                    </div>
                                </td>
                                <td class="p-2">
                                    <NumberField
                                        v-model="item.qty"
                                        placeholder="0.0"
                                        step="0.0001"
                                        min="0.0001"
                                    />
                                    <div
                                        v-if="form.errors[`items.${index}.qty`]"
                                        class="text-[10px] text-red-500 mt-0.5"
                                    >
                                        {{ form.errors[`items.${index}.qty`] }}
                                    </div>
                                </td>
                                <td class="p-2">
                                    <span
                                        class="inline-block px-2 py-1 rounded bg-slate-100 text-slate-700 font-medium text-xs"
                                    >
                                        {{ item.uom || '-' }}
                                    </span>
                                </td>
                                <td class="p-2 text-right font-medium text-slate-700">
                                    {{ formatIDR(getLineCost(item)) }}
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
                                    Total Estimasi HPP Resep:
                                </td>
                                <td class="p-2.5 text-right text-main font-bold">
                                    {{ formatIDR(totalEstimatedHpp) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="form.errors.items" class="text-xs text-red-500 mt-1">
                    {{ form.errors.items }}
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button type="button" class="btn btn-flat btn-sm" @click="closeModal">Batal</button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="btn btn-main btn-sm"
                    @click="submit"
                >
                    <FontAwesomeIcon :icon="faSave" class="mr-1" />
                    {{
                        form.processing
                            ? 'Menyimpan...'
                            : isEdit
                              ? 'Simpan Versi Baru'
                              : 'Simpan Resep'
                    }}
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faTrash, faSave } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import { formatIDR } from '@/Composable/currency-format'

const props = defineProps({
    recipe: {
        type: Object,
        default: null,
    },
    products: {
        type: Array,
        default: () => [],
    },
    rawMaterials: {
        type: Array,
        default: () => [],
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const isEdit = computed(() => Boolean(props.recipe && props.recipe.id))

const initialItems = () => {
    if (props.recipe && Array.isArray(props.recipe.items) && props.recipe.items.length > 0) {
        return props.recipe.items.map(it => ({
            product_item_id: it.product_item_id,
            qty: it.qty,
            uom: it.uom || it.product_item?.uom?.name || 'Pcs',
        }))
    }
    return [{ product_item_id: '', qty: 1, uom: '' }]
}

const form = useForm({
    product_id: props.recipe?.product_id || '',
    product_item_id: props.recipe?.product_item_id || '',
    items: initialItems(),
})

const productOptions = computed(() => {
    return (props.products || []).map(p => ({
        value: p.id,
        label: p.name,
    }))
})

const selectedProduct = computed(() => {
    return (props.products || []).find(p => p.id === form.product_id)
})

const hasVariantSelected = computed(() => {
    return Boolean(selectedProduct.value && selectedProduct.value.has_variant)
})

const variantOptions = computed(() => {
    if (!selectedProduct.value || !selectedProduct.value.has_variant) return []
    const items = selectedProduct.value.product_items || selectedProduct.value.productItems || []
    return items.map(item => ({
        value: item.id,
        label: item.variant_combination || item.name || item.sku || `Varian #${item.id}`,
    }))
})

const rawMaterialOptions = computed(() => {
    return (props.rawMaterials || []).map(rm => ({
        value: rm.id,
        label: rm.name,
    }))
})

const onProductChange = () => {
    if (!hasVariantSelected.value) {
        form.product_item_id = ''
    }
}

const onRawMaterialChange = item => {
    const rm = (props.rawMaterials || []).find(r => r.id === item.product_item_id)
    if (rm) {
        item.uom = rm.uom?.name || 'Pcs'
    } else {
        item.uom = ''
    }
}

const addIngredient = () => {
    form.items.push({
        product_item_id: '',
        qty: 1,
        uom: '',
    })
}

const removeIngredient = index => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
    }
}

const getRawMaterialAvgCost = rawItemId => {
    const rm = (props.rawMaterials || []).find(r => r.id === rawItemId)
    if (!rm) return 0
    const balances = rm.inventory_item?.balances || rm.inventoryItem?.balances || []
    if (balances.length === 0) return 0
    const total = balances.reduce((sum, b) => sum + (parseFloat(b.average_cost) || 0), 0)
    return total / balances.length
}

const getLineCost = item => {
    const qty = parseFloat(item.qty) || 0
    const avg = getRawMaterialAvgCost(item.product_item_id)
    return qty * avg
}

const totalEstimatedHpp = computed(() => {
    return (form.items || []).reduce((sum, item) => sum + getLineCost(item), 0)
})

onMounted(() => {
    isMounted.value = true
})

const closeModal = () => {
    popUpStore.close()
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('master.recipes.update', props.recipe.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('master.recipes.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        })
    }
}
</script>
