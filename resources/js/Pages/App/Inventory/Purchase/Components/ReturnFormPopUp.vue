<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Informasi Dokumen Pembelian Asal -->
        <div
            v-if="purchase"
            class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs space-y-1"
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <span class="text-slate-400 block text-[11px]">PO Referensi:</span>
                    <span class="font-bold text-slate-800">{{ purchase.po_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Pemasok:</span>
                    <span class="font-semibold text-slate-700">{{
                        purchase.supplier?.name || '-'
                    }}</span>
                </div>
                <div v-if="selectedReceipt" class="sm:col-span-2 pt-1 border-t border-slate-200/60">
                    <span class="text-slate-400 block text-[11px]">Surat Jalan Referensi:</span>
                    <span class="font-semibold text-main">
                        {{ selectedReceipt.receipt_number }}
                        <span v-if="selectedReceipt.delivery_order_number" class="text-slate-500 font-normal">
                            (SJ: {{ selectedReceipt.delivery_order_number }})
                        </span>
                    </span>
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
                    <h3 class="text-xs font-bold text-slate-800">
                        Daftar Barang yang Dikembalikan
                    </h3>
                    <p class="text-[11px] text-slate-500">
                        Kuantitas dan konversi dihitung akurat berdasarkan riwayat surat jalan penerimaan barang.
                    </p>
                </div>
            </div>

            <div
                v-if="hasExpiredReceipts"
                class="bg-amber-50/70 p-2.5 rounded-lg border border-amber-200/70 text-xs text-amber-800"
            >
                <strong>Catatan:</strong> Beberapa barang penerimaan tidak dapat diretur karena telah melewati batas masa retur pemasok.
            </div>

            <div
                v-if="form.items.length === 0"
                class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
                Belum ada barang penerimaan yang dapat diretur atau seluruh masa retur telah berakhir.
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="(item, index) in form.items"
                    :key="item.goods_receipt_item_id || item.inventory_item_id || index"
                    class="p-2.5 border border-slate-200 rounded-lg bg-white space-y-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-xs text-slate-800 truncate">
                                {{ item.name }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px]">
                                <span
                                    v-if="item.receipt_number"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium border border-blue-200/60"
                                >
                                    DO: {{ item.receipt_number }}
                                </span>
                                <span
                                    v-if="item.remaining_days !== undefined"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium border border-emerald-200/60"
                                >
                                    Masa Retur: {{ item.remaining_days }} hr lagi
                                </span>
                                <span class="text-slate-500">
                                    SKU: {{ item.sku || '-' }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-600 font-medium">
                                    1 {{ item.uom_name }} = {{ formatQuantity(item.conversion_factor) }} {{ item.base_uom_name }}
                                </span>
                                <span v-if="item.max_returnable_qty" class="text-amber-700 font-medium">
                                    (Maks: {{ formatQuantity(item.max_returnable_qty) }} {{ item.uom_name }})
                                </span>
                            </div>
                        </div>

                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="text-slate-400 hover:text-danger text-xs cursor-pointer p-1 shrink-0"
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
                                v-model="item.return_purchase_qty"
                                :label="'Qty Retur (' + item.uom_name + ')'"
                                class="sm"
                                min="0.0001"
                                :max="item.max_returnable_qty || undefined"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.return_purchase_qty`],
                                }"
                                :error="form.errors[`items.${index}.return_purchase_qty`]"
                                required
                            />
                        </div>

                        <div class="col-span-12 sm:col-span-4">
                            <NumberField
                                :id="'cost_' + index"
                                v-model="item.unit_cost"
                                :label="'Harga Beli Satuan (' + item.uom_name + ')'"
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
                            <div class="text-[11px] text-slate-400">Potong Stok Inventori:</div>
                            <div class="font-bold text-xs text-danger">
                                -{{
                                    formatQuantity(
                                        Number(item.return_purchase_qty || 0) *
                                            Number(item.conversion_factor || 1)
                                    )
                                }}
                                {{ item.base_uom_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Retur -->
            <div
                v-if="form.items.length > 0"
                class="p-3 bg-red-50/50 border border-red-200/60 rounded-lg space-y-1.5 mt-2"
            >
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600">Total Kuantitas Retur:</span>
                    <span class="font-bold text-slate-800">
                        {{ totalQtyRetur }} Unit ({{ form.items.length }} Baris Penerimaan)
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600">Total Pengurangan Fisik Stok:</span>
                    <span class="font-bold text-danger">
                        -{{ formatQuantity(totalInventoryQtyRetur) }} Satuan Dasar
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-red-200/60">
                    <span class="text-red-700 font-semibold">Total Nilai Pengembalian:</span>
                    <span class="font-bold text-base text-danger">
                        {{ formatCurrency(totalReturAmount) }}
                    </span>
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
import { useEnum } from '@/Composable/useEnum'
import { usePopUpStore } from '@/store/popup'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'

const popUpStore = usePopUpStore()
const { enums } = useEnum()
const { outlets: userOutlets, selectedOutlet } = useAuth()

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
    selectedReceipt: {
        type: Object,
        default: null,
    },
})

const isMounted = ref(false)
onMounted(() => {
    isMounted.value = true
    populateForm()
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

const hasExpiredReceipts = ref(false)

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
    return form.items.reduce((sum, i) => sum + Number(i.return_purchase_qty || 0), 0)
})

const totalInventoryQtyRetur = computed(() => {
    return form.items.reduce(
        (sum, i) => sum + Number(i.return_purchase_qty || 0) * Number(i.conversion_factor || 1),
        0
    )
})

const totalReturAmount = computed(() => {
    return form.items.reduce(
        (sum, i) => sum + Number(i.return_purchase_qty || 0) * Number(i.unit_cost || 0),
        0
    )
})

const removeItem = index => {
    form.items.splice(index, 1)
}

const populateForm = () => {
    form.reset()
    hasExpiredReceipts.value = false
    const data = props.purchase
    if (data) {
        form.purchase_order_id = data.id
        form.outlet_id = data.outlet_id
        form.supplier_id = data.supplier_id
        form.return_date = new Date().toISOString().split('T')[0]
        form.reason = ''

        const returnableItems = []

        // 1. Prioritaskan baris Goods Receipt yang berstatus Selesai (Completed)
        if (data.goods_receipts && data.goods_receipts.length > 0) {
            let completedReceipts = data.goods_receipts.filter(
                receipt =>
                    receipt.status === 'completed' ||
                    receipt.status === enums.GoodsReceiptStatus?.Completed
            )

            if (props.selectedReceipt) {
                completedReceipts = completedReceipts.filter(
                    r => r.id === props.selectedReceipt.id
                )
            }

            completedReceipts.forEach(receipt => {
                if (receipt.is_returnable === false) {
                    hasExpiredReceipts.value = true
                    return
                }

                (receipt.items || []).forEach(grItem => {
                    const remaining = Number(
                        grItem.remaining_returnable_qty !== undefined
                            ? grItem.remaining_returnable_qty
                            : grItem.received_purchase_qty
                    )

                    if (remaining > 0) {
                        returnableItems.push({
                            goods_receipt_item_id: grItem.id,
                            receipt_number:
                                receipt.delivery_order_number ||
                                'SJ-' + receipt.id.substring(0, 6),
                            received_at: receipt.received_at,
                            remaining_days: receipt.remaining_return_days,
                            inventory_item_id: grItem.inventory_item_id,
                            name: grItem.inventory_item?.name || 'Item',
                            sku: grItem.inventory_item?.sku || '-',
                            uom_id: grItem.uom_id,
                            uom_name:
                                grItem.uom?.name || grItem.inventory_item?.uom?.name || '-',
                            base_uom_name: grItem.inventory_item?.uom?.name || '-',
                            return_purchase_qty: remaining,
                            conversion_factor: Number(grItem.conversion_factor || 1),
                            max_returnable_qty: remaining,
                            unit_cost: Number(
                                grItem.purchase_unit_cost !== undefined
                                    ? grItem.purchase_unit_cost
                                    : (Number(grItem.unit_cost || 0) * Number(grItem.conversion_factor || 1))
                            ),
                        })
                    }
                })
            })
        }

        form.items = returnableItems
    }
}

watch(
    [() => props.purchase, () => props.selectedReceipt],
    () => {
        populateForm()
    },
    { deep: true }
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
