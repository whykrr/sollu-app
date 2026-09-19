<template>
    <div class="space-y-3">
        <!-- 1. Foto Produk -->
        <div>
            <label
                class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
            >
                Foto Produk
            </label>
            <ProductImagesUploader v-model="form.images" :error="form.errors.images" />
        </div>

        <!-- 2. Tipe Produk -->
        <div>
            <SelectionGroupField
                v-model="form.product_type"
                label="Tipe Produk"
                :options="productTypeOptions"
                name="product_type"
                class="btn-sm"
                @update:model-value="handleProductTypeChange"
            />
        </div>

        <!-- 3. Nama Produk & Kategori (Core Fields 80/20) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="col-span-1 sm:col-span-2">
                <TextField
                    v-model="form.name"
                    label="Nama Produk"
                    placeholder="Misal: Kopi Susu Gula Aren"
                    :error="form.errors.name"
                    required
                />
            </div>

            <div class="col-span-1 sm:col-span-2">
                <DropdownField
                    v-model="form.product_category_id"
                    :options="categoryOptions"
                    label="Kategori Produk"
                    placeholder="Pilih Kategori"
                    :error="form.errors.product_category_id"
                />
            </div>
        </div>

        <!-- 4. Pengaturan Lanjutan (Progressive Disclosure) -->
        <DisclosureSection
            title="Pengaturan Lanjutan"
            description="Kode SKU, barcode, deskripsi, visibilitas kasir & outlet"
            :error="hasAdvancedError"
        >
            <div class="space-y-2 pt-1">
                <!-- Kode & Barcode -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <TextField
                        v-model="form.code"
                        label="Kode Produk / SKU (Opsional)"
                        placeholder="Misal: KOP-AREN-01"
                        :error="form.errors.code"
                    />

                    <TextField
                        v-model="form.barcode"
                        label="Barcode (Opsional)"
                        placeholder="Scan atau input barcode"
                        :error="form.errors.barcode"
                    />
                </div>

                <!-- Deskripsi -->
                <TextareaField
                    v-model="form.description"
                    label="Deskripsi Produk"
                    placeholder="Tuliskan keterangan detail atau komposisi produk..."
                    rows="2"
                    :error="form.errors.description"
                />

                <!-- Switch Visibilitas POS & Jual -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl hover:bg-slate-50 transition"
                    >
                        <div>
                            <div class="font-semibold text-xs text-slate-800">
                                Tampilkan di Kasir
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Muncul di katalog transaksi kasir
                            </div>
                        </div>
                        <Switch v-model="form.is_show" size="sm" />
                    </div>

                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl hover:bg-slate-50 transition"
                    >
                        <div>
                            <div class="font-semibold text-xs text-slate-800">Dapat Dijual</div>
                            <div class="text-[11px] text-slate-500">
                                Aktif untuk transaksi penjualan
                            </div>
                        </div>
                        <Switch v-model="form.sellable" size="sm" />
                    </div>
                </div>

                <!-- Ketersediaan Outlet (Multi-Outlet) -->
                <div
                    v-if="outlets.length > 1 && !selectedOutlet"
                    class="border border-slate-200 p-2.5 rounded-xl space-y-1.5"
                >
                    <div class="font-semibold text-xs text-slate-800">Ketersediaan di Outlet</div>
                    <div class="text-[11px] text-slate-500">
                        Pilih cabang / outlet yang menjual produk ini:
                    </div>
                    <div class="bg-slate-50 border border-slate-200 p-2 rounded-lg">
                        <SelectionGroupField
                            v-model="selectedOutlets"
                            multiple
                            :options="formattedOutlets"
                            name="outlet_ids"
                            class="sm btn-sm"
                            show-select-all
                        />
                    </div>
                </div>
            </div>
        </DisclosureSection>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import { useAuth } from '@/Composable/useAuth'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import Switch from '@/Components/Form/Switch.vue'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'
import ProductImagesUploader from './ProductImagesUploader.vue'

const { selectedOutlet } = useAuth()
const form = inject('productForm')
const categories = inject('categories', [])
const outlets = inject('outlets', [])
const outletStatusMap = inject('outletStatusMap', {})

const productTypeOptions = [
    { value: 'basic', label: 'Barang Fisik' },
    { value: 'service', label: 'Layanan / Jasa' },
    { value: 'bundle', label: 'Paket Bundle' },
]

const handleProductTypeChange = val => {
    if (val === 'service') {
        form.track_inventory = false
        form.has_variant = false
        form.has_recipe = false
        form.uom_id = ''
        form.min_stock = '0'
    } else if (val === 'bundle') {
        form.track_inventory = false
        form.has_variant = false
        form.has_modifier = false
        form.has_recipe = false
        form.uom_id = ''
        form.min_stock = '0'
    }
}

const formattedOutlets = computed(() => {
    const list = outlets && outlets.value !== undefined ? outlets.value : outlets
    return (Array.isArray(list) ? list : []).map(o => ({
        value: o.id,
        label: o.name,
    }))
})

const selectedOutlets = computed({
    get: () => {
        return Object.keys(outletStatusMap.value)
            .filter(id => outletStatusMap.value[id])
            .map(id => Number(id) || id)
    },
    set: newVal => {
        const list = outlets && outlets.value !== undefined ? outlets.value : outlets
        ;(Array.isArray(list) ? list : []).forEach(o => {
            outletStatusMap.value[o.id] = false
        })
        newVal.forEach(id => {
            outletStatusMap.value[id] = true
        })
    },
})

const categoryOptions = computed(() => {
    const raw = categories && categories.value !== undefined ? categories.value : categories
    const list = Array.isArray(raw) ? raw : []
    return list.map(c => ({
        label: c.label || c.name || '',
        value: c.value !== undefined && c.value !== null ? c.value : c.id || '',
    }))
})

const hasAdvancedError = computed(() => {
    return Boolean(
        form.errors.code ||
        form.errors.barcode ||
        form.errors.description ||
        form.errors.is_show ||
        form.errors.sellable ||
        form.errors.outlets
    )
})
</script>
