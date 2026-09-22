<template>
    <div>
        <form class="space-y-3" @submit.prevent="submit">
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-2"
                :class="{ '!grid-cols-1': !(outletOptions.length > 1 && !selectedOutlet) }"
            >
                <!-- Pilihan Outlet: HANYA jika outlet user > 1 DAN tidak ada selectedOutlet aktif di sidebar -->
                <div v-if="outletOptions.length > 1 && !selectedOutlet">
                    <SearchableDropdownField
                        id="outlet_id"
                        v-model="form.outlet_id"
                        label="Pilih Outlet"
                        placeholder="Pilih Outlet..."
                        search-placeholder="Cari outlet..."
                        :options="outletOptions"
                        :error="form.errors.outlet_id"
                        required
                    />
                </div>

                <DropdownField
                    id="reason"
                    v-model="form.reason"
                    label="Alasan Utama"
                    placeholder="Pilih alasan..."
                    :options="getOptions('AdjustmentReason')"
                    :class="{ 'is-invalid': form.errors.reason }"
                    :error="form.errors.reason"
                    required
                />
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Dokumen (Opsional)"
                placeholder="Tambahkan catatan umum mengenai dokumen penyesuaian ini..."
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
                rows="2"
            />

            <div class="border-t border-slate-200 pt-3 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Daftar Barang</h3>
                        <p class="text-xs text-slate-500">
                            Cari dan tambahkan barang yang ingin disesuaikan stok fisiknya.
                        </p>
                    </div>
                    <div class="w-full sm:w-80">
                        <AsyncSelectField
                            id="search_item"
                            label="Cari Barang"
                            placeholder="Cari nama, SKU, atau barcode..."
                            class="sm"
                            :api-url="route('api.internal.inventory-items.search')"
                            :api-params="{
                                outlet_id: form.outlet_id,
                            }"
                            :min-chars="2"
                            :disabled="!form.outlet_id"
                            @select="addItemFromSearch"
                        >
                            <template #option="{ item }">
                                <div class="flex items-center justify-between w-full">
                                    <div>
                                        <div class="font-semibold text-xs text-slate-800">
                                            {{ item.name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            SKU: {{ item.sku || '-' }}
                                        </div>
                                    </div>
                                    <div class="text-right text-[11px] text-slate-500">
                                        Stok:
                                        <span class="font-medium text-slate-700">
                                            {{ Number(item.current_stock ?? 0) }}
                                        </span>
                                        {{ item.uom?.name || '' }}
                                    </div>
                                </div>
                            </template>
                        </AsyncSelectField>
                    </div>
                </div>

                <div v-if="form.errors.items" class="text-danger text-xs mb-1">
                    {{ form.errors.items }}
                </div>

                <!-- Petunjuk Jika Outlet Belum Terpilih -->
                <div
                    v-if="!form.outlet_id"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
                >
                    Pilih outlet terlebih dahulu untuk mencari barang inventori.
                </div>

                <!-- Empty State Jika Belum Ada Item -->
                <div
                    v-else-if="form.items.length === 0"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
                >
                    Belum ada barang ditambahkan. Silakan cari dan pilih barang pada kolom pencarian
                    di atas.
                </div>

                <!-- Daftar Item Penyesuaian -->
                <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.inventory_item_id || index"
                        class="p-3 border border-slate-200 rounded-lg bg-white space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs font-bold text-slate-400 w-5 text-center">
                                    {{ index + 1 }}.
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 truncate">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate">
                                        SKU: {{ item.sku || '-' }} | Satuan:
                                        <span class="font-medium text-slate-600">{{
                                            item.uom || '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Hapus item"
                                @click="removeItem(index)"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>

                        <!-- Baris Input Grid: Qty Change, Unit Cost, Description -->
                        <div
                            class="grid grid-cols-12 gap-2 pt-1 border-t border-slate-100 items-start"
                        >
                            <div
                                :class="
                                    item.qty_change > 0
                                        ? 'col-span-12 sm:col-span-6'
                                        : 'col-span-12'
                                "
                            >
                                <NumberField
                                    :id="'qty_' + index"
                                    v-model="item.qty_change"
                                    label="Perubahan Qty (+/-)"
                                    placeholder="Misal: -2 atau 5"
                                    step="any"
                                    class="sm"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.qty_change`],
                                    }"
                                    :error="form.errors[`items.${index}.qty_change`]"
                                    required
                                />
                            </div>

                            <div v-if="item.qty_change > 0" class="col-span-12 sm:col-span-6">
                                <NumberField
                                    :id="'unit_cost_' + index"
                                    v-model="item.unit_cost"
                                    label="HPP / Biaya Satuan (Opsional)"
                                    placeholder="Auto (Moving Avg)"
                                    min="0"
                                    step="any"
                                    class="sm"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.unit_cost`],
                                    }"
                                    :error="form.errors[`items.${index}.unit_cost`]"
                                />
                            </div>

                            <div class="col-span-12">
                                <TextField
                                    :id="'desc_' + index"
                                    v-model="item.description"
                                    label="Keterangan / Alasan Khusus Item"
                                    placeholder="Misal: Rusak tertindih saat bongkar muat"
                                    class="sm"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.description`],
                                    }"
                                    :error="form.errors[`items.${index}.description`]"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Baris Realtime Kalkulasi Stok -->
                        <div
                            class="flex items-center justify-between pt-1 border-t border-dashed border-slate-100 text-[11px] bg-slate-50/50 p-2 rounded"
                        >
                            <div class="text-slate-500">
                                Stok Saat Ini:
                                <span class="font-bold text-slate-700">
                                    {{ item.current_stock ?? 0 }} {{ item.uom || '' }}
                                </span>
                            </div>

                            <div class="text-right">
                                Proyeksi Stok Sesudah:
                                <span
                                    class="font-bold ml-1"
                                    :class="
                                        calculateProjectedStock(item) < 0
                                            ? 'text-danger'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{ calculateProjectedStock(item) }} {{ item.uom || '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Total Penyesuaian -->
                <div
                    v-if="form.items.length > 0"
                    class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg mt-2"
                >
                    <div>
                        <div class="text-xs text-slate-500">Total Barang:</div>
                        <div class="font-bold text-xs text-slate-800">
                            {{ form.items.length }} Item
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500">Total Perubahan Stok:</div>
                        <div
                            class="font-bold text-sm"
                            :class="netQtyChange >= 0 ? 'text-emerald-600' : 'text-danger'"
                        >
                            {{ netQtyChange >= 0 ? '+' : '' }}{{ netQtyChange }} Unit
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="handleCancel"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                :disabled="form.processing || form.items.length === 0 || !form.outlet_id"
                @click="submit"
            >
                Simpan sebagai Draf
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'

const { outlets: userOutlets, selectedOutlet } = useAuth()
const { getOptions } = useEnum()

const isMounted = ref(false)
onMounted(() => {
    isMounted.value = true
})

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        label: store.name,
        value: store.id,
    }))
)

const form = useForm({
    outlet_id:
        selectedOutlet.value?.id ||
        (userOutlets.value?.length === 1 ? userOutlets.value[0].id : ''),
    reason: '',
    notes: '',
    items: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

// Watch outlet changes: reset items if outlet changes
watch(
    () => form.outlet_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal) {
            form.items = []
        }
    }
)

const calculateProjectedStock = item => {
    const current = Number(item.current_stock || 0)
    const change = Number(item.qty_change || 0)
    return current + change
}

const netQtyChange = computed(() => {
    return form.items.reduce((sum, item) => sum + Number(item.qty_change || 0), 0)
})

const addItemFromSearch = item => {
    const exists = form.items.find(i => i.inventory_item_id === item.id)
    if (!exists) {
        form.items.unshift({
            inventory_item_id: item.id,
            name: item.name,
            sku: item.sku || '-',
            uom: item.uom?.name || '-',
            current_stock: Number(item.current_stock ?? 0),
            qty_change: '',
            unit_cost: '',
            description: '',
        })
    }
}

const removeItem = index => {
    form.items.splice(index, 1)
}

const submit = () => {
    form.post(route('inventory.adjustments.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => forceClose(),
    })
}
</script>
