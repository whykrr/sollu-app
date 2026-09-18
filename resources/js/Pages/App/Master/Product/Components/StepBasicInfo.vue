<template>
    <div class="space-y-4">
        <div class="font-semibold text-lg border-b border-slate-200 pb-1">Informasi Dasar</div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2 mb-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Foto Produk</label>
                <ProductImagesUploader v-model="form.images" :error="form.errors.images" />
            </div>

            <div class="col-span-2">
                <SelectionGroupField
                    v-model="form.product_type"
                    label="Tipe Produk"
                    :options="productTypeOptions"
                    name="product_type"
                    class="btn-sm"
                    @update:model-value="handleProductTypeChange"
                />
            </div>

            <div class="col-span-2">
                <TextField
                    v-model="form.name"
                    label="Nama Produk"
                    :class="{ 'is-invalid': form.errors.name }"
                    :error="form.errors.name"
                    required
                />
            </div>

            <div>
                <TextField
                    v-model="form.code"
                    label="Kode Produk (Opsional)"
                    placeholder="Contoh: PRD-001"
                    :class="{ 'is-invalid': form.errors.code }"
                    :error="form.errors.code"
                />
            </div>

            <div>
                <DropdownField
                    v-model="form.product_category_id"
                    :options="categoryOptions"
                    label="Kategori"
                    placeholder="Pilih Kategori"
                    :class="{ 'is-invalid': form.errors.product_category_id }"
                    :error="form.errors.product_category_id"
                />
            </div>

            <div class="col-span-2">
                <TextareaField
                    v-model="form.description"
                    label="Deskripsi"
                    placeholder="Deskripsi produk atau layanan..."
                    rows="2"
                    :error="form.errors.description"
                />
            </div>

            <!-- Card Checklist Opsi Produk -->
            <div class="col-span-2 space-y-2 mt-2">
                <div class="text-sm font-semibold text-slate-700 mb-1">
                    Pengaturan & Fitur Produk
                </div>

                <!-- Lacak Inventori (Hanya untuk tipe basic) -->
                <FeatureLock
                    v-if="form.product_type === 'basic'"
                    :feature="$enums.FeatureEnum.INVENTORY_MANAGEMENT"
                    as="div"
                    class="w-full"
                >
                    <div
                        class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
                    >
                        <div>
                            <div class="font-bold text-sm text-slate-800">
                                Lacak Inventori (Stok)
                            </div>
                            <div class="text-xs text-slate-500">
                                Lacak stok masuk, keluar, dan batas minimum stok untuk produk ini.
                            </div>
                        </div>
                        <Switch v-model="form.track_inventory" size="md" />
                    </div>
                </FeatureLock>

                <!-- Pilihan Satuan UOM & Min Stok jika Lacak Stok Aktif -->
                <div
                    v-if="form.product_type === 'basic' && form.track_inventory"
                    class="grid grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl"
                >
                    <DropdownField
                        v-model="form.uom_id"
                        :options="uomOptions"
                        label="Satuan (UOM)"
                        placeholder="Pilih Satuan"
                        :class="{ 'is-invalid': form.errors.uom_id }"
                        :error="form.errors.uom_id"
                        required
                    />
                    <TextField
                        v-model="form.min_stock"
                        type="number"
                        label="Minimum Stok"
                        placeholder="0"
                        :error="form.errors.min_stock"
                    />
                </div>

                <!-- Memiliki Varian Produk (Hanya untuk tipe basic) -->
                <FeatureLock
                    v-if="form.product_type === 'basic'"
                    :feature="$enums.FeatureEnum.PRODUCT_VARIANTS"
                    as="div"
                    class="w-full"
                >
                    <div
                        class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
                    >
                        <div>
                            <div class="font-bold text-sm text-slate-800">
                                Memiliki Varian Produk
                            </div>
                            <div class="text-xs text-slate-500">
                                Aktifkan jika produk memiliki opsi variasi (seperti Ukuran, Rasa,
                                atau Warna).
                            </div>
                        </div>
                        <Switch
                            :model-value="form.has_variant"
                            size="md"
                            @change="handleVariantChange"
                        />
                    </div>
                </FeatureLock>

                <!-- Tampilkan di Kasir / POS -->
                <div
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">Tampilkan di POS / Kasir</div>
                        <div class="text-xs text-slate-500">
                            Tampilkan produk ini dalam daftar katalog aplikasi kasir.
                        </div>
                    </div>
                    <Switch v-model="form.is_show" size="md" />
                </div>

                <!-- Dapat Dijual -->
                <div
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">Dapat Dijual</div>
                        <div class="text-xs text-slate-500">
                            Produk tersedia untuk transaksi penjualan.
                        </div>
                    </div>
                    <Switch v-model="form.sellable" size="md" />
                </div>

                <!-- Tersedia di Outlet (Ketersediaan Produk) -->
                <div
                    v-if="outlets.length > 1 && !selectedOutlet"
                    class="border border-slate-200 p-3 rounded-xl space-y-2 mt-2 w-full"
                >
                    <div class="font-bold text-sm text-slate-800">Tersedia di Outlet</div>
                    <div class="text-xs text-slate-500 mb-2">
                        Pilih outlet mana saja yang menjual produk ini.
                    </div>
                    <div class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2">
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
        </div>
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
import ProductImagesUploader from './ProductImagesUploader.vue'
import FeatureLock from '@/Components/UI/FeatureLock.vue'

const { selectedOutlet } = useAuth()
const form = inject('productForm')
const categories = inject('categories', [])
const uoms = inject('uoms', [])
const outlets = inject('outlets', [])
const outletStatusMap = inject('outletStatusMap', {})
const isEdit = inject('isEdit')
const originalProduct = inject('originalProduct')

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
    return outlets.value.map(o => ({
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
        outlets.value.forEach(o => {
            outletStatusMap.value[o.id] = false
        })
        newVal.forEach(id => {
            outletStatusMap.value[id] = true
        })
    },
})

const handleVariantChange = isChecked => {
    if (!isChecked && isEdit.value && originalProduct?.has_variant) {
        if (
            window.confirm(
                'PERINGATAN: Menonaktifkan opsi ini akan menonaktifkan seluruh data varian produk sebelumnya (histori tidak dihapus). Apakah Anda yakin?'
            )
        ) {
            form.has_variant = false
        } else {
            form.has_variant = true
        }
    } else {
        form.has_variant = isChecked
    }
}

const categoryOptions = computed(() => {
    const raw = categories && categories.value !== undefined ? categories.value : categories
    const list = Array.isArray(raw) ? raw : []
    return list.map(c => ({
        label: c.label || c.name || '',
        value: c.value !== undefined && c.value !== null ? c.value : c.id || '',
    }))
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
</script>
