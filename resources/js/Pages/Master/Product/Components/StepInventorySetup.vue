<template>
    <div class="space-y-4">
        <div class="font-semibold text-lg border-b pb-1">Setup Inventori</div>

        <!-- Mode Non-Varian -->
        <div v-if="!form.has_variant" class="grid grid-cols-2 gap-3">
            <TextField
                v-model="form.code"
                label="Kode / SKU (Opsional)"
                :class="{ 'is-invalid': form.errors.code }"
                :error="form.errors.code"
            />
            <TextField
                v-model="form.barcode"
                label="Barcode (Opsional)"
                :class="{ 'is-invalid': form.errors.barcode }"
                :error="form.errors.barcode"
            />
        </div>

        <!-- Mode Varian -->
        <template v-else>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <TextField
                    v-model="form.code"
                    label="Prefix SKU (Opsional)"
                    placeholder="Contoh: BJU"
                    :class="{ 'is-invalid': form.errors.code }"
                    :error="form.errors.code"
                />
            </div>

            <!-- Variant Groups List -->
            <div class="space-y-3">
                <div
                    v-for="(group, gIdx) in form.variants"
                    :key="gIdx"
                    class="border p-3 rounded-lg bg-slate-50 relative"
                >
                    <button
                        type="button"
                        @click="deleteVariantGroup(gIdx)"
                        class="absolute top-2 right-2 text-danger hover:text-red-700 text-sm"
                        title="Hapus Grup Varian"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <TextField
                                v-model="group.name"
                                label="Nama Grup Varian"
                                placeholder="Contoh: Ukuran, Warna"
                                required
                            />
                        </div>
                        <div class="col-span-2">
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Pilihan Opsi</label
                            >
                            <div class="flex flex-wrap gap-2 items-center">
                                <div
                                    v-for="(opt, oIdx) in group.options"
                                    :key="oIdx"
                                    class="flex items-center bg-white border rounded px-2 py-1 gap-1"
                                >
                                    <input
                                        v-model="opt.name"
                                        type="text"
                                        class="border-0 p-0 text-xs focus:ring-0 w-16"
                                        placeholder="Nama Opsi"
                                    />
                                    <button
                                        type="button"
                                        @click="deleteVariantOption(gIdx, oIdx)"
                                        class="text-neutral-400 hover:text-danger text-xs"
                                    >
                                        ✖
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addVariantOption(gIdx)"
                                    class="btn btn-outline-secondary btn-xs py-1 px-2 text-xs"
                                >
                                    + Tambah Opsi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addVariantGroup"
                    class="btn btn-outline-primary btn-sm"
                >
                    + Tambah Grup Varian
                </button>
            </div>

            <!-- Combinations Preview Table -->
            <div
                v-if="form.variant_combinations.length > 0"
                class="mt-4 space-y-2"
            >
                <div class="flex justify-between items-center border-b pb-1">
                    <div class="font-semibold text-md text-slate-700">
                        Daftar Kombinasi Varian
                    </div>
                    <button
                        type="button"
                        @click="autoGenerateAllSkus"
                        class="btn btn-outline-secondary btn-xs"
                    >
                        Generate SKU Otomatis
                    </button>
                </div>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b">
                                <th
                                    class="p-2 font-semibold text-slate-600 w-16"
                                >
                                    Foto
                                </th>
                                <th class="p-2 font-semibold text-slate-600">
                                    Kombinasi
                                </th>
                                <th
                                    class="p-2 font-semibold text-slate-600 w-40"
                                >
                                    SKU
                                </th>
                                <th
                                    class="p-2 font-semibold text-slate-600 w-40"
                                >
                                    Barcode
                                </th>
                                <th
                                    v-if="form.track_inventory"
                                    class="p-2 font-semibold text-slate-600 w-28"
                                >
                                    Minimal Stok
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(
                                    combo, cIdx
                                ) in form.variant_combinations"
                                :key="cIdx"
                                class="border-b hover:bg-slate-50/50"
                            >
                                <td class="p-2">
                                    <div
                                        class="w-10 h-10 bg-slate-100 rounded flex items-center justify-center border border-slate-200 overflow-hidden text-slate-400 relative cursor-pointer hover:bg-slate-200 transition"
                                    >
                                        <img
                                            v-if="combo.image_url"
                                            :src="combo.image_url"
                                            class="w-full h-full object-cover"
                                        />
                                        <FontAwesomeIcon
                                            v-else
                                            :icon="faImage"
                                        />
                                        <!-- Here we would ideally add a file input triggered by click -->
                                    </div>
                                </td>
                                <td class="p-2 font-medium">
                                    {{
                                        Object.values(combo.options).join(' / ')
                                    }}
                                </td>
                                <td class="p-2">
                                    <TextField
                                        v-model="combo.sku"
                                        type="text"
                                        class="form-control py-1 text-xs"
                                        placeholder="SKU"
                                    />
                                </td>
                                <td class="p-2">
                                    <TextField
                                        v-model="combo.barcode"
                                        type="text"
                                        class="form-control py-1 text-xs"
                                        placeholder="Barcode"
                                    />
                                </td>
                                <td v-if="form.track_inventory" class="p-2">
                                    <NumberField
                                        v-model="combo.min_stock"
                                        class="form-control py-1 text-xs"
                                        placeholder="0"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { inject } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrash, faImage } from '@fortawesome/free-solid-svg-icons';
import NumberField from '@/Components/Form/NumberField.vue';
import TextField from '@/Components/Form/TextField.vue';

const form = inject('productForm');
const autoGenerateAllSkus = inject('autoGenerateAllSkus');

const addVariantGroup = () => {
    form.variants.push({
        name: '',
        options: [{ name: '' }],
    });
};

const deleteVariantGroup = (gIdx) => {
    form.variants.splice(gIdx, 1);
};

const addVariantOption = (gIdx) => {
    form.variants[gIdx].options.push({ name: '' });
};

const deleteVariantOption = (gIdx, oIdx) => {
    form.variants[gIdx].options.splice(oIdx, 1);
};
</script>
