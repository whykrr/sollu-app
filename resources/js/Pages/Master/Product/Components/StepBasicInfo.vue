<template>
    <div class="space-y-4">
        <div class="font-semibold text-lg border-b pb-1">Informasi Dasar</div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2 mb-2">
                <label class="block text-sm font-medium text-slate-700 mb-1"
                    >Foto Produk</label
                >
                <ProductImagesUploader
                    v-model="form.images"
                    :error="form.errors.images"
                />
            </div>
            <TextField
                v-model="form.name"
                label="Nama Produk"
                :class="{ 'is-invalid': form.errors.name }"
                :feedback="form.errors.name"
                required
            />
            <TextField
                v-model="form.code"
                label="Kode / SKU (Opsional)"
                :class="{ 'is-invalid': form.errors.code }"
                :feedback="form.errors.code"
            />
            <div class="col-span-2">
                <DropdownField
                    v-model="form.product_category_id"
                    :options="categoryOptions"
                    label="Kategori"
                    :class="{ 'is-invalid': form.errors.product_category_id }"
                    :feedback="form.errors.product_category_id"
                />
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1"
                    >Deskripsi</label
                >
                <textarea
                    v-model="form.description"
                    class="form w-full border-slate-300 rounded-md text-sm"
                    rows="2"
                ></textarea>
            </div>

            <!-- Card Checklist Opsi Produk -->
            <div class="col-span-2 space-y-2 mt-2">
                <div class="text-sm font-semibold text-slate-700 mb-1">
                    Pengaturan & Fitur Produk
                </div>

                <!-- Lacak Inventori (Stok) -->
                <label
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">
                            Lacak Inventori (Stok)
                        </div>
                        <div class="text-xs text-slate-500">
                            Lacak stok masuk, keluar, dan batas minimum stok
                            untuk produk ini.
                        </div>
                    </div>
                    <input
                        v-model="form.track_inventory"
                        type="checkbox"
                        class="rounded h-5 w-5 text-primary cursor-pointer"
                    />
                </label>

                <!-- Pilihan Satuan UOM & Min Stok jika Lacak Stok Aktif -->
                <div
                    v-if="form.track_inventory"
                    class="grid grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl"
                >
                    <DropdownField
                        v-model="form.uom_id"
                        :options="uomOptions"
                        label="Satuan (UOM)"
                        placeholder="Pilih Satuan"
                        :class="{ 'is-invalid': form.errors.uom_id }"
                        :feedback="form.errors.uom_id"
                        required
                    />
                    <TextField
                        v-model="form.min_stock"
                        type="number"
                        label="Minimum Stok"
                        placeholder="0"
                        :feedback="form.errors.min_stock"
                    />
                </div>

                <!-- Memiliki Varian Produk -->
                <label
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">
                            Memiliki Varian Produk
                        </div>
                        <div class="text-xs text-slate-500">
                            Aktifkan jika produk memiliki opsi variasi (seperti
                            Ukuran, Rasa, atau Warna).
                        </div>
                    </div>
                    <input
                        v-model="form.has_variant"
                        type="checkbox"
                        class="rounded h-5 w-5 text-primary cursor-pointer"
                    />
                </label>

                <!-- Tampilkan di Kasir / POS -->
                <label
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">
                            Tampilkan di POS / Kasir
                        </div>
                        <div class="text-xs text-slate-500">
                            Tampilkan produk ini dalam daftar katalog aplikasi
                            kasir.
                        </div>
                    </div>
                    <input
                        v-model="form.is_show"
                        type="checkbox"
                        class="rounded h-5 w-5 text-primary cursor-pointer"
                    />
                </label>

                <!-- Dapat Dijual -->
                <label
                    class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
                >
                    <div>
                        <div class="font-bold text-sm text-slate-800">
                            Dapat Dijual
                        </div>
                        <div class="text-xs text-slate-500">
                            Produk tersedia untuk transaksi penjualan.
                        </div>
                    </div>
                    <input
                        v-model="form.sellable"
                        type="checkbox"
                        class="rounded h-5 w-5 text-primary cursor-pointer"
                    />
                </label>

                <!-- Tersedia di Outlet (Ketersediaan Produk) -->
                <div
                    v-if="outlets.length > 1"
                    class="border border-slate-200 p-3 rounded-xl space-y-2 mt-2 w-full"
                >
                    <div class="font-bold text-sm text-slate-800">
                        Tersedia di Outlet
                    </div>
                    <div class="text-xs text-slate-500 mb-2">
                        Pilih outlet mana saja yang menjual produk ini.
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        <label
                            v-for="outlet in outlets"
                            :key="outlet.id"
                            class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-slate-100 cursor-pointer"
                        >
                            <span
                                class="text-sm font-semibold text-slate-700"
                                >{{ outlet.name }}</span
                            >
                            <input
                                v-model="outletStatusMap[outlet.id]"
                                type="checkbox"
                                class="rounded text-primary cursor-pointer h-4 w-4"
                            />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import ProductImagesUploader from './ProductImagesUploader.vue';

const form = inject('productForm');
const categories = inject('categories', []);
const uoms = inject('uoms', []);
const outlets = inject('outlets', []);
const outletStatusMap = inject('outletStatusMap', {});

const categoryOptions = computed(() => {
    const raw =
        categories && categories.value !== undefined
            ? categories.value
            : categories;
    const list = Array.isArray(raw) ? raw : [];
    return list.map((c) => ({
        label: c.label || c.name || '',
        value: c.value !== undefined && c.value !== null ? c.value : c.id || '',
    }));
});

const uomOptions = computed(() => {
    const raw = uoms && uoms.value !== undefined ? uoms.value : uoms;
    const list = Array.isArray(raw) ? raw : [];
    return list.map((u) => ({
        label:
            u.label ||
            (u.code && u.name && u.code.toLowerCase() !== u.name.toLowerCase()
                ? `${u.name} (${u.code})`
                : u.name || ''),
        value: u.value !== undefined && u.value !== null ? u.value : u.id || '',
    }));
});
</script>
