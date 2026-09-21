<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Informasi Dokumen Pembelian Asal -->
        <div v-if="purchase" class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs space-y-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <span class="text-slate-400 block text-[11px]">PO Referensi:</span>
                    <span class="font-bold text-slate-800">{{ purchase.po_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Pemasok:</span>
                    <span class="font-semibold text-slate-700">{{ purchase.supplier?.name || '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Input Informasi Retur -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <TextField
                id="return_date"
                v-model="form.return_date"
                type="date"
                label="Tanggal Retur"
                :class="{ 'is-invalid': form.errors.return_date }"
                :error="form.errors.return_date"
                required
            />

            <!-- Pilihan Outlet: HANYA jika tidak ada purchase atau outlet aktif -->
            <div v-if="!purchase">
                <DropdownField
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Outlet Asal Retur"
                    :options="outletOptions"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :error="form.errors.outlet_id"
                    required
                />
            </div>
        </div>

        <TextareaField
            id="reason"
            v-model="form.reason"
            label="Alasan Pengembalian Barang"
            placeholder="Misal: Barang rusak saat pengiriman, kadaluarsa, salah spesifikasi..."
            :class="{ 'is-invalid': form.errors.reason }"
            :error="form.errors.reason"
            rows="2"
            required
        />

        <!-- Rincian Barang yang Diretur -->
        <div class="border-t border-slate-200 pt-2 space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-800">Daftar Barang yang Dikembalikan</h3>
                    <p class="text-[11px] text-slate-500">
                        Tentukan kuantitas barang yang dikembalikan ke pemasok. Stok fisik akan berkurang dari inventori outlet.
                    </p>
                </div>
            </div>

            <div
                v-if="form.items.length === 0"
                class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
                Belum ada barang yang dapat diretur.
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="(item, index) in form.items"
                    :key="item.inventory_item_id || index"
                    class="p-2.5 border border-slate-200 rounded-lg bg-white space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="font-bold text-xs text-slate-800 truncate">{{ item.name }}</div>
                            <div class="text-[11px] text-slate-400 truncate">
                                SKU: {{ item.sku || '-' }} | Satuan: <span class="text-slate-700 font-medium">{{ item.uom_name }}</span>
                                <span v-if="item.max_returnable_qty" class="ml-1 text-slate-500">
                                    (Maks. Retur: {{ formatQuantity(item.max_returnable_qty) }})
                                </span>
                            </div>
                        </div>

                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="text-slate-400 hover:text-danger text-xs cursor-pointer p-1"
                            title="Hapus dari daftar retur"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-end pt-1 border-t border-slate-100">
                        <div class="col-span-12 sm:col-span-4">
                            <NumberField
                                :id="'qty_ret_' + index"
                                v-model="item.qty_returned"
                                label="Kuantitas Retur"
                                class="sm"
                                min="0.0001"
                                :max="item.max_returnable_qty || undefined"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.qty_returned`],
                                }"
                                :error="form.errors[`items.${index}.qty_returned`]"
                                required
                            />
                        </div>

                        <div class="col-span-12 sm:col-span-4">
                            <NumberField
                                :id="'cost_' + index"
                                v-model="item.unit_cost"
                                label="Nilai Satuan (Rp)"
                                class="sm"
                                min="0"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.unit_cost`],
                                }"
                                :error="form.errors[`items.${index}.unit_cost`]"
                                required
                            />
                        </div>

                        <div class="col-span-12 sm:col-span-4 pb-1 text-right sm:text-right">
                            <div class="text-[11px] text-slate-400">Total Pengembalian:</div>
                            <div class="font-bold text-xs text-danger">
                                {{
                                    formatCurrency(
                                        Number(item.qty_returned || 0) *
                                            Number(item.unit_cost || 0)
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Retur -->
            <div
                v-if="form.items.length > 0"
                class="flex items-center justify-between p-3 bg-red-50/50 border border-red-200/60 rounded-lg mt-2"
            >
                <div>
                    <div class="text-xs text-red-600 font-medium">Total Barang Retur:</div>
                    <div class="font-bold text-xs text-slate-800">
                        {{ form.items.length }} Item ({{ totalQtyRetur }} Unit)
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-red-600 font-medium">Total Nilai Retur:</div>
                    <div class="font-bold text-base text-danger">
                        {{ formatCurrency(totalReturAmount) }}
                    </div>
                </div>
            </div>
        </div>
    </form>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <button type="button" class="btn btn-flat" :disabled="form.processing" @click="close">
            Batal
        </button>
        <button
            type="button"
            class="btn btn-danger"
            :disabled="form.processing || form.items.length === 0 || !form.reason"
            @click="submit"
        >
            Simpan Retur Pembelian
        </button>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { usePopUpStore } from '@/store/popup'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'

const popUpStore = usePopUpStore()
const { outlets: userOutlets, selectedOutlet } = useAuth()

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
})

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
    purchase_order_id: props.purchase?.id || null,
    outlet_id: props.purchase?.outlet_id || selectedOutlet.value?.id || '',
    supplier_id: props.purchase?.supplier_id || null,
    return_date: new Date().toISOString().split('T')[0],
    reason: '',
    items: [],
})

const formatCurrency = value => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const formatQuantity = value => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

const totalQtyRetur = computed(() => {
    return form.items.reduce((sum, i) => sum + Number(i.qty_returned || 0), 0)
})

const totalReturAmount = computed(() => {
    return form.items.reduce(
        (sum, i) => sum + Number(i.qty_returned || 0) * Number(i.unit_cost || 0),
        0
    )
})

const removeItem = index => {
    form.items.splice(index, 1)
}

watch(
    () => props.purchase,
    data => {
        form.reset()
        if (data) {
            form.purchase_order_id = data.id
            form.outlet_id = data.outlet_id
            form.supplier_id = data.supplier_id
            form.return_date = new Date().toISOString().split('T')[0]
            form.reason = ''

            // Populate item yang telah diterima (qty_received > 0)
            const receivedItems = (data.items || [])
                .filter(i => Number(i.qty_received || 0) > 0)
                .map(i => ({
                    inventory_item_id: i.inventory_item_id,
                    name: i.inventory_item?.name || 'Item',
                    sku: i.inventory_item?.sku || '-',
                    uom_id: i.uom_id,
                    uom_name: i.uom?.name || i.inventory_item?.uom?.name || '-',
                    qty_returned: Number(i.qty_received || 0),
                    max_returnable_qty: Number(i.qty_received || 0),
                    unit_cost: Number(i.purchase_price || 0),
                }))

            form.items = receivedItems
        } else {
            form.items = []
        }
    },
    { immediate: true }
)

const close = () => {
    form.clearErrors()
    popUpStore.close()
}

const submit = () => {
    form.post(route('inventory.purchases.returns.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    })
}
</script>
