<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Informasi Dokumen Pembelian -->
        <div
            v-if="purchase"
            class="bg-slate-50 border border-slate-200 p-3 rounded-lg text-xs space-y-1.5"
        >
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nomor PO:</span>
                    <span class="font-bold text-slate-800">{{ purchase.po_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Pemasok:</span>
                    <span class="font-semibold text-slate-700">{{
                        purchase.supplier?.name || '-'
                    }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Outlet Tujuan:</span>
                    <span class="font-semibold text-slate-700">{{
                        purchase.outlet?.name || '-'
                    }}</span>
                </div>
            </div>
            <div
                v-if="purchase.reference_number"
                class="text-slate-500 pt-1 border-t border-slate-200/60"
            >
                <span class="text-slate-400">No. Referensi:</span> {{ purchase.reference_number }}
            </div>
        </div>

        <!-- Input Surat Jalan & Tanggal Terima -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <TextField
                id="delivery_order_number"
                v-model="form.delivery_order_number"
                label="Nomor Surat Jalan (DO)"
                placeholder="Misal: SJ-2026-00129"
                :class="{ 'is-invalid': form.errors.delivery_order_number }"
                :error="form.errors.delivery_order_number"
                required
            />

            <TextField
                id="received_at"
                v-model="form.received_at"
                type="date"
                label="Tanggal Diterima"
                :class="{ 'is-invalid': form.errors.received_at }"
                :error="form.errors.received_at"
                required
            />
        </div>

        <TextareaField
            id="notes"
            v-model="form.notes"
            label="Catatan Penerimaan (Opsional)"
            placeholder="Kondisi barang saat tiba, nomor polisi armada, atau catatan lain..."
            :class="{ 'is-invalid': form.errors.notes }"
            :error="form.errors.notes"
            rows="2"
        />

        <!-- Input Penerimaan Barang -->
        <div class="border-t border-slate-200 pt-2 space-y-2">
            <div>
                <h3 class="text-xs font-bold text-slate-800">
                    Rincian Fisik Barang & Konversi Satuan
                </h3>
                <p class="text-[11px] text-slate-500">
                    Masukkan jumlah fisik barang yang diterima pada pengiriman ini. Sesuaikan faktor
                    konversi jika kemasan berbeda dengan satuan dasar inventori.
                </p>
            </div>

            <div
                v-if="form.items.length === 0"
                class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
                Seluruh barang dalam pesanan pembelian ini telah diterima lengkap.
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="(item, index) in form.items"
                    :key="item.purchase_order_item_id || index"
                    class="p-2.5 border border-slate-200 rounded-lg bg-white space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-slate-800">{{ item.name }}</div>
                            <div class="text-[11px] text-slate-500">
                                Dipesan:
                                <span class="font-semibold text-slate-700"
                                    >{{ formatQuantity(item.qty_ordered) }}
                                    {{ item.uom_name }}</span
                                >
                                | Belum Tiba:
                                <span class="font-bold text-amber-600"
                                    >{{ formatQuantity(item.outstanding_qty) }}
                                    {{ item.uom_name }}</span
                                >
                            </div>
                        </div>
                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="text-slate-400 hover:text-danger text-xs cursor-pointer p-1"
                            title="Jangan terima barang ini sekarang"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-end pt-1 border-t border-slate-100">
                        <div class="col-span-12 sm:col-span-4">
                            <NumberField
                                :id="'qty_rcv_' + index"
                                v-model="item.qty_received"
                                :label="'Jml Diterima (' + item.uom_name + ')'"
                                class="sm"
                                min="0.0001"
                                :max="Number(item.outstanding_qty)"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.qty_received`],
                                }"
                                :error="form.errors[`items.${index}.qty_received`]"
                                required
                            />
                        </div>

                        <div class="col-span-12 sm:col-span-4">
                            <NumberField
                                :id="'conv_' + index"
                                v-model="item.conversion_factor"
                                label="Faktor Konversi"
                                class="sm"
                                min="0.0001"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.conversion_factor`],
                                }"
                                :error="form.errors[`items.${index}.conversion_factor`]"
                                title="Faktor pengali ke satuan dasar inventori (contoh: 1 dus = 24 botol, isi 24)"
                                required
                            />
                        </div>

                        <div class="col-span-12 sm:col-span-4 pb-1 text-right sm:text-right">
                            <div class="text-[11px] text-slate-400">Masuk Inventori:</div>
                            <div class="font-bold text-xs text-emerald-600">
                                {{
                                    formatQuantity(
                                        Number(item.qty_received || 0) *
                                            Number(item.conversion_factor || 1)
                                    )
                                }}
                                {{ item.base_uom_name }}
                            </div>
                        </div>

                        <!-- Peringatan Faktor Konversi Jika Satuan Berbeda Tapi Nilai 1 -->
                        <div
                            v-if="
                                item.uom_name !== item.base_uom_name &&
                                Number(item.conversion_factor) === 1
                            "
                            class="col-span-12 p-2 bg-amber-50 border border-amber-200 rounded text-[11px] text-amber-800 flex items-start gap-1.5"
                        >
                            <FontAwesomeIcon
                                :icon="faExclamationTriangle"
                                class="text-amber-500 mt-0.5 shrink-0"
                            />
                            <div>
                                <span class="font-semibold">Perhatian:</span> Satuan beli (<strong
                                    >{{ item.uom_name }}</strong
                                >) berbeda dengan satuan dasar inventori (<strong>{{
                                    item.base_uom_name
                                }}</strong
                                >), tetapi faktor konversi masih bernilai 1. Pastikan 1
                                {{ item.uom_name }} memang berisi 1 {{ item.base_uom_name }}.
                            </div>
                        </div>
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
            :disabled="form.processing || form.items.length === 0 || !form.delivery_order_number"
            @click="submit"
        >
            Simpan Penerimaan
        </button>
    </Teleport>
</template>

<script setup>
import { watch, ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import NumberField from '@/Components/Form/NumberField.vue'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'

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

const form = useForm({
    delivery_order_number: '',
    received_at: new Date().toISOString().split('T')[0],
    notes: '',
    items: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const formatQuantity = value => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

watch(
    () => props.purchase,
    data => {
        form.reset()
        if (data && data.items) {
            // Filter hanya item yang masih memiliki sisa kuantitas belum diterima (outstanding > 0)
            const remainingItems = data.items
                .map(i => {
                    const ordered = Number(i.qty_ordered || 0)
                    const received = Number(i.qty_received || 0)
                    const outstanding = Math.max(0, ordered - received)
                    return {
                        purchase_order_item_id: i.id,
                        name: i.inventory_item?.name || 'Item',
                        uom_name: i.uom?.name || i.inventory_item?.uom?.name || '-',
                        base_uom_name: i.inventory_item?.uom?.name || '-',
                        qty_ordered: ordered,
                        qty_received: outstanding, // default isi sisa
                        outstanding_qty: outstanding,
                        conversion_factor: Number(i.conversion_factor || 1),
                    }
                })
                .filter(i => i.outstanding_qty > 0)

            form.items = remainingItems
        } else {
            form.items = []
        }
    },
    { immediate: true }
)

const removeItem = index => {
    form.items.splice(index, 1)
}

const submit = () => {
    if (props.purchase?.id) {
        form.post(route('inventory.purchases.receive', props.purchase.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => forceClose(),
        })
    }
}
</script>
